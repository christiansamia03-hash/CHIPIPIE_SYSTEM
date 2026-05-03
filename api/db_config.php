<?php
/**
 * ChipiPie Cafe — Database Configuration
 * 
 * Centralizes database connection and email settings.
 * Update credentials for your environment.
 */

// ============ TIMEZONE (must match MySQL) ============
date_default_timezone_set('Asia/Manila');

// ============ DATABASE CONFIG ============
define('DB_HOST', 'localhost');
define('DB_NAME', 'chipipie_pos');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

// ============ EMAIL CONFIG ============
// For production, configure SMTP. For XAMPP dev, tokens also display on-screen.
define('MAIL_FROM', 'christiansamia03@gmail.com');
define('MAIL_FROM_NAME', 'ChipiPie Cafe POS');

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
 * Check if current user is admin
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Send email (with XAMPP dev fallback)
 */
function sendEmail($to, $subject, $body) {
    $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $sent = @mail($to, $subject, $body, $headers);

    // Log for debugging
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " | To: $to | Subject: $subject | Sent: " . ($sent ? 'YES' : 'NO') . "\n";
    file_put_contents($logDir . '/email_log.txt', $logEntry, FILE_APPEND);

    // Save email content to file as backup (useful when SMTP not configured)
    $emailFile = $logDir . '/last_email.html';
    $emailContent = "<p><strong>To:</strong> $to<br><strong>Subject:</strong> $subject<br><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p><hr>" . $body;
    file_put_contents($emailFile, $emailContent);

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
