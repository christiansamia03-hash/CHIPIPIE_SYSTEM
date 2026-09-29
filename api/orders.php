<?php
/**
 * ChipiPie Cafe — Orders API
 * 
 * POST ?action=checkout — Process checkout
 * GET                   — Fetch orders
 * GET  ?action=stats    — Dashboard stats
 */
require_once __DIR__ . '/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && !$action)                       { handleFetchOrders(); }
elseif ($method === 'GET' && $action === 'stats')         { handleStats(); }
elseif ($method === 'GET' && $action === 'print_summary') { handlePrintSummary(); }
elseif ($method === 'GET' && $action === 'get_adjustments') { handleGetAdjustments(); }
elseif ($action === 'checkout')                           { handleCheckout(); }
elseif ($action === 'void_order')                         { handleVoidOrder(); }
elseif ($action === 'refund_order')                       { handleRefundOrder(); }
else { jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400); }

function handleVoidOrder() {
    requireLogin();
    session_write_close();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Only admins can void orders.'], 403);
    }
    $input = getJsonInput();
    $orderId = intval($input['order_id'] ?? 0);
    $reason  = trim($input['reason'] ?? 'Order voided by admin');
    if (!$orderId) { jsonResponse(['success' => false, 'message' => 'Invalid order ID.'], 400); }

    $db = getDB();
    $order = $db->prepare("SELECT id, status, total, user_id FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    if (in_array($order['status'], ['Voided','Refunded'])) {
        jsonResponse(['success' => false, 'message' => 'Order already ' . strtolower($order['status']) . '.'], 400);
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE orders SET status = 'Voided' WHERE id = ?")->execute([$orderId]);
        $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        foreach ($items->fetchAll() as $item) {
            $db->prepare("UPDATE menu_items SET stock = stock + ?, is_available = 1 WHERE id = ?")
               ->execute([$item['quantity'], $item['menu_item_id']]);
        }
        // Log the adjustment with reason
        $db->prepare("INSERT INTO order_adjustments (order_id, type, amount, reason, processed_by) VALUES (?, 'void', ?, ?, ?)")
           ->execute([$orderId, $order['total'], $reason, $order['user_id']]);
        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Order voided and stock restored.']);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
    }
}

function handleRefundOrder() {
    requireLogin();
    session_write_close();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Only admins can refund orders.'], 403);
    }
    $input = getJsonInput();
    $orderId = intval($input['order_id'] ?? 0);
    $reason  = trim($input['reason'] ?? 'Customer refund request');
    if (!$orderId) { jsonResponse(['success' => false, 'message' => 'Invalid order ID.'], 400); }

    $db = getDB();
    $order = $db->prepare("SELECT id, status, total, user_id FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    if (in_array($order['status'], ['Voided','Refunded'])) {
        jsonResponse(['success' => false, 'message' => 'Order already ' . strtolower($order['status']) . '.'], 400);
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE orders SET status = 'Refunded' WHERE id = ?")->execute([$orderId]);
        $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        foreach ($items->fetchAll() as $item) {
            $db->prepare("UPDATE menu_items SET stock = stock + ?, is_available = 1 WHERE id = ?")
               ->execute([$item['quantity'], $item['menu_item_id']]);
        }
        // Log the adjustment
        $db->prepare("INSERT INTO order_adjustments (order_id, type, amount, reason, processed_by) VALUES (?, 'refund', ?, ?, ?)")
           ->execute([$orderId, $order['total'], $reason, $order['user_id']]);
        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Order refunded and stock restored.']);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
    }
}

function handleGetAdjustments() {
    requireLogin();
    session_write_close();
    $db = getDB();
    $rows = $db->query("
        SELECT oa.id, oa.type, oa.amount, oa.reason, oa.created_at,
               o.order_number, o.payment_method,
               u.full_name AS processed_by_name
        FROM order_adjustments oa
        JOIN orders o ON o.id = oa.order_id
        LEFT JOIN users u ON u.id = oa.processed_by
        ORDER BY oa.created_at DESC
        LIMIT 100
    ")->fetchAll();
    jsonResponse(['success' => true, 'adjustments' => $rows]);
}

function handleCheckout() {
    requireLogin();
    $input = getJsonInput();
    $cartItems = $input['items'] ?? [];
    $paymentMethod = trim($input['payment_method'] ?? 'Cash');

    if (empty($cartItems)) {
        jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 400);
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $total = 0;
        $lineItems = [];
        $stockAlerts = [];

        // Validate stock and calculate totals
        foreach ($cartItems as $ci) {
            $itemId = intval($ci['id'] ?? 0);
            $qty = intval($ci['quantity'] ?? 0);
            $size = trim($ci['size'] ?? '');
            $clientPrice = isset($ci['unit_price']) ? floatval($ci['unit_price']) : null;
            if (!$itemId || $qty <= 0) continue;

            $stmt = $db->prepare("SELECT id, name, price, size_prices, stock, min_stock FROM menu_items WHERE id = ? FOR UPDATE");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();

            if (!$item) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => "Item ID $itemId not found."], 404);
            }
            if ($qty > $item['stock']) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => "Not enough stock for {$item['name']}. Only {$item['stock']} left."], 400);
            }

            // Determine the unit price based on size
            $unitPrice = floatval($item['price']); // default base price
            if ($size && $item['size_prices']) {
                $sp = json_decode($item['size_prices'], true);
                if ($sp && isset($sp[$size])) {
                    $unitPrice = floatval($sp[$size]);
                }
            }

            // If client sent a price, validate it matches (prevents tampering)
            if ($clientPrice !== null && abs($clientPrice - $unitPrice) > 0.01) {
                // Price mismatch — use the server-validated price
                // (silent correction, no error to avoid blocking checkout)
            }

            $subtotal = $unitPrice * $qty;
            $total += $subtotal;
            $lineItems[] = [
                'item_id' => $itemId,
                'qty' => $qty,
                'price' => $unitPrice,
                'subtotal' => $subtotal,
                'name' => $item['name'],
                'size' => $size ?: null
            ];

            // Deduct stock
            $newStock = $item['stock'] - $qty;
            $avail = $newStock > 0 ? 1 : 0;
            $db->prepare("UPDATE menu_items SET stock = ?, is_available = ? WHERE id = ?")->execute([$newStock, $avail, $itemId]);

            if ($newStock <= 0) {
                $stockAlerts[] = ['name' => $item['name'], 'stock' => $newStock, 'critical' => true];
            } elseif ($newStock <= $item['min_stock']) {
                $stockAlerts[] = ['name' => $item['name'], 'stock' => $newStock, 'min_stock' => $item['min_stock'], 'critical' => false];
            }
        }

        // Generate order number
        $countStmt = $db->query("SELECT COUNT(*) as cnt FROM orders");
        $orderNum = '#' . str_pad(($countStmt->fetch()['cnt'] + 1), 3, '0', STR_PAD_LEFT);

        // Create order
        $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, total, payment_method, status) VALUES (?, ?, ?, ?, 'Completed')");
        $stmt->execute([$orderNum, $_SESSION['user_id'], $total, $paymentMethod]);
        $orderId = $db->lastInsertId();

        // Create order items (now with size)
        $stmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal, size) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($lineItems as $li) {
            $stmt->execute([$orderId, $li['item_id'], $li['qty'], $li['price'], $li['subtotal'], $li['size']]);
        }

        $db->commit();

        $itemStr = implode(', ', array_map(function($li) {
            $sizeLabel = $li['size'] ? " ({$li['size']})" : '';
            return ($li['qty'] > 1 ? $li['qty'] . '× ' : '') . $li['name'] . $sizeLabel;
        }, $lineItems));

        jsonResponse([
            'success' => true,
            'message' => "Order $orderNum placed — ₱" . number_format($total, 2),
            'order' => [
                'id' => $orderId,
                'order_number' => $orderNum,
                'total' => $total,
                'items' => $itemStr,
                'payment_method' => $paymentMethod,
                'status' => 'Completed',
                'time' => date('g:i A'),
            ],
            'stock_alerts' => $stockAlerts
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()], 500);
    }
}

function handleFetchOrders() {
    requireLogin();
    $db = getDB();

    $dateFilter = $_GET['date'] ?? 'today';
    $where = '';
    if ($dateFilter === 'today') {
        $where = "WHERE DATE(o.created_at) = CURDATE()";
    } elseif ($dateFilter === 'week') {
        $where = "WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($dateFilter === 'month') {
        $where = "WHERE YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
    } elseif ($dateFilter === 'year') {
        $where = "WHERE YEAR(o.created_at) = YEAR(CURDATE())";
    }

    $stmt = $db->query("
        SELECT o.id, o.order_number, o.total, o.payment_method, o.status,
               o.created_at, u.full_name AS cashier,
               GROUP_CONCAT(
                 CONCAT(
                   oi.quantity, '× ', m.name,
                   IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' (', oi.size, ')'), '')
                 ) SEPARATOR ', '
               ) AS items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 500
    ");
    $orders = $stmt->fetchAll();

    // Format time/date and compute summary (exclude Voided/Refunded from revenue)
    $totalRevenue = 0;
    $completedCount = 0;
    foreach ($orders as &$o) {
        $o['time'] = date('g:i A', strtotime($o['created_at']));
        $o['date'] = date('M d, Y', strtotime($o['created_at']));
        if ($o['status'] === 'Completed') {
            $totalRevenue += floatval($o['total']);
            $completedCount++;
        }
    }

    jsonResponse([
        'success'       => true,
        'orders'        => $orders,
        'total_revenue' => $totalRevenue,
        'total_orders'  => $completedCount,   // only count Completed orders
        'filter'        => $dateFilter
    ]);
}

function handleStats() {
    requireLogin();
    $db = getDB();

    // Drink category IDs (Hot Coffee=1, Iced Coffee=2, Classic Milktea=3, Virgin Mojito=4, Citrus Popping Boba=5, Soda Float=6, Matcha Series=7, Frappé=8)
    $drinkCatIds = '1,2,3,4,5,6,7,8';
    $foodCatIds = '9'; // Fork and Fun

    // Today
    $today = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'Completed'")->fetch();
    // This week
    $week = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'")->fetch();
    // This month
    $month = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'")->fetch();

    // Cups sold today (drink categories)
    $cupsStmt = $db->query("
        SELECT COALESCE(SUM(oi.quantity),0) as cups
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE DATE(o.created_at) = CURDATE() AND o.status = 'Completed' AND m.category_id IN ($drinkCatIds)
    ");
    $cupsSold = intval($cupsStmt->fetch()['cups']);

    // Food sold today
    $foodStmt = $db->query("
        SELECT COALESCE(SUM(oi.quantity),0) as food
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE DATE(o.created_at) = CURDATE() AND o.status = 'Completed' AND m.category_id IN ($foodCatIds)
    ");
    $foodSold = intval($foodStmt->fetch()['food']);

    // Top seller
    $topStmt = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE() AND o.status = 'Completed'
        GROUP BY m.id ORDER BY sold DESC LIMIT 1
    ");
    $topSeller = $topStmt->fetch();

    // Top items list (for dashboard top selling items)
    $topItems = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold, COALESCE(SUM(oi.subtotal),0) as revenue
        FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE() AND o.status = 'Completed'
        GROUP BY m.id ORDER BY sold DESC LIMIT 5
    ")->fetchAll();

    // Category breakdown (this month)
    $catBreakdown = $db->query("
        SELECT c.name, SUM(oi.quantity) as qty
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN categories c ON m.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status = 'Completed'
        GROUP BY c.id ORDER BY qty DESC
    ")->fetchAll();

    // Weekly chart data (daily totals for last 7 days)
    $weeklyChart = $db->query("
        SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
        FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'
        GROUP BY DAYOFWEEK(created_at)
    ")->fetchAll();
    $weeklyData = [0,0,0,0,0,0,0];
    foreach ($weeklyChart as $r) {
        $idx = ($r['dow'] + 5) % 7; // Convert to Mon=0
        $weeklyData[$idx] = floatval($r['rev']);
    }

    // Hourly chart data (today)
    $hourlyChart = $db->query("
        SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
        FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'Completed'
        GROUP BY HOUR(created_at)
    ")->fetchAll();
    $hourlyData = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
    foreach ($hourlyChart as $r) {
        $h = intval($r['hr']);
        if ($h >= 7 && $h <= 22) {
            $hourlyData[$h - 7] = floatval($r['rev']);
        }
    }

    // Monthly chart data (this year)
    $monthlyChart = $db->query("
        SELECT MONTH(created_at) as mo, COALESCE(SUM(total),0) as rev
        FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND status = 'Completed'
        GROUP BY MONTH(created_at)
    ")->fetchAll();
    $monthlyData = [0,0,0,0,0,0,0,0,0,0,0,0];
    foreach ($monthlyChart as $r) {
        $monthlyData[intval($r['mo']) - 1] = floatval($r['rev']);
    }

    // Item sales performance (all time, top 9)
    $itemPerf = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'Completed'
        GROUP BY m.id ORDER BY sold DESC LIMIT 9
    ")->fetchAll();

    jsonResponse([
        'success' => true,
        'today' => ['orders' => intval($today['cnt']), 'revenue' => floatval($today['rev'])],
        'week'  => ['orders' => intval($week['cnt']),  'revenue' => floatval($week['rev'])],
        'month' => ['orders' => intval($month['cnt']), 'revenue' => floatval($month['rev'])],
        'cups_sold' => $cupsSold,
        'food_sold' => $foodSold,
        'top_seller' => $topSeller ? ['name' => $topSeller['name'], 'sold' => intval($topSeller['sold'])] : null,
        'top_items' => $topItems,
        'cat_breakdown' => $catBreakdown,
        'weekly_chart' => $weeklyData,
        'hourly_chart' => $hourlyData,
        'monthly_chart' => $monthlyData,
        'item_performance' => $itemPerf,
        'avg_order' => $today['cnt'] > 0 ? round($today['rev'] / $today['cnt'], 2) : 0,
    ]);
}

function handlePrintSummary() {
    requireLogin();
    $db = getDB();
    $dateFilter = $_GET['date'] ?? 'today';

    // Period label
    $label = match($dateFilter) {
        'today' => 'Today — ' . date('F d, Y'),
        'week'  => 'This Week (' . date('M d', strtotime('monday this week')) . ' – ' . date('M d, Y', strtotime('sunday this week')) . ')',
        'month' => 'This Month — ' . date('F Y'),
        'year'  => 'This Year — ' . date('Y'),
        default => 'All Time'
    };

    $where = match($dateFilter) {
        'today' => "WHERE DATE(o.created_at) = CURDATE()",
        'week'  => "WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)",
        'month' => "WHERE YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())",
        'year'  => "WHERE YEAR(o.created_at) = YEAR(CURDATE())",
        default => ''
    };

    // Order totals
    $statusFilter = $where ? "AND o.status = 'Completed'" : "WHERE o.status = 'Completed'";
    $summary = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders o $where $statusFilter")->fetch();

    // Payment method breakdown
    $payments = $db->query("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total),0) as rev
        FROM orders o $where $statusFilter
        GROUP BY o.payment_method
    ")->fetchAll();

    // Top selling items
    $topItems = $db->query("
        SELECT m.name, SUM(oi.quantity) as qty, COALESCE(SUM(oi.subtotal),0) as rev
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        $where $statusFilter
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 10
    ")->fetchAll();

    // Orders list
    $orders = $db->query("
        SELECT o.order_number, o.total, o.payment_method, o.status,
               o.created_at, u.full_name AS cashier,
               GROUP_CONCAT(
                 CONCAT(
                   oi.quantity, '× ', m.name,
                   IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' (', oi.size, ')'), '')
                 ) SEPARATOR ', '
               ) AS items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 500
    ")->fetchAll();

    foreach ($orders as &$o) {
        $o['time'] = date('g:i A', strtotime($o['created_at']));
        $o['date'] = date('M d, Y', strtotime($o['created_at']));
    }

    jsonResponse([
        'success'  => true,
        'label'    => $label,
        'summary'  => ['orders' => intval($summary['cnt']), 'revenue' => floatval($summary['rev'])],
        'payments' => $payments,
        'top_items'=> $topItems,
        'orders'   => $orders
    ]);
}
