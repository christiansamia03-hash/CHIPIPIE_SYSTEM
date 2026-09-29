-- =====================================================
-- ChipiPie Cafe — POS System Database
-- Created: 2026-05-02
-- Updated: 2026-05-20 — Full menu with size variants
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
  size_prices TEXT DEFAULT NULL,
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
  size VARCHAR(20) DEFAULT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ORDER ADJUSTMENTS TABLE — Void & Refund log
-- =====================================================
CREATE TABLE IF NOT EXISTS order_adjustments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  type ENUM('void','refund') NOT NULL,
  amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  reason VARCHAR(255) DEFAULT NULL,
  processed_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
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
  ('admin', '$2y$10$6Nd1EXZaeWc5ki0y5UtFs.raUqUf6NkFu.sFhNOS3viZMOLWasSmy', 'Admin', 'admin@chipipiecafe.com', 'admin', 1),
  ('cashier1', '$2y$10$GhR3nPD10tADKOSjNIfgQO0VfhU49VdbicGTMT9PH9NFHcAQStuYi', 'Staff 1', 'cashier1@chipipiecafe.com', 'cashier', 1);

-- Categories (matching actual ChipiPie Cafe menu)
INSERT INTO categories (name) VALUES
  ('Hot Coffee'),
  ('Iced Coffee'),
  ('Classic Milktea'),
  ('Virgin Mojito'),
  ('Citrus Popping Boba'),
  ('Soda Float'),
  ('Matcha Series'),
  ('Frappé'),
  ('Fork and Fun');

-- =====================================================
-- MENU ITEMS — Full ChipiPie Cafe menu with size prices
-- =====================================================

-- 1. HOT COFFEE (Regular / Upsize)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Espresso',           1, 70.00, '{"Regular":70,"Upsize":85}', 50, 10, 1),
  ('Americano',          1, 75.00, '{"Regular":75,"Upsize":95}', 50, 10, 1),
  ('Cappuccino',         1, 110.00, '{"Regular":110,"Upsize":130}', 50, 10, 1),
  ('Café Latte',         1, 110.00, '{"Regular":110,"Upsize":130}', 50, 10, 1),
  ('Café Mocha',         1, 145.00, '{"Regular":145,"Upsize":170}', 50, 10, 1),
  ('Caramel Macchiato',  1, 145.00, '{"Regular":145,"Upsize":170}', 50, 10, 1),
  ('Vietnamese Coffee',  1, 130.00, '{"Regular":130,"Upsize":150}', 50, 10, 1);

-- 2. ICED COFFEE (12oz / 16oz)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Iced Americano',          2, 70.00, '{"12oz":70,"16oz":85}', 50, 10, 1),
  ('Iced Cappuccino',         2, 105.00, '{"12oz":105,"16oz":120}', 50, 10, 1),
  ('Iced Café Latte',         2, 95.00, '{"12oz":95,"16oz":110}', 50, 10, 1),
  ('Iced Cafe Mocha',         2, 135.00, '{"12oz":135,"16oz":150}', 50, 10, 1),
  ('Iced Caramel Macchiato',  2, 140.00, '{"12oz":140,"16oz":155}', 50, 10, 1),
  ('Iced Vietnamese Coffee',  2, 105.00, '{"12oz":105,"16oz":120}', 50, 10, 1),
  ('Iced Sea Salt Latte',     2, 130.00, '{"12oz":130,"16oz":145}', 50, 10, 1),
  ('Iced Cheese Cake Coffee', 2, 135.00, '{"12oz":135,"16oz":150}', 50, 10, 1),
  ('Iced White Mocha',        2, 130.00, '{"12oz":130,"16oz":145}', 50, 10, 1),
  ('Roasted Almond Latte',    2, 140.00, '{"12oz":140,"16oz":155}', 50, 10, 1),
  ('Iced Spanish Latte',      2, 110.00, '{"12oz":110,"16oz":125}', 50, 10, 1);

-- 3. CLASSIC MILKTEA (S / M / L — same prices for all flavors)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Strawberry Milktea',            3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Dark Chocolate Milktea',        3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Cookies and Cream Milktea',     3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Hokkaido Milktea',              3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Matcha Milktea',                3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Okinawa Milktea',               3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Taro Milktea',                  3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Wintermelon Milktea',           3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Ube Milktea',                   3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Yogurt Cookies and Cream',      3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1),
  ('Vanilla Milktea',               3, 55.00, '{"S":55,"M":70,"L":85}', 50, 10, 1);

-- 4. VIRGIN MOJITO (non-alcoholic, 2 sizes)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('European Strawberry',     4, 85.00, '{"Regular":85,"Large":95}', 40, 8, 1),
  ('Fragrant Lychee',         4, 85.00, '{"Regular":85,"Large":95}', 40, 8, 1),
  ('Green Apple Campagna',    4, 85.00, '{"Regular":85,"Large":95}', 40, 8, 1),
  ('Blue Ocean',              4, 85.00, '{"Regular":85,"Large":95}', 40, 8, 1),
  ('Tropical Passion Fruit',  4, 85.00, '{"Regular":85,"Large":95}', 40, 8, 1);

-- 5. CITRUS POPPING BOBA (S / M / L)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Citrus Orange Tea',         5, 65.00, '{"S":65,"M":75,"L":85}', 40, 8, 1),
  ('Citrus Lemon Tea',          5, 65.00, '{"S":65,"M":75,"L":85}', 40, 8, 1),
  ('Citrus Guava-Strawberry',   5, 65.00, '{"S":65,"M":75,"L":85}', 40, 8, 1);

-- 6. SODA FLOAT (S / M / L)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Coke Float',      6, 55.00, '{"S":55,"M":65,"L":70}', 40, 8, 1),
  ('Rootbeer Float',  6, 55.00, '{"S":55,"M":65,"L":70}', 40, 8, 1),
  ('Sprite Float',    6, 55.00, '{"S":55,"M":65,"L":70}', 40, 8, 1);

-- 7. MATCHA SERIES (12oz / 16oz)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('White Choco Matcha',  7, 130.00, '{"12oz":130,"16oz":145}', 40, 8, 1),
  ('Sea Salt Matcha',     7, 110.00, '{"12oz":110,"16oz":125}', 40, 8, 1),
  ('Matcha Frappé',       7, 160.00, '{"12oz":160,"16oz":180}', 40, 8, 1);

-- 8. FRAPPÉ (S / M / L)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Java Chips Frappé',        8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Oreo Cookies Frappé',      8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Salted Caramel Frappé',    8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Dark Choco SeaSalt Frappé',8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Hazelnut Frappé',          8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('White Rabbit Frappé',      8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Strawberry Frappé',        8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1),
  ('Biscoff Frappé',           8, 110.00, '{"S":110,"M":130,"L":150}', 40, 8, 1);

-- 9. FORK AND FUN (Food items — some with sizes, some single)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Classic Nachos',          9, 195.00, '{"S":195,"L":335}', 30, 5, 1),
  ('Baked Nacho Dip',         9, 515.00, NULL, 20, 5, 1),
  ('Caesar Salad',            9, 230.00, NULL, 25, 5, 1),
  ('ChipiPie Fries',          9, 109.00, NULL, 40, 8, 1),
  ('Sour Cream Fries',        9, 109.00, NULL, 40, 8, 1),
  ('Cheese Fries',             9, 109.00, NULL, 40, 8, 1),
  ('Mini Heart Attack Fries', 9, 315.00, NULL, 20, 5, 1),
  ('Mac and Fries Overload',  9, 330.00, NULL, 20, 5, 1);

-- =====================================================
-- PIZZA HOUSE CATEGORIES (ids 10-16)
-- =====================================================
INSERT INTO categories (name) VALUES
  ('Pizza'),
  ('Quesadilla'),
  ('Patatas'),
  ('Pasta'),
  ('Chickenpops'),
  ('Combo'),
  ('Gipitmeal');

-- =====================================================
-- PIZZA HOUSE MENU ITEMS
-- =====================================================

-- 10. PIZZA (16 slices / 8 slices)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Delicioso Chiz',   10, 135.00, '{\"16 slices\":230,\"8 slices\":135}', 20, 3, 1),
  ('Pepperoni',        10, 180.00, '{\"16 slices\":320,\"8 slices\":180}', 20, 3, 1),
  ('Hawaiian',         10, 190.00, '{\"16 slices\":335,\"8 slices\":190}', 20, 3, 1),
  ('Bacon Mushroom',   10, 199.00, '{\"16 slices\":355,\"8 slices\":199}', 20, 3, 1),
  ('Quatro Formaggio', 10, 210.00, '{\"16 slices\":380,\"8 slices\":210}', 20, 3, 1),
  ('Supremo',          10, 210.00, '{\"16 slices\":380,\"8 slices\":210}', 20, 3, 1),
  ('Don Pepe',         10, 205.00, '{\"16 slices\":370,\"8 slices\":205}', 20, 3, 1),
  ('Spinach Cream',    10, 195.00, '{\"16 slices\":345,\"8 slices\":195}', 20, 3, 1),
  ('Chicken Alfredo',  10, 205.00, '{\"16 slices\":365,\"8 slices\":205}', 20, 3, 1),
  ('Combination',      10, 225.00, '{\"16 slices\":405,\"8 slices\":225}', 20, 3, 1),
  ('Garlic Shrimps',   10, 205.00, '{\"16 slices\":365,\"8 slices\":205}', 20, 3, 1);

-- 11. QUESADILLA
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Regular Beef Quesadilla', 11, 190.00, NULL, 20, 3, 1),
  ('Cheese Classic',          11, 180.00, NULL, 20, 3, 1),
  ('Popeye Quesadilla',       11, 205.00, NULL, 20, 3, 1),
  ('Engrande Quesadilla',     11, 335.00, NULL, 20, 3, 1);

-- 12. PATATAS
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Heart Attack Fries',     12, 615.00, NULL, 15, 3, 1),
  ('Mini Heart Attack Fries',12, 315.00, NULL, 20, 3, 1),
  ('Mac and Fries Overload', 12, 330.00, NULL, 20, 3, 1),
  ('Baked Potato',           12, 205.00, NULL, 20, 3, 1),
  ('Extra Fries',            12, 109.00, NULL, 30, 5, 1),
  ('Skinny Fries',           12, 99.00,  NULL, 30, 5, 1);

-- 13. PASTA (S / M / L)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('MR. Aji Pasta',       13, 215.00, '{\"S\":215,\"M\":395,\"L\":855}',  20, 3, 1),
  ('Mac and Cheese',      13, 235.00, '{\"S\":235,\"M\":450,\"L\":1200}', 20, 3, 1),
  ('Shrimp Aglio e Olio', 13, 215.00, '{\"S\":215,\"M\":395,\"L\":855}',  20, 3, 1),
  ('Carbonara',           13, 189.00, '{\"S\":189,\"M\":365,\"L\":815}',  20, 3, 1);

-- 14. CHICKENPOPS (flavors — Selfie/Usie/Groupfie/Familie sizes)
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Honey Sriracha',    14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Super Hot Sriracha',14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Honey Butter',      14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Honey Lemon',       14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Buffalo',           14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Soy Garlic',        14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Parmesan',          14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Korean',            14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Butter Garlic',     14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Smoked BBQ',        14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1),
  ('Salt Pepper',       14, 180.00, '{\"Selfie 200g\":180,\"Usie 400g\":339,\"Groupfie 800g\":635,\"Familie 1200g\":1100}', 30, 5, 1);

-- 15. COMBO
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Barkada Combo',      15, 639.00,  NULL, 10, 2, 1),
  ('Combo Quesadilla',   15, 375.00,  NULL, 10, 2, 1),
  ('Engrande Combo',     15, 475.00,  NULL, 10, 2, 1),
  ('Sulit ka-Tropa',     15, 410.00,  NULL, 10, 2, 1),
  ('Combo Pizza',        15, 435.00,  NULL, 10, 2, 1),
  ('Popeye Combo',       15, 415.00,  NULL, 10, 2, 1),
  ("PAMPAM'S MUKBANG",   15, 1399.00, NULL, 5,  1, 1);

-- 16. GIPITMEAL
INSERT INTO menu_items (name, category_id, price, size_prices, stock, min_stock, is_available) VALUES
  ('Gipit Combo Quesadilla', 16, 239.00, NULL, 15, 3, 1),
  ('Gipit Combo Pizza',      16, 279.00, NULL, 15, 3, 1),
  ('Gipit Combo Engrande',   16, 285.00, NULL, 15, 3, 1),
  ('Gipit Combo Popeye',     16, 265.00, NULL, 15, 3, 1);
