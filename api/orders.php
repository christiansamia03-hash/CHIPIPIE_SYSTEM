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
elseif ($method === 'GET' && $action === 'receipt')         { handleGetReceipt(); }
elseif ($action === 'checkout')                           { handleCheckout(); }
elseif ($action === 'void_order')                         { handleVoidOrder(); }
elseif ($action === 'refund_order')                       { handleRefundOrder(); }
elseif ($action === 'request_void_batch')                 { handleRequestVoidBatch(); }
elseif ($action === 'execute_void_batch_with_code')       { handleExecuteVoidBatchWithCode(); }
elseif ($action === 'reject_void_batch')                  { handleRejectVoidBatch(); }
elseif ($method === 'GET' && $action === 'check_batch_status') { handleCheckBatchStatus(); }
elseif ($method === 'GET' && $action === 'decide_void_refund') { handleDecideVoidRefund(); }
elseif ($method === 'GET' && $action === 'chart_weekly')       { handleChartWeekly(); }
elseif ($method === 'GET' && $action === 'chart_weekly_weeks') { handleChartWeeklyWeeks(); }
elseif ($method === 'GET' && $action === 'chart_hourly')       { handleChartHourly(); }
elseif ($method === 'GET' && $action === 'chart_monthly')      { handleChartMonthly(); }
else { jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400); }

function getVoidRefundRecipients() {
    $db = getDB();
    $managerEmailsStr = getSetting('void_refund_manager_email');
    $adminIdsStr      = getSetting('void_refund_admin_ids');

    $recipients = [];

    // Strictly exclude any inactive POS accounts from receiving tokens
    $inactiveEmails = $db->query("SELECT LOWER(email) as email FROM users WHERE is_active = 0 AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
    $inactiveSet = array_flip($inactiveEmails);

    // 1. If admin IDs were saved from Super Admin settings, fetch those admins' emails ONLY IF active in POS
    if (!empty($adminIdsStr)) {
        $ids = array_filter(array_map('intval', explode(',', $adminIdsStr)));
        if (!empty($ids)) {
            $inClause = implode(',', $ids);
            $stmt = $db->query("SELECT email FROM users WHERE id IN ($inClause) AND email != '' AND is_active = 1");
            foreach ($stmt->fetchAll() as $row) {
                $em = trim($row['email']);
                $low = strtolower($em);
                if (!isset($inactiveSet[$low]) && isValidSendableEmail($em)) {
                    $recipients[$low] = $em;
                }
            }
        }
    }

    // 2. Also check void_refund_manager_email setting, strictly excluding any inactive POS accounts
    if (!empty($managerEmailsStr)) {
        $mgrList = array_filter(array_map('trim', explode(',', $managerEmailsStr)));
        foreach ($mgrList as $mEmail) {
            $cleanEmail = strtolower($mEmail);
            // Inactive emails in POS cannot receive tokens
            if (isset($inactiveSet[$cleanEmail])) {
                continue;
            }
            if (isValidSendableEmail($mEmail)) {
                $recipients[$cleanEmail] = $mEmail;
            }
        }
    }

    // 3. Fallback only if NO recipients were ever configured in settings (active admins only)
    if (empty($recipients)) {
        $admins = $db->query("SELECT email FROM users WHERE role IN ('admin','super_admin') AND email != '' AND is_active = 1")->fetchAll();
        foreach ($admins as $adm) {
            $em = trim($adm['email']);
            $low = strtolower($em);
            if (!isset($inactiveSet[$low]) && isValidSendableEmail($em)) {
                $recipients[$low] = $em;
            }
        }
    }
    return $recipients;
}

function generateUniquePinCode($db) {
    do {
        $pinCode = sprintf("%06d", mt_rand(100000, 999999));
        $stmt = $db->prepare("SELECT id FROM admin_tokens WHERE token = ?");
        $stmt->execute([$pinCode]);
    } while ($stmt->fetch());
    return $pinCode;
}

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
    
    // Generate a secure token for email approval and a 6-digit PIN for POS
    $approvalToken = bin2hex(random_bytes(32));
    $pinCode = generateUniquePinCode($db);

    // Store the PIN code in admin_tokens for POS validation
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pinCode, $_SESSION['user_id'], $approvalToken, $expiresAt]);

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'void_order',
           $_SESSION['user_id'],
           $requester,
           "Void Order #{$orderId}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'reason' => $reason, 'amount' => $order['total'], 'pin' => $pinCode]),
           $approvalToken
       ]);

    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order ID:</td><td style='font-weight: 700; color: #fff;'>#{$orderId}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Requested By:</td><td>{$requester}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Amount:</td><td style='color: #dc2626; font-weight: bold;'>&#8369;" . number_format($order['total'], 2) . "</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Reason:</td><td>{$reason}</td></tr>";

    $actionBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Void Request &mdash; Authorization Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <div style='text-align:center;margin:20px 0;'>
                <p style='font-size:12px;color:#aaa;margin-bottom:6px;'>Authorization PIN / Token for POS:</p>
                <span style='background:#3a2f2d;color:#fff;padding:12px 24px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$pinCode</span>
                <p style='font-size:11px;color:#888;margin-top:6px;'>Valid for $expiryMin minutes</p>
            </div>
            <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve Void</a>
                <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject</a>
            </div>
        </div>
    </div>";

    $resp = [
        'success'     => true,
        'message'     => 'Void request submitted for manager/admin approval.',
        'batch_token' => $approvalToken
    ];
    if (DEV_MODE) $resp['dev_token'] = $pinCode;

    // Flush response to browser immediately (<50ms)
    jsonResponseAndContinue($resp);

    // Deliver emails in background
    $recipients = getVoidRefundRecipients();
    foreach ($recipients as $email) {
        sendEmail($email, "$storeName - Void Authorization Code: Order #{$orderId} ($pinCode)", $actionBody);
    }
    exit;
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

    // Generate a secure token for email approval and a 6-digit PIN for POS
    $approvalToken = bin2hex(random_bytes(32));
    $pinCode = generateUniquePinCode($db);

    // Store the PIN code in admin_tokens for POS validation
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pinCode, $_SESSION['user_id'], $approvalToken, $expiresAt]);

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'refund_order',
           $_SESSION['user_id'],
           $requester,
           "Refund Order #{$orderId}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'reason' => $reason, 'amount' => $order['total'], 'pin' => $pinCode]),
           $approvalToken
       ]);

    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order ID:</td><td style='font-weight: 700; color: #fff;'>#{$orderId}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Requested By:</td><td>{$requester}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Amount:</td><td style='color: #ea580c; font-weight: bold;'>&#8369;" . number_format($order['total'], 2) . "</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Reason:</td><td>{$reason}</td></tr>";

    $actionBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #ea580c; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#8634;</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>Order Refund Request &mdash; Authorization Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            <div style='text-align:center;margin:20px 0;'>
                <p style='font-size:12px;color:#aaa;margin-bottom:6px;'>Authorization PIN / Token for POS:</p>
                <span style='background:#3a2f2d;color:#fff;padding:12px 24px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$pinCode</span>
                <p style='font-size:11px;color:#888;margin-top:6px;'>Valid for $expiryMin minutes</p>
            </div>
            <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve Refund</a>
                <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject</a>
            </div>
        </div>
    </div>";

    $resp = [
        'success'     => true,
        'message'     => 'Refund request submitted for manager/admin approval.',
        'batch_token' => $approvalToken
    ];
    if (DEV_MODE) $resp['dev_token'] = $pinCode;

    // Flush response to browser immediately (<50ms)
    jsonResponseAndContinue($resp);

    // Deliver emails in background
    $recipients = getVoidRefundRecipients();
    foreach ($recipients as $email) {
        sendEmail($email, "$storeName - Refund Authorization Code: Order #{$orderId} ($pinCode)", $actionBody);
    }
    exit;
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
    session_write_close();
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

            $stmt = $db->prepare("SELECT id, name, price, size_prices, stock, min_stock, is_available FROM menu_items WHERE id = ? FOR UPDATE");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();

            if (!$item) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => "Item ID $itemId not found."], 404);
            }
            if (!$item['is_available']) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => "{$item['name']} is currently marked unavailable."], 400);
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

        $cashierName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';
        $formattedDate = date('M d, Y h:i A');

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
                'date' => date('M d, Y'),
                'created_at' => date('Y-m-d H:i:s'),
                'formatted_date' => $formattedDate,
                'cashier' => $cashierName,
                'line_items' => array_map(function($li) {
                    return [
                        'item_id' => $li['item_id'],
                        'name' => $li['name'],
                        'size' => $li['size'],
                        'qty' => $li['qty'],
                        'price' => $li['price'],
                        'subtotal' => $li['subtotal']
                    ];
                }, $lineItems)
            ],
            'stock_alerts' => $stockAlerts
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()], 500);
    }
}

function handleGetReceipt() {
    requireLogin();
    $id = intval($_GET['id'] ?? 0);
    $orderNumber = trim($_GET['order_number'] ?? '');
    $db = getDB();

    if ($id) {
        $stmt = $db->prepare("SELECT o.*, u.full_name AS cashier_name, u.username AS cashier_username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$id]);
    } elseif ($orderNumber) {
        $stmt = $db->prepare("SELECT o.*, u.full_name AS cashier_name, u.username AS cashier_username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.order_number = ?");
        $stmt->execute([$orderNumber]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Order ID or order number is required.'], 400);
    }

    $order = $stmt->fetch();
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Fetch line items with item details
    $iStmt = $db->prepare("
        SELECT oi.*, m.name AS item_name, m.emoji
        FROM order_items oi
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $iStmt->execute([$order['id']]);
    $items = $iStmt->fetchAll();

    $cashier = $order['cashier_name'] ?: ($order['cashier_username'] ?: 'Staff');
    $formattedDate = date('M d, Y h:i A', strtotime($order['created_at']));

    jsonResponse([
        'success' => true,
        'order' => [
            'id' => intval($order['id']),
            'order_number' => $order['order_number'],
            'total' => floatval($order['total']),
            'payment_method' => $order['payment_method'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'formatted_date' => $formattedDate,
            'cashier' => $cashier,
            'line_items' => array_map(function($it) {
                return [
                    'item_id' => intval($it['menu_item_id']),
                    'name' => $it['item_name'] ?: 'Item',
                    'size' => $it['size'],
                    'qty' => intval($it['quantity']),
                    'price' => floatval($it['unit_price']),
                    'subtotal' => floatval($it['subtotal'])
                ];
            }, $items)
        ]
    ]);
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
    $allCats = $db->query("SELECT id, name FROM categories")->fetchAll();
    $drinkIds = [];
    $foodIds = [];
    foreach ($allCats as $cat) {
        $n = strtolower($cat['name']);
        if (preg_match('/coffee|tea|milktea|drink|beverage|mojito|boba|float|frapp|matcha|juice|shake|smoothie|water/i', $n)) {
            $drinkIds[] = intval($cat['id']);
        } else {
            $foodIds[] = intval($cat['id']);
        }
    }
    $drinkCatIds = !empty($drinkIds) ? implode(',', $drinkIds) : '1,2,3,4,5,6,7,8,18';
    $foodCatIds  = !empty($foodIds)  ? implode(',', $foodIds)  : '9,10,11,12,13,14,15,16,17,19,20';


    // Today
    $today = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'Completed'")->fetch();
    // This week
    $week = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'")->fetch();
    // This month
    $month = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'")->fetch();

    // Volume breakdown for Today, This Week, This Month, and This Year
    $vol = $db->query("
        SELECT 
            COALESCE(SUM(CASE WHEN DATE(o.created_at) = CURDATE() AND m.category_id IN ($drinkCatIds) THEN oi.quantity ELSE 0 END), 0) AS today_cups,
            COALESCE(SUM(CASE WHEN DATE(o.created_at) = CURDATE() AND m.category_id IN ($foodCatIds) THEN oi.quantity ELSE 0 END), 0) AS today_food,
            COALESCE(SUM(CASE WHEN YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1) AND m.category_id IN ($drinkCatIds) THEN oi.quantity ELSE 0 END), 0) AS week_cups,
            COALESCE(SUM(CASE WHEN YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1) AND m.category_id IN ($foodCatIds) THEN oi.quantity ELSE 0 END), 0) AS week_food,
            COALESCE(SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) AND m.category_id IN ($drinkCatIds) THEN oi.quantity ELSE 0 END), 0) AS month_cups,
            COALESCE(SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) AND m.category_id IN ($foodCatIds) THEN oi.quantity ELSE 0 END), 0) AS month_food,
            COALESCE(SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND m.category_id IN ($drinkCatIds) THEN oi.quantity ELSE 0 END), 0) AS year_cups,
            COALESCE(SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND m.category_id IN ($foodCatIds) THEN oi.quantity ELSE 0 END), 0) AS year_food
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE (YEAR(o.created_at) = YEAR(CURDATE()) OR YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)) AND o.status = 'Completed'
    ")->fetch();

    $cupsSold = intval($vol['today_cups'] ?? 0);
    $foodSold = intval($vol['today_food'] ?? 0);
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

    // Best revenue day calculation (Hierarchy: This Week -> Last 30 Days -> All-Time Peak)
    $bestDayWeek = $db->query("
        SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev, COUNT(*) as cnt
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'
        GROUP BY DATE(created_at), DAYNAME(created_at)
        ORDER BY rev DESC LIMIT 1
    ")->fetch();

    $bestDayMonth = $db->query("
        SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev, COUNT(*) as cnt
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'
        GROUP BY DATE(created_at), DAYNAME(created_at)
        ORDER BY rev DESC LIMIT 1
    ")->fetch();

    $bestDayAllTime = $db->query("
        SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev, COUNT(*) as cnt
        FROM orders 
        WHERE status = 'Completed'
        GROUP BY DATE(created_at), DAYNAME(created_at)
        ORDER BY rev DESC LIMIT 1
    ")->fetch();

    $bestRevenueDay = null;
    if ($bestDayWeek && floatval($bestDayWeek['rev']) > 0) {
        $bestRevenueDay = [
            'day_name' => $bestDayWeek['day_name'],
            'date'     => date('M j', strtotime($bestDayWeek['d'])),
            'revenue'  => floatval($bestDayWeek['rev']),
            'period'   => 'this week'
        ];
    } elseif ($bestDayMonth && floatval($bestDayMonth['rev']) > 0) {
        $bestRevenueDay = [
            'day_name' => $bestDayMonth['day_name'],
            'date'     => date('M j', strtotime($bestDayMonth['d'])),
            'revenue'  => floatval($bestDayMonth['rev']),
            'period'   => 'last 30 days'
        ];
    } elseif ($bestDayAllTime && floatval($bestDayAllTime['rev']) > 0) {
        $bestRevenueDay = [
            'day_name' => $bestDayAllTime['day_name'],
            'date'     => date('M j, Y', strtotime($bestDayAllTime['d'])),
            'revenue'  => floatval($bestDayAllTime['rev']),
            'period'   => 'all-time peak'
        ];
    }

    jsonResponse([
        'success' => true,
        'today' => ['orders' => intval($today['cnt']), 'revenue' => floatval($today['rev'])],
        'week'  => ['orders' => intval($week['cnt']),  'revenue' => floatval($week['rev'])],
        'month' => ['orders' => intval($month['cnt']), 'revenue' => floatval($month['rev'])],
        'best_revenue_day' => $bestRevenueDay,
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
        'volume_breakdown' => [
            'today' => ['cups' => intval($vol['today_cups'] ?? 0), 'food' => intval($vol['today_food'] ?? 0)],
            'week'  => ['cups' => intval($vol['week_cups']  ?? 0), 'food' => intval($vol['week_food']  ?? 0)],
            'month' => ['cups' => intval($vol['month_cups'] ?? 0), 'food' => intval($vol['month_food'] ?? 0)],
            'year'  => ['cups' => intval($vol['year_cups']  ?? 0), 'food' => intval($vol['year_food']  ?? 0)],
        ],
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
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Request Already Processed</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1514; color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .container { text-align: center; background: #2a2423; padding: 40px; border-radius: 16px; border: 1px solid #3d3533; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 420px; width: 90%; }
                .icon { font-size: 52px; margin-bottom: 16px; }
                h1 { color: #f59e0b; margin: 0 0 10px 0; font-size: 22px; }
                p { color: #aaa; margin: 0; line-height: 1.5; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='icon'>&#9888;</div>
                <h1>Request Already Processed</h1>
                <p>This void/refund request has already been approved, rejected, or expired.</p>
                <p style='margin-top: 16px; font-size: 12px; color: #777;'>You can safely close this window.</p>
            </div>
        </body>
        </html>";
        exit;
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
                    $processedBy = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : (!empty($request['requested_by']) ? $request['requested_by'] : null);
                    if ($processedBy) {
                        $chkUser = $db->prepare("SELECT id FROM users WHERE id = ?");
                        $chkUser->execute([$processedBy]);
                        if (!$chkUser->fetch()) {
                            $processedBy = null;
                        }
                    }
                    $db->prepare("INSERT INTO order_adjustments (order_id, type, amount, reason, processed_by) VALUES (?, ?, ?, ?, ?)")
                       ->execute([$orderId, $adjType, $payload['amount'] ?? 0, $payload['reason'] ?? '', $processedBy]);
                    $db->commit();
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Void/Refund error for order {$orderId}: " . $e->getMessage());
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

    try {
        $db = getDB();
        $approvalToken = bin2hex(random_bytes(32));
        $pinCode = generateUniquePinCode($db);
        
        // Store the PIN code in admin_tokens for POS validation
        $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
        if ($expiryMin < 5) $expiryMin = 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
        $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$pinCode, $_SESSION['user_id'], $approvalToken, $expiresAt]);

        $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
        $orderListHtml = '';
        $createdCount = 0;

        foreach ($orders as $o) {
            $orderId = intval($o['id']);
            $type = ($o['type'] ?? '') === 'refund' ? 'refund_order' : 'void_order';
            $perReason = trim($o['reason'] ?? ($type === 'void_order' ? 'Order cancelled' : 'Customer refund'));
            $reason = $manager ? "{$perReason} — Requested by: {$manager}" : $perReason;
            
            $orderInfo = $db->prepare("SELECT id, status, total FROM orders WHERE id = ?");
            $orderInfo->execute([$orderId]);
            $orderData = $orderInfo->fetch();
            if (!$orderData || in_array($orderData['status'], ['Voided', 'Refunded'])) continue;

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
            $createdCount++;
                
            $color = $type === 'void_order' ? '#dc2626' : '#ea580c';
            $orderListHtml .= "
            <tr>
                <td style='padding: 6px 0; font-weight: 600; color: #aaa;'>#{$orderId}</td>
                <td style='padding: 6px 0; color: #fff;'>{$reason}</td>
                <td style='padding: 6px 0; font-weight: bold; color: {$color};'>₱" . number_format($orderData['total'], 2) . "</td>
            </tr>";
        }

        if ($createdCount === 0) {
            $db->prepare("DELETE FROM admin_tokens WHERE token = ?")->execute([$pinCode]);
            jsonResponse(['success' => false, 'message' => 'Selected orders are already voided, refunded, or not found.'], 400);
        }

        $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
        $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
        $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

        $requesterDesc = "<strong>{$requester}</strong>";
        if ($manager && strtolower($manager) !== strtolower($requester)) {
            $requesterDesc .= " (Staff: <strong>" . htmlspecialchars($manager) . "</strong>)";
        }

        // --- Shared order table ---
        $orderTable = "
            <p style='color: #ddd; margin-top: 0;'>Requested By: {$requesterDesc}</p>
            <table style='width: 100%; font-size: 14px; text-align: left; margin: 0 0 16px 0; border-collapse: collapse;'>
                <tr>
                    <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>ID</th>
                    <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>Reason</th>
                    <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>Amount</th>
                </tr>
                {$orderListHtml}
            </table>";

        // --- Action required email body sent to manager(s) and admin/owner ---
        $actionBody = "
        <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='display: inline-block; width: 50px; height: 50px; background: #dc2626; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>&#9888;</div>
                <h2 style='color: #fff; margin: 10px 0 5px;'>$storeName</h2>
                <p style='color: #aaa; font-size: 13px; margin: 0;'>Batch Void/Refund Request &mdash; Authorization Token</p>
            </div>
            <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
                {$orderTable}
                <div style='text-align:center;margin:24px 0;'>
                    <p style='font-size:12px;color:#aaa;margin-bottom:6px;'>Authorization PIN / Token for POS:</p>
                    <span style='background:#3a2f2d;color:#fff;padding:12px 24px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$pinCode</span>
                    <p style='font-size:11px;color:#888;margin-top:6px;'>Valid for $expiryMin minutes</p>
                </div>
                <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
                    <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; Approve All</a>
                    <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject All</a>
                </div>
            </div>
        </div>";

        $recipients = getVoidRefundRecipients();
        $countRecipients = count($recipients);

        $resp = [
            'success'          => true, 
            'message'          => "Void/refund request submitted! Token sent to {$countRecipients} manager/admin email(s).", 
            'batch_token'      => $approvalToken,
            'recipients_count' => $countRecipients
        ];
        if (DEV_MODE) $resp['dev_token'] = $pinCode;

        // Flush response to browser immediately (<50ms)
        jsonResponseAndContinue($resp);

        // Deliver batch emails in background
        try {
            foreach ($recipients as $email) {
                sendEmail($email, "$storeName - Void/Refund Authorization Token ($pinCode)", $actionBody);
            }
        } catch (Throwable $mailEx) {
            error_log("Failed to send void batch email: " . $mailEx->getMessage());
        }
        exit;

    } catch (Throwable $e) {
        error_log("handleRequestVoidBatch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Request failed: ' . $e->getMessage()], 500);
    }
}
function handleExecuteVoidBatchWithCode() {
    requireLogin();
    $savedUserId = $_SESSION['user_id'] ?? null;
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

    $approvalToken = $tokenRow['purpose']; // We stored the approval_token in purpose column

    // Mark token as used
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $tokenRow['id']]);

    // Fetch all pending system_requests for this approval token
    $stmt = $db->prepare("SELECT * FROM system_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$approvalToken]);
    $requests = $stmt->fetchAll();

    if (!$requests || count($requests) === 0) {
        // May have already been approved via email link â€” still return success to the cashier
        jsonResponse(['success' => true, 'message' => 'Request already processed.']);
    }

    // Mark all matching requests as approved via PIN
    $db->prepare("UPDATE system_requests SET status = 'approved', reviewed_by = ?, reviewed_at = ?, review_note = 'Processed via PIN Code' WHERE approval_token = ?")
       ->execute([$savedUserId, $now, $approvalToken]);

    // Also resolve any older orphaned pending requests for the same order IDs (belt-and-suspenders)
    $orderIdsInBatch = [];
    foreach ($requests as $_r) {
        $_p = $_r['payload'] ? json_decode($_r['payload'], true) : [];
        if (!empty($_p['order_id'])) $orderIdsInBatch[] = strval(intval($_p['order_id']));
    }
    if (!empty($orderIdsInBatch)) {
        foreach ($orderIdsInBatch as $_oid) {
            $db->prepare("UPDATE system_requests SET status = 'approved', reviewed_by = ?, reviewed_at = ?, review_note = 'Approved in POS via PIN' WHERE JSON_UNQUOTE(JSON_EXTRACT(payload, '\$.order_id')) = ? AND status = 'pending' AND type IN ('void_order','refund_order')")
               ->execute([$savedUserId, $now, $_oid]);
        }
    }

    $processed = 0;
    $errors    = [];

    foreach ($requests as $request) {
        $payload   = $request['payload'] ? json_decode($request['payload'], true) : [];
        $orderId   = intval($payload['order_id'] ?? 0);
        if (!$orderId) continue;

        $newOrderStatus = $request['type'] === 'void_order' ? 'Voided' : 'Refunded';
        $adjType        = $request['type'] === 'void_order' ? 'void'   : 'refund';

        try {
            $db->beginTransaction();

            // Update order status
            $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newOrderStatus, $orderId]);

            // Restore stock
            $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $db->prepare("UPDATE menu_items SET stock = stock + ?, is_available = 1 WHERE id = ?")
                   ->execute([$item['quantity'], $item['menu_item_id']]);
            }

            // Resolve processed_by: prefer the session user who entered PIN, then token owner, then requester
            $processedBy = $savedUserId ? intval($savedUserId) : null;
            if (!$processedBy && !empty($tokenRow['user_id'])) {
                $processedBy = intval($tokenRow['user_id']);
            }
            if (!$processedBy && !empty($request['requested_by'])) {
                $processedBy = intval($request['requested_by']);
            }
            // Validate the user actually exists
            if ($processedBy) {
                $chk = $db->prepare("SELECT id FROM users WHERE id = ?");
                $chk->execute([$processedBy]);
                if (!$chk->fetch()) $processedBy = null;
            }

            $db->prepare("INSERT INTO order_adjustments (order_id, type, amount, reason, processed_by) VALUES (?, ?, ?, ?, ?)")
               ->execute([$orderId, $adjType, $payload['amount'] ?? 0, $payload['reason'] ?? '', $processedBy]);

            $db->commit();
            $processed++;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Order {$orderId}: " . $e->getMessage();
            error_log("Void/Refund PIN error for order {$orderId}: " . $e->getMessage());
        }
    }

    if ($processed === 0 && !empty($errors)) {
        jsonResponse(['success' => false, 'message' => 'Processing failed: ' . implode('; ', $errors)], 500);
    }

    jsonResponse(['success' => true, 'message' => "Successfully processed {$processed} order(s) via PIN."]);
}


function handleRejectVoidBatch() {
    requireLogin();
    $savedUserId   = $_SESSION['user_id'] ?? null;
    $savedUserName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');
    session_write_close();
    $input      = getJsonInput();
    $batchToken = trim($input['batch_token'] ?? '');
    $reason     = trim($input['reason'] ?? '');
    $isCancel   = !empty($input['is_cancel']); // true = user cancelled themselves; false = admin rejected

    if (!$batchToken) {
        jsonResponse(['success' => false, 'message' => 'batch_token is required.'], 400);
    }

    $newStatus  = $isCancel ? 'cancelled' : 'rejected';
    $defaultMsg = $isCancel ? 'Cancelled in POS' : 'Rejected in POS';
    if (!$reason) $reason = $defaultMsg;
    $reviewNote = ($isCancel ? 'Cancelled' : 'Rejected') . " in POS by {$savedUserName}: {$reason}";

    $db  = getDB();
    $now = date('Y-m-d H:i:s');

    // Fetch all pending requests for this token
    $stmt = $db->prepare("SELECT * FROM system_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$batchToken]);
    $requests = $stmt->fetchAll();

    if (!$requests || count($requests) === 0) {
        // Already processed — still return success so the POS modal closes cleanly
        jsonResponse(['success' => true, 'message' => 'Request already processed.']);
    }

    // Mark token requests as cancelled/rejected
    $db->prepare("UPDATE system_requests SET status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE approval_token = ? AND status = 'pending'")->execute([$newStatus, $savedUserId, $now, $reviewNote, $batchToken]);

    // Also mark any older orphaned pending requests for the same order IDs
    foreach ($requests as $req) {
        $payload = $req['payload'] ? json_decode($req['payload'], true) : [];
        $orderId = strval(intval($payload['order_id'] ?? 0));
        if ($orderId && $orderId !== '0') {
            $db->prepare("UPDATE system_requests SET status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE JSON_UNQUOTE(JSON_EXTRACT(payload, '$.order_id')) = ? AND status = 'pending' AND type IN ('void_order','refund_order')")->execute([$newStatus, $savedUserId, $now, $reviewNote, $orderId]);
        }
    }

    jsonResponse(['success' => true, 'message' => "Void/refund request {$newStatus}."]);
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

    $hasCancelled = in_array('cancelled', $statuses);

    if (!$hasPending && $hasApproved) {
        $note = $rows[0]['review_note'] ?? '';
        $via  = (strpos($note, 'Email') !== false) ? 'Email'
              : ((strpos($note, 'PIN')   !== false) ? 'PIN' : 'Super Admin');
        jsonResponse(['success' => true, 'batch_status' => 'approved', 'approved_via' => $via]);
    } elseif (!$hasPending && $hasCancelled && !$hasApproved) {
        jsonResponse(['success' => true, 'batch_status' => 'cancelled']);
    } elseif (!$hasPending && !$hasApproved) {
        jsonResponse(['success' => true, 'batch_status' => 'rejected']);
    } else {
        jsonResponse(['success' => true, 'batch_status' => 'pending']);
    }
}

// ── Chart: Weekly (by week_start or week offset) ─────────────────────────────
function handleChartWeekly() {
    requireLogin();
    $db = getDB();

    if (!empty($_GET['week_start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['week_start'])) {
        $weekStart = $_GET['week_start'];
        $weekEnd   = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    } else {
        $offset = max(0, min(52, intval($_GET['week_offset'] ?? 0)));
        $weekStart = date('Y-m-d', strtotime("monday this week -{$offset} week"));
        $weekEnd   = date('Y-m-d', strtotime("sunday this week -{$offset} week"));
    }

    $rows = $db->prepare("
        SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
          AND status = 'Completed'
        GROUP BY DAYOFWEEK(created_at)
    ");
    $rows->execute([$weekStart, $weekEnd]);
    $data = [0,0,0,0,0,0,0];
    foreach ($rows->fetchAll() as $r) {
        $idx = ($r['dow'] + 5) % 7; // Mon=0
        $data[$idx] = floatval($r['rev']);
    }

    // Build day labels with actual dates
    $labels = [];
    for ($i = 0; $i < 7; $i++) {
        $labels[] = date('D j', strtotime($weekStart . " +{$i} days"));
    }

    $formattedLabel = date('M j', strtotime($weekStart)) . ' – ' . date('M j, Y', strtotime($weekEnd));

    jsonResponse([
        'success'         => true,
        'data'            => $data,
        'labels'          => $labels,
        'formatted_label' => $formattedLabel,
        'week_start'      => $weekStart,
        'week_end'        => $weekEnd
    ]);
}

// ── Chart: Available weeks for picker ────────────────────────────────────────
function handleChartWeeklyWeeks() {
    requireLogin();
    $db = getDB();
    // Get up to 52 distinct Monday-of-week dates that have orders
    $rows = $db->query("
        SELECT DISTINCT DATE_SUB(DATE(created_at), INTERVAL (DAYOFWEEK(created_at)+5)%7 DAY) as week_start
        FROM orders WHERE status='Completed'
        ORDER BY week_start DESC LIMIT 52
    ")->fetchAll();
    $weeks = [];
    foreach ($rows as $r) {
        $start = $r['week_start'];
        $end   = date('Y-m-d', strtotime($start . ' +6 days'));
        $weeks[] = [
            'week_start' => $start,
            'label'      => date('M j', strtotime($start)) . ' – ' . date('M j, Y', strtotime($end))
        ];
    }
    if (empty($weeks)) {
        $start = date('Y-m-d', strtotime('monday this week'));
        $end   = date('Y-m-d', strtotime('sunday this week'));
        $weeks[] = ['week_start' => $start, 'label' => date('M j', strtotime($start)) . ' – ' . date('M j, Y', strtotime($end))];
    }
    jsonResponse(['success' => true, 'weeks' => $weeks]);
}

// ── Chart: Hourly (by date) ──────────────────────────────────────────────────
function handleChartHourly() {
    requireLogin();
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $db = getDB();
    $rows = $db->prepare("
        SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
        FROM orders
        WHERE DATE(created_at) = ?
          AND status = 'Completed'
        GROUP BY HOUR(created_at)
    ");
    $rows->execute([$date]);
    $data = array_fill(0, 18, 0); // 9am–2am (18 slots)
    foreach ($rows->fetchAll() as $r) {
        $h = intval($r['hr']);
        if ($h >= 9)       $data[$h - 9]  = floatval($r['rev']);
        elseif ($h <= 2)   $data[$h + 15] = floatval($r['rev']);
    }

    jsonResponse(['success' => true, 'data' => $data, 'date' => $date]);
}

// ── Chart: Monthly (by year) ─────────────────────────────────────────────────
function handleChartMonthly() {
    requireLogin();
    $year = intval($_GET['year'] ?? date('Y'));
    if ($year < 2020 || $year > 2100) $year = intval(date('Y'));

    $db = getDB();
    $rows = $db->prepare("
        SELECT MONTH(created_at) as mo, COALESCE(SUM(total),0) as rev
        FROM orders
        WHERE YEAR(created_at) = ? AND status = 'Completed'
        GROUP BY MONTH(created_at)
    ");
    $rows->execute([$year]);
    $data = [0,0,0,0,0,0,0,0,0,0,0,0];
    foreach ($rows->fetchAll() as $r) {
        $data[intval($r['mo']) - 1] = floatval($r['rev']);
    }

    // Available years (for the dropdown)
    $yearsRaw = $db->query("SELECT DISTINCT YEAR(created_at) as yr FROM orders WHERE status='Completed' ORDER BY yr DESC")->fetchAll();
    $years = array_map(fn($r) => intval($r['yr']), $yearsRaw);
    if (empty($years)) $years = [intval(date('Y'))];

    jsonResponse(['success' => true, 'data' => $data, 'year' => $year, 'available_years' => $years]);
}
