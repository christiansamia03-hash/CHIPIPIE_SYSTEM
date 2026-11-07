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
    case 'reset_password_link':
        handleResetPasswordLink();
        break;
    case 'approve_admin':
        handleApproveAdmin();
        break;
    case 'approve_registration':
    case 'approve_admin_ui':
        handleApproveAdminUI();
        break;
    case 'reject_registration':
    case 'reject_admin_ui':
        handleRejectAdminUI();
        break;
    case 'resend_approval':
        handleResendApproval();
        break;
    case 'delete_admin_request':
    case 'delete_registration_request':
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
    // ISO 25010 — input validation: enforce username length & format
    if (strlen($username) > 50) {
        jsonResponse(['success' => false, 'message' => 'Username must not exceed 50 characters.'], 400);
    }

    // Rate Limiting Checks (batch-load settings in one DB query via cache)
    $cfg            = getSettings(['rate_limit_login_attempts', 'rate_limit_lockout_minutes']);
    $maxAttempts    = intval($cfg['rate_limit_login_attempts'] ?: 5);
    $lockoutMinutes = intval($cfg['rate_limit_lockout_minutes'] ?: 5);
    $lockoutSeconds = $lockoutMinutes * 60;
    $windowSeconds  = $lockoutMinutes * 60;
    $ip             = getClientIP();
    $userKey        = strtolower($username);

    // Check IP lockout (allow 3x max attempts per IP to accommodate multiple cashiers/terminals behind the same NAT router)
    $ipCheck = checkRateLimit('login_ip', $ip, $maxAttempts * 3, $windowSeconds, $lockoutSeconds);
    if (!$ipCheck['allowed']) {
        $mins = ceil($ipCheck['retry_after'] / 60);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'retry_after'  => $ipCheck['retry_after'],
            'message'      => "Too many failed login attempts from this network. Temporarily locked for {$mins} minute" . ($mins > 1 ? 's' : '') . "."
        ], 429);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, is_active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 1. If username is NOT registered in the system:
    // Do NOT add it to the user history or rate_limits table.
    if (!$user) {
        // Check if there is a pending registration request for this username
        $chkReq = $db->prepare("SELECT role, status FROM admin_registration_requests WHERE username = ? ORDER BY created_at DESC LIMIT 1");
        $chkReq->execute([$username]);
        $pendingReq = $chkReq->fetch();
        if ($pendingReq && $pendingReq['status'] === 'pending') {
            $roleLabel = ucfirst($pendingReq['role'] ?? 'cashier');
            jsonResponse([
                'success' => false,
                'pending_approval' => true,
                'message' => "Your {$roleLabel} registration is still pending approval. You will be able to log in once management approves your account."
            ], 403);
        }

        recordRateLimitHit('login_ip', $ip, $maxAttempts * 3, $windowSeconds, $lockoutSeconds, false);
        jsonResponse(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    // 2. If username IS registered: Check if this user account is currently locked out
    $userCheck = checkRateLimit('login_user', $user['username'], $maxAttempts, $windowSeconds, $lockoutSeconds);
    if (!$userCheck['allowed']) {
        $mins = ceil($userCheck['retry_after'] / 60);
        $cnt = $userCheck['lockout_count'] ?: 1;
        jsonResponse([
            'success'       => false,
            'rate_limited'  => true,
            'retry_after'   => $userCheck['retry_after'],
            'lockout_count' => $cnt,
            'message'       => "Account is temporarily locked due to multiple failed login attempts. Please try again in {$mins} minutes." . ($cnt >= 3 ? " (Extended 20-minute security lockout applied)" : "")
        ], 429);
    }

    if (!$user['is_active']) {
        jsonResponse(['success' => false, 'message' => 'Account is deactivated. Contact admin.'], 403);
    }

    // 3. Verify password
    if (!password_verify($password, $user['password'])) {
        // Record failed attempt with progressive lockout enabled (3rd lockout = 20 mins)
        $uRec = recordRateLimitHit('login_user', $user['username'], $maxAttempts, $windowSeconds, $lockoutSeconds, true);
        $iRec = recordRateLimitHit('login_ip', $ip, $maxAttempts * 3, $windowSeconds, $lockoutSeconds, false);

        $lockCount = $uRec['lockout_count'];
        $lockSecs  = $uRec['retry_after'];
        $lockMins  = ceil($lockSecs / 60);

        if ($uRec['just_locked']) {
            // Log security warning in system audit log immediately
            $logDetail = "Security Warning: Account '{$user['username']}' locked for {$lockMins}m (Lockout #{$lockCount}) after {$maxAttempts} failed login attempts from IP: {$ip}";
            try {
                $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (NULL, ?, 'account_lockout', 'user', ?, ?, ?)")
                   ->execute([$user['username'], $user['id'], $logDetail, $ip]);
            } catch (Exception $e) {}

            // Send instant response to browser so UI updates in <30ms without freezing for SMTP
            jsonResponseAndContinue([
                'success'       => false,
                'rate_limited'  => true,
                'retry_after'   => $lockSecs,
                'lockout_count' => $lockCount,
                'message'       => "Account temporarily locked for {$lockMins} minutes due to multiple failed login attempts." . ($lockCount >= 3 ? " (Extended 20-minute security lockout applied)" : "")
            ], 429);

            // Deliver security alert emails in background
            if ($lockCount === 1 && !empty($user['email'])) {
                sendUserLockoutNotification($user, $ip, $lockMins);
            }
            sendSuperAdminLockoutNotification($user, $ip, $lockCount, $lockMins);
            exit;
        }

        if (!$uRec['allowed']) {
            jsonResponse([
                'success'       => false,
                'rate_limited'  => true,
                'retry_after'   => $lockSecs,
                'lockout_count' => $lockCount,
                'message'       => "Account temporarily locked for {$lockMins} minutes due to multiple failed login attempts." . ($lockCount >= 3 ? " (Extended 20-minute security lockout applied)" : "")
            ], 429);
        }

        $rem = $uRec['remaining'];
        $remMsg = $rem > 0 ? " ({$rem} attempt" . ($rem === 1 ? '' : 's') . " remaining)" : "";
        jsonResponse(['success' => false, 'message' => "Invalid credentials.{$remMsg}"], 401);
    }

    // Clear rate limit counters upon successful authentication
    clearRateLimit('login_user', $user['username']);
    clearRateLimit('login_ip', $ip);


    // Transparently upgrade bcrypt cost on successful login
    // (upgrades cost=10 → cost=9 without any user disruption)
    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 9])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 9]);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        $user['password'] = $newHash;
    }
    $portal = getPortalContext();

    // Determine offline / online connectivity status
    $clientOffline = (isset($_SERVER['HTTP_X_OFFLINE']) && $_SERVER['HTTP_X_OFFLINE'] === '1');
    $isOffline = $clientOffline || !isInternetAvailable();
    $admin2faSetting = getSetting('require_admin_pos_2fa');
    // POS Admin 2FA is required only when online AND setting is NOT explicitly disabled ('0')
    $requireAdmin2fa = ($admin2faSetting !== '0') && !$isOffline;



    // Guard: Prevent POS cashiers/admins from logging into the Super Admin control portal
    if ($portal === 'superadmin' && $user['role'] !== 'super_admin') {
        jsonResponse([
            'success' => false,
            'message' => 'Access denied. Only Super Administrators can log in to the Super Admin Portal. Cashiers and Store Admins must sign in via the POS system.'
        ], 403);
    }

    // Two-Factor Authentication (2FA) for Super Admin
    if ($user['role'] === 'super_admin') {
        if ($isOffline) {
            // Super Admin direct login in Offline Mode
            ensureSession();
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            $companionPortal = ($portal === 'superadmin') ? 'pos' : 'superadmin';
            syncCompanionSession($user, $companionPortal);

            try {
                $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'superadmin_login_offline', 'user', ?, 'Super Admin logged in offline (2FA bypassed: no internet connectivity)', ?)")
                   ->execute([$user['id'], $user['username'], $user['id'], $ip]);
            } catch (Exception $e) {}

            jsonResponse([
                'success'      => true,
                'message'      => 'Signed in successfully in Offline Mode.',
                'offline_mode' => true,
                'session_id'   => session_id(),
                'user' => [
                    'id'        => $user['id'],
                    'username'  => $user['username'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role'],
                ]
            ]);
        }

        ensureSession();
        // Clear any previous fully authenticated session
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email'], $_SESSION['role']);

        // Batch-load all settings needed for SA 2FA in one cached DB query
        $saSettings = getSettings(['superadmin_email', 'owner_email', 'token_expiry_minutes', 'store_name']);
        $saEmail = $saSettings['superadmin_email'] ?: ($user['email'] ?: $saSettings['owner_email']);
        if (!$saEmail) {
            jsonResponse(['success' => false, 'message' => 'Super admin email is not configured in settings.'], 500);
        }

        // Generate 6-digit numeric verification token
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $expiryMin = intval($saSettings['token_expiry_minutes'] ?: 10);
        if ($expiryMin < 5) $expiryMin = 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

        // Invalidate any older unused 2FA tokens for this user
        $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = 'superadmin_2fa' AND is_used = 0 AND expires_at > NOW() - INTERVAL 1 DAY")
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

        // Format email (use already-cached settings)
        $storeName = $saSettings['store_name'] ?: 'ChipiPie Cafe';
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

        $masked = maskEmail($saEmail);
        $resp = [
            'success'       => true,
            'requires_2fa'  => true,
            'redirect'      => 'superadmin-panel.php',
            'message'       => "Verification code sent to $masked. Please enter the code to continue.",
            'masked_email'  => $masked,
            'expires_in'    => $expiryMin * 60,
            'email_sent'    => true
        ];

        if (DEV_MODE) {
            $resp['dev_code'] = $code;
        }

        // Return instant response to browser so 2FA screen displays in ~30ms
        jsonResponseAndContinue($resp);

        // Deliver 2FA email in background
        sendEmail($saEmail, $subject, $emailBody);
        exit;
    }

    // Two-Factor Authentication (2FA) for POS Admin (sent directly to the Admin's registered email)
    if ($user['role'] === 'admin') {
        ensureSession();
        // Clear any previous fully authenticated session
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email'], $_SESSION['role']);

        $adminEmail = trim($user['email'] ?? '');
        // Batch-load all settings needed for Admin 2FA in one cached DB query
        $adminSettings = getSettings(['superadmin_email', 'owner_email', 'token_expiry_minutes', 'store_name']);
        if (!$adminEmail || !isValidSendableEmail($adminEmail)) {
            $fallback = $adminSettings['superadmin_email'] ?: $adminSettings['owner_email'];
            if ($fallback && isValidSendableEmail($fallback)) {
                $adminEmail = $fallback;
            } else {
                jsonResponse(['success' => false, 'message' => 'No valid email address registered for this administrator account. Please contact Super Admin.'], 400);
            }
        }

        // Generate 6-digit numeric verification token
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $expiryMin = intval($adminSettings['token_expiry_minutes'] ?: 10);
        if ($expiryMin < 5) $expiryMin = 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

        // Invalidate any older unused 2FA tokens for this user
        $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = 'admin_2fa' AND is_used = 0 AND expires_at > NOW() - INTERVAL 1 DAY")
           ->execute([$user['id']]);

        // Save new 2FA token to database
        $stmtToken = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, 'admin_2fa', ?)");
        $stmtToken->execute([$code, $user['id'], $expiresAt]);

        // Save pending 2FA state in session (recipient is strictly the Admin's registered email)
        $_SESSION['pending_2fa'] = [
            'user_id'         => $user['id'],
            'username'        => $user['username'],
            'full_name'       => $user['full_name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'recipient_email' => $adminEmail,
            'code'            => $code,
            'expires_at'      => time() + ($expiryMin * 60),
            'attempts'        => 0
        ];

        // Format email directly to the Admin (use already-cached settings)
        $storeName = $adminSettings['store_name'] ?: 'ChipiPie Cafe';
        $subject   = "$storeName — Admin Login 2FA Verification Code";
        $emailBody = "
        <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <div style='display: inline-block; width: 56px; height: 56px; background: #5c3d2e; color: #f5eedc; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(92,61,46,0.2);'>🔐</div>
                <h2 style='color: #2b1f1d; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
                <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Administrator Two-Factor Authentication</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>Here is your two-factor authentication (2FA) verification code to complete your login to the <strong>POS System</strong>:</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #2b1f1d; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #5c3d2e; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> This code is single-use and will expire in <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Never share this verification code with anyone.
                </div>
                <p style='font-size: 12px; color: #a8a29e; margin-top: 18px; margin-bottom: 0; text-align: center;'>If you did not attempt to sign in to your administrator account, please change your password immediately.</p>
            </div>
        </div>";

        $masked = maskEmail($adminEmail);
        $resp = [
            'success'       => true,
            'requires_2fa'  => true,
            'role'          => 'admin',
            'message'       => "Verification code sent to your email ($masked). Please enter the code to continue.",
            'masked_email'  => $masked,
            'expires_in'    => $expiryMin * 60,
            'email_sent'    => true
        ];

        if (DEV_MODE) {
            $resp['dev_code'] = $code;
        }

        // Return instant response to browser so 2FA screen displays in ~30ms
        jsonResponseAndContinue($resp);

        // Deliver 2FA email in background
        sendEmail($adminEmail, $subject, $emailBody);
        exit;
    }

    // Standard login for other roles (e.g. cashier)
    ensureSession();
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name']= $user['full_name'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];

    $response = [
        'success'    => true,
        'message'    => 'Login successful.',
        'session_id' => session_id(),
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
    // Anti-spam rate limiting: max 10 registration attempts per 10 minutes per IP
    $ip = getClientIP();
    $spamCheck = checkRateLimit('register_ip', $ip, 10, 600, 600);
    if (!$spamCheck['allowed']) {
        $mins = ceil($spamCheck['retry_after'] / 60);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'retry_after'  => $spamCheck['retry_after'],
            'message'      => "Too many registration requests. Please wait {$mins} minute" . ($mins > 1 ? 's' : '') . " before trying again."
        ], 429);
    }

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
    // ISO 25010 — input validation: username must be 3–50 chars, letters/digits/underscore/hyphen only
    if (strlen($username) < 3 || strlen($username) > 50) {
        jsonResponse(['success' => false, 'message' => 'Username must be between 3 and 50 characters.'], 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        jsonResponse(['success' => false, 'message' => 'Username may only contain letters, numbers, underscores, and hyphens (no spaces or special characters).'], 400);
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

    // Check if username or email already exists (single query)
    $stmt = $db->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 2");
    $stmt->execute([$username, $email]);
    while ($existRow = $stmt->fetch()) {
        if (strtolower($existRow['username']) === strtolower($username)) {
            jsonResponse(['success' => false, 'message' => 'Username already taken.'], 409);
        }
        if (strtolower($existRow['email']) === strtolower($email)) {
            jsonResponse(['success' => false, 'message' => 'This email address is already registered to an existing account.'], 409);
        }
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 9]);

    $roleLabel = ucfirst($role);

    // Check if username already exists in pending requests
    $stmt = $db->prepare("SELECT id FROM admin_registration_requests WHERE username = ? AND status = 'pending'");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => "A pending registration request for this username already exists."], 409);
    }

    // Check if email already used in pending requests
    $stmt = $db->prepare("SELECT id FROM admin_registration_requests WHERE email = ? AND status = 'pending'");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address already has a pending registration request.'], 409);
    }

    recordRateLimitHit('register_ip', $ip, 10, 600, 600);
    $approvalToken = generateToken(32);
    $stmt = $db->prepare("INSERT INTO admin_registration_requests (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, approval_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $firstName, $lastName, $middleInitial, $suffix, $full_name, $email, $role, $approvalToken]);

    // Send approval email to owner (batch-load from settings cache)
    $regSettings = getSettings(['owner_email', 'store_name']);
    $ownerEmail  = $regSettings['owner_email'];
    $storeName   = $regSettings['store_name'] ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/auth.php?action=approve_admin&token=" . $approvalToken;

    $emailBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #838582; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>☕</div>
            <h2 style='color: #3a2f2d; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #888; font-size: 13px; margin: 0;'>$roleLabel Registration Request</p>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='color: #333; font-size: 14px;'>A new <strong>$roleLabel</strong> registration has been requested:</p>
            <table style='width: 100%; font-size: 14px; color: #444; margin: 16px 0;'>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Role:</td><td><strong style='color:#5c3d2e;'>$roleLabel</strong></td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Name:</td><td>$full_name</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Username:</td><td>$username</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Email:</td><td>$email</td></tr>
            </table>
            <a href='$approveUrl' style='display: block; text-align: center; background: #3a2f2d; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px;'>
                ✅ Approve $roleLabel Registration
            </a>
            <p style='font-size: 11px; color: #aaa; text-align: center; margin-top: 14px;'>If you did not expect this request, you can safely ignore this email.</p>
        </div>
    </div>";

    $response = [
        'success' => true,
        'message' => "$roleLabel registration submitted. Awaiting approval before account activation.",
        'role' => $role,
        'email_sent_to' => $ownerEmail,
        'email_delivered' => true
    ];

    if (DEV_MODE) {
        $response['dev_approve_url'] = $approveUrl;
        $response['dev_token'] = $approvalToken;
    }

    // Return instant response to browser so UI updates immediately
    jsonResponseAndContinue($response);

    // Deliver owner approval notification in background
    sendEmail($ownerEmail, "$storeName — $roleLabel Registration Approval Needed", $emailBody);
    exit;
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

    // Create the account with requested role
    $role = !empty($request['role']) ? $request['role'] : 'admin';
    $roleLabel = ucfirst($role);

    $stmt = $db->prepare("INSERT INTO users (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $request['username'], 
        $request['password'], 
        $request['first_name'] ?? null, 
        $request['last_name'] ?? null, 
        $request['middle_initial'] ?? null, 
        $request['suffix'] ?? null, 
        $request['full_name'], 
        $request['email'],
        $role
    ]);

    // Mark request as approved
    $db->prepare("UPDATE admin_registration_requests SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$request['id']]);

    // Send confirmation email to the new user
    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $confirmBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #3a2f2d;'>$storeName</h2>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='font-size: 14px; color: #333;'>🎉 <strong>{$request['full_name']}</strong>, your {$roleLabel} account has been approved!</p>
            <p style='font-size: 14px; color: #555;'>You can now log in to the POS system with your username: <strong>{$request['username']}</strong></p>
        </div>
    </div>";
    sendEmail($request['email'], "$storeName — {$roleLabel} Account Approved", $confirmBody);

    echo "
    <div style='font-family:Segoe UI,sans-serif;text-align:center;margin-top:80px;max-width:500px;margin-left:auto;margin-right:auto;'>
        <div style='background:#d4edda;border-radius:16px;padding:40px 30px;border:1px solid #b7e2c4;'>
            <div style='font-size:48px;margin-bottom:16px;'>✅</div>
            <h2 style='color:#1a5c2e;margin-bottom:8px;'>{$roleLabel} Approved!</h2>
            <p style='color:#333;font-size:15px;'><strong>{$request['full_name']}</strong> has been registered as {$role}.</p>
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

    $role = !empty($request['role']) ? $request['role'] : 'admin';
    $roleLabel = ucfirst($role);
    $ownerEmail = getSetting('owner_email');
    $storeName  = getSetting('store_name') ?: 'ChipiPie Cafe';
    $approveUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/CHIPIPIE_SYSTEM/api/auth.php?action=approve_admin&token=" . $request['approval_token'];

    $emailBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: #838582; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>☕</div>
            <h2 style='color: #3a2f2d; margin: 10px 0 5px;'>$storeName</h2>
            <p style='color: #888; font-size: 13px; margin: 0;'>$roleLabel Registration Request (Resent)</p>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='color: #333; font-size: 14px;'>A pending <strong>$roleLabel</strong> registration awaits your approval:</p>
            <table style='width: 100%; font-size: 14px; color: #444; margin: 16px 0;'>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Role:</td><td><strong style='color:#5c3d2e;'>$roleLabel</strong></td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Name:</td><td>{$request['full_name']}</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Username:</td><td>{$request['username']}</td></tr>
                <tr><td style='padding: 6px 0; font-weight: 600; color: #3a2f2d;'>Email:</td><td>{$request['email']}</td></tr>
            </table>
            <a href='$approveUrl' style='display: block; text-align: center; background: #3a2f2d; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px;'>
                ✅ Approve $roleLabel Registration
            </a>
            <p style='font-size: 11px; color: #aaa; text-align: center; margin-top: 14px;'>If you did not expect this request, you can safely ignore this email.</p>
        </div>
    </div>";

    jsonResponseAndContinue(['success' => true, 'message' => "Approval notification resent to owner."]);
    sendEmail($ownerEmail, "$storeName - $roleLabel Registration Approval Needed (Resent)", $emailBody);
    exit;
}

function handleListUsers() {
    requireAdmin();

    $db = getDB();
    $stmt = $db->query("SELECT id, username, full_name, email, role, is_active, created_at FROM users WHERE role != 'super_admin' AND username NOT IN ('superadmin', 'super_admin') ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Clean up any approved admin requests where the admin no longer exists in users
    $db->exec("DELETE FROM admin_registration_requests WHERE status='approved' AND username NOT IN (SELECT username FROM users)");

    // Exclude super admin from registration requests
    $stmt2 = $db->query("SELECT id, username, full_name, email, COALESCE(role, 'cashier') AS role, status, approval_token, created_at FROM admin_registration_requests WHERE username NOT IN ('superadmin', 'super_admin') AND LOWER(full_name) NOT LIKE '%super admin%' ORDER BY created_at DESC");
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

    // Create the account with requested role
    $role = !empty($request['role']) ? $request['role'] : 'cashier';
    $roleLabel = ucfirst($role);

    $stmt = $db->prepare("INSERT INTO users (username, password, first_name, last_name, middle_initial, suffix, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $request['username'], 
        $request['password'], 
        $request['first_name'] ?? null, 
        $request['last_name'] ?? null, 
        $request['middle_initial'] ?? null, 
        $request['suffix'] ?? null, 
        $request['full_name'], 
        $request['email'],
        $role
    ]);

    // Mark as approved
    $now = date('Y-m-d H:i:s');
    $db->prepare("UPDATE admin_registration_requests SET status = 'approved', approved_at = ? WHERE id = ?")->execute([$now, $requestId]);

    // Send confirmation email to user
    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $confirmBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #3a2f2d;'>$storeName</h2>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e0d8;'>
            <p style='font-size: 14px; color: #333;'>🎉 <strong>{$request['full_name']}</strong>, your {$roleLabel} account has been approved!</p>
            <p style='font-size: 14px; color: #555;'>You can now log in to the POS system with your username: <strong>{$request['username']}</strong></p>
        </div>
    </div>";
    jsonResponseAndContinue([
        'success' => true,
        'message' => "{$roleLabel} '{$request['full_name']}' approved and account created!"
    ]);
    sendEmail($request['email'], "$storeName — {$roleLabel} Account Approved", $confirmBody);
    exit;
}

// =====================================================
// REJECT REGISTRATION FROM UI (Admin only)
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

    $role = !empty($request['role']) ? $request['role'] : 'cashier';
    $roleLabel = ucfirst($role);

    $db->prepare("UPDATE admin_registration_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);

    jsonResponse([
        'success' => true,
        'message' => "{$roleLabel} registration request from '{$request['full_name']}' has been rejected."
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
    $portal = getPortalContext();

    if (isset($_SESSION['user_id'])) {
        // Enforce portal isolation: POS ignores superadmin sessions; Super Admin ignores POS cashier/admin sessions

        if ($portal === 'superadmin' && $_SESSION['role'] !== 'super_admin') {
            jsonResponse(['success' => true, 'loggedIn' => false]);
        }

        jsonResponse([
            'success'    => true,
            'loggedIn'   => true,
            'session_id' => session_id(),
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

        if ($portal === 'superadmin' && $pending['role'] !== 'super_admin') {
            jsonResponse(['success' => true, 'loggedIn' => false]);
        }
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
    $userId  = $pending['user_id'];

    // Check 2FA verification rate limit
    $twoFaCheck = checkRateLimit('verify_2fa', $userId, 5, 300, 300);
    if (!$twoFaCheck['allowed']) {
        unset($_SESSION['pending_2fa']);
        $mins = ceil($twoFaCheck['retry_after'] / 60);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'retry_after'  => $twoFaCheck['retry_after'],
            'message'      => "Too many failed verification attempts. Temporarily locked for {$mins} minute" . ($mins > 1 ? 's' : '') . ". Please sign in again later."
        ], 429);
    }

    // Check token in DB or session
    $stmt = $db->prepare("SELECT id FROM admin_tokens WHERE token = ? AND user_id = ? AND purpose = ? AND is_used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$code, $pending['user_id'], $purpose, $now]);
    $tokenRow = $stmt->fetch();

    $codeMatches = ($tokenRow || $code === strtoupper($pending['code']));

    if (!$codeMatches) {
        $_SESSION['pending_2fa']['attempts'] = ($pending['attempts'] + 1);
        $hit = recordRateLimitHit('verify_2fa', $userId, 5, 300, 300);
        if (!$hit['allowed']) {
            unset($_SESSION['pending_2fa']);
            jsonResponse([
                'success'      => false,
                'rate_limited' => true,
                'retry_after'  => 300,
                'message'      => 'Too many invalid 2FA attempts. Temporarily locked for 5 minutes. Please sign in again later.'
            ], 429);
        }
        $remaining = max(0, 5 - $_SESSION['pending_2fa']['attempts']);
        jsonResponse(['success' => false, 'message' => "Invalid verification code. ($remaining attempts remaining)"], 400);
    }

    // Clear 2FA rate limit on successful verification
    clearRateLimit('verify_2fa', $userId);

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

    // When super_admin logs in, synchronize companion session so both Super Admin & POS are logged in at the same time
    if ($pending['role'] === 'super_admin') {
    
    $portal = getPortalContext();
        $companionPortal = ($portal === 'superadmin') ? 'pos' : 'superadmin';
        syncCompanionSession($pending, $companionPortal);
    }

    // Log login to audit trail
    $actionName   = ($pending['role'] === 'super_admin' ? 'superadmin_login' : 'admin_login');
    $actionDetail = ($pending['role'] === 'super_admin' ? 'Super admin logged in via 2FA verification code' : 'Admin logged in via 2FA verification code');
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$_SESSION['user_id'], $_SESSION['username'], $actionName, 'user', $_SESSION['user_id'], $actionDetail, $ip]);
    } catch (Exception $e) {}

    $resp = [
        'success'    => true,
        'message'    => 'Two-factor verification successful.',
        'session_id' => session_id(),
        'user'       => [
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

    $resendCfg     = getSettings(['rate_limit_email_throttle', 'token_expiry_minutes', 'store_name']);
    $emailThrottle = intval($resendCfg['rate_limit_email_throttle'] ?: 3);
    $ip = getClientIP();
    $throttleCheck = checkRateLimit('email_resend_2fa', $ip, $emailThrottle, 600, 600);
    if (!$throttleCheck['allowed']) {
        $mins = ceil($throttleCheck['retry_after'] / 60);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'retry_after'  => $throttleCheck['retry_after'],
            'message'      => "Too many verification code requests. Please wait {$mins} minute" . ($mins > 1 ? 's' : '') . " before requesting another code."
        ], 429);
    }
    recordRateLimitHit('email_resend_2fa', $ip, $emailThrottle, 600, 600);

    $pending = $_SESSION['pending_2fa'];
    $db = getDB();

    $code = sprintf("%06d", mt_rand(100000, 999999));
    $expiryMin = intval($resendCfg['token_expiry_minutes'] ?: 10);
    if ($expiryMin < 5) $expiryMin = 10;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $purpose   = ($pending['role'] === 'super_admin' ? 'superadmin_2fa' : 'admin_2fa');

    // Invalidate old tokens for this user (scoped to recent rows using idx_expires_at)
    $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose = ? AND is_used = 0 AND expires_at > NOW() - INTERVAL 1 DAY")
       ->execute([$pending['user_id'], $purpose]);

    // Insert new token
    $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)")
       ->execute([$code, $pending['user_id'], $purpose, $expiresAt]);

    $_SESSION['pending_2fa']['code']       = $code;
    $_SESSION['pending_2fa']['expires_at'] = time() + ($expiryMin * 60);

    $recipientStr = $pending['recipient_email'];
    $recipients   = array_filter(array_map('trim', explode(',', $recipientStr)));
    $storeName    = $resendCfg['store_name'] ?: 'ChipiPie Cafe';

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
                <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Two-Factor Authentication (Resent Code)</p>
            </div>
            <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
                <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($pending['full_name']) . "</strong>,</p>
                <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>Here is your new two-factor verification code for Admin POS login:</p>
                
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: #2b1f1d; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: Consolas, Monaco, monospace; border: 2px solid #5c3d2e; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>$code</div>
                </div>

                <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4;'>
                    ⏱ <strong>Security Notice:</strong> Valid for <strong>$expiryMin minutes</strong> (" . date('g:i A', strtotime($expiresAt)) . "). Previous codes are now invalidated.
                </div>
            </div>
        </div>";
    }

    $maskedList = array_map('maskEmail', $recipients);
    $maskedStr  = implode(', ', $maskedList);

    $resp = [
        'success'    => true,
        'message'    => "A new verification code was sent to $maskedStr.",
        'expires_in' => $expiryMin * 60,
        'email_sent' => true
    ];
    if (DEV_MODE) {
        $resp['dev_code'] = $code;
    }

    // Return instant response to browser
    jsonResponseAndContinue($resp);

    // Deliver resent email in background
    foreach ($recipients as $toEmail) {
        sendEmail($toEmail, $subject, $emailBody);
    }
    exit;
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

    // Rate limit password reset requests per IP
    $emailThrottle = intval(getSetting('rate_limit_email_throttle') ?: 3);
    $ip = getClientIP();
    $forgotCheck = checkRateLimit('email_forgot', $ip, $emailThrottle, 600, 600);
    if (!$forgotCheck['allowed']) {
        $mins = ceil($forgotCheck['retry_after'] / 60);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'retry_after'  => $forgotCheck['retry_after'],
            'message'      => "Too many password reset requests. Please wait {$mins} minute" . ($mins > 1 ? 's' : '') . " before trying again."
        ], 429);
    }
    recordRateLimitHit('email_forgot', $ip, $emailThrottle, 600, 600);

    $genericSuccess = [
        'success' => true,
        'message' => 'The email has been sent. If an account matches this username or email, a reset link was sent.'
    ];

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name, email, role, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user) {
        // Even if user is not found, proceed as if sent to protect privacy
        jsonResponse($genericSuccess);
    }

    // Determine target email
    if ($user['role'] === 'super_admin') {
        $targetEmail = getSetting('superadmin_email') ?: ($user['email'] ?: getSetting('owner_email'));
    } else {
        $targetEmail = $user['email'];
    }

    if (!$targetEmail || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL) || !isValidSendableEmail($targetEmail)) {
        jsonResponse([
            'success' => false,
            'message' => 'No valid email address is registered for this account. Please contact your Super Admin to reset your password directly.'
        ], 400);
    }

    // Generate secure 64-character token
    $token     = bin2hex(random_bytes(32));
    $expiryMin = 30;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

    // Invalidate prior unused password reset tokens for this user
    $db->prepare("UPDATE admin_tokens SET is_used = 1 WHERE user_id = ? AND purpose IN ('pos_reset_link', 'password_reset') AND is_used = 0")
       ->execute([$user['id']]);

    // Store in admin_tokens
    $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, 'pos_reset_link', ?)")
       ->execute([$token, $user['id'], $expiresAt]);

    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $host      = ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $resetUrl  = "http://{$host}/CHIPIPIE_SYSTEM/chipipie-pos.php?reset_token={$token}";

    $subject   = "$storeName — Password Reset Link";
    $emailBody = "
    <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
        <div style='text-align: center; margin-bottom: 24px;'>
            <div style='display: inline-block; width: 56px; height: 56px; background: #5c3d2e; color: #f5eedc; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(92,61,46,0.2);'>🔒</div>
            <h2 style='color: #2b1f1d; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
            <p style='color: #8c7365; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>Password Reset Request</p>
        </div>
        <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: center;'>
            <p style='color: #292524; font-size: 15px; margin-top: 0; text-align: left;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p style='color: #57534e; font-size: 14px; line-height: 1.6; text-align: left;'>We received a request to reset the password for your account (<code>" . htmlspecialchars($user['username']) . "</code>). Click the button below to set a new password:</p>
            
            <div style='margin: 28px 0;'>
                <a href='$resetUrl' style='display: inline-block; background: #5c3d2e; color: #ffffff; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(92,61,46,0.3);'>🔒 Reset My Password</a>
            </div>

            <div style='background: #fdf8f4; border: 1px solid #ebd9cc; border-radius: 8px; padding: 12px 14px; color: #5c3d2e; font-size: 12px; line-height: 1.4; text-align: left;'>
                ⏱ <strong>Notice:</strong> This link is valid for <strong>$expiryMin minutes</strong>. If you did not request a password reset, you can safely ignore this email.
            </div>
        </div>
    </div>";

    jsonResponseAndContinue($genericSuccess);
    sendEmail($targetEmail, $subject, $emailBody);
    exit;
}

// =====================================================
// RESET PASSWORD WITH LINK TOKEN (POS)
// =====================================================
function handleResetPasswordLink() {
    $input    = getJsonInput();
    $token    = trim($input['token'] ?? '');
    $newPass  = trim($input['new_password'] ?? '');

    if (!$token || !$newPass) {
        jsonResponse(['success' => false, 'message' => 'Reset token and new password are required.'], 400);
    }

    $pwdErr = '';
    if (!validatePasswordComplexity($newPass, $pwdErr)) {
        jsonResponse(['success' => false, 'message' => $pwdErr], 400);
    }

    $db  = getDB();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND purpose = 'pos_reset_link' AND is_used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$token, $now]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow || empty($tokenRow['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'This reset link is invalid or has expired. Please request a new one.'], 400);
    }

    $userId = $tokenRow['user_id'];
    $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 9]);

    $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $userId]);
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = NOW() WHERE id = ?")->execute([$tokenRow['id']]);

    // Log action
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$userId, null, 'reset_password_link', 'user', $userId, 'User reset password via magic reset link', $ip]);
    } catch (Exception $e) {}

    jsonResponse(['success' => true, 'message' => 'Password has been successfully reset! You can now log in with your new password.']);
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
    $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 9]);

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

// =====================================================
// SECURITY LOCKOUT NOTIFICATIONS & WARNINGS
// =====================================================

/**
 * Send security email notice to the user's Gmail/email upon account lockout
 */
function sendUserLockoutNotification($user, $ip, $lockMins) {
    if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) return;

    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $subject   = "$storeName — Security Notice: Unauthorized Sign-In Attempts Detected";
    $timeStr   = date('F j, Y, g:i A');

    $body = "
    <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
        <div style='text-align: center; margin-bottom: 24px;'>
            <div style='display: inline-block; width: 56px; height: 56px; background: #991b1b; color: #ffffff; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(153,27,27,0.25);'>⚠️</div>
            <h2 style='color: #26221f; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
            <p style='color: #b91c1c; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;'>Security Notice — Account Temporarily Locked</p>
        </div>
        <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
            <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>We detected repeated failed sign-in attempts on your ChipiPie POS account (<code>" . htmlspecialchars($user['username']) . "</code>) using incorrect passwords.</p>
            <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>To protect your account from unauthorized access, it has been temporarily locked for <strong>$lockMins minutes</strong>.</p>
            
            <div style='background: #fef2f2; border: 1px solid #fee2e2; border-radius: 10px; padding: 14px 16px; margin: 18px 0;'>
                <div style='font-size: 12px; color: #991b1b; margin-bottom: 6px; font-weight: 700;'>Security Details:</div>
                <div style='font-size: 13px; color: #450a0a; margin-bottom: 3px;'><strong>Target Account:</strong> " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</div>
                <div style='font-size: 13px; color: #450a0a; margin-bottom: 3px;'><strong>Date & Time:</strong> $timeStr</div>
                <div style='font-size: 13px; color: #450a0a; margin-bottom: 3px;'><strong>Source IP:</strong> $ip</div>
                <div style='font-size: 13px; color: #450a0a;'><strong>Lockout Cooldown:</strong> $lockMins minutes</div>
            </div>

            <div style='background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 14px; color: #92400e; font-size: 12px; line-height: 1.4;'>
                🛡️ <strong>If this was NOT you:</strong> Someone may be attempting to guess your password. Please notify your Super Administrator immediately so your account can be safeguarded.
            </div>
            <p style='font-size: 12px; color: #a8a29e; margin-top: 18px; margin-bottom: 0; text-align: center;'>If this was you, you may sign in again once the $lockMins-minute cooldown period expires.</p>
        </div>
    </div>";

    sendEmail($user['email'], $subject, $body);
}

/**
 * Send security email notice to the Super Administrator upon account lockout
 */
function sendSuperAdminLockoutNotification($user, $ip, $lockCount, $lockMins) {
    $saEmail = getSetting('superadmin_email') ?: getSetting('owner_email');
    if (!$saEmail) return;

    $storeName    = getSetting('store_name') ?: 'ChipiPie Cafe';
    $subject      = "$storeName — [SECURITY WARNING] Account Locked: {$user['username']} (Lockout #$lockCount)";
    $timeStr      = date('F j, Y, g:i A');
    $durationDesc = ($lockCount >= 3) ? "$lockMins minutes (Extended 20-minute security lockout applied due to 3 lockouts)" : "$lockMins minutes";

    $body = "
    <div style='font-family: Segoe UI, -apple-system, BlinkMacSystemFont, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 30px; background: #faf9f7; border-radius: 16px; border: 1px solid #ede8e1;'>
        <div style='text-align: center; margin-bottom: 24px;'>
            <div style='display: inline-block; width: 56px; height: 56px; background: #1e1b18; color: #ef4444; border-radius: 14px; line-height: 56px; font-size: 26px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>🚨</div>
            <h2 style='color: #26221f; margin: 12px 0 4px; font-size: 22px; font-weight: 700;'>$storeName</h2>
            <p style='color: #dc2626; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;'>Super Admin Security Warning</p>
        </div>
        <div style='background: #ffffff; border-radius: 14px; padding: 26px; border: 1px solid #e7e2da; box-shadow: 0 2px 8px rgba(0,0,0,0.04);'>
            <p style='color: #292524; font-size: 15px; margin-top: 0;'>Hello Administrator,</p>
            <p style='color: #57534e; font-size: 14px; line-height: 1.5;'>A security lockout was triggered for user <strong>" . htmlspecialchars($user['full_name']) . "</strong> (<code>" . htmlspecialchars($user['username']) . "</code>) after consecutive failed login attempts.</p>
            
            <div style='background: #fdf2f8; border: 1px solid #fce7f3; border-radius: 10px; padding: 14px 16px; margin: 18px 0;'>
                <div style='font-size: 12px; color: #9d174d; margin-bottom: 6px; font-weight: 700;'>Lockout Breakdown:</div>
                <div style='font-size: 13px; color: #831843; margin-bottom: 3px;'><strong>Username:</strong> " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role']) . ")</div>
                <div style='font-size: 13px; color: #831843; margin-bottom: 3px;'><strong>User Email:</strong> " . htmlspecialchars($user['email'] ?: 'Not set') . "</div>
                <div style='font-size: 13px; color: #831843; margin-bottom: 3px;'><strong>Lockout Frequency:</strong> Lockout #$lockCount</div>
                <div style='font-size: 13px; color: #831843; margin-bottom: 3px;'><strong>Lockout Duration:</strong> $durationDesc</div>
                <div style='font-size: 13px; color: #831843; margin-bottom: 3px;'><strong>Source IP:</strong> $ip</div>
                <div style='font-size: 13px; color: #831843;'><strong>Time:</strong> $timeStr</div>
            </div>

            <div style='background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 8px; padding: 12px 14px; color: #166534; font-size: 12px; line-height: 1.4;'>
                ⚙️ <strong>In-System Warning:</strong> A warning has been recorded in the Super Admin System Requests and Audit Log. You can inspect or clear this lockout anytime in Super Admin Control Center &rarr; System Settings &rarr; Rate Limiting.
            </div>
        </div>
    </div>";

    sendEmail($saEmail, $subject, $body);
}

/**
 * Create a system warning request in database for Super Admin
 */
function createSecurityWarningRequest($user, $ip, $lockCount, $lockMins) {
    // Security warnings are recorded in system_logs (audit log), not system_requests
    return;
}
