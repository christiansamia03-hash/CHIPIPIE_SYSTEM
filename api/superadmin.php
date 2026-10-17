<?php
/**
 * ChipiPie Cafe — Super Admin API
 *
 * Endpoints:
 *   GET  ?action=dashboard          — System overview stats
 *   GET  ?action=requests           — All pending system requests
 *   POST ?action=approve_request    — Approve a system request
 *   POST ?action=reject_request     — Reject a system request
 *   GET  ?action=users              — All users (all roles)
 *   POST ?action=create_user        — Create any user (including admin)
 *   POST ?action=update_user        — Update user info/role
 *   POST ?action=toggle_user        — Activate/deactivate user
 *   POST ?action=reset_password     — Force-reset a user's password
 *   GET  ?action=orders             — All orders with filters
 *   GET  ?action=logs               — Audit log
 *   GET  ?action=settings           — System settings
 *   POST ?action=save_settings      — Update system settings
 *   POST ?action=submit_request     — Admin submits a request for approval
 */

require_once __DIR__ . '/db_config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':      handleSADashboard(); break;
    case 'requests':       handleSARequests(); break;
    case 'approve_request': handleSAApproveRequest(); break;
    case 'reject_request':  handleSARejectRequest(); break;
    case 'users':          handleSAUsers(); break;
    case 'create_user':    handleSACreateUser(); break;
    case 'update_user':    handleSAUpdateUser(); break;
    case 'toggle_user':    handleSAToggleUser(); break;
    case 'reset_password': handleSAResetPassword(); break;
    case 'delete_user':    handleSADeleteUser(); break;
    case 'delete_admin_request': handleSADeleteAdminRequest(); break;
    case 'orders':         handleSAOrders(); break;
    case 'logs':           handleSALogs(); break;
    case 'settings':       handleSASettings(); break;
    case 'save_settings':  handleSASaveSettings(); break;
    case 'submit_request': handleSubmitRequest(); break;
    case 'forgot_info':        handleSAForgotInfo(); break;
    case 'forgot_password':     handleSAForgotPassword(); break;
    case 'reset_password_link': handleSAResetPasswordLink(); break;
    case 'rate_limits':         handleSAGetRateLimits(); break;
    case 'reset_rate_limits':   handleSAResetRateLimits(); break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}

// Function removed because it is now in db_config.php

// =====================================================
// AUDIT LOG HELPER
// =====================================================
function logAction($action, $targetType = null, $targetId = null, $details = null) {
    ensureSession();
    try {
        $db = getDB();
        $actorId       = $_SESSION['user_id'] ?? null;
        $actorUsername = $_SESSION['username'] ?? null;
        $ip            = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$actorId, $actorUsername, $action, $targetType, $targetId, $details, $ip]);
    } catch (Exception $e) { /* silent */ }
}

// =====================================================
// FORGOT PASSWORD — Super Admin (No Email Input)
// =====================================================

function maskEmail($email) {
    if (!$email || strpos($email, '@') === false) return '***@***.***';
    [$local, $domain] = explode('@', $email, 2);
    $visible = min(3, strlen($local));
    return substr($local, 0, $visible) . str_repeat('*', max(0, strlen($local) - $visible)) . '@' . $domain;
}

/** GET ?action=forgot_info — Returns masked superadmin email (no auth required) */
function handleSAForgotInfo() {
    $email = getSetting('superadmin_email');
    if (!$email) {
        // Fallback: look up super_admin user
        try {
            $db = getDB();
            $email = $db->query("SELECT email FROM users WHERE role='super_admin' AND email!='' LIMIT 1")->fetchColumn();
        } catch (Exception $e) { $email = ''; }
    }
    jsonResponse([
        'success'      => true,
        'masked_email' => maskEmail($email ?: '')
    ]);
}

/** POST ?action=forgot_password — Sends a secure reset LINK to the superadmin email */
function handleSAForgotPassword() {
    $db     = getDB();
    // Determine destination: prefer settings superadmin_email, else super_admin user email
    $email  = getSetting('superadmin_email');
    $saUser = $db->query("SELECT id, email FROM users WHERE role='super_admin' LIMIT 1")->fetch();
    if (!$email && $saUser) $email = $saUser['email'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'No valid Super Admin email is configured in settings.'], 400);
    }

    // Generate a secure random token (not a 6-digit code)
    $token     = bin2hex(random_bytes(32)); // 64-char hex
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $userId    = $saUser['id'] ?? null;

    $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?,?,?,?)")
       ->execute([$token, $userId, 'sa_reset_link', $expiresAt]);

    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $host      = ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $resetUrl  = "http://{$host}/CHIPIPIE_SYSTEM/superadmin-panel.php?reset_token={$token}";

    $body = "
    <div style='font-family:Segoe UI,sans-serif;max-width:480px;margin:0 auto;padding:30px;background:#1a1514;color:#f4f4f4;border-radius:16px;border:1px solid #332d2c;'>
      <div style='text-align:center;margin-bottom:24px;'>
        <div style='display:inline-block;width:56px;height:56px;background:#6d28d9;color:#fff;border-radius:14px;line-height:56px;font-size:26px;box-shadow:0 6px 20px rgba(109,40,217,0.4);'>&#128272;</div>
        <h2 style='color:#fff;margin:12px 0 4px;'>$storeName</h2>
        <p style='color:#aaa;font-size:13px;margin:0;'>Super Admin Password Reset</p>
      </div>
      <div style='background:#2a2423;border-radius:12px;padding:24px;border:1px solid #3d3533;text-align:center;'>
        <p style='color:#ddd;font-size:14px;margin:0 0 20px;line-height:1.6;'>Click the button below to set a new password for your Super Admin account. This link expires in <strong style='color:#f59e0b;'>30 minutes</strong>.</p>
        <a href='$resetUrl' style='display:inline-block;background:linear-gradient(135deg,#6d28d9,#a855f7);color:#fff;padding:14px 28px;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.02em;'>&#128274; Reset My Password</a>
        <p style='font-size:11px;color:#666;margin-top:16px;'>If you did not request this, you can safely ignore this email.</p>
      </div>
    </div>";

    sendEmail($email, "$storeName — Super Admin Password Reset Link", $body);

    jsonResponse(['success' => true, 'message' => 'A password reset link has been sent to your email.']);
}

/** POST ?action=reset_password_link — Validates the link token and resets the SA password */
function handleSAResetPasswordLink() {
    $input   = getJsonInput();
    $token   = trim($input['token']        ?? '');
    $newPass = trim($input['new_password'] ?? '');

    if (!$token || !$newPass) {
        jsonResponse(['success' => false, 'message' => 'Token and new password are required.'], 400);
    }

    $db  = getDB();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND purpose = 'sa_reset_link' AND is_used = 0 AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['success' => false, 'message' => 'This reset link is invalid or has expired. Please request a new one.'], 400);
    }

    // Validate password complexity
    if (strlen($newPass) < 8 || !preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and a number.'], 400);
    }

    // Mark token used
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $row['id']]);

    // Update super_admin password
    $hash = password_hash($newPass, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password = ? WHERE role = 'super_admin'")->execute([$hash]);

    jsonResponse(['success' => true, 'message' => 'Password reset successfully. You can now sign in.']);
}

// =====================================================
// DASHBOARD — System Overview
// =====================================================
function handleSADashboard() {
    requireSuperAdmin();
    $db = getDB();

    $totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalAdmins   = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    $totalCashiers = $db->query("SELECT COUNT(*) FROM users WHERE role='cashier'")->fetchColumn();
    $activeUsers   = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();

    $totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $todayOrders   = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $totalRevenue  = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='Completed'")->fetchColumn();
    $todayRevenue  = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='Completed' AND DATE(created_at)=CURDATE()")->fetchColumn();

    $pendingRequests    = $db->query("SELECT COUNT(*) FROM system_requests WHERE status='pending'")->fetchColumn();
    $pendingAdminReqs   = $db->query("SELECT COUNT(*) FROM admin_registration_requests WHERE status='pending'")->fetchColumn();
    $totalMenuItems     = $db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    $lowStockItems      = $db->query("SELECT COUNT(*) FROM menu_items WHERE stock <= min_stock AND stock > 0")->fetchColumn();
    $outOfStockItems    = $db->query("SELECT COUNT(*) FROM menu_items WHERE stock = 0")->fetchColumn();

    // Recent logs
    $logsStmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10");
    $recentLogs = $logsStmt->fetchAll();

    // Weekly revenue chart (Mon-Sun)
    $weeklyChart = $db->query("
        SELECT DAYOFWEEK(created_at) as dow, COALESCE(SUM(total),0) as rev
        FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'
        GROUP BY DAYOFWEEK(created_at)
    ")->fetchAll();
    $weeklyData = [0,0,0,0,0,0,0];
    foreach ($weeklyChart as $r) {
        $idx = ($r['dow'] + 5) % 7; // Mon=0
        $weeklyData[$idx] = floatval($r['rev']);
    }

    // Hourly chart (9am to 2am shift)
    $hourlyChart = $db->query("
        SELECT HOUR(created_at) as hr, COALESCE(SUM(total),0) as rev
        FROM orders 
        WHERE created_at >= CONCAT(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), ' 09:00:00') 
          AND created_at < CONCAT(DATE_ADD(DATE(DATE_SUB(NOW(), INTERVAL 3 HOUR)), INTERVAL 1 DAY), ' 03:00:00')
          AND status='Completed'
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

    // Monthly chart (this year)
    $monthlyChart = $db->query("
        SELECT MONTH(created_at) as mo, COALESCE(SUM(total),0) as rev
        FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND status='Completed'
        GROUP BY MONTH(created_at)
    ")->fetchAll();
    $monthlyData = array_fill(0, 12, 0);
    foreach ($monthlyChart as $r) {
        $monthlyData[intval($r['mo']) - 1] = floatval($r['rev']);
    }

    // Item sales performance (top 9 all time)
    $itemPerf = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status='Completed'
        GROUP BY m.id ORDER BY sold DESC LIMIT 9
    ")->fetchAll();

    // Category breakdown (last 30 days)
    $catBreakdown = $db->query("
        SELECT c.name, SUM(oi.quantity) as qty
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN categories c ON m.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status='Completed'
        GROUP BY c.id ORDER BY qty DESC
    ")->fetchAll();

    // Top seller (all time)
    $topSeller = $db->query("
        SELECT m.name, SUM(oi.quantity) as sold
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status='Completed'
        GROUP BY m.id ORDER BY sold DESC LIMIT 1
    ")->fetch();

    // Monthly/Avg stats
    $monthStats = $db->query("
        SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev
        FROM orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status='Completed'
    ")->fetch();
    $avgDaily = $db->query("
        SELECT COALESCE(AVG(daily_rev),0) as avg FROM (
            SELECT DATE(created_at) as d, SUM(total) as daily_rev FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status='Completed'
            GROUP BY DATE(created_at)
        ) t
    ")->fetchColumn();
    $bestDay = $db->query("
        SELECT DATE(created_at) as d, SUM(total) as rev FROM orders
        WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status='Completed'
        GROUP BY DATE(created_at) ORDER BY rev DESC LIMIT 1
    ")->fetch();

    // Active security lockouts
    ensureRateLimitTable();
    $activeLockouts = $db->query("SELECT id, rate_key, action, identifier, hits, lockout_count, first_hit_at, last_hit_at, locked_until 
                                 FROM rate_limits 
                                 WHERE locked_until IS NOT NULL AND locked_until > NOW() 
                                 ORDER BY locked_until DESC")->fetchAll();

    jsonResponse([
        'success' => true,
        'users' => [
            'total'     => (int)$totalUsers,
            'admins'    => (int)$totalAdmins,
            'cashiers'  => (int)$totalCashiers,
            'active'    => (int)$activeUsers,
        ],
        'orders' => [
            'total'         => (int)$totalOrders,
            'today'         => (int)$todayOrders,
            'total_revenue' => (float)$totalRevenue,
            'today_revenue' => (float)$todayRevenue,
        ],
        'requests' => [
            'pending'        => (int)$pendingRequests,
            'pending_admins' => (int)$pendingAdminReqs,
        ],
        'menu' => [
            'total'       => (int)$totalMenuItems,
            'low_stock'   => (int)$lowStockItems,
            'out_of_stock'=> (int)$outOfStockItems,
        ],
        'month_revenue'  => (float)$monthStats['rev'],
        'month_orders'   => (int)$monthStats['cnt'],
        'avg_daily'      => round((float)$avgDaily, 2),
        'best_day'       => $bestDay ? ['date' => $bestDay['d'], 'rev' => (float)$bestDay['rev']] : null,
        'top_seller'     => $topSeller ? ['name' => $topSeller['name'], 'sold' => (int)$topSeller['sold']] : null,
        'recent_logs'    => $recentLogs,
        'security_alerts'=> $activeLockouts,
        'weekly_chart'   => $weeklyData,
        'hourly_chart'   => $hourlyData,
        'monthly_chart'  => $monthlyData,
        'item_performance'=> $itemPerf,
        'cat_breakdown'  => $catBreakdown,
    ]);
}

// =====================================================
// SYSTEM REQUESTS
// =====================================================
function handleSARequests() {
    requireSuperAdmin();
    $db = getDB();

    $status = $_GET['status'] ?? 'all';
    $type   = $_GET['type']   ?? 'all';

    $where  = [];
    $params = [];

    if ($status !== 'all') { $where[] = 'r.status = ?'; $params[] = $status; }
    if ($type   !== 'all') { $where[] = 'r.type = ?';   $params[] = $type; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT r.*,
               u.full_name  AS reviewer_name
        FROM system_requests r
        LEFT JOIN users u ON u.id = r.reviewed_by
        $whereClause
        ORDER BY r.created_at ASC
        LIMIT 200
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Clean up any approved admin requests where the admin was deleted from users
    $db->exec("DELETE FROM admin_registration_requests WHERE status='approved' AND username NOT IN (SELECT username FROM users)");

    // Also get pending admin registration requests
    $adminReqs = $db->query("
        SELECT id, username, full_name, email, status, created_at, approved_at
        FROM admin_registration_requests
        ORDER BY created_at DESC
        LIMIT 100
    ")->fetchAll();

    jsonResponse(['success' => true, 'requests' => $requests, 'admin_requests' => $adminReqs]);
}

function handleSAApproveRequest() {
    requireSuperAdmin();
    $input     = getJsonInput();
    $id        = intval($input['id'] ?? 0);
    $type      = trim($input['type'] ?? 'system');  // 'system' | 'admin_reg'
    $note      = trim($input['note'] ?? '');

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Request ID required.'], 400);
    }

    $db = getDB();

    if ($type === 'admin_reg') {
        // Approve an admin registration request
        $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE id=? AND status='pending'");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            jsonResponse(['success' => false, 'message' => 'Request not found or already processed.'], 404);
        }

        // Check username not taken
        $taken = $db->prepare("SELECT id FROM users WHERE username=?");
        $taken->execute([$req['username']]);
        if ($taken->fetch()) {
            $db->prepare("UPDATE admin_registration_requests SET status='rejected' WHERE id=?")->execute([$id]);
            jsonResponse(['success' => false, 'message' => 'Username already taken — request auto-rejected.'], 409);
        }

        $db->prepare("INSERT INTO users (username,password,first_name,last_name,middle_initial,suffix,full_name,email,role,is_active) VALUES (?,?,?,?,?,?,?,?,'admin',1)")
           ->execute([
               $req['username'],
               $req['password'],
               $req['first_name'] ?? null,
               $req['last_name'] ?? null,
               $req['middle_initial'] ?? null,
               $req['suffix'] ?? null,
               $req['full_name'],
               $req['email']
           ]);
        $db->prepare("UPDATE admin_registration_requests SET status='approved', approved_at=NOW() WHERE id=?")
           ->execute([$id]);

        logAction('approve_admin_registration', 'admin_registration_request', $id, "Approved: {$req['full_name']} ({$req['username']})");

        // Send confirmation email
        $storeName   = getSetting('store_name') ?: 'ChipiPie Cafe';
        $confirmBody = "<p>🎉 <strong>{$req['full_name']}</strong>, your admin account has been approved by the Super Administrator! You can now log in.</p>";
        sendEmail($req['email'], "$storeName — Admin Account Approved", $confirmBody);

        jsonResponse(['success' => true, 'message' => "Admin '{$req['full_name']}' approved and account created!"]);
    } else {
        // Approve a system_requests entry
        $stmt = $db->prepare("SELECT * FROM system_requests WHERE id=? AND status='pending'");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            jsonResponse(['success' => false, 'message' => 'Request not found or already processed.'], 404);
        }

        $now = date('Y-m-d H:i:s');
        $saNote = $note ?: 'Approved by Super Admin';
        $db->prepare("UPDATE system_requests SET status='approved', reviewed_by=?, reviewed_at=?, review_note=? WHERE id=?")
           ->execute([$_SESSION['user_id'], $now, $saNote, $id]);

        // If this request is part of a batch (shared approval_token), approve all siblings too
        if (!empty($req['approval_token'])) {
            $db->prepare("UPDATE system_requests SET status='approved', reviewed_by=?, reviewed_at=?, review_note=? WHERE approval_token=? AND status='pending' AND id != ?")
               ->execute([$_SESSION['user_id'], $now, $saNote, $req['approval_token'], $id]);
        }

        // Execute the payload action
        $payload = $req['payload'] ? json_decode($req['payload'], true) : [];
        $message = "Request approved: {$req['title']}";

        switch ($req['type']) {
            case 'void_order':
                $orderId = $payload['order_id'] ?? 0;
                if ($orderId) {
                    try {
                        $db->beginTransaction();
                        $db->prepare("UPDATE orders SET status='Voided' WHERE id=?")->execute([$orderId]);
                        $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id=?");
                        $items->execute([$orderId]);
                        foreach ($items->fetchAll() as $item) {
                            $db->prepare("UPDATE menu_items SET stock=stock+?, is_available=1 WHERE id=?")
                               ->execute([$item['quantity'], $item['menu_item_id']]);
                        }
                        $db->prepare("INSERT INTO order_adjustments (order_id,type,amount,reason,processed_by) VALUES (?,'void',?,?,?)")
                           ->execute([$orderId, $payload['amount'] ?? 0, $payload['reason'] ?? '', $_SESSION['user_id']]);
                        $db->commit();
                        $message = "Order #{$orderId} voided and stock restored.";
                    } catch (Exception $e) {
                        $db->rollBack();
                    }
                }
                break;

            case 'refund_order':
                $orderId = $payload['order_id'] ?? 0;
                if ($orderId) {
                    try {
                        $db->beginTransaction();
                        $db->prepare("UPDATE orders SET status='Refunded' WHERE id=?")->execute([$orderId]);
                        $items = $db->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id=?");
                        $items->execute([$orderId]);
                        foreach ($items->fetchAll() as $item) {
                            $db->prepare("UPDATE menu_items SET stock=stock+?, is_available=1 WHERE id=?")
                               ->execute([$item['quantity'], $item['menu_item_id']]);
                        }
                        $db->prepare("INSERT INTO order_adjustments (order_id,type,amount,reason,processed_by) VALUES (?,'refund',?,?,?)")
                           ->execute([$orderId, $payload['amount'] ?? 0, $payload['reason'] ?? '', $_SESSION['user_id']]);
                        $db->commit();
                        $message = "Order #{$orderId} refunded and stock restored.";
                    } catch (Exception $e) {
                        $db->rollBack();
                    }
                }
                break;

            case 'user_deactivate':
                $userId = $payload['user_id'] ?? 0;
                if ($userId) {
                    $db->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$userId]);
                    $message = "User deactivated.";
                }
                break;

            case 'user_activate':
                $userId = $payload['user_id'] ?? 0;
                if ($userId) {
                    $db->prepare("UPDATE users SET is_active=1 WHERE id=?")->execute([$userId]);
                    $message = "User activated.";
                }
                break;

            case 'user_delete':
                $userId = $payload['user_id'] ?? 0;
                if ($userId) {
                    $uStmt = $db->prepare("SELECT username, email, full_name FROM users WHERE id=?");
                    $uStmt->execute([$userId]);
                    $targetUser = $uStmt->fetch();
                    if ($targetUser) {
                        // Remove from admin approvals (admin_registration_requests)
                        $db->prepare("DELETE FROM admin_registration_requests WHERE username=? OR email=?")
                           ->execute([$targetUser['username'], $targetUser['email']]);
                    }
                    // Clean up any remaining system_requests for this user
                    $db->prepare("DELETE FROM system_requests WHERE id != ? AND (payload LIKE ? OR payload LIKE ?)")
                       ->execute([$id, '%"user_id":' . $userId . '%', '%"user_id":"' . $userId . '"%']);

                    $db->prepare("DELETE FROM users WHERE id=? AND role != 'super_admin'")->execute([$userId]);
                    $message = "User account deleted.";
                }
                break;

            case 'menu_change':
                $act = $payload['action'] ?? ($payload['item_id'] ? 'delete' : 'add');
                if ($act === 'add') {
                    $name = trim($payload['name'] ?? '');
                    $categoryId = intval($payload['category_id'] ?? 1);
                    $price = floatval($payload['price'] ?? 0);
                    $image = $payload['image'] ?? null;
                    $stock = intval($payload['stock'] ?? 0);
                    $minStock = intval($payload['min_stock'] ?? 5);
                    $sizePrices = $payload['size_prices'] ?? null;
                    $sizePricesJson = null;
                    if ($sizePrices && is_array($sizePrices)) {
                        $sizePricesJson = json_encode($sizePrices);
                    } elseif ($sizePrices && is_string($sizePrices)) {
                        $sizePricesJson = $sizePrices;
                    }

                    if ($name && $categoryId && $price > 0) {
                        $stmt = $db->prepare("INSERT INTO menu_items (name, category_id, price, size_prices, image, stock, min_stock, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $categoryId, $price, $sizePricesJson, $image, $stock, $minStock, $stock > 0 ? 1 : 0]);
                        $message = "Menu item '{$name}' approved and added to menu.";
                    } else {
                        $message = "Failed to add menu item: invalid item data.";
                    }
                } elseif ($act === 'delete') {
                    $itemId = intval($payload['item_id'] ?? $payload['id'] ?? 0);
                    if ($itemId) {
                        $stmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
                        $stmt->execute([$itemId]);
                        $itemRow = $stmt->fetch();
                        $itemName = $itemRow ? $itemRow['name'] : "#$itemId";

                        $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$itemId]);
                        $message = "Menu item '{$itemName}' approved and removed from menu.";
                    } else {
                        $message = "Failed to remove menu item: invalid item ID.";
                    }
                }
                break;
        }

        logAction('approve_system_request', 'system_request', $id, $message);
        jsonResponse(['success' => true, 'message' => $message]);
    }
}

function handleSARejectRequest() {
    requireSuperAdmin();
    $input = getJsonInput();
    $id    = intval($input['id'] ?? 0);
    $type  = trim($input['type'] ?? 'system');
    $note  = trim($input['note'] ?? '');

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Request ID required.'], 400);
    }

    $db = getDB();

    if ($type === 'admin_reg') {
        $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE id=? AND status='pending'");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) jsonResponse(['success' => false, 'message' => 'Not found.'], 404);

        $db->prepare("UPDATE admin_registration_requests SET status='rejected' WHERE id=?")->execute([$id]);
        logAction('reject_admin_registration', 'admin_registration_request', $id, "Rejected: {$req['full_name']}");
        jsonResponse(['success' => true, 'message' => "Admin request from '{$req['full_name']}' rejected."]);
    } else {
        $stmt = $db->prepare("SELECT * FROM system_requests WHERE id=? AND status='pending'");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) jsonResponse(['success' => false, 'message' => 'Not found.'], 404);

        $now = date('Y-m-d H:i:s');
        $db->prepare("UPDATE system_requests SET status='rejected', reviewed_by=?, reviewed_at=?, review_note=? WHERE id=?")
           ->execute([$_SESSION['user_id'], $now, $note, $id]);

        logAction('reject_system_request', 'system_request', $id, "Rejected: {$req['title']}");
        jsonResponse(['success' => true, 'message' => "Request '{$req['title']}' rejected."]);
    }
}

// =====================================================
// USERS MANAGEMENT
// =====================================================
function handleSAUsers() {
    requireSuperAdmin();
    $db = getDB();

    $users = $db->query("
        SELECT id, username, full_name, email, role, is_active, created_at, updated_at
        FROM users
        ORDER BY role ASC, created_at DESC
    ")->fetchAll();

    jsonResponse(['success' => true, 'users' => $users]);
}

function handleSACreateUser() {
    requireSuperAdmin();
    $input     = getJsonInput();
    $username  = trim($input['username']  ?? '');
    $password  = trim($input['password']  ?? '');
    $full_name = trim($input['full_name'] ?? '');
    $email     = trim($input['email']     ?? '');
    $role      = trim($input['role']      ?? 'cashier');

    $first_name     = trim($input['first_name'] ?? '');
    $last_name      = trim($input['last_name'] ?? '');
    $middle_initial = trim($input['middle_initial'] ?? '');
    $suffix         = trim($input['suffix'] ?? '');

    if (!$full_name && ($first_name || $last_name)) {
        $parts = array_filter([$first_name, $middle_initial ? $middle_initial . '.' : '', $last_name, $suffix]);
        $full_name = implode(' ', $parts);
    }

    if (!$username || !$password || !$full_name || !$email) {
        jsonResponse(['success' => false, 'message' => 'All fields required.'], 400);
    }
    if (!in_array($role, ['admin','cashier'])) { // SA cannot create another SA via UI
        jsonResponse(['success' => false, 'message' => 'Invalid role.'], 400);
    }
    $pwdErr = '';
    if (!validatePasswordComplexity($password, $pwdErr)) {
        jsonResponse(['success' => false, 'message' => $pwdErr], 400);
    }

    $db = getDB();
    $exists = $db->prepare("SELECT id FROM users WHERE username=?");
    $exists->execute([$username]);
    if ($exists->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Username already taken.'], 409);
    }

    // Check if email already registered
    $emailCheck = $db->prepare("SELECT id FROM users WHERE email=?");
    $emailCheck->execute([$email]);
    if ($emailCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already registered to an existing account.'], 409);
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (username,password,first_name,last_name,middle_initial,suffix,full_name,email,role,is_active) VALUES (?,?,?,?,?,?,?,?,1)")
       ->execute([$username, $hashed, $first_name ?: null, $last_name ?: null, $middle_initial ?: null, $suffix ?: null, $full_name, $email, $role]);

    $newId = $db->lastInsertId();
    logAction('create_user', 'user', $newId, "Created $role: $full_name ($username)");

    jsonResponse(['success' => true, 'message' => "User '$full_name' created as $role.", 'user_id' => $newId]);
}

function handleSAUpdateUser() {
    requireSuperAdmin();
    $input     = getJsonInput();
    $userId    = intval($input['user_id'] ?? 0);
    $full_name = trim($input['full_name'] ?? '');
    $email     = trim($input['email']     ?? '');
    $role      = trim($input['role']      ?? '');

    if (!$userId) jsonResponse(['success' => false, 'message' => 'User ID required.'], 400);

    $db = getDB();
    $user = $db->prepare("SELECT * FROM users WHERE id=?");
    $user->execute([$userId]);
    $user = $user->fetch();
    if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    if ($user['role'] === 'super_admin') jsonResponse(['success' => false, 'message' => 'Cannot modify super admin.'], 403);

    $updates = [];
    $params  = [];
    if ($full_name) { $updates[] = 'full_name=?'; $params[] = $full_name; }
    if ($email)     { $updates[] = 'email=?';     $params[] = $email; }
    if ($role && in_array($role, ['admin','cashier'])) { $updates[] = 'role=?'; $params[] = $role; }

    if (empty($updates)) jsonResponse(['success' => false, 'message' => 'Nothing to update.'], 400);

    $params[] = $userId;
    $db->prepare("UPDATE users SET " . implode(',', $updates) . " WHERE id=?")->execute($params);

    logAction('update_user', 'user', $userId, "Updated user: {$user['username']}");
    jsonResponse(['success' => true, 'message' => 'User updated successfully.']);
}

function handleSAToggleUser() {
    requireSuperAdmin();
    $input  = getJsonInput();
    $userId = intval($input['user_id'] ?? 0);

    if (!$userId) jsonResponse(['success' => false, 'message' => 'User ID required.'], 400);
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Cannot deactivate your own account.'], 400);
    }

    $db = getDB();
    $user = $db->prepare("SELECT id, is_active, full_name, role FROM users WHERE id=?");
    $user->execute([$userId]);
    $user = $user->fetch();

    if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    if ($user['role'] === 'super_admin') jsonResponse(['success' => false, 'message' => 'Cannot deactivate super admin.'], 403);

    $newStatus = $user['is_active'] ? 0 : 1;
    $db->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$newStatus, $userId]);

    $statusText = $newStatus ? 'activated' : 'deactivated';
    logAction("user_{$statusText}", 'user', $userId, "{$user['full_name']} $statusText");
    jsonResponse(['success' => true, 'message' => "User '{$user['full_name']}' $statusText.", 'is_active' => $newStatus]);
}

function handleSAResetPassword() {
    requireSuperAdmin();
    $input     = getJsonInput();
    $userId    = intval($input['user_id']      ?? 0);
    $newPass   = trim($input['new_password']   ?? '');

    if (!$userId || !$newPass) {
        jsonResponse(['success' => false, 'message' => 'User ID and new password required.'], 400);
    }
    $pwdErr = '';
    if (!validatePasswordComplexity($newPass, $pwdErr)) {
        jsonResponse(['success' => false, 'message' => $pwdErr], 400);
    }

    $db = getDB();
    $user = $db->prepare("SELECT id, username, full_name, role FROM users WHERE id=?");
    $user->execute([$userId]);
    $user = $user->fetch();

    if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    if ($user['role'] === 'super_admin' && $userId != $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Cannot reset another super admin password.'], 403);
    }

    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $userId]);

    logAction('reset_password', 'user', $userId, "Password reset for: {$user['username']}");
    jsonResponse(['success' => true, 'message' => "Password reset for '{$user['full_name']}'."]);
}

function handleSADeleteUser() {
    requireSuperAdmin();
    $input  = getJsonInput();
    $userId = intval($input['user_id'] ?? 0);

    if (!$userId) jsonResponse(['success' => false, 'message' => 'User ID required.'], 400);
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete your own account.'], 400);
    }

    $db = getDB();
    $user = $db->prepare("SELECT id, username, full_name, email, role FROM users WHERE id=?");
    $user->execute([$userId]);
    $user = $user->fetch();
    if (!$user) jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    if ($user['role'] === 'super_admin') jsonResponse(['success' => false, 'message' => 'Cannot delete super admin.'], 403);

    // Delete from admin approvals (admin_registration_requests)
    $db->prepare("DELETE FROM admin_registration_requests WHERE username=? OR email=?")
       ->execute([$user['username'], $user['email']]);

    // Clean up any pending system_requests for this user
    $db->prepare("DELETE FROM system_requests WHERE payload LIKE ? OR payload LIKE ?")
       ->execute(['%"user_id":' . $userId . '%', '%"user_id":"' . $userId . '"%']);

    $db->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
    logAction('delete_user', 'user', $userId, "Deleted: {$user['full_name']} ({$user['username']})");
    jsonResponse(['success' => true, 'message' => "User '{$user['full_name']}' permanently deleted."]);
}

function handleSADeleteAdminRequest() {
    requireSuperAdmin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Request ID required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name FROM admin_registration_requests WHERE id=?");
    $stmt->execute([$id]);
    $req = $stmt->fetch();
    if (!$req) {
        jsonResponse(['success' => false, 'message' => 'Admin request not found.'], 404);
    }

    $db->prepare("DELETE FROM admin_registration_requests WHERE id=?")->execute([$id]);
    logAction('delete_admin_registration_request', 'admin_registration_request', $id, "Deleted admin request: {$req['full_name']} ({$req['username']})");
    jsonResponse(['success' => true, 'message' => "Admin request for '{$req['full_name']}' permanently removed."]);
}

// =====================================================
// ORDERS
// =====================================================
function handleSAOrders() {
    requireSuperAdmin();
    $db = getDB();

    $dateMode = trim($_GET['date_mode'] ?? '');
    $dateVal  = trim($_GET['date_val']  ?? '');
    $date     = trim($_GET['date']      ?? 'today');
    $status   = trim($_GET['status']    ?? '');
    $limit    = min(intval($_GET['limit'] ?? 100), 500);

    $where  = [];
    $params = [];

    if ($dateMode === 'date' && $dateVal) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            $where[]  = "DATE(o.created_at) = ?";
            $params[] = $dateVal;
        }
    } elseif ($dateMode === 'week' && $dateVal) {
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $dateVal, $m)) {
            $dto = new DateTime();
            $dto->setISODate(intval($m[1]), intval($m[2]));
            $start = $dto->format('Y-m-d 00:00:00');
            $dto->modify('+6 days');
            $end = $dto->format('Y-m-d 23:59:59');
            $where[]  = "o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'month' && $dateVal) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateVal)) {
            $start = $dateVal . "-01 00:00:00";
            $end   = date("Y-m-t 23:59:59", strtotime($start));
            $where[]  = "o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'year' && $dateVal) {
        $year = intval($dateVal);
        if ($year >= 2000 && $year <= 2100) {
            $where[]  = "YEAR(o.created_at) = ?";
            $params[] = $year;
        }
    } elseif ($date === 'today') {
        $where[] = 'DATE(o.created_at) = CURDATE()';
    } elseif ($date === 'week') {
        $where[] = 'YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)';
    } elseif ($date === 'month') {
        $where[] = 'MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())';
    } elseif ($date === 'year') {
        $where[] = 'YEAR(o.created_at) = YEAR(CURDATE())';
    } elseif ($date && $date !== 'all') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $where[] = 'DATE(o.created_at) = ?';
            $params[] = $date;
        }
    }

    if ($status) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT o.*, u.full_name AS cashier_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $limit
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $items = $db->prepare("
            SELECT oi.quantity, oi.unit_price, oi.subtotal, oi.size, mi.name
            FROM order_items oi
            JOIN menu_items mi ON mi.id = oi.menu_item_id
            WHERE oi.order_id = ?
        ");
        $items->execute([$order['id']]);
        $order['items'] = $items->fetchAll();
    }
    unset($order);

    jsonResponse(['success' => true, 'orders' => $orders]);
}

// =====================================================
// AUDIT LOGS
// =====================================================
function handleSALogs() {
    requireSuperAdmin();
    $db    = getDB();
    $limit = min(intval($_GET['limit'] ?? 200), 1000);

    $dateMode = trim($_GET['date_mode'] ?? 'all');
    $dateVal  = trim($_GET['date_val']  ?? '');

    // Fallback support for standard date param (e.g. today, week, month, year)
    $dateParam = trim($_GET['date'] ?? '');
    if ($dateParam && $dateMode === 'all') {
        if ($dateParam === 'today') {
            $dateMode = 'date';
            $dateVal  = date('Y-m-d');
        } elseif ($dateParam === 'week') {
            $dateMode = 'week';
            $dateVal  = date('Y-\WW');
        } elseif ($dateParam === 'month') {
            $dateMode = 'month';
            $dateVal  = date('Y-m');
        } elseif ($dateParam === 'year') {
            $dateMode = 'year';
            $dateVal  = date('Y');
        } elseif ($dateParam !== 'all') {
            $dateMode = 'date';
            $dateVal  = $dateParam;
        }
    }

    $where  = [];
    $params = [];

    if ($dateMode === 'date' && $dateVal) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            $where[]  = "DATE(created_at) = ?";
            $params[] = $dateVal;
        }
    } elseif ($dateMode === 'week' && $dateVal) {
        // Format YYYY-Www, e.g. 2026-W42
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $dateVal, $m)) {
            $year = intval($m[1]);
            $week = intval($m[2]);
            $dto  = new DateTime();
            $dto->setISODate($year, $week);
            $start = $dto->format('Y-m-d 00:00:00');
            $dto->modify('+6 days');
            $end   = $dto->format('Y-m-d 23:59:59');
            $where[]  = "created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'month' && $dateVal) {
        // Format YYYY-MM, e.g. 2026-10
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateVal)) {
            $start = $dateVal . "-01 00:00:00";
            $end   = date("Y-m-t 23:59:59", strtotime($start));
            $where[]  = "created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
        }
    } elseif ($dateMode === 'year' && $dateVal) {
        $year = intval($dateVal);
        if ($year >= 2000 && $year <= 2100) {
            $where[]  = "YEAR(created_at) = ?";
            $params[] = $year;
        }
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT * FROM system_logs 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT $limit
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    jsonResponse([
        'success'   => true, 
        'logs'      => $logs, 
        'date_mode' => $dateMode, 
        'date_val'  => $dateVal,
        'count'     => count($logs)
    ]);
}

// =====================================================
// SYSTEM SETTINGS
// =====================================================
function handleSASettings() {
    requireSuperAdmin();
    $db = getDB();
    $settings = $db->query("SELECT setting_key, setting_value, updated_at FROM system_settings")->fetchAll();
    $admins = $db->query("SELECT id, username, full_name, email, role, is_active FROM users WHERE role IN ('admin', 'super_admin') ORDER BY is_active DESC, role DESC, full_name ASC")->fetchAll();
    jsonResponse(['success' => true, 'settings' => $settings, 'admins' => $admins]);
}

function handleSASaveSettings() {
    requireSuperAdmin();
    $input = getJsonInput();
    $settings = $input['settings'] ?? [];

    if (!is_array($settings) || empty($settings)) {
        jsonResponse(['success' => false, 'message' => 'No settings provided.'], 400);
    }

    $db = getDB();
    $allowed = [
        'store_name',
        'owner_email',
        'token_expiry_minutes',
        'superadmin_email',
        'require_superadmin_approval',
        'void_refund_manager_email',
        'void_refund_admin_ids',
        'rate_limit_enabled',
        'rate_limit_login_attempts',
        'rate_limit_lockout_minutes',
        'rate_limit_api_rpm',
        'rate_limit_email_throttle'
    ];

    // Inactive accounts in POS cannot be added to receive a token
    if (isset($settings['void_refund_admin_ids'])) {
        $rawIds = array_filter(array_map('intval', explode(',', $settings['void_refund_admin_ids'])));
        if (!empty($rawIds)) {
            $inClause = implode(',', $rawIds);
            $activeIds = $db->query("SELECT id FROM users WHERE id IN ($inClause) AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
            $settings['void_refund_admin_ids'] = implode(',', $activeIds);
        } else {
            $settings['void_refund_admin_ids'] = '';
        }
    }

    if (isset($settings['void_refund_manager_email'])) {
        $inactiveEmails = $db->query("SELECT LOWER(email) FROM users WHERE is_active = 0 AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
        $inactiveSet = array_flip($inactiveEmails);
        $rawEmails = array_filter(array_map('trim', explode(',', $settings['void_refund_manager_email'])));
        $cleanEmails = [];
        foreach ($rawEmails as $em) {
            $low = strtolower($em);
            if (!isset($inactiveSet[$low]) && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $cleanEmails[] = $em;
            }
        }
        $settings['void_refund_manager_email'] = implode(', ', $cleanEmails);
    }

    foreach ($settings as $key => $value) {
        if (!in_array($key, $allowed)) continue;
        $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
           ->execute([$key, $value, $value]);
    }

    logAction('save_system_settings', 'settings', null, json_encode($settings));
    jsonResponse(['success' => true, 'message' => 'Settings saved successfully.']);
}

// =====================================================
// RATE LIMIT MANAGEMENT
// =====================================================
function handleSAGetRateLimits() {
    requireSuperAdmin();
    $db = getDB();
    ensureRateLimitTable();
    $limits = $db->query("SELECT id, rate_key, action, identifier, hits, first_hit_at, last_hit_at, locked_until, 
                         CASE WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 1 ELSE 0 END AS is_locked
                         FROM rate_limits ORDER BY (locked_until > NOW()) DESC, last_hit_at DESC LIMIT 60")->fetchAll();
    jsonResponse(['success' => true, 'rate_limits' => $limits]);
}

function handleSAResetRateLimits() {
    requireSuperAdmin();
    $input = getJsonInput();
    $db = getDB();
    ensureRateLimitTable();

    $key = trim($input['rate_key'] ?? '');
    if ($key) {
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE rate_key = ?");
        $stmt->execute([$key]);
        logAction('reset_rate_limit', 'rate_limit', null, "Cleared rate limit lockout for key: {$key}");
        jsonResponse(['success' => true, 'message' => "Rate limit for '{$key}' has been reset."]);
    } else {
        $db->exec("TRUNCATE TABLE rate_limits");
        logAction('reset_all_rate_limits', 'rate_limit', null, "Reset all rate limits and active lockouts");
        jsonResponse(['success' => true, 'message' => 'All active rate limits and lockouts have been reset.']);
    }
}

// =====================================================
// SUBMIT REQUEST (Admin submits to SA for approval)
// =====================================================
function handleSubmitRequest() {
    requireAdmin(); // admins + superadmin can submit
    $input       = getJsonInput();
    $type        = trim($input['type']        ?? '');
    $title       = trim($input['title']       ?? '');
    $description = trim($input['description'] ?? '');
    $payload     = $input['payload']          ?? null;

    $validTypes = ['admin_approval','void_order','refund_order','menu_change','setting_change','user_deactivate','user_activate','user_delete','other'];
    if (!in_array($type, $validTypes)) {
        jsonResponse(['success' => false, 'message' => 'Invalid request type.'], 400);
    }
    if (!$title) {
        jsonResponse(['success' => false, 'message' => 'Request title is required.'], 400);
    }

    $db = getDB();
    $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload) VALUES (?,?,?,?,?,?)")
       ->execute([
           $type,
           $_SESSION['user_id'],
           $_SESSION['full_name'] ?? $_SESSION['username'],
           $title,
           $description,
           $payload ? json_encode($payload) : null
       ]);

    logAction('submit_request', 'system_request', $db->lastInsertId(), "Type: $type — $title");
    jsonResponse(['success' => true, 'message' => 'Request submitted for Super Admin review.']);
}
