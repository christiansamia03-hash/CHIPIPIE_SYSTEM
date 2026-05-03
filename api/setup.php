<?php
/**
 * ChipiPie Cafe — Database Setup Script
 * 
 * Run this once to create the database and seed data.
 * Access: http://localhost/CHIPIPIE_SYSTEM/api/setup.php
 */

// Database credentials (no database name yet)
$host = 'localhost';
$user = 'root';
$pass = '';

// Owner email — CHANGE THIS to the actual owner's email
$ownerEmail = 'owner@chipipiecafe.com';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS chipipie_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE chipipie_pos");

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            user_id INT DEFAULT NULL,
            purpose VARCHAR(100) NOT NULL DEFAULT 'bypass',
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_registration_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            approval_token VARCHAR(64) NOT NULL UNIQUE,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            approved_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category_id INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            emoji VARCHAR(10) DEFAULT '🍽️',
            image TEXT DEFAULT NULL,
            stock INT NOT NULL DEFAULT 0,
            min_stock INT NOT NULL DEFAULT 5,
            is_available TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(20) NOT NULL,
            user_id INT DEFAULT NULL,
            total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'Cash',
            status VARCHAR(20) NOT NULL DEFAULT 'Completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10, 2) NOT NULL,
            subtotal DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // Seed settings
    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
        ('owner_email', '$ownerEmail'),
        ('store_name', 'ChipiPie Cafe'),
        ('token_expiry_minutes', '15')
    ");

    // Seed users with properly hashed passwords
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $cashierHash = password_hash('cashier123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute(['admin', $adminHash, 'Admin', 'admin@chipipiecafe.com', 'admin']);
    $stmt->execute(['cashier1', $cashierHash, 'Staff 1', 'cashier1@chipipiecafe.com', 'cashier']);

    // Seed categories
    $pdo->exec("INSERT IGNORE INTO categories (name) VALUES ('Pies'), ('Drinks'), ('Snacks'), ('Add-ons')");

    // Seed menu items
    $menuData = [
        ['Buko Pie (Whole)', 1, 280, '🥧', 20, 5],
        ['Ube Pie Slice', 1, 75, '🫐', 30, 8],
        ['Egg Pie Slice', 1, 60, '🥚', 25, 8],
        ['Buko Pie Slice', 1, 65, '🥥', 35, 10],
        ['Caramel Apple Pie', 1, 90, '🍎', 15, 5],
        ['Strawberry Pie', 1, 85, '🍓', 3, 5],
        ['Brewed Coffee', 2, 80, '☕', 50, 10],
        ['Iced Latte', 2, 110, '🧊', 40, 10],
        ['Buko Juice', 2, 65, '🥥', 20, 5],
        ['Hot Chocolate', 2, 95, '🍫', 30, 8],
        ['Iced Tea', 2, 55, '🍵', 45, 10],
        ['Cheese Bread', 3, 45, '🧀', 25, 8],
        ['Ensaymada', 3, 55, '🍞', 20, 5],
        ['Empanada', 3, 50, '🥟', 0, 5],
        ['Whipped Cream', 4, 20, '🍦', 60, 15],
        ['Extra Sauce', 4, 15, '🍯', 50, 10],
    ];

    $check = $pdo->query("SELECT COUNT(*) as cnt FROM menu_items")->fetch();
    if ($check['cnt'] == 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, category_id, price, emoji, stock, min_stock, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($menuData as $m) {
            $stmt->execute([$m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[4] > 0 ? 1 : 0]);
        }
    }

    // Output success page
    echo '<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Setup Complete</title></head>
    <body style="font-family:Segoe UI,sans-serif;background:#faf9f7;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">
    <div style="background:#fff;border-radius:16px;padding:40px;max-width:500px;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.1);border:1px solid #e5e0d8;">
        <div style="font-size:48px;margin-bottom:16px;">✅</div>
        <h2 style="color:#1a5c2e;margin-bottom:8px;">Database Setup Complete!</h2>
        <p style="color:#555;font-size:14px;margin-bottom:20px;">The <strong>chipipie_pos</strong> database has been created with all tables and seed data.</p>
        <div style="background:#f9f9f9;border-radius:10px;padding:16px;text-align:left;font-size:13px;color:#444;border:1px solid #eee;">
            <strong>Default Accounts:</strong><br>
            Admin: <code>admin</code> / <code>admin123</code><br>
            Cashier: <code>cashier1</code> / <code>cashier123</code><br><br>
            <strong>Owner Email:</strong> <code>' . $ownerEmail . '</code><br>
            <small style="color:#888;">(Change in system_settings table or db_config.php)</small>
        </div>
        <a href="../chipipie-pos.html" style="display:inline-block;margin-top:20px;background:#3a2f2d;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:600;">
            Go to POS System →
        </a>
    </div>
    </body></html>';

} catch (PDOException $e) {
    echo '<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Setup Error</title></head>
    <body style="font-family:Segoe UI,sans-serif;background:#fef1f1;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">
    <div style="background:#fff;border-radius:16px;padding:40px;max-width:500px;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.1);border:1px solid #f5c6cb;">
        <div style="font-size:48px;margin-bottom:16px;">❌</div>
        <h2 style="color:#842029;">Setup Failed</h2>
        <p style="color:#555;font-size:14px;">' . htmlspecialchars($e->getMessage()) . '</p>
        <p style="color:#888;font-size:12px;margin-top:16px;">Make sure MySQL is running in XAMPP.</p>
    </div>
    </body></html>';
}
