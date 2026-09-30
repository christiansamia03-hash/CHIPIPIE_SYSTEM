-- =====================================================
-- Super Admin Migration — ChipiPie POS
-- Run once against chipipie_pos database
-- =====================================================

USE chipipie_pos;

-- Expand role enum to include super_admin
ALTER TABLE users
  MODIFY COLUMN role ENUM('super_admin','admin','cashier') NOT NULL DEFAULT 'cashier';

-- Audit log table
CREATE TABLE IF NOT EXISTS system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_id INT DEFAULT NULL,
  actor_username VARCHAR(50) DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  details TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- System requests table (items pending super-admin approval)
CREATE TABLE IF NOT EXISTS system_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('admin_approval','void_order','refund_order','menu_change','setting_change','user_deactivate','other') NOT NULL,
  requested_by INT DEFAULT NULL,
  requester_name VARCHAR(100) DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  payload JSON DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by INT DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  review_note TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert super admin account (password: SuperAdmin@2024)
INSERT INTO users (username, password, full_name, email, role, is_active) VALUES
  ('superadmin', '.', 'Super Administrator', 'superadmin@chipipiecafe.com', 'super_admin', 1)
ON DUPLICATE KEY UPDATE role = 'super_admin', password = '.';

-- System settings for superadmin
INSERT INTO system_settings (setting_key, setting_value) VALUES
  ('superadmin_email', 'superadmin@chipipiecafe.com'),
  ('require_superadmin_approval', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);