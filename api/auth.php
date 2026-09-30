<?php
/**
 * ChipiPie Cafe — Authentication API
 * 
 * Endpoints:
 *   POST ?action=login       — Authenticate user
 *   POST ?action=logout      — Destroy session
 *   POST ?action=register    — Register new user (admin registration needs owner approval)
 *   GET  ?action=users       — List all users (admin only)
 *   POST ?action=toggle_user — Activate/deactivate user (admin only)
 */

require_once __DIR__ . '/db_config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'register':
        handleRegister();
        break;
    case 'users':
        handleListUsers();
        break;
    case 'toggle_user':
        handleToggleUser();
        break;
    case 'session':
        handleCheckSession();
        break;
    case 'approve_admin':
        handleApproveAdmin();
        break;
    case 'resend_approval':
        handleResendApproval();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}

// =====================================================
// LOGIN
// =====================================================
function handleLogin() {
    $input = getJsonInput();
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$username || !$password) {
        jsonResponse(['success' => false, 'message' => 'Username and password are required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, is_active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    if (!$user['is_active']) {
        jsonResponse(['success' => false, 'message' => 'Account is deactivated. Contact admin.'], 403);
    }

    if (!password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    // Start session
    ensureSession();
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name']= $user['full_name'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];

    $response = [
        'success' => true,
        'message' => 'Login successful.',
        'user' => [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ]
    ];

    // Tell the frontend to redirect super_admin to the dedicated panel
    if ($user['role'] === 'super_admin') {
        $response['redirect'] = 'superadmin-panel.php';
    }

    jsonResponse($response);
}

// =====================================================
// LOGOUT
// =====================================================
function handleLogout() {
    ensureSession();

    // Clear all session variables
    $_SESSION = [];

    // Expire session cookie across all possible paths
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        setcookie(session_name(), '', time() - 86400, '/');
        setcookie(session_name(), '', time() - 86400, '/CHIPIPIE_SYSTEM');
        setcookie(session_name(), '', time() - 86400, '/CHIPIPIE_SYSTEM/');
        setcookie(session_name(), '', time() - 86400, '');
    }

    // Destroy server-side session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // If called as a direct browser redirect, redirect back
    $redirect = $_GET['redirect'] ?? '';
    if ($redirect) {
        $safe = basename($redirect);
        header('Location: ../' . $safe);
        exit;
    }

    jsonResponse(['success' => true, 'message' => 'Logged out.']);
}

// =====================================================
// REGISTER
// =====================================================
function handleRegister() {
    $input = getJsonInput();
    $username  = trim($input['username'] ?? '');
    $password  = trim($input['password'] ?? '');
    $full_name = trim($input['full_name'] ?? '');
    $email     = trim($input['email'] ?? '');
    $role      = trim($input['role'] ?? 'cashier');

    if (!$username || !$password || !$full_name || !$email) {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address.'], 400);
    }

    if (!in_array($role, ['admin', 'cashier'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid role.'], 400);
    }

    $db = getDB();

    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Username already taken.'], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'admin') {
        // Admin registration requires owner approval
        // Check if there's already a pending request for this username
        $stmt = $db->prepare("SELECT id FROM admin_registration_requests WHERE username = ? AND status = 'pending'");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'A pending admin registration for this username already exists.'], 409);
        }

        $approvalToken = generateToken(32);
        $stmt = $db->prepare("INSERT INTO admin_registration_requests (username, password, full_name, email, approval_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $full_name, $email, $approvalToken]);

        // Send approval email to owner
        $ownerEmail = getSetting('owner_email');
        $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
        $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/auth.php?action=approve_admin&token=" . $approvalToken;

        $emailBody = "
        <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='display: inline-block; width: 50px; height: 50px; background: #838582; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>☕</div>
                <h2 style='color: #3a2f2d; margin: 10px 0 5px;'>$storeName</h2>
                <p style='color: #888; font-size: 13px; margin: 0;'>Admin Registration Request</p>
            </div>
            <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
                <p style='color: #333; font-size: 14px;'>A new admin registration has been requested:</p>
                <table style='width: 100%; font-size: 14px; color: #444; margin: 16px 0;'>
                    <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Name:</td><td>$full_name</td></tr>
                    <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Username:</td><td>$username</td></tr>
                    <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Email:</td><td>$email</td></tr>
                </table>
                <a href='$approveUrl' style='display: block; text-align: center; background: #3a2f2d; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px;'>
                    ✅ Approve Admin Registration
                </a>
                <p style='font-size: 11px; color: #aaa; text-align: center; margin-top: 14px;'>If you did not expect this request, you can safely ignore this email.</p>
            </div>
        </div>";

        $emailSent = sendEmail($ownerEmail, "$storeName — Admin Registration Approval Needed", $emailBody);

        $response = [
            'success' => true,
            'message' => 'Admin registration submitted. Awaiting owner approval via email.',
            'email_sent_to' => $ownerEmail,
            'email_delivered' => $emailSent
        ];

        // In dev mode, include the approval link for testing
        if (DEV_MODE) {
            $response['dev_approve_url'] = $approveUrl;
            $response['dev_token'] = $approvalToken;
        }

        jsonResponse($response);
    } else {
        // Cashier registration — immediate activation (admin must be logged in)
        ensureSession();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Only admins can register new cashiers.'], 403);
        }

        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'cashier', 1)");
        $stmt->execute([$username, $hashedPassword, $full_name, $email]);

        jsonResponse([
            'success' => true,
            'message' => "Cashier '$full_name' registered successfully.",
            'user_id' => $db->lastInsertId()
        ]);
    }
}

// =====================================================
// APPROVE ADMIN (via email link)
// =====================================================
function handleApproveAdmin() {
    $token = $_GET['token'] ?? '';

    if (!$token) {
        echo "<h2 style='font-family:Segoe UI,sans-serif;color:#842029;text-align:center;margin-top:80px;'>❌ Invalid approval link.</h2>";
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $request = $stmt->fetch();

    if (!$request) {
        echo "
        <div style='font-family:Segoe UI,sans-serif;text-align:center;margin-top:80px;'>
            <h2 style='color:#842029;'>❌ Invalid or Already Used</h2>
            <p style='color:#666;'>This approval link is no longer valid.</p>
        </div>";
        exit;
    }

    // Check if username was taken in the meantime
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$request['username']]);
    if ($stmt->fetch()) {
        $db->prepare("UPDATE admin_registration_requests SET status = 'rejected' WHERE id = ?")->execute([$request['id']]);
        echo "
        <div style='font-family:Segoe UI,sans-serif;text-align:center;margin-top:80px;'>
            <h2 style='color:#856404;'>⚠️ Username Conflict</h2>
            <p style='color:#666;'>The username '{$request['username']}' has already been taken.</p>
        </div>";
        exit;
    }

    // Create the admin account
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([$request['username'], $request['password'], $request['full_name'], $request['email']]);

    // Mark request as approved
    $db->prepare("UPDATE admin_registration_requests SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$request['id']]);

    // Send confirmation email to the new admin
    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $confirmBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #3a2f2d;'>$storeName</h2>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='font-size: 14px; color: #333;'>🎉 <strong>{$request['full_name']}</strong>, your admin account has been approved!</p>
            <p style='font-size: 14px; color: #555;'>You can now log in with your username: <strong>{$request['username']}</strong></p>
        </div>
    </div>";
    sendEmail($request['email'], "$storeName — Admin Account Approved", $confirmBody);

    echo "
    <div style='font-family:Segoe UI,sans-serif;text-align:center;margin-top:80px;max-width:500px;margin-left:auto;margin-right:auto;'>
        <div style='background:#d4edda;border-radius:16px;padding:40px 30px;border:1px solid #b7e2c4;'>
            <div style='font-size:48px;margin-bottom:16px;'>✅</div>
            <h2 style='color:#1a5c2e;margin-bottom:8px;'>Admin Approved!</h2>
            <p style='color:#333;font-size:15px;'><strong>{$request['full_name']}</strong> has been registered as an admin.</p>
            <p style='color:#666;font-size:13px;margin-top:12px;'>They can now log in at the POS system.</p>
        </div>
    </div>";
    exit;
}

// =====================================================
// RESEND APPROVAL EMAIL TO OWNER (Admin can trigger)
// =====================================================
function handleResendApproval() {
    requireAdmin();
    $input = getJsonInput();
    $requestId = intval($input['request_id'] ?? 0);

    if (!$requestId) {
        jsonResponse(['success' => false, 'message' => 'Request ID is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => false, 'message' => 'Request not found or already processed.'], 404);
    }

    $ownerEmail = getSetting('owner_email');
    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/auth.php?action=approve_admin&token=" . $request['approval_token'];

    $emailBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #838582; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>☕</div>
            <h2 style='color: #3a2f2d; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #888; font-size: 13px; margin: 0;'>Admin Registration Request (Resent)</p>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='color: #333; font-size: 14px;'>A pending admin registration awaits your approval:</p>
            <table style='width: 100%; font-size: 14px; color: #444; margin: 16px 0;'>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Name:</td><td>{$request['full_name']}</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Username:</td><td>{$request['username']}</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Email:</td><td>{$request['email']}</td></tr>
            </table>
            <a href='$approveUrl' style='display: block; text-align: center; background: #3a2f2d; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px;'>
                ✅ Approve Admin Registration
            </a>
            <p style='font-size: 11px; color: #aaa; text-align: center; margin-top: 14px;'>If you did not expect this request, you can safely ignore this email.</p>
        </div>
    </div>";

    $sent = sendEmail($ownerEmail, "$storeName — Admin Registration Approval Needed (Resent)", $emailBody);

    if ($sent) {
        jsonResponse(['success' => true, 'message' => "Approval email resent to owner ({$ownerEmail})."]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to send email. Check SMTP settings.'], 500);
    }
}

function handleListUsers() {
    requireAdmin();

    $db = getDB();
    $stmt = $db->query("SELECT id, username, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Also get pending admin requests
    $stmt2 = $db->query("SELECT id, username, full_name, email, status, approval_token, created_at FROM admin_registration_requests ORDER BY created_at DESC");
    $pendingRequests = $stmt2->fetchAll();

    // In dev mode, include approval URLs for pending requests
    if (DEV_MODE) {
        foreach ($pendingRequests as &$req) {
            if ($req['status'] === 'pending') {
                $req['approve_url'] = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/auth.php?action=approve_admin&token=" . $req['approval_token'];
            }
            unset($req['approval_token']); // Don't expose raw token to frontend
        }
        unset($req);
    } else {
        foreach ($pendingRequests as &$req) {
            unset($req['approval_token']);
        }
        unset($req);
    }

    jsonResponse([
        'success' => true,
        'users' => $users,
        'pending_admin_requests' => $pendingRequests
    ]);
}

// =====================================================
// APPROVE ADMIN FROM UI (Admin only — requires token)
// =====================================================
function handleApproveAdminUI() {
    requireAdmin();
    $input = getJsonInput();
    $requestId = intval($input['request_id'] ?? 0);

    if (!$requestId) {
        jsonResponse(['success' => false, 'message' => 'Request ID is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => false, 'message' => 'Request not found or already processed.'], 404);
    }

    // Check username not taken
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$request['username']]);
    if ($stmt->fetch()) {
        $db->prepare("UPDATE admin_registration_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
        jsonResponse(['success' => false, 'message' => 'Username already taken. Request auto-rejected.'], 409);
    }

    // Create the admin account
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([$request['username'], $request['password'], $request['full_name'], $request['email']]);

    // Mark as approved
    $now = date('Y-m-d H:i:s');
    $db->prepare("UPDATE admin_registration_requests SET status = 'approved', approved_at = ? WHERE id = ?")->execute([$now, $requestId]);

    jsonResponse([
        'success' => true,
        'message' => "Admin '{$request['full_name']}' approved and account created!"
    ]);
}

// =====================================================
// REJECT ADMIN FROM UI (Admin only)
// =====================================================
function handleRejectAdminUI() {
    requireAdmin();
    $input = getJsonInput();
    $requestId = intval($input['request_id'] ?? 0);

    if (!$requestId) {
        jsonResponse(['success' => false, 'message' => 'Request ID is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_registration_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => false, 'message' => 'Request not found or already processed.'], 404);
    }

    $db->prepare("UPDATE admin_registration_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);

    jsonResponse([
        'success' => true,
        'message' => "Admin request from '{$request['full_name']}' has been rejected."
    ]);
}

// =====================================================
// TOGGLE USER STATUS (Admin only)
// =====================================================
function handleToggleUser() {
    requireAdmin();

    $input = getJsonInput();
    $userId = intval($input['user_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'User ID is required.'], 400);
    }

    // Prevent self-deactivation
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own account.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, is_active, full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }

    $newStatus = $user['is_active'] ? 0 : 1;
    $db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $userId]);

    $statusText = $newStatus ? 'activated' : 'deactivated';
    jsonResponse([
        'success' => true,
        'message' => "User '{$user['full_name']}' has been $statusText.",
        'is_active' => $newStatus
    ]);
}

// =====================================================
// CHECK SESSION
// =====================================================
function handleCheckSession() {
    ensureSession();
    if (isset($_SESSION['user_id'])) {
        jsonResponse([
            'success'  => true,
            'loggedIn' => true,
            'user' => [
                'id'        => $_SESSION['user_id'],
                'username'  => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'email'     => $_SESSION['email'],
                'role'      => $_SESSION['role'],
            ]
        ]);
    } else {
        jsonResponse(['success' => true, 'loggedIn' => false]);
    }
}
