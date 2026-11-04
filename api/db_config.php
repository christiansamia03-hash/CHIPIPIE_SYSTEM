<?php
/**
 * ChipiPie Cafe - Database Configuration
 * Centralizes database connection and email settings.
 * Update credentials for your environment.
 */

// Suppress PHP notices/warnings from polluting JSON API responses
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// ============ TIMEZONE (must match MySQL) ============
date_default_timezone_set('Asia/Manila');

// ============ DATABASE CONFIG ============
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1'); 
define('DB_NAME', getenv('DB_NAME') ?: 'chipipie_pos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// ============ EMAIL CONFIG ============
define('MAIL_FROM',      'christiansamia03@gmail.com');
define('MAIL_FROM_NAME', 'ChipiPie Cafe POS');
define('SMTP_HOST',      'ssl://smtp.gmail.com');
define('SMTP_PORT',      465);
define('SMTP_USER',      'christiansamia03@gmail.com');
define('SMTP_PASS',      'sfuvrhxavoqdztbe');   // Gmail App Password (no spaces)

// Set to true to also return token in API response (for dev/testing)
define('DEV_MODE', true);

// ============ TOKEN CONFIG ============
define('TOKEN_EXPIRY_MINUTES', 15);

// ============ PDO CONNECTION ============
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $port = defined('DB_PORT') && DB_PORT ? ';port=' . DB_PORT : '';
            $dsn = "mysql:host=" . DB_HOST . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ];
            if (getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1' || (defined('DB_PORT') && DB_PORT == 4000)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = true;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

// ============ HELPERS ============

/**
 * Send JSON response and exit
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send JSON response immediately to browser and close HTTP connection,
 * allowing long-running tasks (such as SMTP email delivery) to complete in the background.
 */
function jsonResponseAndContinue($data, $statusCode = 200) {
    if (headers_sent()) {
        jsonResponse($data, $statusCode);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    ignore_user_abort(true);
    set_time_limit(60);

    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Connection: close');

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    ob_start();
    echo json_encode($data);
    $size = ob_get_length();
    header("Content-Length: {$size}");
    ob_end_flush();
    @ob_flush();
    flush();

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

/**
 * Get JSON POST body
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return $data ?: [];
}

/**
 * Ensure sessions table exists in TiDB/MySQL
 */
function ensureSessionTable($force = false) {
    static $ensured = true; // Optimistic: session table already verified in DB
    if ($ensured && !$force) return;
    $ensured = true;
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS php_sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMTEXT NOT NULL,
            last_activity INT NOT NULL,
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

/**
 * Ensure role column exists in admin_registration_requests
 */
function ensureRegistrationRequestsRoleColumn($pdo = null) {
    static $ensured = false;
    if ($ensured) return;
    try {
        $db = $pdo ?: getDB();
        $cols = $db->query("SHOW COLUMNS FROM admin_registration_requests LIKE 'role'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE admin_registration_requests ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cashier' AFTER email");
        }
        $ensured = true;
    } catch (Exception $e) {}
}

/**
 * Register DB-backed session handler using TiDB / MySQL
 * This enables session persistence across Vercel serverless Lambda instances!
 */
function registerDbSessionHandler() {
    static $registered = false;
    if ($registered) return;
    $registered = true;

    ensureSessionTable();

    session_set_save_handler(
        function($savePath, $sessionName) {
            return true;
        },
        function() {
            return true;
        },
        function($sessionId) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT data FROM php_sessions WHERE id = ?");
                $stmt->execute([$sessionId]);
                $row = $stmt->fetch();
                return ($row && isset($row['data'])) ? (string)$row['data'] : '';
            } catch (Exception $e) {
                return '';
            }
        },
        function($sessionId, $data) {
            try {
                $db = getDB();
                $stmt = $db->prepare("REPLACE INTO php_sessions (id, data, last_activity) VALUES (?, ?, ?)");
                return $stmt->execute([$sessionId, $data, time()]);
            } catch (Exception $e) {
                return false;
            }
        },
        function($sessionId) {
            try {
                $db = getDB();
                return $db->prepare("DELETE FROM php_sessions WHERE id = ?")->execute([$sessionId]);
            } catch (Exception $e) {
                return false;
            }
        },
        function($maxlifetime) {
            try {
                $db = getDB();
                $expire = time() - $maxlifetime;
                return $db->prepare("DELETE FROM php_sessions WHERE last_activity < ?")->execute([$expire]);
            } catch (Exception $e) {
                return false;
            }
        }
    );
}

/**
 * Detect whether current request belongs to the dedicated Super Admin Portal or the POS System
 */
function getPortalContext() {
    // 1. Explicit Header sent by fetch / AJAX
    $header = strtolower(trim($_SERVER['HTTP_X_PORTAL'] ?? ''));
    if ($header === 'superadmin' || $header === 'sa') return 'superadmin';
    if ($header === 'pos') return 'pos';

    // 2. Query or Post parameter
    $param = strtolower(trim($_GET['portal'] ?? ($_POST['portal'] ?? '')));
    if ($param === 'superadmin' || $param === 'sa') return 'superadmin';
    if ($param === 'pos') return 'pos';

    // 3. Redirect parameter
    $redirect = strtolower(trim($_GET['redirect'] ?? ''));
    if (strpos($redirect, 'superadmin') !== false) return 'superadmin';

    // 4. Script filename or Request URI inspection
    $script = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
    $uri    = strtolower($_SERVER['REQUEST_URI'] ?? '');
    if (strpos($script, 'superadmin') !== false || strpos($uri, 'superadmin') !== false) {
        return 'superadmin';
    }

    return 'pos';
}

/**
 * Start session with TiDB persistence, distinct session isolation (POS vs Super Admin), and cookie/header support
 */
/**
 * Synchronize a companion session for the other portal so Super Admin can be logged into both portals simultaneously
 */
function syncCompanionSession($user, $targetPortal) {
    $sessName = ($targetPortal === 'superadmin') ? 'CHIPIPIE_SA_SESS' : 'CHIPIPIE_POS_SESS';
    $prefix   = ($targetPortal === 'superadmin') ? 'sa' : 'pos';
    $newSid   = $prefix . bin2hex(random_bytes(16));
    $userId   = $user['id'] ?? ($user['user_id'] ?? 0);
    $data = serialize([
        'user_id'   => $userId,
        'username'  => $user['username'] ?? '',
        'full_name' => $user['full_name'] ?? '',
        'email'     => $user['email'] ?? '',
        'role'      => $user['role'] ?? 'super_admin'
    ]);
    try {
        $db = getDB();
        $db->prepare("REPLACE INTO php_sessions (id, data, last_activity) VALUES (?, ?, ?)")
           ->execute([$newSid, $data, time()]);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie($sessName, $newSid, [
            'expires'  => time() + 86400 * 7,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {}
    return $newSid;
}
function ensureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Dedicated session name: CHIPIPIE_SA_SESS for Super Admin, CHIPIPIE_POS_SESS for POS
    $portal = getPortalContext();
    $cookieName = ($portal === 'superadmin') ? 'CHIPIPIE_SA_SESS' : 'CHIPIPIE_POS_SESS';
    session_name($cookieName);

    // Check for session ID in header or query param if cookie is missing
    if (empty($_COOKIE[$cookieName])) {
        $altId = $_SERVER['HTTP_X_SESSION_ID'] 
            ?? $_SERVER['HTTP_AUTHORIZATION'] 
            ?? ($_GET['session_id'] ?? '');
        if ($altId) {
            $altId = preg_replace('/^Bearer\s+/i', '', trim($altId));
            if (preg_match('/^[a-zA-Z0-9,_-]{16,128}$/', $altId)) {
                session_id($altId);
            }
        }
    }

    registerDbSessionHandler();

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        @session_start();
    }
}

/**
 * Check if current user is logged in
 */
function requireLogin() {
    ensureSession();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Not authenticated. Please log in.'], 401);
    }
}

/**
 * Check if current user is admin (or super_admin)
 */
function requireAdmin() {
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin', 'super_admin'])) {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }
}

/**
 * Check if current user is super_admin
 */
function requireSuperAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'super_admin') {
        jsonResponse(['success' => false, 'message' => 'Super Admin access required.'], 403);
    }
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate password complexity:
 * - Minimum 8 characters
 * - At least one uppercase letter (A-Z)
 * - At least one lowercase letter (a-z)
 * - At least one number (0-9)
 */
function validatePasswordComplexity($password, &$error = '') {
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter (A-Z).';
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter (a-z).';
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number (0-9).';
        return false;
    }
    return true;
}

/**
 * Check if email address is valid and not a reserved/dummy placeholder domain
 */
function isValidSendableEmail($email) {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $parts = explode('@', $email);
    if (count($parts) !== 2) return false;
    $domain = strtolower(end($parts));

    // Filter placeholder, reserved, and unroutable default demo domains
    $dummyDomains = [
        'chipipiecafe.com',
        'example.com',
        'example.org',
        'example.net',
        'localhost',
        'test.com',
        'domain.com',
        'sample.com'
    ];
    if (in_array($domain, $dummyDomains)) return false;
    if (str_ends_with($domain, '.chipipiecafe.com') || str_ends_with($domain, '.example.com')) return false;

    // Verify domain has active DNS MX or A records on the internet
    // Cache results per-domain within this request to avoid duplicate DNS round-trips
    if (function_exists('checkdnsrr')) {
        static $dnsCache = [];
        if (!array_key_exists($domain, $dnsCache)) {
            $dnsCache[$domain] = (@checkdnsrr($domain, 'MX') || @checkdnsrr($domain, 'A'));
        }
        if (!$dnsCache[$domain]) {
            return false;
        }
    }

    return true;
}

/**
 * Send email via direct SMTP (bypasses sendmail / php mail())
 */
function sendEmail($to, $subject, $body) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);

    // Filter out dummy/example addresses to prevent mail bouncebacks
    if (!isValidSendableEmail($to)) {
        file_put_contents($logDir . '/email_log.txt', date('Y-m-d H:i:s') . " | To: $to | Skipped sending (invalid or example.com dummy domain) | Sent: NO\n", FILE_APPEND);
        return false;
    }

    // Always save a local copy of the email
    $emailContent = "<p><strong>To:</strong> $to<br><strong>Subject:</strong> $subject<br><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p><hr>" . $body;
    file_put_contents($logDir . '/last_email.html', $emailContent);

    $sent = false;
    $smtpLog = '';

    try {
        // Open SSL socket directly to Gmail SMTP
        $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 8);
        if ($socket) stream_set_timeout($socket, 8);
        if (!$socket) throw new Exception("Socket error $errno: $errstr");

        $read = function() use ($socket, &$smtpLog) {
            $r = '';
            while ($line = fgets($socket, 515)) {
                $smtpLog .= $line;
                $r .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $r;
        };
        $write = function($cmd) use ($socket, &$smtpLog) {
            $smtpLog .= "> $cmd";
            fputs($socket, $cmd);
        };

        $read(); // 220 greeting
        $write("EHLO localhost\r\n");       $read();
        $write("AUTH LOGIN\r\n");           $read();
        $write(base64_encode(SMTP_USER) . "\r\n"); $read();
        $write(base64_encode(SMTP_PASS) . "\r\n"); $resp = $read();

        if (strpos($resp, '235') === false) throw new Exception('AUTH failed: ' . trim($resp));

        $write("MAIL FROM:<" . MAIL_FROM . ">\r\n"); $read();
        $write("RCPT TO:<$to>\r\n");                 $read();
        $write("DATA\r\n");                           $read();

        $boundary = md5(time());
        $message  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $message .= "To: $to\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n" . $body . "\r\n.\r\n";
        $write($message);
        $dResp = $read();

        if (strpos($dResp, '250') === false) throw new Exception('DATA failed: ' . trim($dResp));

        $write("QUIT\r\n");
        fclose($socket);
        $sent = true;

    } catch (Exception $e) {
        $smtpLog .= "EXCEPTION: " . $e->getMessage() . "\n";
    }

    // Log result
    $logEntry = date('Y-m-d H:i:s') . " | To: $to | Subject: $subject | Sent: " . ($sent ? 'YES' : 'NO') . "\n";
    file_put_contents($logDir . '/email_log.txt', $logEntry, FILE_APPEND);
    file_put_contents($logDir . '/smtp_debug.log', date('Y-m-d H:i:s') . "\n" . $smtpLog . "\n---\n", FILE_APPEND);

    return $sent;
}

/**
 * Load all system settings into a static in-memory cache (one DB round-trip per request).
 * @internal Called lazily on the first getSetting() / getSettings() call.
 */
function _loadSettingsCache() {
    static $cache = null;
    static $loaded = false;
    if ($loaded) return $cache;
    $loaded = true;
    try {
        $db = getDB();
        $rows = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
        $cache = [];
        foreach ($rows as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}

/**
 * Get a system setting (cached — only one DB query per PHP request regardless of how many times called)
 */
function getSetting($key) {
    $cache = _loadSettingsCache();
    return $cache[$key] ?? null;
}

/**
 * Get multiple system settings at once from the cache.
 * Returns an associative array of key => value. Missing keys return the provided default.
 */
function getSettings(array $keys, $default = null) {
    $cache = _loadSettingsCache();
    $result = [];
    foreach ($keys as $k) {
        $result[$k] = $cache[$k] ?? $default;
    }
    return $result;
}

// =====================================================
// RATE LIMITING SYSTEM
// =====================================================

/**
 * Get client IP address reliably across proxies / Cloudflare / local network
 */
function getClientIP() {
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Ensure rate_limits table exists in database
 */
function ensureRateLimitTable($force = false) {
    static $ensured = true; // Optimistic: table & columns already verified in DB
    if ($ensured && !$force) return;
    $ensured = true;
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rate_key VARCHAR(128) NOT NULL,
            action VARCHAR(64) NOT NULL,
            identifier VARCHAR(64) NOT NULL,
            hits INT NOT NULL DEFAULT 1,
            lockout_count INT NOT NULL DEFAULT 0,
            first_hit_at DATETIME NOT NULL,
            last_hit_at DATETIME NOT NULL,
            locked_until DATETIME DEFAULT NULL,
            INDEX idx_rate_key (rate_key),
            INDEX idx_action_id (action, identifier),
            INDEX idx_locked_until (locked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

/**
 * Check if rate limiting is enabled
 */
function isRateLimitEnabled() {
    $val = getSetting('rate_limit_enabled');
    return $val === null || $val === '1' || $val === 1 || $val === true || $val === 'true';
}

/**
 * Clean up stale rate limit entries older than 24 hours (run opportunistically)
 */
function cleanupExpiredRateLimits() {
    if (mt_rand(1, 40) === 1) {
        try {
            $db = getDB();
            $db->exec("DELETE FROM rate_limits WHERE (locked_until IS NOT NULL AND locked_until < NOW() - INTERVAL 2 HOUR) OR (locked_until IS NULL AND last_hit_at < NOW() - INTERVAL 24 HOUR)");
        } catch (Exception $e) {}
    }
}

/**
 * Check if an action + identifier has exceeded its rate limit without recording a hit
 */
function checkRateLimit($action, $identifier, $maxAttempts, $windowSeconds, $lockoutSeconds = 0) {
    if (!isRateLimitEnabled()) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0, 'locked' => false, 'hits' => 0, 'lockout_count' => 0];
    }

    ensureRateLimitTable();
    cleanupExpiredRateLimits();
    $db = getDB();
    $rateKey = $action . ':' . strtolower(trim($identifier));

    $stmt = $db->prepare("SELECT id, hits, lockout_count, first_hit_at, last_hit_at, locked_until FROM rate_limits WHERE rate_key = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$rateKey]);
    $rec = $stmt->fetch();

    if (!$rec) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0, 'locked' => false, 'hits' => 0, 'lockout_count' => 0];
    }

    $now = time();
    $lockoutCount = intval($rec['lockout_count'] ?? 0);

    // 1. Check if currently locked
    if (!empty($rec['locked_until'])) {
        $lockedTime = strtotime($rec['locked_until']);
        if ($lockedTime > $now) {
            $retryAfter = $lockedTime - $now;
            return [
                'allowed'       => false,
                'remaining'     => 0,
                'retry_after'   => $retryAfter,
                'locked'        => true,
                'hits'          => intval($rec['hits']),
                'lockout_count' => $lockoutCount,
                'message'       => "Rate limit exceeded. Locked for {$retryAfter} seconds."
            ];
        }
    }

    // 2. Check rolling window
    $firstHit = strtotime($rec['first_hit_at']);
    $windowElapsed = $now - $firstHit;

    if ($windowElapsed > $windowSeconds) {
        // Window expired; allowed and will reset on next hit
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0, 'locked' => false, 'hits' => 0, 'lockout_count' => $lockoutCount];
    }

    $hits = intval($rec['hits']);
    if ($hits >= $maxAttempts) {
        $retryAfter = $lockoutSeconds > 0 ? $lockoutSeconds : max(1, $windowSeconds - $windowElapsed);
        return [
            'allowed'       => false,
            'remaining'     => 0,
            'retry_after'   => $retryAfter,
            'locked'        => true,
            'hits'          => $hits,
            'lockout_count' => $lockoutCount,
            'message'       => "Rate limit reached. Please wait {$retryAfter} seconds."
        ];
    }

    return [
        'allowed'       => true,
        'remaining'     => max(0, $maxAttempts - $hits),
        'retry_after'   => 0,
        'locked'        => false,
        'hits'          => $hits,
        'lockout_count' => $lockoutCount
    ];
}

/**
 * Record a hit against an action + identifier. Increments counter and sets lockout if limit is reached.
 * Supports progressive lockout (3rd lockout triggers 20-minute extended lockout).
 */
function recordRateLimitHit($action, $identifier, $maxAttempts, $windowSeconds, $lockoutSeconds = 0, $enableProgressiveLockout = false) {
    if (!isRateLimitEnabled()) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0, 'locked' => false, 'just_locked' => false, 'hits' => 1, 'lockout_count' => 0, 'locked_until' => null];
    }

    ensureRateLimitTable();
    $db = getDB();
    $rateKey = $action . ':' . strtolower(trim($identifier));
    $now = time();

    $stmt = $db->prepare("SELECT id, hits, lockout_count, first_hit_at, locked_until FROM rate_limits WHERE rate_key = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$rateKey]);
    $rec = $stmt->fetch();

    $newHits = 1;
    $prevLockoutCount = $rec ? intval($rec['lockout_count'] ?? 0) : 0;
    $newLockoutCount = $prevLockoutCount;
    $lockedUntil = null;
    $justLocked = false;
    $effectiveLockoutSecs = $lockoutSeconds > 0 ? $lockoutSeconds : $windowSeconds;

    if ($rec) {
        $firstHit = strtotime($rec['first_hit_at']);
        $windowExpired = ($now - $firstHit) > $windowSeconds;

        if ($windowExpired) {
            // New window
            $newHits = 1;
            if ($newHits >= $maxAttempts && $lockoutSeconds > 0) {
                $newLockoutCount++;
                if ($enableProgressiveLockout && $newLockoutCount >= 3) {
                    $effectiveLockoutSecs = 20 * 60; // 20 minutes
                }
                $lockedUntil = date('Y-m-d H:i:s', $now + $effectiveLockoutSecs);
                $justLocked = true;
            }
            $db->prepare("UPDATE rate_limits SET hits = ?, lockout_count = ?, first_hit_at = NOW(), last_hit_at = NOW(), locked_until = ? WHERE id = ?")
               ->execute([$newHits, $newLockoutCount, $lockedUntil, $rec['id']]);
        } else {
            // Continuing window
            $newHits = intval($rec['hits']) + 1;
            $wasAlreadyLocked = !empty($rec['locked_until']) && (strtotime($rec['locked_until']) > $now);

            if ($newHits >= $maxAttempts && $lockoutSeconds > 0) {
                if (!$wasAlreadyLocked) {
                    $newLockoutCount++;
                    $justLocked = true;
                }
                if ($enableProgressiveLockout && $newLockoutCount >= 3) {
                    $effectiveLockoutSecs = 20 * 60; // 20 minutes
                }
                $lockedUntil = date('Y-m-d H:i:s', $now + $effectiveLockoutSecs);
            }
            $db->prepare("UPDATE rate_limits SET hits = ?, lockout_count = ?, last_hit_at = NOW(), locked_until = COALESCE(?, locked_until) WHERE id = ?")
               ->execute([$newHits, $newLockoutCount, $lockedUntil, $rec['id']]);
        }
    } else {
        if ($newHits >= $maxAttempts && $lockoutSeconds > 0) {
            $newLockoutCount = 1;
            if ($enableProgressiveLockout && $newLockoutCount >= 3) {
                $effectiveLockoutSecs = 20 * 60;
            }
            $lockedUntil = date('Y-m-d H:i:s', $now + $effectiveLockoutSecs);
            $justLocked = true;
        }
        $db->prepare("INSERT INTO rate_limits (rate_key, action, identifier, hits, lockout_count, first_hit_at, last_hit_at, locked_until) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)")
           ->execute([$rateKey, $action, strtolower(trim($identifier)), $newHits, $newLockoutCount, $lockedUntil]);
    }

    $isLocked = ($newHits >= $maxAttempts);
    $cooldown = $lockedUntil ? (strtotime($lockedUntil) - $now) : ($lockoutSeconds > 0 ? $lockoutSeconds : $windowSeconds);

    return [
        'allowed'       => !$isLocked,
        'remaining'     => max(0, $maxAttempts - $newHits),
        'retry_after'   => $isLocked ? max(1, $cooldown) : 0,
        'locked'        => $isLocked,
        'just_locked'   => $justLocked,
        'hits'          => $newHits,
        'lockout_count' => $newLockoutCount,
        'locked_until'  => $lockedUntil
    ];
}

/**
 * Clear rate limit for an action + identifier (e.g. on successful login)
 */
function clearRateLimit($action, $identifier) {
    try {
        $db = getDB();
        $rateKey = $action . ':' . strtolower(trim($identifier));
        $db->prepare("DELETE FROM rate_limits WHERE rate_key = ?")->execute([$rateKey]);
    } catch (Exception $e) {}
}

/**
 * Enforce global API rate limit per client IP
 */
function enforceApiRateLimit() {
    if (!isRateLimitEnabled() || defined('SKIP_RATE_LIMIT')) {
        return;
    }

    $rpm = intval(getSetting('rate_limit_api_rpm') ?: 120);
    if ($rpm <= 0) return; // 0 disables RPM limit

    $ip = getClientIP();
    $status = checkRateLimit('api_rpm', $ip, $rpm, 60, 60);

    header('X-RateLimit-Limit: ' . $rpm);
    header('X-RateLimit-Remaining: ' . max(0, $status['remaining']));

    if (!$status['allowed']) {
        header('Retry-After: ' . $status['retry_after']);
        jsonResponse([
            'success'      => false,
            'rate_limited' => true,
            'message'      => "Too many API requests. Please wait {$status['retry_after']}s before making more requests.",
            'retry_after'  => $status['retry_after']
        ], 429);
    }

    // Record hit for this IP
    recordRateLimitHit('api_rpm', $ip, $rpm, 60, 60);
}

// Set CORS headers for API access (supports credentials & X-Session-Id for serverless Vercel)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Id, X-Requested-With, X-Portal');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enforce global API rate limit on incoming web requests
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
    enforceApiRateLimit();
}