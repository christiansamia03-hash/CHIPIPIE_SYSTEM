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
elseif ($method === 'GET' && $action === 'chart_weekly_weeks') { handleChartWeeklyWeeks(); }
elseif ($method === 'GET' && $action === 'chart_weekly')       { handleChartWeekly(); }
elseif ($method === 'GET' && $action === 'chart_hourly')       { handleChartHourly(); }
elseif ($method === 'GET' && $action === 'chart_monthly')      { handleChartMonthly(); }
elseif ($method === 'GET' && ($action === 'pizza_orders' || $action === 'station_orders')) { handleStationOrders(); }
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

    // 3. Fallback only if NO recipients were ever configured in settings (active admins, owner, superadmin)
    if (empty($recipients)) {
        $admins = $db->query("SELECT email FROM users WHERE role IN ('admin','super_admin') AND email != '' AND is_active = 1")->fetchAll();
        foreach ($admins as $adm) {
            $em = trim($adm['email']);
            $low = strtolower($em);
            if (!isset($inactiveSet[$low]) && isValidSendableEmail($em)) {
                $recipients[$low] = $em;
            }
        }
        foreach ([getSetting('owner_email'), getSetting('superadmin_email')] as $fallbackEm) {
            $em = trim((string)$fallbackEm);
            $low = strtolower($em);
            if (!empty($em) && !isset($inactiveSet[$low]) && isValidSendableEmail($em)) {
                $recipients[$low] = $em;
            }
        }
    }
    return $recipients;
}

function generateUniquePinCode($db) {
    do {
        $pinCode = sprintf("%06d", mt_rand(100000, 999999));
        $stmt = $db->prepare("SELECT id FROM admin_tokens WHERE token = ? OR token = ?");
        $stmt->execute([hashToken($pinCode), $pinCode]);
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
    $order = $db->prepare("SELECT id, order_number, status, total, user_id FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    if (in_array($order['status'], ['Voided','Refunded'])) {
        jsonResponse(['success' => false, 'message' => 'Order already ' . strtolower($order['status']) . '.'], 400);
    }

    $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
    $rawOrderNum = !empty($order['order_number']) ? $order['order_number'] : $orderId;
    $cleanOrderNum = '#' . ltrim((string)$rawOrderNum, '#');
    
    // Generate a secure token for email approval and a 6-digit PIN for POS
    $approvalToken = bin2hex(random_bytes(32));
    $pinCode = generateUniquePinCode($db);

    // Store the PIN code in admin_tokens for POS validation
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([hashToken($pinCode), $_SESSION['user_id'], $approvalToken, $expiresAt]);
    purgeExpiredAdminTokens($db);

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'void_order',
           $_SESSION['user_id'],
           $requester,
           "Void Order {$cleanOrderNum}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'order_number' => $cleanOrderNum, 'reason' => $reason, 'amount' => $order['total'], 'pin' => $pinCode]),
           $approvalToken
       ]);

    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order #:</td><td style='font-weight: 700; color: #fff;'>{$cleanOrderNum}</td></tr>
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

    $recipients = getVoidRefundRecipients();
    if (!empty($recipients)) {
        sendEmail(array_values($recipients), "$storeName - Void Authorization Code: Order #{$orderId} ($pinCode)", $actionBody);
    }
    jsonResponse($resp);
}

function handleRefundOrder() {
    requireLogin();
    session_write_close();
    $input = getJsonInput();
    $orderId = intval($input['order_id'] ?? 0);
    $reason  = trim($input['reason'] ?? 'Customer refund request');
    if (!$orderId) { jsonResponse(['success' => false, 'message' => 'Invalid order ID.'], 400); }

    $db = getDB();
    $order = $db->prepare("SELECT id, order_number, status, total, user_id FROM orders WHERE id = ?");
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    if (in_array($order['status'], ['Voided','Refunded'])) {
        jsonResponse(['success' => false, 'message' => 'Order already ' . strtolower($order['status']) . '.'], 400);
    }

    $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
    $rawOrderNum = !empty($order['order_number']) ? $order['order_number'] : $orderId;
    $cleanOrderNum = '#' . ltrim((string)$rawOrderNum, '#');

    // Generate a secure token for email approval and a 6-digit PIN for POS
    $approvalToken = bin2hex(random_bytes(32));
    $pinCode = generateUniquePinCode($db);

    // Store the PIN code in admin_tokens for POS validation
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([hashToken($pinCode), $_SESSION['user_id'], $approvalToken, $expiresAt]);
    purgeExpiredAdminTokens($db);

    // Create the request
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
       ->execute([
           'refund_order',
           $_SESSION['user_id'],
           $requester,
           "Refund Order {$cleanOrderNum}",
           "Reason: {$reason}",
           json_encode(['order_id' => $orderId, 'order_number' => $cleanOrderNum, 'reason' => $reason, 'amount' => $order['total'], 'pin' => $pinCode]),
           $approvalToken
       ]);

    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/orders.php?action=decide_void_refund&token=" . $approvalToken . "&decision=reject";

    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Order #:</td><td style='font-weight: 700; color: #fff;'>{$cleanOrderNum}</td></tr>
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

    $recipients = getVoidRefundRecipients();
    if (!empty($recipients)) {
        sendEmail(array_values($recipients), "$storeName - Refund Authorization Code: Order #{$orderId} ($pinCode)", $actionBody);
    }
    jsonResponse($resp);
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
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $sessionStation = trim($_SESSION['assigned_station'] ?? '');
    $cashierName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';
    session_write_close();

    $input = getJsonInput();
    $cartItems = $input['items'] ?? [];
    $paymentMethod = trim($input['payment_method'] ?? 'Cash');
    $branchInput = trim($input['branch'] ?? 'coffee');
    $originInput = trim($input['origin_branch'] ?? $input['origin'] ?? $input['station'] ?? '');

    // Determine originating branch: explicit origin > session station > fallback to branch
    $originBranch = '';
    if ($originInput === 'pizza' || $originInput === 'cafe') {
        $originBranch = $originInput;
    } elseif ($sessionStation === 'pizza' || $sessionStation === 'cafe') {
        $originBranch = $sessionStation;
    }

    if (empty($cartItems)) {
        jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 400);
    }

    $db = getDB();

    if (!$originBranch && $userId > 0) {
        $uStmt = $db->prepare("SELECT assigned_station FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $dbStation = $uStmt->fetchColumn() ?: '';
        if ($dbStation === 'pizza' || $dbStation === 'cafe') {
            $originBranch = $dbStation;
        }
    }
    if (!$originBranch) {
        $originBranch = ($branchInput === 'pizza') ? 'pizza' : 'cafe';
    }

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
        // Create order (recording origin branch: cafe or pizza)
        try {
            $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, total, payment_method, status, origin_branch) VALUES (?, ?, ?, ?, 'Completed', ?)");
            $stmt->execute([$orderNum, $userId, $total, $paymentMethod, $originBranch]);
        } catch (Exception $e) {
            $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, total, payment_method, status) VALUES (?, ?, ?, ?, 'Completed')");
            $stmt->execute([$orderNum, $userId, $total, $paymentMethod]);
        }


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
                'origin_branch' => $originBranch,
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

    $userRole   = $_SESSION['role'] ?? 'cashier';
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');
    $where = '';
    $params = [];

    // Cashiers are restricted strictly to today's orders
    if ($userRole === 'cashier') {
        $dateFilter = 'today';
        $dateMode   = '';
        $dateVal    = '';
    }

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
               o.created_at, MAX(u.full_name) AS cashier,
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
        GROUP BY o.id, o.order_number, o.total, o.payment_method, o.status, o.created_at
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

    // 1. Single merged query for counts and revenue (1 round-trip instead of 3)
    $kpi = $db->query("
        SELECT
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) AS today_cnt,
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END), 0) AS today_rev,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS week_cnt,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN total ELSE 0 END), 0) AS week_rev,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS month_cnt,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total ELSE 0 END), 0) AS month_rev
        FROM orders
        WHERE status = 'Completed'
    ")->fetch(PDO::FETCH_ASSOC);

    $todayOrders  = intval($kpi['today_cnt'] ?? 0);
    $todayRevenue = floatval($kpi['today_rev'] ?? 0);
    $weekOrders   = intval($kpi['week_cnt'] ?? 0);
    $weekRevenue  = floatval($kpi['week_rev'] ?? 0);
    $monthOrders  = intval($kpi['month_cnt'] ?? 0);
    $monthRevenue = floatval($kpi['month_rev'] ?? 0);

    // 2. Drink & Food category IDs (avoids full categories table scan)
    $drinkCatIds = '1,2,3,4,5,6,7,8,18';
    $foodCatIds  = '9,10,11,12,13,14,15,16,17,19,20';

    // 3. Volume breakdown for Today, This Week, This Month, and This Year
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
    ")->fetch(PDO::FETCH_ASSOC);

    $cupsSold = intval($vol['today_cups'] ?? 0);
    $foodSold = intval($vol['today_food'] ?? 0);

    // 4. Top seller (All time)
    $topSeller = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'Completed'
        GROUP BY m.id, m.name ORDER BY sold DESC LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    // 5. Top items today (fast shortcut: skip query if today has 0 orders)
    $topItems = [];
    if ($todayOrders > 0) {
        $topItems = $db->query("
            SELECT m.name, SUM(oi.quantity) as sold, COALESCE(SUM(oi.subtotal),0) as revenue
            FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) = CURDATE() AND o.status = 'Completed'
            GROUP BY m.id, m.name ORDER BY sold DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Category breakdown (last 30 days)
    $catBreakdown = $db->query("
        SELECT c.name, SUM(oi.quantity) as qty
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN categories c ON m.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status = 'Completed'
        GROUP BY c.id, c.name ORDER BY qty DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 7. Weekly chart data (skip if week has 0 orders)
    $weeklyData = [0,0,0,0,0,0,0];
    if ($weekOrders > 0) {
        $weeklyChart = $db->query("
            SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
            FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'
            GROUP BY DAYOFWEEK(created_at)
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($weeklyChart as $r) {
            $idx = ($r['dow'] + 5) % 7; // Convert to Mon=0
            $weeklyData[$idx] = floatval($r['rev']);
        }
    }

    // 8. Hourly chart data (9am to 2am shift - skip if today has 0 orders)
    $hourlyData = array_fill(0, 18, 0);
    if ($todayOrders > 0) {
        $hourlyChart = $db->query("
            SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
            FROM orders 
            WHERE created_at >= CONCAT(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), ' 09:00:00') 
              AND created_at < CONCAT(DATE_ADD(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), INTERVAL 1 DAY), ' 03:00:00')
              AND status = 'Completed'
            GROUP BY HOUR(created_at)
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($hourlyChart as $r) {
            $h = intval($r['hr']);
            if ($h >= 9) {
                $hourlyData[$h - 9] = floatval($r['rev']);
            } elseif ($h <= 2) {
                $hourlyData[$h + 15] = floatval($r['rev']);
            }
        }
    }

    // 9. Monthly chart data (this year)
    $monthlyChart = $db->query("
        SELECT MONTH(created_at) as mo, COALESCE(SUM(total),0) as rev
        FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND status = 'Completed'
        GROUP BY MONTH(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC);
    $monthlyData = [0,0,0,0,0,0,0,0,0,0,0,0];
    foreach ($monthlyChart as $r) {
        $monthlyData[intval($r['mo']) - 1] = floatval($r['rev']);
    }

    // 10. Item sales performance (all time, top 9)
    $itemPerf = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'Completed'
        GROUP BY m.id, m.name ORDER BY sold DESC LIMIT 9
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 11. Best revenue day calculation (smart single-check hierarchy)
    $bestRevenueDay = null;
    if ($weekOrders > 0) {
        $bestDayWeek = $db->query("
            SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY rev DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        if ($bestDayWeek && floatval($bestDayWeek['rev']) > 0) {
            $bestRevenueDay = [
                'day_name' => $bestDayWeek['day_name'],
                'date'     => date('M j', strtotime($bestDayWeek['d'])),
                'revenue'  => floatval($bestDayWeek['rev']),
                'period'   => 'this week'
            ];
        }
    }

    if (!$bestRevenueDay && $monthOrders > 0) {
        $bestDayMonth = $db->query("
            SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY rev DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        if ($bestDayMonth && floatval($bestDayMonth['rev']) > 0) {
            $bestRevenueDay = [
                'day_name' => $bestDayMonth['day_name'],
                'date'     => date('M j', strtotime($bestDayMonth['d'])),
                'revenue'  => floatval($bestDayMonth['rev']),
                'period'   => 'last 30 days'
            ];
        }
    }

    if (!$bestRevenueDay) {
        $bestDayAllTime = $db->query("
            SELECT DATE(created_at) as d, DAYNAME(created_at) as day_name, SUM(total) as rev
            FROM orders 
            WHERE status = 'Completed'
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY rev DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        if ($bestDayAllTime && floatval($bestDayAllTime['rev']) > 0) {
            $bestRevenueDay = [
                'day_name' => $bestDayAllTime['day_name'],
                'date'     => date('M j, Y', strtotime($bestDayAllTime['d'])),
                'revenue'  => floatval($bestDayAllTime['rev']),
                'period'   => 'all-time peak'
            ];
        }
    }

    // 12. Day-of-week sales & zero-sales days analysis (current month)
    $currentMonth = intval(date('m'));
    $currentYear  = intval(date('Y'));
    $dowSales = $db->query("
        SELECT 
            DAYOFWEEK(created_at) as dow,
            DAYNAME(DATE(created_at)) as day_name,
            COUNT(*) as orders_count,
            COALESCE(SUM(total), 0) as total_revenue
        FROM orders
        WHERE YEAR(created_at) = {$currentYear} 
          AND MONTH(created_at) = {$currentMonth}
          AND status = 'Completed'
        GROUP BY DAYOFWEEK(created_at), DAYNAME(DATE(created_at))
    ")->fetchAll(PDO::FETCH_ASSOC);

    $daysOrder = [
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
        7 => 'Saturday',
        1 => 'Sunday'
    ];

    $salesByDow = [];
    foreach ($dowSales as $row) {
        $salesByDow[intval($row['dow'])] = [
            'day_name' => $row['day_name'],
            'orders'   => intval($row['orders_count']),
            'revenue'  => floatval($row['total_revenue'])
        ];
    }

    $dowBreakdown = [];
    $zeroSalesDays = [];
    foreach ($daysOrder as $dowNum => $dayName) {
        if (isset($salesByDow[$dowNum]) && $salesByDow[$dowNum]['orders'] > 0) {
            $dowBreakdown[] = [
                'dow'       => $dowNum,
                'day'       => $dayName,
                'orders'    => $salesByDow[$dowNum]['orders'],
                'revenue'   => $salesByDow[$dowNum]['revenue'],
                'has_sales' => true
            ];
        } else {
            $dowBreakdown[] = [
                'dow'       => $dowNum,
                'day'       => $dayName,
                'orders'    => 0,
                'revenue'   => 0.0,
                'has_sales' => false
            ];
            $zeroSalesDays[] = $dayName;
        }
    }

    jsonResponse([
        'success' => true,
        'today' => ['orders' => $todayOrders, 'revenue' => $todayRevenue],
        'week'  => ['orders' => $weekOrders,  'revenue' => $weekRevenue],
        'month' => ['orders' => $monthOrders, 'revenue' => $monthRevenue],
        'best_revenue_day' => $bestRevenueDay,
        'dow_breakdown'   => $dowBreakdown,
        'zero_sales_days' => $zeroSalesDays,
        'cups_sold' => $cupsSold,
        'food_sold' => $foodSold,
        'top_seller' => $topSeller ? ['name' => $topSeller['name'], 'sold' => intval($topSeller['sold'])] : null,
        'top_items' => $topItems,
        'cat_breakdown' => $catBreakdown,
        'weekly_chart' => $weeklyData,
        'hourly_chart' => $hourlyData,
        'monthly_chart' => $monthlyData,
        'item_performance' => $itemPerf,
        'avg_order' => $todayOrders > 0 ? round($todayRevenue / $todayOrders, 2) : 0,
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
    $userRole   = $_SESSION['role'] ?? 'cashier';
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');

    // Cashiers are restricted strictly to today's orders
    if ($userRole === 'cashier') {
        $dateFilter = 'today';
        $dateMode   = '';
        $dateVal    = '';
    }

    $where  = '';
    $params = [];
    $label  = '';

    if ($dateMode === 'date' && $dateVal) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            $where    = "WHERE DATE(o.created_at) = ?";
            $params[] = $dateVal;
            $label    = 'Date — ' . date('F d, Y', strtotime($dateVal));
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
            $label    = "Week {$m[2]}, {$m[1]} (" . date('M d', strtotime($start)) . ' – ' . date('M d, Y', strtotime($end)) . ')';
        }
    } elseif ($dateMode === 'month' && $dateVal) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateVal)) {
            $start    = $dateVal . "-01 00:00:00";
            $end      = date("Y-m-t 23:59:59", strtotime($start));
            $where    = "WHERE o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
            $label    = date('F Y', strtotime($start));
        }
    } elseif ($dateMode === 'year' && $dateVal) {
        $year = intval($dateVal);
        if ($year >= 2000 && $year <= 2100) {
            $where    = "WHERE YEAR(o.created_at) = ?";
            $params[] = $year;
            $label    = "Year {$year}";
        }
    }

    if (!$where) {
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
    }

    // Order totals
    $statusFilter = $where ? "AND o.status = 'Completed'" : "WHERE o.status = 'Completed'";
    $stmt1 = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders o $where $statusFilter");
    $stmt1->execute($params);
    $summary = $stmt1->fetch();

    // Payment method breakdown
    $stmt2 = $db->prepare("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total),0) as rev
        FROM orders o $where $statusFilter
        GROUP BY o.payment_method
    ");
    $stmt2->execute($params);
    $payments = $stmt2->fetchAll();

    // Top selling items
    $stmt3 = $db->prepare("
        SELECT m.name, SUM(oi.quantity) as qty, COALESCE(SUM(oi.subtotal),0) as rev
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        $where $statusFilter
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 10
    ");
    $stmt3->execute($params);
    $topItems = $stmt3->fetchAll();

        // Orders list
    $stmt4 = $db->prepare("
        SELECT o.order_number, o.total, o.payment_method, o.status,
               o.created_at, MAX(u.full_name) AS cashier,
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
        GROUP BY o.id, o.order_number, o.total, o.payment_method, o.status, o.created_at
        ORDER BY o.created_at DESC
        LIMIT 500
    ");
    $stmt4->execute($params);
    $orders = $stmt4->fetchAll();

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
        $stmt->execute([hashToken($pinCode), $_SESSION['user_id'], $approvalToken, $expiresAt]);
        purgeExpiredAdminTokens($db);

        $requester = $_SESSION['full_name'] ?? $_SESSION['username'];
        $orderListHtml = '';
        $createdCount = 0;

        foreach ($orders as $o) {
            $orderId = intval($o['id']);
            $type = ($o['type'] ?? '') === 'refund' ? 'refund_order' : 'void_order';
            $perReason = trim($o['reason'] ?? ($type === 'void_order' ? 'Order cancelled' : 'Customer refund'));
            $reason = $manager ? "{$perReason} — Requested by: {$manager}" : $perReason;
            
            $orderInfo = $db->prepare("SELECT id, order_number, status, total FROM orders WHERE id = ?");
            $orderInfo->execute([$orderId]);
            $orderData = $orderInfo->fetch();
            if (!$orderData || in_array($orderData['status'], ['Voided', 'Refunded'])) continue;

            $rawOrderNum = !empty($orderData['order_number']) ? $orderData['order_number'] : $orderId;
            $cleanOrderNum = '#' . ltrim((string)$rawOrderNum, '#');
            $typeLabel = ucwords(str_replace('_', ' ', $type));
            $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token) VALUES (?,?,?,?,?,?,?)")
               ->execute([
                   $type,
                   $_SESSION['user_id'],
                   $requester,
                   "{$typeLabel} {$cleanOrderNum}",
                   "Reason: {$reason}",
                   json_encode(['order_id' => $orderId, 'order_number' => $cleanOrderNum, 'reason' => $reason, 'amount' => $orderData['total'], 'pin' => $pinCode]),
                   $approvalToken
               ]);
            $createdCount++;
                
            $color = $type === 'void_order' ? '#dc2626' : '#ea580c';
            $orderListHtml .= "
            <tr>
                <td style='padding: 6px 0; font-weight: 600; color: #aaa;'>{$cleanOrderNum}</td>
                <td style='padding: 6px 0; color: #fff;'>{$reason}</td>
                <td style='padding: 6px 0; font-weight: bold; color: {$color};'>₱" . number_format($orderData['total'], 2) . "</td>
            </tr>";
        }

        if ($createdCount === 0) {
            $db->prepare("DELETE FROM admin_tokens WHERE token = ? OR token = ?")->execute([hashToken($pinCode), $pinCode]);
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
                    <th style='border-bottom: 1px solid #444; padding-bottom: 4px; color:#888;'>Order #</th>
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

        if (!empty($recipients)) {
            sendEmail(array_values($recipients), "$storeName - Void/Refund Authorization Token ($pinCode)", $actionBody);
        }
        jsonResponse($resp);

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
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE (token = ? OR token = ?) AND is_used = 0 AND expires_at > ?");
    $stmt->execute([hashToken($token), $token, $now]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired PIN code.'], 400);
    }

    $approvalToken = $tokenRow['purpose']; // We stored the approval_token in purpose column

    // Mark token as used
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $tokenRow['id']]);
    purgeExpiredAdminTokens($db);

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

// =====================================================
// CHART & ANALYTICS COMPARISON (VS) ENDPOINTS (POS/SHARED)
// =====================================================

function handleChartWeeklyWeeks() {
    requireLogin();
    $db = getDB();

    $curDow = (int)date('w');
    $daysFromMon = ($curDow + 6) % 7;
    $currentMonday = date('Y-m-d', strtotime("-{$daysFromMon} days"));

    $rows = $db->query("
        SELECT 
            DATE(DATE_SUB(created_at, INTERVAL (DAYOFWEEK(created_at) + 5) % 7 DAY)) as monday,
            COUNT(*) as order_count,
            COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE status = 'Completed'
        GROUP BY monday
        ORDER BY monday DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weeks = [];
    $seenCurrent = false;

    foreach ($rows as $r) {
        $mon = $r['monday'];
        if ($mon === $currentMonday) $seenCurrent = true;
        $sun = date('Y-m-d', strtotime($mon . ' +6 days'));
        $label = date('M j', strtotime($mon)) . ' – ' . date('M j, Y', strtotime($sun));
        if ($mon === $currentMonday) {
            $label .= ' (Current Week)';
        }
        $weeks[] = [
            'week_start'  => $mon,
            'week_end'    => $sun,
            'label'       => $label,
            'order_count' => (int)$r['order_count'],
            'revenue'     => (float)$r['revenue'],
            'is_current'  => ($mon === $currentMonday)
        ];
    }

    if (!$seenCurrent) {
        $sun = date('Y-m-d', strtotime($currentMonday . ' +6 days'));
        $label = date('M j', strtotime($currentMonday)) . ' – ' . date('M j, Y', strtotime($sun)) . ' (Current Week)';
        array_unshift($weeks, [
            'week_start'  => $currentMonday,
            'week_end'    => $sun,
            'label'       => $label,
            'order_count' => 0,
            'revenue'     => 0.0,
            'is_current'  => true
        ]);
    }

    jsonResponse([
        'success'            => true,
        'weeks'              => $weeks,
        'current_week_start' => $currentMonday
    ]);
}

function handleChartWeekly() {
    requireLogin();
    $db = getDB();

    $curDow = (int)date('w');
    $daysFromMon = ($curDow + 6) % 7;
    $defaultMon = date('Y-m-d', strtotime("-{$daysFromMon} days"));

    $weekStart = $_GET['week_start'] ?? $defaultMon;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
        $weekStart = $defaultMon;
    }

    $fetchWeek = function($mon) use ($db) {
        $sun = date('Y-m-d', strtotime($mon . ' +6 days'));
        $stmt = $db->prepare("
            SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
            FROM orders 
            WHERE created_at >= ? AND created_at <= ? AND status = 'Completed'
            GROUP BY DAYOFWEEK(created_at)
        ");
        $stmt->execute([$mon . ' 00:00:00', $sun . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [0, 0, 0, 0, 0, 0, 0];
        $total = 0.0;
        foreach ($rows as $r) {
            $idx = ($r['dow'] + 5) % 7;
            $val = floatval($r['rev']);
            $data[$idx] = $val;
            $total += $val;
        }

        $label = date('M j', strtotime($mon)) . ' – ' . date('M j, Y', strtotime($sun));
        return [
            'week_start' => $mon,
            'week_end'   => $sun,
            'label'      => $label,
            'data'       => $data,
            'total'      => round($total, 2)
        ];
    };

    $primary = $fetchWeek($weekStart);

    $vsWeekStart = $_GET['vs_week_start'] ?? '';
    $vsData = null;
    if ($vsWeekStart && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vsWeekStart)) {
        $vsData = $fetchWeek($vsWeekStart);
    }

    jsonResponse([
        'success' => true,
        'labels'  => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        'primary' => $primary,
        'vs'      => $vsData
    ]);
}

function handleChartHourly() {
    requireLogin();
    $db = getDB();
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $fetchDay = function($targetDate) use ($db) {
        $stmt = $db->prepare("
            SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
            FROM orders 
            WHERE created_at >= ? AND created_at < ? AND status='Completed'
            GROUP BY HOUR(created_at)
        ");
        $start = $targetDate . ' 09:00:00';
        $end   = date('Y-m-d', strtotime($targetDate . ' +1 day')) . ' 03:00:00';
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_fill(0, 18, 0);
        $total = 0.0;
        foreach ($rows as $r) {
            $h = intval($r['hr']);
            $rev = floatval($r['rev']);
            $total += $rev;
            if ($h >= 9) {
                $data[$h - 9] = $rev;
            } elseif ($h <= 2) {
                $data[$h + 15] = $rev;
            }
        }
        $label = date('M j, Y', strtotime($targetDate));
        return [
            'date'  => $targetDate,
            'label' => $label,
            'data'  => $data,
            'total' => round($total, 2)
        ];
    };

    $primary = $fetchDay($date);
    $vsDate  = $_GET['vs_date'] ?? '';
    $vsData  = null;
    if ($vsDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vsDate)) {
        $vsData = $fetchDay($vsDate);
    }

    $hourLabels = ['9am','10am','11am','12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm','12am','1am','2am'];

    jsonResponse([
        'success' => true,
        'labels'  => $hourLabels,
        'primary' => $primary,
        'vs'      => $vsData
    ]);
}

function handleChartMonthly() {
    requireLogin();
    $db = getDB();

    $year = intval($_GET['year'] ?? date('Y'));
    if ($year < 2000 || $year > 2100) $year = (int)date('Y');

    $fetchYear = function($targetYear) use ($db) {
        $stmt = $db->prepare("
            SELECT MONTH(created_at) as mo, COALESCE(SUM(total),0) as rev
            FROM orders 
            WHERE YEAR(created_at) = ? AND status='Completed'
            GROUP BY MONTH(created_at)
        ");
        $stmt->execute([$targetYear]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_fill(0, 12, 0);
        $total = 0.0;
        foreach ($rows as $r) {
            $val = floatval($r['rev']);
            $data[intval($r['mo']) - 1] = $val;
            $total += $val;
        }

        return [
            'year'  => $targetYear,
            'label' => "Year $targetYear",
            'data'  => $data,
            'total' => round($total, 2)
        ];
    };

    $primary = $fetchYear($year);
    $vsYear  = isset($_GET['vs_year']) && $_GET['vs_year'] !== '' ? intval($_GET['vs_year']) : null;
    $vsData  = null;
    if ($vsYear && $vsYear >= 2000 && $vsYear <= 2100) {
        $vsData = $fetchYear($vsYear);
    }

    $years = $db->query("
        SELECT DISTINCT YEAR(created_at) as yr 
        FROM orders 
        WHERE status = 'Completed' 
        ORDER BY yr DESC
    ")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array((string)$year, array_map('strval', $years))) {
        array_unshift($years, (int)$year);
    }

    jsonResponse([
        'success' => true,
        'labels'  => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        'primary' => $primary,
        'vs'      => $vsData,
        'years'   => array_values(array_unique(array_map('intval', $years)))
    ]);
}


function handleStationOrders() {
    requireLogin();
    $db = getDB();

    $station = trim($_GET['station'] ?? '');
    if (!$station) {
        $station = $_SESSION['assigned_station'] ?? 'both';
    }
    $sinceId = intval($_GET['since_id'] ?? 0);
    $limit = min(50, max(1, intval($_GET['limit'] ?? 25)));

    $pizzaCatIds = [10, 11, 12, 13, 14, 15, 16];
    $placeholders = implode(',', array_fill(0, count($pizzaCatIds), '?'));

    $where = "WHERE o.status != 'Voided'";
    $params = [];

    if ($sinceId > 0) {
        $where .= " AND o.id > ?";
        $params[] = $sinceId;
    } else {
        $where .= " AND (DATE(o.created_at) = CURDATE() OR o.created_at >= (NOW() - INTERVAL 12 HOUR))";
    }

    if ($station === 'pizza') {
        // Pizza station: wants orders containing pizza items from cafe counter
        $where .= " AND m.category_id IN ($placeholders) AND (o.origin_branch IS NULL OR o.origin_branch != 'pizza')";
        $params = array_merge($params, $pizzaCatIds);
    } elseif ($station === 'cafe') {
        // Cafe station: wants orders containing drinks/cafe items from pizza counter
        $where .= " AND m.category_id NOT IN ($placeholders) AND o.origin_branch = 'pizza'";
        $params = array_merge($params, $pizzaCatIds);
    } else {
        // Both: either pizza items from cafe counter, OR cafe items from pizza counter
        $where .= " AND ((m.category_id IN ($placeholders) AND (o.origin_branch IS NULL OR o.origin_branch != 'pizza')) OR (m.category_id NOT IN ($placeholders) AND o.origin_branch = 'pizza'))";
        $params = array_merge($params, $pizzaCatIds, $pizzaCatIds);
    }

    $sql = "
        SELECT DISTINCT o.id, o.order_number, o.total, o.payment_method, o.status,
               o.created_at, COALESCE(o.origin_branch, 'cafe') AS origin_branch,
               COALESCE(u.full_name, u.username, 'Staff') AS cashier
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN menu_items m ON oi.menu_item_id = m.id
        LEFT JOIN users u ON o.user_id = u.id
        $where
        ORDER BY o.id " . ($sinceId > 0 ? "ASC" : "DESC") . "
        LIMIT $limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    $maxId = $sinceId;

    foreach ($orders as $ord) {
        $orderId = intval($ord['id']);
        if ($orderId > $maxId) {
            $maxId = $orderId;
        }

        $itemsStmt = $db->prepare("
            SELECT oi.id, oi.menu_item_id, oi.quantity, oi.unit_price, oi.subtotal, oi.size,
                   m.name, m.category_id, c.name AS category_name
            FROM order_items oi
            JOIN menu_items m ON oi.menu_item_id = m.id
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $itemsStmt->execute([$orderId]);
        $allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $pizzaItems = [];
        $cafeItems = [];
        $pizzaSummaryArr = [];
        $cafeSummaryArr = [];
        $allSummaryArr = [];

        foreach ($allItems as $it) {
            $isPizza = in_array((int)$it['category_id'], $pizzaCatIds, true);
            $sizeLabel = !empty($it['size']) ? " ({$it['size']})" : '';
            $summaryText = ($it['quantity'] > 1 ? $it['quantity'] . '× ' : '') . $it['name'] . $sizeLabel;

            $itemData = [
                'name' => $it['name'],
                'category_id' => (int)$it['category_id'],
                'category' => $it['category_name'] ?? '',
                'size' => $it['size'] ?: '',
                'qty' => (int)$it['quantity'],
                'price' => (float)$it['unit_price'],
                'subtotal' => (float)$it['subtotal'],
                'is_pizza' => $isPizza
            ];

            $allSummaryArr[] = $summaryText;
            if ($isPizza) {
                $pizzaItems[] = $itemData;
                $pizzaSummaryArr[] = $summaryText;
            } else {
                $cafeItems[] = $itemData;
                $cafeSummaryArr[] = $summaryText;
            }
        }

        $ord['items_summary'] = implode(', ', $allSummaryArr);
        $ord['pizza_summary'] = implode(', ', $pizzaSummaryArr);
        $ord['cafe_summary']  = implode(', ', $cafeSummaryArr);
        $ord['pizza_items'] = $pizzaItems;
        $ord['cafe_items']  = $cafeItems;
        $ord['all_items']   = array_merge($pizzaItems, $cafeItems);
        $ord['pizza_count'] = array_sum(array_column($pizzaItems, 'qty'));
        $ord['cafe_count']  = array_sum(array_column($cafeItems, 'qty'));
        $ord['time'] = date('g:i A', strtotime($ord['created_at']));
        $ord['date'] = date('M d, Y', strtotime($ord['created_at']));
        $ord['is_from_cafe']  = ($ord['origin_branch'] !== 'pizza');
        $ord['is_from_pizza'] = ($ord['origin_branch'] === 'pizza');

        // Determine destination station:
        if ($ord['is_from_cafe'] && !empty($pizzaItems)) {
            $ord['target_station'] = 'pizza';
            $ord['notice_title']   = '🍕 NEW PIZZA ORDER FROM CAFE SIDE';
            $ord['action_items']   = $pizzaItems;
            $ord['action_summary'] = $ord['pizza_summary'];
            $ord['other_items']    = $cafeItems;
        } elseif ($ord['is_from_pizza'] && !empty($cafeItems)) {
            $ord['target_station'] = 'cafe';
            $ord['notice_title']   = '☕ NEW CAFE/DRINK ORDER FROM PIZZA SIDE';
            $ord['action_items']   = $cafeItems;
            $ord['action_summary'] = $ord['cafe_summary'];
            $ord['other_items']    = $pizzaItems;
        } else {
            $ord['target_station'] = 'both';
            $ord['notice_title']   = '🔔 NEW CROSS-STATION ORDER';
            $ord['action_items']   = $ord['all_items'];
            $ord['action_summary'] = $ord['items_summary'];
            $ord['other_items']    = [];
        }

        $result[] = $ord;
    }

    $maxSysStmt = $db->query("SELECT MAX(id) FROM orders");
    $latestSystemId = (int)($maxSysStmt->fetchColumn() ?: 0);

    jsonResponse([
        'success' => true,
        'station' => $station,
        'orders' => $result,
        'count' => count($result),
        'max_id' => max($maxId, $latestSystemId),
        'latest_system_id' => $latestSystemId
    ]);
}

function handlePizzaOrders() {
    handleStationOrders();
}
