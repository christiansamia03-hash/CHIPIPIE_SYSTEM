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

if ($method === 'GET' && !$action)                      { handleFetchOrders(); }
elseif ($method === 'GET' && $action === 'stats')        { handleStats(); }
elseif ($method === 'GET' && $action === 'print_summary') { handlePrintSummary(); }
elseif ($action === 'checkout')                          { handleCheckout(); }
else { jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400); }

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
            if (!$itemId || $qty <= 0) continue;

            $stmt = $db->prepare("SELECT id, name, price, stock, min_stock FROM menu_items WHERE id = ? FOR UPDATE");
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

            $subtotal = $item['price'] * $qty;
            $total += $subtotal;
            $lineItems[] = ['item_id' => $itemId, 'qty' => $qty, 'price' => $item['price'], 'subtotal' => $subtotal, 'name' => $item['name']];

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

        // Create order items
        $stmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($lineItems as $li) {
            $stmt->execute([$orderId, $li['item_id'], $li['qty'], $li['price'], $li['subtotal']]);
        }

        $db->commit();

        $itemStr = implode(', ', array_map(function($li) {
            return ($li['qty'] > 1 ? $li['qty'] . '× ' : '') . $li['name'];
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
               GROUP_CONCAT(CONCAT(oi.quantity, '\u00d7 ', m.name) SEPARATOR ', ') AS items
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

    // Format time/date and compute summary
    $totalRevenue = 0;
    foreach ($orders as &$o) {
        $o['time'] = date('g:i A', strtotime($o['created_at']));
        $o['date'] = date('M d, Y', strtotime($o['created_at']));
        $totalRevenue += floatval($o['total']);
    }

    jsonResponse([
        'success'       => true,
        'orders'        => $orders,
        'total_revenue' => $totalRevenue,
        'total_orders'  => count($orders),
        'filter'        => $dateFilter
    ]);
}

function handleStats() {
    requireLogin();
    $db = getDB();

    // Today
    $today = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE DATE(created_at) = CURDATE()")->fetch();
    // This week
    $week = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
    // This month
    $month = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch();

    // Top seller
    $topStmt = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE()
        GROUP BY m.id ORDER BY sold DESC LIMIT 1
    ");
    $topSeller = $topStmt->fetch();

    // Weekly chart data (daily totals for last 7 days)
    $weeklyChart = $db->query("
        SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
        FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DAYOFWEEK(created_at)
    ")->fetchAll();
    $weeklyData = [0,0,0,0,0,0,0];
    foreach ($weeklyChart as $r) {
        $idx = ($r['dow'] + 5) % 7; // Convert to Mon=0
        $weeklyData[$idx] = floatval($r['rev']);
    }

    jsonResponse([
        'success' => true,
        'today' => ['orders' => intval($today['cnt']), 'revenue' => floatval($today['rev'])],
        'week'  => ['orders' => intval($week['cnt']),  'revenue' => floatval($week['rev'])],
        'month' => ['orders' => intval($month['cnt']), 'revenue' => floatval($month['rev'])],
        'top_seller' => $topSeller ? ['name' => $topSeller['name'], 'sold' => intval($topSeller['sold'])] : null,
        'weekly_chart' => $weeklyData,
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
    $summary = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders o $where")->fetch();

    // Payment method breakdown
    $payments = $db->query("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total),0) as rev
        FROM orders o $where
        GROUP BY o.payment_method
    ")->fetchAll();

    // Top selling items
    $topItems = $db->query("
        SELECT m.name, SUM(oi.quantity) as qty, COALESCE(SUM(oi.subtotal),0) as rev
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        $where
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 10
    ")->fetchAll();

    // Orders list
    $orders = $db->query("
        SELECT o.order_number, o.total, o.payment_method, o.status,
               o.created_at, u.full_name AS cashier,
               GROUP_CONCAT(CONCAT(oi.quantity, '× ', m.name) SEPARATOR ', ') AS items
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
