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
elseif ($action === 'request_void_batch')                 { handleRequestVoidBatch(); }
elseif ($action === 'execute_void_batch_with_code')       { handleExecuteVoidBatchWithCode(); }
elseif ($method === 'GET' && $action === 'check_batch_status') { handleCheckBatchStatus(); }
elseif ($method === 'GET' && $action === 'decide_void_refund') { handleDecideVoidRefund(); }
else { jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400); }

function handleVoidOrder() {
    requireLogin();
    session_write_close();
    $input = getJsonInput();
    $orderId = intval($input['order_id'] ?? 0);
    $reason  = trim($input['reason'] ?? 'Order voided by user');
    if (!$orderId) { jsonResponse(['success' => false, 'message' => 'Invalid order ID.'], 400); }

    $db = getDB();
    $order = $db->prepare("SELECT id, status, total, user_id FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    if (in_array($order['status'], ['Voided','Refunded'])) {
        jsonResponse(['success' => false, 'message' => 'Order already ' . strtolower($order['status']) . '.'], 400);
    }

    $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
    
    // Generate a secure token for email approval
    $approvalToken = bin2hex(random_bytes(32));

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'void_order',
           $_SESSION['user_id'],
           $requester,
           "Void Order #{$orderId}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'reason' => $reason, 'amount' => $order['total']]),
           $approvalToken
       ]);

    // Send emails: manager gets notification-only; owner + superadmin get approve/deny (deduplicated)
    $storeName        = getSetting('store_name') ?: 'ChipiPie Cafe';
    $managerEmailsStr = getSetting('void_refund_manager_email');
    $ownerEmail       = trim(getSetting('owner_email') ?? '');
    $superAdminEmail  = trim(getSetting('superadmin_email') ?? '');

    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    // --- Shared detail rows ---
    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order ID:</td><td style='font-weight: 700; color: #fff;'>#{$orderId}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Requested By:</td><td>{$requester}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Amount:</td><td style='color: #dc2626; font-weight: bold;'>&#8369;" . number_format($order['total'], 2) . "</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Reason:</td><td>{$reason}</td></tr>";

    // --- Notification-only body (no buttons) ---
    $notifyBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Void Request (Notification)</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <p style='font-size: 12px; color: #888; margin: 0;'>This is a notification only. Approval is handled by the owner/super admin.</p>
        </div>
    </div>";

    // --- Approve/deny body ---
    $actionBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Void Request &mdash; Action Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve Void</a>
                <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject</a>
            </div>
        </div>
    </div>";

    // Send notification-only to managers
    if ($managerEmailsStr) {
        $managerEmails = array_filter(array_map('trim', explode(',', $managerEmailsStr)));
        foreach ($managerEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendEmail($email, "$storeName - Void Request: Order #{$orderId} (Notification)", $notifyBody);
            }
        }
    }

    // Send approve/deny to owner and superadmin (deduplicated)
    $actionRecipients = [];
    if ($ownerEmail && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        $actionRecipients[] = strtolower($ownerEmail);
        sendEmail($ownerEmail, "$storeName - Void Request: Order #{$orderId}", $actionBody);
    }
    if ($superAdminEmail && filter_var($superAdminEmail, FILTER_VALIDATE_EMAIL)
        && !in_array(strtolower($superAdminEmail), $actionRecipients)) {
        sendEmail($superAdminEmail, "$storeName - Void Request: Order #{$orderId}", $actionBody);
    }

    jsonResponse(['success' => true, 'message' => 'Void request submitted for manager/admin approval.']);
}

function handleRefundOrder() {
    requireLogin();
    session_write_close();
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

    $requester = $_SESSION['full_name'] ?? $_SESSION['username'];

    // Generate a secure token for email approval
    $approvalToken = bin2hex(random_bytes(32));

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'refund_order',
           $_SESSION['user_id'],
           $requester,
           "Refund Order #{$orderId}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'reason' => $reason, 'amount' => $order['total']]),
           $approvalToken
       ]);

    // Send emails: manager gets notification-only; owner + superadmin get approve/deny (deduplicated)
    $storeName        = getSetting('store_name') ?: 'ChipiPie Cafe';
    $managerEmailsStr = getSetting('void_refund_manager_email');
    $ownerEmail       = trim(getSetting('owner_email') ?? '');
    $superAdminEmail  = trim(getSetting('superadmin_email') ?? '');

    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    // --- Shared detail rows ---
    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order ID:</td><td style='font-weight: 700; color: #fff;'>#{$orderId}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Requested By:</td><td>{$requester}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Amount:</td><td style='color: #ea580c; font-weight: bold;'>&#8369;" . number_format($order['total'], 2) . "</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Reason:</td><td>{$reason}</td></tr>";

    // --- Notification-only body (no buttons) ---
    $notifyBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #ea580c; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Refund Request (Notification)</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <p style='font-size: 12px; color: #888; margin: 0;'>This is a notification only. Approval is handled by the owner/super admin.</p>
        </div>
    </div>";

    // --- Approve/deny body ---
    $actionBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #ea580c; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Refund Request &mdash; Action Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve Refund</a>
                <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject</a>
            </div>
        </div>
    </div>";

    // Send notification-only to managers
    if ($managerEmailsStr) {
        $managerEmails = array_filter(array_map('trim', explode(',', $managerEmailsStr)));
        foreach ($managerEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendEmail($email, "$storeName - Refund Request: Order #{$orderId} (Notification)", $notifyBody);
            }
        }
    }

    // Send approve/deny to owner and superadmin (deduplicated)
    $actionRecipients = [];
    if ($ownerEmail && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        $actionRecipients[] = strtolower($ownerEmail);
        sendEmail($ownerEmail, "$storeName - Refund Request: Order #{$orderId}", $actionBody);
    }
    if ($superAdminEmail && filter_var($superAdminEmail, FILTER_VALIDATE_EMAIL)
        && !in_array(strtolower($superAdminEmail), $actionRecipients)) {
        sendEmail($superAdminEmail, "$storeName - Refund Request: Order #{$orderId}", $actionBody);
    }

    jsonResponse(['success' => true, 'message' => 'Refund request submitted for manager/admin approval.']);
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
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');
    $where = '';
    $params = [];

    if ($dateMode === 'date' && $dateVal) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            $where    = "WHERE DATE(o.created_at) = ?";
            $params[] = $dateVal;
        }
    } elseif ($dateMode === 'week' && $dateVal) {
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $dateVal, $m)) {
            $dto = new DateTime();
            $dto->setISODate(intval($m[1]), intval($m[2]));
            $start    = $dto->format('Y-m-d 00:00:00');
            $dto->modify('+6 days');
            $end      = $dto->format('Y-m-d 23:59:59');
            $where    = "WHERE o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'month' && $dateVal) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateVal)) {
            $start    = $dateVal . "-01 00:00:00";
            $end      = date("Y-m-t 23:59:59", strtotime($start));
            $where    = "WHERE o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'year' && $dateVal) {
        $year = intval($dateVal);
        if ($year >= 2000 && $year <= 2100) {
            $where    = "WHERE YEAR(o.created_at) = ?";
            $params[] = $year;
        }
    } elseif ($dateFilter === 'today') {
        $where = "WHERE DATE(o.created_at) = CURDATE()";
    } elseif ($dateFilter === 'week') {
        $where = "WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($dateFilter === 'month') {
        $where = "WHERE YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
    } elseif ($dateFilter === 'year') {
        $where = "WHERE YEAR(o.created_at) = YEAR(CURDATE())";
    }

    $sql = "
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
    ";
    if ($params) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $db->query($sql);
    }
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
        WHERE o.status = 'Completed'
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

    // Hourly chart data (9am to 2am shift)
    $hourlyChart = $db->query("
        SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
        FROM orders 
        WHERE created_at >= CONCAT(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), ' 09:00:00') 
          AND created_at < CONCAT(DATE_ADD(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), INTERVAL 1 DAY), ' 03:00:00')
          AND status = 'Completed'
        GROUP BY HOUR(created_at)
    ")->fetchAll();
    $hourlyData = array_fill(0, 18, 0);
    foreach ($hourlyChart as $r) {
        $h = intval($r['hr']);
        if ($h >= 9) {
            $hourlyData[$h - 9] = floatval($r['rev']);
        } elseif ($h <= 2) {
            $hourlyData[$h + 15] = floatval($r['rev']);
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

function handleDecideVoidRefund() {
    $token = $_GET['token'] ?? '';
    $decision = $_GET['decision'] ?? '';

    if (!$token || !in_array($decision, ['approve', 'reject'])) {
        die("Invalid link.");
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM system_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $requests = $stmt->fetchAll();

    if (!$requests || count($requests) === 0) {
        die("Request not found, already processed, or invalid token.");
    }

    $now = date('Y-m-d H:i:s');
    $status = $decision === 'approve' ? 'approved' : 'rejected';
    
    // Process the requests and update the status
    $db->prepare("UPDATE system_requests SET status = ?, reviewed_at = ?, review_note = 'Processed via Email Link' WHERE approval_token = ?")
       ->execute([$status, $now, $token]);

    $orderIds = [];

    if ($decision === 'approve') {
        foreach ($requests as $request) {
            $payload = $request['payload'] ? json_decode($request['payload'], true) : [];
            $orderId = $payload['order_id'] ?? 0;

            if ($orderId) {
                $orderIds[] = $orderId;
                $newOrderStatus = $request['type'] === 'void_order' ? 'Voided' : 'Refunded';
                $adjType = $request['type'] === 'void_order' ? 'void' : 'refund';
                
                try {
                    $db->beginTransaction();
                    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newOrderStatus, $orderId]);
                    $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
                    $items->execute([$orderId]);
                    foreach ($items->fetchAll() as $item) {
                        $db->prepare("UPDATE menu_items SET stock = stock + ?, is_available = 1 WHERE id = ?")
                           ->execute([$item['quantity'], $item['menu_item_id']]);
                    }
                    $db->prepare("INSERT INTO order_adjustments (order_id, type, amount, reason, processed_by) VALUES (?, ?, ?, ?, ?)")
                       ->execute([$orderId, $adjType, $payload['amount'] ?? 0, $payload['reason'] ?? '', 0]);
                    $db->commit();
                } catch (Exception $e) {
                    $db->rollBack();
                }
            }
        }
    }

    // Beautiful confirmation page
    $actionText = $decision === 'approve' ? 'Approved' : 'Rejected';
    $color = $decision === 'approve' ? '#16a34a' : '#ef4444';
    $icon = $decision === 'approve' ? '✅' : '❌';
    $orderIdsStr = implode(', ', $orderIds);
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Requests {$actionText}</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1514; color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { text-align: center; background: #2a2423; padding: 40px; border-radius: 16px; border: 1px solid #3d3533; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 400px; width: 90%; }
            .icon { font-size: 64px; margin-bottom: 20px; }
            h1 { color: {$color}; margin: 0 0 10px 0; font-size: 28px; }
            p { color: #aaa; margin: 0; line-height: 1.5; font-size: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='icon'>{$icon}</div>
            <h1>Request(s) {$actionText}</h1>
            <p>The requests have been successfully {$actionText}.</p>
            <p style='margin-top: 20px; font-size: 13px;'>You can now close this tab.</p>
        </div>
    </body>
    </html>
    ";
}

function handleRequestVoidBatch() {
    requireLogin();
    session_write_close();
    $input = getJsonInput();
    $orders = $input['orders'] ?? [];
    $manager = trim($input['manager'] ?? '');
    
    if (empty($orders)) {
        jsonResponse(['success' => false, 'message' => 'No orders provided.'], 400);
    }

    $db = getDB();
    $approvalToken = bin2hex(random_bytes(32));
    $pinCode = strtoupper(substr(generateToken(16), 0, 6)); // 6-digit PIN for POS
    
    // Store the PIN code in admin_tokens for POS validation
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pinCode, $_SESSION['user_id'], $approvalToken, $expiresAt]); // Using purpose field to store the approval_token linkage!

    $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
    $orderListHtml = '';

    foreach ($orders as $o) {
        $orderId = intval($o['id']);
        $type = $o['type'] === 'void' ? 'void_order' : 'refund_order';
        $perReason = trim($o['reason'] ?: ($type === 'void_order' ? 'Order cancelled' : 'Customer refund'));
        $reason = $manager ? "{$perReason} — Authorized by: {$manager}" : $perReason;
        
        $orderInfo = $db->prepare("SELECT total FROM orders WHERE id = ?");
        $orderInfo->execute([$orderId]);
        $orderData = $orderInfo->fetch();
        if (!$orderData) continue;

        $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
           ->execute([
               $type,
               $_SESSION['user_id'],
               $requester,
               ucwords(str_replace('_', ' ', $type)) . " #{$orderId}",
               "Reason: {$reason}",
               json_encode(['order_id' => $orderId, 'reason' => $reason, 'amount' => $orderData['total'], 'pin' => $pinCode]),
               $approvalToken
           ]);
           
        $color = $type === 'void_order' ? '#dc2626' : '#ea580c';
        $orderListHtml .= "
        <tr>
            <td style='padding: 6px 0; font-weight: 600; color: #aaa;'>#{$orderId}</td>
            <td style='padding: 6px 0; color: #fff;'>{$reason}</td>
            <td style='padding: 6px 0; font-weight: bold; color: {$color};'>₱" . number_format($orderData['total'], 2) . "</td>
        </tr>";
    }

    $storeName        = getSetting('store_name') ?: 'ChipiPie Cafe';
    $managerEmailsStr = getSetting('void_refund_manager_email');
    $ownerEmail       = trim(getSetting('owner_email') ?? '');
    $superAdminEmail  = trim(getSetting('superadmin_email') ?? '');

    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    // --- Shared order table ---
    $orderTable = "
        <p style='color: #ddd; margin-top: 0;'>Requested By: <strong>{$requester}</strong></p>
        <table style='width: 100%; font-size: 14px; text-align: left; margin: 0 0 16px 0; border-collapse: collapse;'>
            <tr>
                <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>ID</th>
                <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>Reason</th>
                <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>Amount</th>
            </tr>
            {$orderListHtml}
        </table>";

    // --- Notification-only body (manager) ---
    $notifyBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Batch Void/Refund Request (Notification)</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            {$orderTable}
            <p style='font-size: 12px; color: #888; margin: 0;'>This is a notification only. Approval is handled by the owner/super admin.</p>
        </div>
    </div>";

    // --- Approve/deny body (owner + superadmin) ---
    $actionBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Batch Void/Refund Request &mdash; Action Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            {$orderTable}
            <div style='text-align:center;margin:24px 0;'>
                <p style='font-size:12px;color:#aaa;margin-bottom:6px;'>Or provide this PIN code to the cashier:</p>
                <span style='background:#3a2f2d;color:#fff;padding:12px 24px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$pinCode</span>
            </div>
            <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve All</a>
                <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject All</a>
            </div>
        </div>
    </div>";

    // Send notification-only to managers
    if ($managerEmailsStr) {
        $managerEmails = array_filter(array_map('trim', explode(',', $managerEmailsStr)));
        foreach ($managerEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendEmail($email, "$storeName - Batch Void/Refund Request (Notification)", $notifyBody);
            }
        }
    }

    // Send approve/deny to owner and superadmin (deduplicated)
    $actionRecipients = [];
    if ($ownerEmail && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        $actionRecipients[] = strtolower($ownerEmail);
        sendEmail($ownerEmail, "$storeName - Batch Void/Refund Request", $actionBody);
    }
    if ($superAdminEmail && filter_var($superAdminEmail, FILTER_VALIDATE_EMAIL)
        && !in_array(strtolower($superAdminEmail), $actionRecipients)) {
        sendEmail($superAdminEmail, "$storeName - Batch Void/Refund Request", $actionBody);
    }

    $resp = ['success' => true, 'message' => 'Requests submitted to Super Admin & Managers.', 'batch_token' => $approvalToken];
    if (DEV_MODE) $resp['dev_token'] = $pinCode;
    jsonResponse($resp);
}

function handleExecuteVoidBatchWithCode() {
    requireLogin();
    session_write_close();
    $input = getJsonInput();
    $token = strtoupper(trim($input['token'] ?? ''));
    if (!$token) jsonResponse(['success' => false, 'message' => 'Token is required.'], 400);

    $db = getDB();
    $now = date('Y-m-d H:i:s');
    
    // Validate PIN in admin_tokens
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $tokenRow = $stmt->fetch();
    
    if (!$tokenRow) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired PIN code.'], 400);
    }
    
    $approvalToken = $tokenRow['purpose']; // We stored approval_token here
    
    // Mark token as used
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $tokenRow['id']]);

    // Now approve all requests matching this approval_token (sets review_note = 'Processed via Email Link' from handleDecideVoidRefund)
    $_GET['token'] = $approvalToken;
    $_GET['decision'] = 'approve';
    
    ob_start();
    handleDecideVoidRefund();
    ob_end_clean();
    
    // Override review_note to reflect PIN approval
    $db->prepare("UPDATE system_requests SET review_note = 'Processed via PIN Code' WHERE approval_token = ? AND status = 'approved'")
       ->execute([$approvalToken]);
    
    jsonResponse(['success' => true, 'message' => 'Void requests successfully approved and processed via PIN.']);
}

function handleCheckBatchStatus() {
    requireLogin();
    session_write_close();
    $approvalToken = trim($_GET['batch_token'] ?? '');
    if (!$approvalToken) {
        jsonResponse(['success' => false, 'message' => 'batch_token is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, type, status, review_note FROM system_requests WHERE approval_token = ? LIMIT 20");
    $stmt->execute([$approvalToken]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        jsonResponse(['success' => false, 'message' => 'Batch not found.'], 404);
    }

    $statuses = array_column($rows, 'status');
    $hasPending  = in_array('pending',  $statuses);
    $hasApproved = in_array('approved', $statuses);

    if (!$hasPending && $hasApproved) {
        $note = $rows[0]['review_note'] ?? '';
        $via  = (strpos($note, 'Email') !== false) ? 'Email'
              : ((strpos($note, 'PIN')   !== false) ? 'PIN' : 'Super Admin');
        jsonResponse(['success' => true, 'batch_status' => 'approved', 'approved_via' => $via]);
    } elseif (!$hasPending && !$hasApproved) {
        jsonResponse(['success' => true, 'batch_status' => 'rejected']);
    } else {
        jsonResponse(['success' => true, 'batch_status' => 'pending']);
    }
}