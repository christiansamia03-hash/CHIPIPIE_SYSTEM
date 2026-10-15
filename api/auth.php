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
    case 'verify_2fa':
        handleVerify2FA();
        break;
    case 'resend_2fa':
        handleResend2FA();
        break;
    case 'cancel_2fa':
        handleCancel2FA();
        break;
    case 'forgot_password':
        handleForgotPassword();
        break;
    case 'reset_password_code':
        handleResetPasswordCode();
        break;
    case 'approve_admin':
        handleApproveAdmin();
        break;
    case 'resend_approval':
        handleResendApproval();
        break;
    case 'delete_admin_request':
        handleDeleteAdminRequest();
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

    // Two-Factor Authentication (2FA) for Super Admin
    if ($user['role'] === 'super_admin') {
        ensureSession();
        // Clear any previous fully authenticated session
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email'], $_SESSION['role']);

        $saEmail = getSetting('superadmin_email') ?: ($user['email'] ?: getSetting('owner_email'));
        if (!$saEmail) {
            jsonResponse(['success' => false, 'message' => 'Super admin email is not configured in settings.'], 500);
        }

        // Generate 6-digit numeric verification token
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $expiryMin = intval(getSetting('token_expiry_minutes') ?: 10);
        if ($expiryMin < 5) $expiryMin = 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

        // Invalidate any older unused 2FA tokens for this user
        $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = 'superadmin_2fa' AND is_used = 0")
           ->execute([$user['id']]);

        // Save new 2FA token to database
        $stmtToken = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, 'superadmin_2fa', ?)");
        $stmtToken->execute([$code, $user['id'], $expiresAt]);

        // Save pending 2FA state in session
        $_SESSION['pending_2fa'] = [
            'user_id'         => $user['id'],
            'username'        => $user['username'],
            'full_name'       => $user['full_name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'recipient_email' => $saEmail,
            'code'            => $code,
            'expires_at'      => time() + ($expiryMin * 60),
            'attempts'        => 0
        ];

        // Format email
        $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
        $subject   = "$storeName — Super Admin 2FA Verification Code";
        $emailBody = "
        <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <div style='display: inline-block; width: 56px; height: 56px; background: #1e1b18; color: #f59e0b; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>🛡️</div>
                <h2 style='color: #26221f; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
                <p style='color: #78716c; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Super Admin Security Verification</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>A sign-in attempt was detected for your Super Admin account. Use the confirmation code below to verify your identity:</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #1e1b18; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #3f3935; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 14px; color: #92400e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> This code is valid for <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Do not share this code with anyone.
                </div>
                <p style='font-size: 12px; color: #a8a29e; margin-top: 18px; margin-bottom: 0; text-align: center;'>If you did not initiate this login, please secure your account immediately.</p>
            </div>
        </div>";

        // Release session lock before SMTP delivery
        session_write_close();
        $emailSent = sendEmail($saEmail, $subject, $emailBody);

        $masked = maskEmail($saEmail);
        $resp = [
            'success'       => true,
            'requires_2fa'  => true,
            'redirect'      => 'superadmin-panel.php',
            'message'       => "Verification code sent to $masked. Please enter the code to continue.",
            'masked_email'  => $masked,
            'expires_in'    => $expiryMin * 60,
            'email_sent'    => $emailSent
        ];

        if (DEV_MODE) {
            $resp['dev_code'] = $code;
        }

        jsonResponse($resp);
    }

    // Two-Factor Authentication (2FA) for POS Admin (sent to Manager / void-refund notification email)
    if ($user['role'] === 'admin') {
        ensureSession();
        // Clear any previous fully authenticated session
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email'], $_SESSION['role']);

        $managerEmailsStr = getSetting('void_refund_manager_email');
        $managerEmails = array_filter(array_map('trim', explode(',', $managerEmailsStr ?: '')));

        if (empty($managerEmails)) {
            $fallback = getSetting('owner_email') ?: $user['email'];
            if ($fallback) $managerEmails = [$fallback];
        }

        if (empty($managerEmails)) {
            jsonResponse(['success' => false, 'message' => 'Manager email (void/refund notification email) is not configured in settings.'], 500);
        }

        // Generate 6-digit numeric verification token
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $expiryMin = intval(getSetting('token_expiry_minutes') ?: 10);
        if ($expiryMin < 5) $expiryMin = 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

        // Invalidate any older unused 2FA tokens for this user
        $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = 'admin_2fa' AND is_used = 0")
           ->execute([$user['id']]);

        // Save new 2FA token to database
        $stmtToken = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, 'admin_2fa', ?)");
        $stmtToken->execute([$code, $user['id'], $expiresAt]);

        // Save pending 2FA state in session
        $_SESSION['pending_2fa'] = [
            'user_id'         => $user['id'],
            'username'        => $user['username'],
            'full_name'       => $user['full_name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'recipient_email' => implode(', ', $managerEmails),
            'code'            => $code,
            'expires_at'      => time() + ($expiryMin * 60),
            'attempts'        => 0
        ];

        // Format email to Manager
        $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
        $subject   = "$storeName — Admin Login 2FA Verification Code";
        $emailBody = "
        <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <div style='display: inline-block; width: 56px; height: 56px; background: #5c3d2e; color: #f5eedc; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(92,61,46,0.2);'>🔐</div>
                <h2 style='color: #2b1f1d; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
                <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Manager Authorization Required</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello Manager,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>Admin <strong>" . htmlspecialchars($user['full_name']) . "</strong> (<code>" . htmlspecialchars($user['username']) . "</code>) is attempting to sign in to the <strong>POS System</strong>. If you approve this login, share the two-factor authentication (2FA) code below with them:</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #2b1f1d; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #5c3d2e; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> This code is single-use and will expire in <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Do not share this code if you did not authorize this login.
                </div>
                <p style='font-size: 12px; color: #a8a29e; margin-top: 18px; margin-bottom: 0; text-align: center;'>If you did not authorize this administrator login, you can safely ignore this email.</p>
            </div>
        </div>";

        // Release session lock before SMTP delivery
        session_write_close();
        $emailSent = false;
        foreach ($managerEmails as $mEmail) {
            $sent = sendEmail($mEmail, $subject, $emailBody);
            if ($sent) $emailSent = true;
        }

        $masked = implode(', ', array_map('maskEmail', $managerEmails));
        $resp = [
            'success'       => true,
            'requires_2fa'  => true,
            'role'          => 'admin',
            'message'       => "Verification code sent to manager email ($masked). Please enter the code to continue.",
            'masked_email'  => $masked,
            'expires_in'    => $expiryMin * 60,
            'email_sent'    => $emailSent
        ];

        if (DEV_MODE) {
            $resp['dev_code'] = $code;
        }

        jsonResponse($resp);
    }

    // Standard login for other roles (e.g. cashier)
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
    $input         = getJsonInput();
    $username      = trim($input['username'] ?? '');
    $password      = trim($input['password'] ?? '');
    $firstName     = trim($input['first_name'] ?? '');
    $lastName      = trim($input['last_name'] ?? '');
    $middleInitial = trim($input['middle_initial'] ?? '');
    $suffix        = trim($input['suffix'] ?? '');
    $email         = trim($input['email'] ?? '');
    $role          = trim($input['role'] ?? 'cashier');

    // Build full_name if separate fields provided
    if ($firstName && $lastName) {
        $mi = $middleInitial ? (rtrim($middleInitial, '.') . '.') : '';
        $nameParts = array_filter([$firstName, $mi, $lastName, $suffix]);
        $full_name = implode(' ', $nameParts);
    } else {
        $full_name = trim($input['full_name'] ?? '');
        $firstName = $full_name;
        $lastName  = '';
    }

    if (!$username || !$password || !$full_name || !$email) {
        jsonResponse(['success' => false, 'message' => 'First name, last name, username, email, and password are required.'], 400);
    }

    $pwdErr = '';
    if (!validatePasswordComplexity($password, $pwdErr)) {
        jsonResponse(['success' => false, 'message' => $pwdErr], 400);
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

    // Check if email already exists in users table
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already registered to an existing account.'], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'admin') {
        // Admin registration requires owner approval
        $stmt = $db->prepare("SELECT id FROM admin_registration_requests WHERE username = ? AND status = 'pending'");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'A pending admin registration for this username already exists.'], 409);
        }

        // Check if email already used in pending requests
        $stmt = $db->prepare("SELECT id FROM admin_registration_requests WHERE email = ? AND status = 'pending'");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'This email address already has a pending admin registration request.'], 409);
        }

        $approvalToken = generateToken(32);
        $stmt = $db->prepare("INSERT INTO admin_registration_requests (username, password, first_name, last_name, middle_initial, suffix, full_name, email, approval_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $firstName, $lastName, $middleInitial, $suffix, $full_name, $email, $approvalToken]);

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
        // Cashier registration — immediate activation (admin or superadmin must be logged in)
        ensureSession();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
            jsonResponse(['success' => false, 'message' => 'Only admins can register new cashiers.'], 403);
        }

        $stmt = $db->prepare("INSERT INTO users (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cashier', 1)");
        $stmt->execute([$username, $hashedPassword, $firstName, $lastName, $middleInitial, $suffix, $full_name, $email]);

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
    $stmt = $db->prepare("INSERT INTO users (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([
        $request['username'], 
        $request['password'], 
        $request['first_name'] ?? null, 
        $request['last_name'] ?? null, 
        $request['middle_initial'] ?? null, 
        $request['suffix'] ?? null, 
        $request['full_name'], 
        $request['email']
    ]);

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

    // Clean up any approved admin requests where the admin no longer exists in users
    $db->exec("DELETE FROM admin_registration_requests WHERE status='approved' AND username NOT IN (SELECT username FROM users)");

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
    $stmt = $db->prepare("INSERT INTO users (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([
        $request['username'], 
        $request['password'], 
        $request['first_name'] ?? null, 
        $request['last_name'] ?? null, 
        $request['middle_initial'] ?? null, 
        $request['suffix'] ?? null, 
        $request['full_name'], 
        $request['email']
    ]);

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
// DELETE ADMIN REQUEST (Admin only)
// =====================================================
function handleDeleteAdminRequest() {
    requireAdmin();
    $input = getJsonInput();
    $requestId = intval($input['request_id'] ?? $input['id'] ?? 0);

    if (!$requestId) {
        jsonResponse(['success' => false, 'message' => 'Request ID is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name FROM admin_registration_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => false, 'message' => 'Request not found.'], 404);
    }

    $db->prepare("DELETE FROM admin_registration_requests WHERE id = ?")->execute([$requestId]);

    jsonResponse([
        'success' => true,
        'message' => "Admin request for '{$request['full_name']}' permanently removed."
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
    } else if (isset($_SESSION['pending_2fa']) && time() <= $_SESSION['pending_2fa']['expires_at']) {
        $pending = $_SESSION['pending_2fa'];
        $emails = array_filter(array_map('trim', explode(',', $pending['recipient_email'] ?? '')));
        $maskedList = array_map('maskEmail', $emails);
        $maskedStr = implode(', ', $maskedList);

        $resp = [
            'success'      => true,
            'loggedIn'     => false,
            'pending_2fa'  => true,
            'role'         => $pending['role'],
            'masked_email' => $maskedStr,
            'expires_in'   => max(0, $pending['expires_at'] - time())
        ];
        if (DEV_MODE) {
            $resp['dev_code'] = $pending['code'];
        }
        jsonResponse($resp);
    } else {
        jsonResponse(['success' => true, 'loggedIn' => false]);
    }
}

// =====================================================
// VERIFY 2FA TOKEN (Admin & Super Admin)
// =====================================================
function handleVerify2FA() {
    ensureSession();
    $input = getJsonInput();
    $code  = strtoupper(trim($input['code'] ?? ''));

    if (!$code) {
        jsonResponse(['success' => false, 'message' => 'Verification code is required.'], 400);
    }

    if (!isset($_SESSION['pending_2fa'])) {
        jsonResponse(['success' => false, 'message' => 'No pending 2FA login session found. Please sign in again.'], 401);
    }

    $pending = $_SESSION['pending_2fa'];

    if (time() > $pending['expires_at']) {
        unset($_SESSION['pending_2fa']);
        jsonResponse(['success' => false, 'message' => 'Verification code has expired. Please sign in again.'], 400);
    }

    if ($pending['attempts'] >= 5) {
        unset($_SESSION['pending_2fa']);
        jsonResponse(['success' => false, 'message' => 'Too many failed attempts. Please sign in again.'], 403);
    }

    $db = getDB();
    $now = date('Y-m-d H:i:s');
    $purpose = ($pending['role'] === 'super_admin' ? 'superadmin_2fa' : 'admin_2fa');

    // Check token in DB or session
    $stmt = $db->prepare("SELECT id FROM admin_tokens WHERE token = ? AND user_id = ? AND purpose = ? AND is_used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$code, $pending['user_id'], $purpose, $now]);
    $tokenRow = $stmt->fetch();

    $codeMatches = ($tokenRow || $code === strtoupper($pending['code']));

    if (!$codeMatches) {
        $_SESSION['pending_2fa']['attempts'] = ($pending['attempts'] + 1);
        $remaining = 5 - $_SESSION['pending_2fa']['attempts'];
        jsonResponse(['success' => false, 'message' => "Invalid verification code. ($remaining attempts remaining)"], 400);
    }

    // Mark token as used in DB
    if ($tokenRow) {
        $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = NOW() WHERE id = ?")->execute([$tokenRow['id']]);
    } else {
        $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = NOW() WHERE token = ? AND purpose = ?")->execute([$code, $purpose]);
    }

    // Fully establish authenticated session
    $_SESSION['user_id']   = $pending['user_id'];
    $_SESSION['username']  = $pending['username'];
    $_SESSION['full_name'] = $pending['full_name'];
    $_SESSION['email']     = $pending['email'];
    $_SESSION['role']      = $pending['role'];
    unset($_SESSION['pending_2fa']);

    // Log login to audit trail
    $actionName   = ($pending['role'] === 'super_admin' ? 'superadmin_login' : 'admin_login');
    $actionDetail = ($pending['role'] === 'super_admin' ? 'Super admin logged in via 2FA verification code' : 'Admin logged in via Manager 2FA verification code');
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$_SESSION['user_id'], $_SESSION['username'], $actionName, 'user', $_SESSION['user_id'], $actionDetail, $ip]);
    } catch (Exception $e) {}

    $resp = [
        'success'  => true,
        'message'  => 'Two-factor verification successful.',
        'user'     => [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'email'     => $_SESSION['email'],
            'role'      => $_SESSION['role']
        ]
    ];
    if ($pending['role'] === 'super_admin') {
        $resp['redirect'] = 'superadmin-panel.php';
    }

    jsonResponse($resp);
}

// =====================================================
// RESEND 2FA TOKEN
// =====================================================
function handleResend2FA() {
    ensureSession();
    if (!isset($_SESSION['pending_2fa'])) {
        jsonResponse(['success' => false, 'message' => 'No pending 2FA login session found. Please sign in again.'], 400);
    }

    $pending = $_SESSION['pending_2fa'];
    $db = getDB();

    $code = sprintf("%06d", mt_rand(100000, 999999));
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: 10);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $purpose   = ($pending['role'] === 'super_admin' ? 'superadmin_2fa' : 'admin_2fa');

    // Invalidate old tokens for this user
    $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = ? AND is_used = 0")
       ->execute([$pending['user_id'], $purpose]);

    // Insert new token
    $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)")
       ->execute([$code, $pending['user_id'], $purpose, $expiresAt]);

    $_SESSION['pending_2fa']['code']       = $code;
    $_SESSION['pending_2fa']['expires_at'] = time() + ($expiryMin * 60);

    $recipientStr = $pending['recipient_email'];
    $recipients   = array_filter(array_map('trim', explode(',', $recipientStr)));
    $storeName    = getSetting('store_name') ?: 'ChipiPie Cafe';

    if ($pending['role'] === 'super_admin') {
        $subject = "$storeName — Super Admin 2FA Verification Code (Resent)";
        $emailBody = "
        <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <div style='display: inline-block; width: 56px; height: 56px; background: #1e1b18; color: #f59e0b; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>🛡️</div>
                <h2 style='color: #26221f; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
                <p style='color: #78716c; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>New 2FA Code (Resent)</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($pending['full_name']) . "</strong>,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>Here is your new two-factor verification code for Super Admin login:</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #1e1b18; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #3f3935; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 14px; color: #92400e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> Valid for <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Previous codes are now invalidated.
                </div>
            </div>
        </div>";
    } else {
        $subject = "$storeName — Admin Login 2FA Verification Code (Resent)";
        $emailBody = "
        <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <div style='display: inline-block; width: 56px; height: 56px; background: #5c3d2e; color: #f5eedc; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(92,61,46,0.2);'>🔐</div>
                <h2 style='color: #2b1f1d; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
                <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Manager Authorization (Resent Code)</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello Manager,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>Here is the resent two-factor verification code for Admin <strong>" . htmlspecialchars($pending['full_name']) . "</strong> (<code>" . htmlspecialchars($pending['username']) . "</code>):</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #2b1f1d; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #5c3d2e; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> Valid for <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Previous codes are now invalidated.
                </div>
            </div>
        </div>";
    }

    session_write_close();
    $emailSent = false;
    foreach ($recipients as $toEmail) {
        $sent = sendEmail($toEmail, $subject, $emailBody);
        if ($sent) $emailSent = true;
    }

    $maskedList = array_map('maskEmail', $recipients);
    $maskedStr  = implode(', ', $maskedList);

    $resp = [
        'success'    => true,
        'message'    => "A new verification code was sent to $maskedStr.",
        'expires_in' => $expiryMin * 60,
        'email_sent' => $emailSent
    ];
    if (DEV_MODE) {
        $resp['dev_code'] = $code;
    }
    jsonResponse($resp);
}

// =====================================================
// CANCEL 2FA SESSION
// =====================================================
function handleCancel2FA() {
    ensureSession();
    unset($_SESSION['pending_2fa']);
    jsonResponse(['success' => true, 'message' => '2FA session cleared.']);
}

// =====================================================
// HELPER: MASK EMAIL FOR PRIVACY
// =====================================================
function maskEmail($email) {
    if (!$email || strpos($email, '@') === false) return $email;
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = $user[0] . '*';
    } else {
        $maskedUser = $user[0] . str_repeat('*', max(3, $len - 2)) . $user[$len - 1];
    }
    return $maskedUser . '@' . $domain;
}

// =====================================================
// FORGOT PASSWORD — REQUEST RESET CODE
// =====================================================
function handleForgotPassword() {
    $input = getJsonInput();
    $identity = trim($input['identifier'] ?? ($input['identity'] ?? ($input['username'] ?? ($input['email'] ?? ''))));

    if (!$identity) {
        jsonResponse(['success' => false, 'message' => 'Please enter your username or email address.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name, email, role, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'No active user found with that username or email.'], 404);
    }

    // Determine target email
    if ($user['role'] === 'super_admin') {
        $targetEmail = getSetting('superadmin_email') ?: ($user['email'] ?: getSetting('owner_email'));
    } else {
        $targetEmail = $user['email'];
    }

    if (!$targetEmail || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'No valid email is registered for this account. Contact your administrator.'], 400);
    }

    // 6-digit numeric reset code
    $code = sprintf("%06d", mt_rand(100000, 999999));
    $expiryMin = 15;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

    // Invalidate prior unused password reset tokens for this user
    $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = 'password_reset' AND is_used = 0")
       ->execute([$user['id']]);

    // Store in admin_tokens
    $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)")
       ->execute([$code, $user['id'], $expiresAt]);

    // Save pending reset state in session
    ensureSession();
    $_SESSION['pending_pwd_reset'] = [
        'user_id'    => $user['id'],
        'username'   => $user['username'],
        'email'      => $targetEmail,
        'code'       => $code,
        'expires_at' => time() + ($expiryMin * 60)
    ];

    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $subject   = "$storeName — Password Reset Code";
    $emailBody = "
    <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
        <div style='text-align: center; margin-bottom: 24px;'>
            <div style='display: inline-block; width: 56px; height: 56px; background: #5c3d2e; color: #f5eedc; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(92,61,46,0.2);'>🔑</div>
            <h2 style='color: #2b1f1d; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
            <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Password Reset Request</p>
        </div>
        <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
            <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>We received a request to reset the password for your account (<code>" . htmlspecialchars($user['username']) . "</code>). Enter the 6-digit confirmation code below to set a new password:</p>
            
            <div style='text-align: center; margin: 24px 0;'>
                <div style='display: inline-block; background: #2b1f1d; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #5c3d2e; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
            </div>

            <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4;'>
                ⏱ <strong>Security Notice:</strong> Valid for <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). If you did not request this, you can safely ignore this email — your password will remain unchanged.
            </div>
        </div>
    </div>";

    session_write_close();
    $emailSent = sendEmail($targetEmail, $subject, $emailBody);

    $masked = maskEmail($targetEmail);
    $resp = [
        'success'      => true,
        'message'      => "A 6-digit password reset code has been sent to $masked.",
        'masked_email' => $masked,
        'username'     => $user['username'],
        'expires_in'   => $expiryMin * 60,
        'email_sent'   => $emailSent
    ];
    if (DEV_MODE) {
        $resp['dev_code'] = $code;
    }
    jsonResponse($resp);
}

// =====================================================
// RESET PASSWORD WITH CONFIRMATION CODE
// =====================================================
function handleResetPasswordCode() {
    $input    = getJsonInput();
    $code     = strtoupper(trim($input['token'] ?? ($input['code'] ?? '')));
    $newPass  = trim($input['new_password'] ?? '');
    $identity = trim($input['identifier'] ?? ($input['identity'] ?? ($input['username'] ?? ($input['email'] ?? ''))));

    if (!$code || !$newPass) {
        jsonResponse(['success' => false, 'message' => 'Reset code and new password are required.'], 400);
    }

    $pwdErr = '';
    if (!validatePasswordComplexity($newPass, $pwdErr)) {
        jsonResponse(['success' => false, 'message' => $pwdErr], 400);
    }

    $db = getDB();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND purpose = 'password_reset' AND is_used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$code, $now]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset code. Please request a new one.'], 400);
    }

    if ($identity) {
        $uStmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
        $uStmt->execute([$identity, $identity]);
        $matchUser = $uStmt->fetch();
        if ($matchUser && $matchUser['id'] != $tokenRow['user_id']) {
            jsonResponse(['success' => false, 'message' => 'Reset code does not match this user account.'], 400);
        }
    }

    $userId = $tokenRow['user_id'];
    $hashed = password_hash($newPass, PASSWORD_BCRYPT);

    $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $userId]);
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = NOW() WHERE id = ?")->execute([$tokenRow['id']]);

    // Log action
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$userId, null, 'reset_password', 'user', $userId, 'User reset password via email verification code', $ip]);
    } catch (Exception $e) {}

    ensureSession();
    unset($_SESSION['pending_pwd_reset']);

    jsonResponse(['success' => true, 'message' => 'Password has been successfully reset! You can now log in with your new password.']);
}

