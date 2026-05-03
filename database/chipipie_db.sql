-- =====================================================
-- ChipiPie Cafe — POS System Database
-- Created: 2026-05-02
-- =====================================================

CREATE DATABASE IF NOT EXISTS chipipie_pos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE chipipie_pos;

-- =====================================================
-- USERS TABLE — Admin & Cashier accounts
-- =====================================================
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
) ENGINE=InnoDB;

-- =====================================================
-- ADMIN TOKENS — Bypass token system
-- =====================================================
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
) ENGINE=InnoDB;

-- =====================================================
-- ADMIN REGISTRATION REQUESTS — Pending admin approvals
-- =====================================================
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
) ENGINE=InnoDB;

-- =====================================================
-- CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- MENU ITEMS TABLE — Inventory / Products
-- =====================================================
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
) ENGINE=InnoDB;

-- =====================================================
-- ORDERS TABLE — Transaction records
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(20) NOT NULL,
  user_id INT DEFAULT NULL,
  total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'Cash',
  status VARCHAR(20) NOT NULL DEFAULT 'Completed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ORDER ITEMS TABLE — Line items per order
-- =====================================================
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL,
  subtotal DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- SYSTEM SETTINGS — Owner email, store config
-- =====================================================
CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Owner email (CHANGE THIS to the actual owner's email)
INSERT INTO system_settings (setting_key, setting_value) VALUES
  ('owner_email', 'owner@chipipiecafe.com'),
  ('store_name', 'ChipiPie Cafe'),
  ('token_expiry_minutes', '15');

-- Default users (passwords are bcrypt hashed)
-- admin / admin123
-- cashier1 / cashier123
INSERT INTO users (username, password, full_name, email, role, is_active) VALUES
  ('admin', '$2y$10$YourHashedPasswordHere1234567890abcdefghijklmnopqrstuv', 'Admin', 'admin@chipipiecafe.com', 'admin', 1),
  ('cashier1', '$2y$10$YourHashedPasswordHere1234567890abcdefghijklmnopqrstuv', 'Staff 1', 'cashier1@chipipiecafe.com', 'cashier', 1);

-- Categories
INSERT INTO categories (name) VALUES
  ('Pies'),
  ('Drinks'),
  ('Snacks'),
  ('Add-ons');

-- Menu Items (matching existing frontend data)
INSERT INTO menu_items (name, category_id, price, emoji, stock, min_stock, is_available) VALUES
  ('Buko Pie (Whole)', 1, 280.00, '🥧', 20, 5, 1),
  ('Ube Pie Slice', 1, 75.00, '🫐', 30, 8, 1),
  ('Egg Pie Slice', 1, 60.00, '🥚', 25, 8, 1),
  ('Buko Pie Slice', 1, 65.00, '🥥', 35, 10, 1),
  ('Caramel Apple Pie', 1, 90.00, '🍎', 15, 5, 1),
  ('Strawberry Pie', 1, 85.00, '🍓', 3, 5, 1),
  ('Brewed Coffee', 2, 80.00, '☕', 50, 10, 1),
  ('Iced Latte', 2, 110.00, '🧊', 40, 10, 1),
  ('Buko Juice', 2, 65.00, '🥥', 20, 5, 1),
  ('Hot Chocolate', 2, 95.00, '🍫', 30, 8, 1),
  ('Iced Tea', 2, 55.00, '🍵', 45, 10, 1),
  ('Cheese Bread', 3, 45.00, '🧀', 25, 8, 1),
  ('Ensaymada', 3, 55.00, '🍞', 20, 5, 1),
  ('Empanada', 3, 50.00, '🥟', 0, 5, 0),
  ('Whipped Cream', 4, 20.00, '🍦', 60, 15, 1),
  ('Extra Sauce', 4, 15.00, '🍯', 50, 10, 1);
