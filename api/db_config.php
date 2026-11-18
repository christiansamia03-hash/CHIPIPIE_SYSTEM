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

// ============ LOAD .ENV FILE IF AVAILABLE ============
$possibleEnvPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env'
];
foreach ($possibleEnvPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    if ((strpos($val, '"') === 0 && substr($val, -1) === '"') ||
                        (strpos($val, "'") === 0 && substr($val, -1) === "'")) {
                        $val = substr($val, 1, -1);
                    }
                    if (getenv($key) === false) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
        break;
    }
}

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
define('BREVO_API_KEY',  getenv('BREVO_API_KEY') ?: '');

// Set to true to also return token in API response (for dev/testing)
define('DEV_MODE', true);

// ============ TOKEN CONFIG ============
define('TOKEN_EXPIRY_MINUTES', 15);

// ============ CSRF PROTECTION ============
/**
 * Start session alias for backward & forward compatibility
 */
function startChipipieSession() {
    ensureSession();
}

/**
 * Generate (or retrieve) a CSRF token for the current session.
 * Token is a 32-byte cryptographically random hex string stored in $_SESSION.
 */
function getCsrfToken() {
    ensureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the X-CSRF-Token header on all state-changing (POST/PUT/DELETE) API requests.
 * Skips validation for CLI scripts, GET, HEAD, and OPTIONS.
 */
function validateCsrf() {
    if (php_sapi_name() === 'cli') return true;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'OPTIONS', 'HEAD'])) return true;

    ensureSession();
    $sent     = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected) {
        $expected = getCsrfToken();
    }
    if (!$sent || !hash_equals($expected, $sent)) {
        jsonResponse([
            'success'    => false,
            'csrf_error' => true,
            'message'    => 'Invalid or missing CSRF token. Please refresh the page and try again.'
        ], 403);
        exit;
    }
    return true;
}
/**
 * Deterministically hash an authorization token, PIN, or verification code using SHA-256 (64 hex characters).
 * Stored securely in admin_tokens table so plaintext verification codes and reset tokens are not exposed.
 */
function hashToken($rawToken) {
    if ($rawToken === null || $rawToken === '') return '';
    return hash('sha256', trim((string)$rawToken));
}

/**
 * Automatically purge expired and stale used admin tokens from the database.
 * Deletes:
 * 1) Any token where expires_at <= NOW()
 * 2) Any used token where used_at <= DATE_SUB(NOW(), INTERVAL 5 MINUTE) OR used_at IS NULL
 */
function purgeExpiredAdminTokens($pdo = null) {
    try {
        $db = $pdo ?: getDB();
        $db->exec("DELETE FROM admin_tokens WHERE expires_at <= NOW() OR (is_used = 1 AND (used_at <= DATE_SUB(NOW(), INTERVAL 5 MINUTE) OR used_at IS NULL))");
    } catch (\Throwable $e) {}
}

// ============ PDO CONNECTION ============
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $port = defined('DB_PORT') && DB_PORT ? ';port=' . DB_PORT : '';
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

    // Build candidate hosts (Primary host + Anycast static IP fallbacks)
    $hosts = [];
    if (defined('DB_HOST') && DB_HOST) {
        $hosts[] = DB_HOST;
    }
    $envFallbacks = getenv('DB_FALLBACK_HOSTS');
    if ($envFallbacks) {
        foreach (explode(',', $envFallbacks) as $fb) {
            $fb = trim($fb);
            if ($fb && !in_array($fb, $hosts)) {
                $hosts[] = $fb;
            }
        }
    }
    // TiDB Cloud Anycast static IP fallbacks (bypasses Windows/Tailscale DNS getaddrinfo drops)
    if (defined('DB_HOST') && strpos(DB_HOST, 'tidbcloud.com') !== false) {
        $tidbAnycastIps = ['75.2.106.174', '99.83.224.222'];
        foreach ($tidbAnycastIps as $ip) {
            if (!in_array($ip, $hosts)) {
                $hosts[] = $ip;
            }
        }
    }

    $lastException = null;
    foreach ($hosts as $h) {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $dsn = "mysql:host=" . $h . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

                // Probabilistic auto-purge of expired admin tokens (1 in 5 DB connections)
                if (mt_rand(1, 5) === 1) {
                    purgeExpiredAdminTokens($pdo);
                }
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
                $msg = $e->getMessage();
                // If it is a DNS resolution failure (getaddrinfo), move to IP fallback immediately
                if (strpos($msg, 'php_network_getaddresses') !== false || strpos($msg, 'getaddrinfo') !== false) {
                    break;
                }
                usleep(100000); // 100ms backoff
            }
        }
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($lastException ? $lastException->getMessage() : 'Unknown database error.')
    ]);
    exit;
}

// ============ HELPERS ============

/**
 * Send JSON response and exit
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
            ob_start('ob_gzhandler');
        }
    }
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

    // In serverless environments (Vercel, AWS Lambda), flushing early terminates/freezes
    // the container immediately, killing any background tasks. We register a shutdown
    // hook so the script runs to completion before emitting the response.
    $isServerless = !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || getenv('VERCEL') || !empty($_ENV['AWS_LAMBDA_FUNCTION_NAME']);
    if ($isServerless) {
        register_shutdown_function(function() use ($data, $statusCode) {
            if (!headers_sent()) {
                http_response_code($statusCode);
                header('Content-Type: application/json');
                echo json_encode($data);
            }
        });
        return;
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

/**
 * Start a brand-new isolated session (e.g. for login or station switching)
 * without reusing or overwriting any existing session from another tab.
 */
function startFreshSession($portal = null) {
    if ($portal === null) {
        $portal = getPortalContext();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $cookieName = ($portal === 'superadmin') ? 'CHIPIPIE_SA_SESS' : 'CHIPIPIE_POS_SESS';
    session_name($cookieName);

    $prefix = ($portal === 'superadmin') ? 'sa' : 'pos';
    $freshSid = $prefix . bin2hex(random_bytes(16));
    session_id($freshSid);
    ensureSession();
    return $freshSid;
}

function ensureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Dedicated session name: CHIPIPIE_SA_SESS for Super Admin, CHIPIPIE_POS_SESS for POS
    $portal = getPortalContext();
    $cookieName = ($portal === 'superadmin') ? 'CHIPIPIE_SA_SESS' : 'CHIPIPIE_POS_SESS';
    session_name($cookieName);

    // Prioritize explicit session ID in request header / parameter to allow multi-tab & multi-device isolation
    if (!session_id()) {
        $altId = $_SERVER['HTTP_X_SESSION_ID'] 
            ?? $_SERVER['HTTP_AUTHORIZATION'] 
            ?? ($_GET['session_id'] ?? '');
        if ($altId) {
            $altId = preg_replace('/^Bearer\s+/i', '', trim($altId));
            if (preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $altId)) {
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

    // Inactivity timeout: 30 minutes server-side limit for Super Admin
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
        $saMaxIdle = 1800; // 30 minutes
        if (isset($_SESSION['sa_last_activity']) && (time() - $_SESSION['sa_last_activity'] > $saMaxIdle)) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            return;
        }
        $_SESSION['sa_last_activity'] = time();
    }
    }
}

/**
 * Check if current user is logged in
 */
function requireLogin() {
    ensureSession();
    // Validate CSRF token first on all state-changing requests
    validateCsrf();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Not authenticated. Please log in.', 'session_expired' => true], 401);
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
        jsonResponse(['success' => false, 'message' => 'Super Admin access required.', 'session_expired' => true], 403);
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
/**
 * Check if the server has active external internet connectivity.
 * Fast check using raw DNS server IPs (bypasses local DNS resolution) with a 0.5s timeout.
 */
function isInternetAvailable($timeout = 0.5) {
    static $isOnline = null;
    if ($isOnline !== null) return $isOnline;

    // Check if client explicitly signaled offline via HTTP header
    if (isset($_SERVER['HTTP_X_OFFLINE']) && $_SERVER['HTTP_X_OFFLINE'] === '1') {
        $isOnline = false;
        return false;
    }

    $hosts = [
        ['ip' => '1.1.1.1', 'port' => 443],
        ['ip' => '8.8.8.8', 'port' => 443],
        ['ip' => '1.1.1.1', 'port' => 53],
    ];

    foreach ($hosts as $host) {
        $socket = @fsockopen($host['ip'], $host['port'], $errno, $errstr, $timeout);
        if ($socket) {
            fclose($socket);
            $isOnline = true;
            return true;
        }
    }

    $isOnline = false;
    return false;
}

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
    // Verify domain has active DNS MX or A records on the internet ONLY if internet is available
    if (isInternetAvailable() && function_exists('checkdnsrr')) {
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
 * Send email via SMTP — uses cURL SMTPS (works in Vercel serverless + local),
 * with stream_socket_client fallback and multi-recipient support.
 * 
 * @param string|array $to Single email or array of recipient emails
 * @param string $subject
 * @param string $body HTML body
 * @return bool
 */
function sendEmail($to, $subject, $body) {
    // Normalize recipients
    if (is_array($to)) {
        $recipients = array_values(array_filter(array_unique(array_map('trim', $to)), 'isValidSendableEmail'));
    } else {
        $clean = trim((string)$to);
        $recipients = isValidSendableEmail($clean) ? [$clean] : [];
    }

    if (empty($recipients)) {
        return false;
    }

    // Determine writable log directory (safe for Vercel read-only filesystem)
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir) || !is_writable($logDir)) {
        $logDir = sys_get_temp_dir() . '/chipipie_logs';
    }
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

    // Save local copy of last email (best-effort)
    $emailContent = "<p><strong>To:</strong> " . htmlspecialchars(implode(', ', $recipients)) . "<br><strong>Subject:</strong> " . htmlspecialchars($subject) . "<br><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p><hr>" . $body;
    @file_put_contents($logDir . '/last_email.html', $emailContent);

    // Build RFC2822 MIME message
    $message  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $message .= "To: " . implode(', ', $recipients) . "\r\n";
    $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $body . "\r\n";

    $sent = false;
    $debugLog = '';

    // ── Method 0: HTTPS REST API (Port 443 — Bypasses DigitalOcean & Cloud Port Blocks) ──
    $brevoKey = defined('BREVO_API_KEY') && BREVO_API_KEY ? BREVO_API_KEY : (getenv('BREVO_API_KEY') ?: getSetting('brevo_api_key'));
    if (!empty($brevoKey) && function_exists('curl_init')) {
        try {
            $payload = [
                'sender' => [
                    'name'  => MAIL_FROM_NAME,
                    'email' => MAIL_FROM
                ],
                'to' => array_map(function($e) { return ['email' => $e]; }, $recipients),
                'subject' => $subject,
                'htmlContent' => $body
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: ' . $brevoKey,
                'content-type: application/json'
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $sent = true;
                $debugLog .= "Method 0 (Brevo HTTPS 443): SUCCESS ($httpCode)\n";
            } else {
                $debugLog .= "Method 0 (Brevo HTTPS 443) failed ($httpCode): $response $curlErr\n";
            }
        } catch (Throwable $e) {
            $debugLog .= "Method 0 Exception: " . $e->getMessage() . "\n";
        }
    }

    // ── Method 1: cURL SMTPS on Port 465 (Native SSL, works on Vercel + Local) ──
    if (!$sent && function_exists('curl_init')) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'smtps://smtp.gmail.com:465');
            curl_setopt($ch, CURLOPT_USERNAME, SMTP_USER);
            curl_setopt($ch, CURLOPT_PASSWORD, SMTP_PASS);
            curl_setopt($ch, CURLOPT_MAIL_FROM, '<' . MAIL_FROM . '>');
            $rcptList = array_map(function($e) { return '<' . $e . '>'; }, $recipients);
            curl_setopt($ch, CURLOPT_MAIL_RCPT, $rcptList);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_UPLOAD, true);

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $message);
            rewind($stream);
            curl_setopt($ch, CURLOPT_INFILE, $stream);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($message));

            $res = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_errno($ch);
            curl_close($ch);
            fclose($stream);

            if ($res && !$err) {
                $sent = true;
                $debugLog .= "Method 1 (cURL SMTPS 465): SUCCESS\n";
            } else {
                $debugLog .= "Method 1 (cURL SMTPS 465) failed: ($code) $err\n";
            }
        } catch (Throwable $e) {
            $debugLog .= "Method 1 Exception: " . $e->getMessage() . "\n";
        }
    }

    // ── Method 2: cURL SMTP on Port 587 with STARTTLS (Fallback) ──
    if (!$sent && function_exists('curl_init')) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'smtp://smtp.gmail.com:587');
            curl_setopt($ch, CURLOPT_USERNAME, SMTP_USER);
            curl_setopt($ch, CURLOPT_PASSWORD, SMTP_PASS);
            curl_setopt($ch, CURLOPT_MAIL_FROM, '<' . MAIL_FROM . '>');
            $rcptList = array_map(function($e) { return '<' . $e . '>'; }, $recipients);
            curl_setopt($ch, CURLOPT_MAIL_RCPT, $rcptList);
            curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_UPLOAD, true);

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $message);
            rewind($stream);
            curl_setopt($ch, CURLOPT_INFILE, $stream);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($message));

            $res = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_errno($ch);
            curl_close($ch);
            fclose($stream);

            if ($res && !$err) {
                $sent = true;
                $debugLog .= "Method 2 (cURL STARTTLS 587): SUCCESS\n";
            } else {
                $debugLog .= "Method 2 (cURL STARTTLS 587) failed: ($code) $err\n";
            }
        } catch (Throwable $e) {
            $debugLog .= "Method 2 Exception: " . $e->getMessage() . "\n";
        }
    }

    // ── Method 3: stream_socket_client (Port 465, with verify_peer disabled) ──
    if (!$sent) {
        try {
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            $socket = @stream_socket_client('ssl://smtp.gmail.com:465', $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
            if ($socket) {
                stream_set_timeout($socket, 5);

                $read = function() use ($socket, &$debugLog) {
                    $r = '';
                    while ($line = fgets($socket, 515)) {
                        $debugLog .= $line;
                        $r .= $line;
                        if (substr($line, 3, 1) === ' ') break;
                    }
                    return $r;
                };
                $write = function($cmd) use ($socket, &$debugLog) {
                    $debugLog .= "> $cmd";
                    fputs($socket, $cmd);
                };

                $read(); // 220
                $write("EHLO localhost\r\n");       $read();
                $write("AUTH LOGIN\r\n");           $read();
                $write(base64_encode(SMTP_USER) . "\r\n"); $read();
                $write(base64_encode(SMTP_PASS) . "\r\n"); $resp = $read();

                if (strpos($resp, '235') !== false) {
                    $write("MAIL FROM:<" . MAIL_FROM . ">\r\n"); $read();
                    foreach ($recipients as $rTo) {
                        $write("RCPT TO:<$rTo>\r\n"); $read();
                    }
                    $write("DATA\r\n"); $read();
                    $write($message . ".\r\n");
                    $dResp = $read();
                    if (strpos($dResp, '250') !== false) {
                        $sent = true;
                        $debugLog .= "Method 3 (stream_socket 465): SUCCESS\n";
                    }
                }
                $write("QUIT\r\n");
                fclose($socket);
            } else {
                $debugLog .= "Method 3 connection failed: $errstr ($errno)\n";
            }
        } catch (Throwable $e) {
            $debugLog .= "Method 3 Exception: " . $e->getMessage() . "\n";
        }
    }

    // Log outcome
    $logEntry = date('Y-m-d H:i:s') . " | To: " . implode(', ', $recipients) . " | Subject: $subject | Sent: " . ($sent ? 'YES' : 'NO') . "\n";
    @file_put_contents($logDir . '/email_log.txt', $logEntry, FILE_APPEND);
    @file_put_contents($logDir . '/smtp_debug.log', date('Y-m-d H:i:s') . "\n" . $debugLog . "\n---\n", FILE_APPEND);

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
 * Get a system setting (cached â€” only one DB query per PHP request regardless of how many times called)
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

// Set CORS headers â€” only allow same-host and known Vercel deployment origins.
// Never echo back arbitrary $origin; wildcard is disallowed when credentials are used.
$origin         = $_SERVER['HTTP_ORIGIN'] ?? '';
$host           = $_SERVER['HTTP_HOST']   ?? '';
$allowedOrigins = [
    'http://'  . $host,
    'https://' . $host,
    'http://localhost',
    'https://localhost',
    'http://127.0.0.1',
    'https://127.0.0.1',
];
if ($origin && in_array(rtrim($origin, '/'), $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} elseif (!$origin) {
    // Same-origin request (no Origin header) â€” no CORS header needed
} else {
    // Unknown origin â€” reject preflight, allow response without credentials
    header('Access-Control-Allow-Origin: null');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Id, X-Requested-With, X-Portal, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enforce global API rate limit on incoming web requests
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
    enforceApiRateLimit();
}