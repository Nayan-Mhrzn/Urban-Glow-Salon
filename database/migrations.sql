-- =============================================
-- Urban Glow Salon — Admin Panel Migrations
-- Run this file to add new tables/columns
-- =============================================

-- Staff-Service assignment junction table
CREATE TABLE IF NOT EXISTS staff_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_service (staff_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add staff assignment to bookings (if not exists)
-- Run: ALTER TABLE bookings ADD COLUMN staff_id INT DEFAULT NULL;
-- Run: ALTER TABLE bookings ADD FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL;

-- Settings table for dynamic site configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_name', 'Urban Glow Salon'),
('site_tagline', 'Premium Grooming For All'),
('site_email', 'info@urbanglowsalon.com'),
('site_phone', '+977 9800000000'),
('site_address', 'Kathmandu, Nepal'),
('opening_time', '09:00'),
('closing_time', '20:00'),
('payment_cod', '1'),
('payment_esewa', '0'),
('payment_khalti', '0');
