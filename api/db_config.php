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

// Set CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}