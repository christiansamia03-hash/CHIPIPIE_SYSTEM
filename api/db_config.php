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
// CHANGED: 'localhost' to '127.0.0.1' to fix the [2002] connection refusal error
define('DB_HOST', '127.0.0.1'); 
define('DB_NAME', 'chipipie_pos');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default XAMPP has no password
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
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]);
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
 * Get JSON POST body
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return $data ?: [];
}

/**
 * Start session if not already started
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
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
 * Send email via direct SMTP (bypasses sendmail / php mail())
 */
function sendEmail($to, $subject, $body) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);

    // Always save a local copy of the email
    $emailContent = "<p><strong>To:</strong> $to<br><strong>Subject:</strong> $subject<br><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p><hr>" . $body;
    file_put_contents($logDir . '/last_email.html', $emailContent);

    $sent = false;
    $smtpLog = '';

    try {
        // Open SSL socket directly to Gmail SMTP
        $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
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
 * Get a system setting
 */
function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : null;
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
function ensureRateLimitTable() {
    static $ensured = false;
    if ($ensured) return;
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

        // Ensure lockout_count exists for upgrades
        $cols = $db->query("SHOW COLUMNS FROM rate_limits LIKE 'lockout_count'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE rate_limits ADD COLUMN lockout_count INT NOT NULL DEFAULT 0 AFTER hits");
        }
        $ensured = true;
    } catch (Exception $e) {
        // Table might already exist or DB error
    }
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

// Set CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enforce global API rate limit on incoming web requests
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
    enforceApiRateLimit();
}