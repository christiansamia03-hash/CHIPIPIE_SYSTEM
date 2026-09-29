<?php
/**
 * ChipiPie Cafe — Admin Token API
 * 
 * POST ?action=request  — Request bypass token (sent to admin's email)
 * POST ?action=validate — Validate a token for privileged actions
 */
require_once __DIR__ . '/db_config.php';

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'request':  handleRequestToken(); break;
    case 'validate': handleValidateToken(); break;
    default: jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}

function handleRequestToken() {
    requireAdmin();
    session_write_close(); // Release session lock before SMTP work
    $input = getJsonInput();
    $purpose = trim($input['purpose'] ?? 'bypass');
    $db = getDB();

    $token = strtoupper(substr(generateToken(16), 0, 8));
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));

    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$token, $_SESSION['user_id'], $purpose, $expiresAt]);

    // Route email based on purpose:
    // - General bypass → owner's email (needs owner approval)
    // - Everything else → current admin's email (self-service)
    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';

    if ($purpose === 'bypass' || $purpose === 'void_orders') {
        // These require owner/manager approval — send to owner's email
        $recipientEmail = getSetting('owner_email');
        $recipientLabel = 'Owner (' . $recipientEmail . ')';
        if ($purpose === 'void_orders') {
            $bodyIntro = "<p>Admin <strong>{$_SESSION['full_name']}</strong> is requesting to <strong>void/refund orders</strong>.</p>"
                . "<p>If you approve, share this authorization code with them:</p>";
        } else {
            $bodyIntro = "<p>Admin <strong>{$_SESSION['full_name']}</strong> is requesting a general bypass token.</p>"
                . "<p>If you approve, share this token with them:</p>";
        }
    } else {
        // Operational tokens go to the admin themselves
        $recipientEmail = $_SESSION['email'];
        $recipientLabel = $_SESSION['full_name'] . ' (' . $recipientEmail . ')';
        $purposeLabels = [
            'delete_menu_item' => 'Delete Menu Item',
            'manage_users'     => 'Manage Users',
        ];
        $purposeText = $purposeLabels[$purpose] ?? $purpose;
        $bodyIntro = "<p>Hello <strong>{$_SESSION['full_name']}</strong>, your token for <strong>{$purposeText}</strong>:</p>";
    }

    $body = "<div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:0 auto;padding:30px;background:#faf9f7;border-radius:16px;'>"
        . "<h2 style='color:#3a2f2d;text-align:center;'>$storeName — Bypass Token</h2>"
        . "<div style='background:#fff;border-radius:12px;padding:24px;border:1px solid #e5e0d8;'>"
        . $bodyIntro
        . "<div style='text-align:center;margin:20px 0;'><span style='background:#3a2f2d;color:#fff;padding:16px 32px;border-radius:12px;font-size:28px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$token</span></div>"
        . "<p style='font-size:12px;color:#856404;background:#fff3cd;padding:10px;border-radius:8px;'>⏱ Expires in $expiryMin minutes (" . date('g:i A', strtotime($expiresAt)) . "). Do not share publicly.</p>"
        . "</div></div>";

    $emailSent = sendEmail($recipientEmail, "$storeName — Bypass Token ($purpose)", $body);

    $resp = [
        'success'    => true,
        'message'    => "Token sent to $recipientLabel. Expires in $expiryMin min.",
        'sent_to'    => $recipientEmail,
        'expires_at' => $expiresAt,
        'email_sent' => $emailSent,
        'is_owner'   => ($purpose === 'bypass'),
    ];

    // In dev mode, always show token on screen (since XAMPP mail often doesn't work)
    if (DEV_MODE) {
        $resp['dev_token'] = $token;
        if (!$emailSent) {
            $resp['message'] = "Email delivery failed (SMTP not configured). Token shown below. Expires in $expiryMin min.";
        }
    }

    jsonResponse($resp);
}

function handleValidateToken() {
    requireLogin();
    session_write_close(); // Release session lock before DB query
    $input = getJsonInput();
    $token = strtoupper(trim($input['token'] ?? ''));
    if (!$token) jsonResponse(['success' => false, 'message' => 'Token is required.'], 400);

    $db = getDB();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $row = $stmt->fetch();

    if (!$row) {
        $stmt2 = $db->prepare("SELECT * FROM admin_tokens WHERE token = ?");
        $stmt2->execute([$token]);
        $ex = $stmt2->fetch();
        if ($ex) {
            $msg = $ex['is_used'] ? 'Token already used.' : 'Token expired (expired: ' . $ex['expires_at'] . ', now: ' . $now . '). Request a new one.';
            jsonResponse(['success' => false, 'message' => $msg], 400);
        }
        jsonResponse(['success' => false, 'message' => 'Invalid token.'], 400);
    }

    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $row['id']]);
    jsonResponse(['success' => true, 'message' => 'Token validated. You may proceed.', 'purpose' => $row['purpose']]);
}
