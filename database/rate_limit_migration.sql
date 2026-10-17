-- =====================================================
-- Rate Limiting Migration — ChipiPie POS
-- =====================================================

USE chipipie_pos;

CREATE TABLE IF NOT EXISTS rate_limits (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default rate limit settings if not already present
INSERT INTO system_settings (setting_key, setting_value) VALUES
  ('rate_limit_enabled', '1'),
  ('rate_limit_login_attempts', '5'),
  ('rate_limit_lockout_minutes', '5'),
  ('rate_limit_api_rpm', '120'),
  ('rate_limit_email_throttle', '3')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
