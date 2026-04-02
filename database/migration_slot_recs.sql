-- =============================================
-- Migration: Adaptive Slot Recommendation System
-- Urban Glow Salon
-- Run: mysql -u root urban_glow_salon < migration_slot_recs.sql
-- Safe to re-run (idempotent)
-- =============================================

-- 1. Add outcome tracking + day_of_week to existing bookings table
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_booking_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'outcome') THEN
        ALTER TABLE bookings ADD COLUMN outcome ENUM('completed','cancelled','no_show') DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'day_of_week') THEN
        ALTER TABLE bookings ADD COLUMN day_of_week TINYINT DEFAULT NULL;
    END IF;
END //
DELIMITER ;

CALL add_booking_columns();
DROP PROCEDURE IF EXISTS add_booking_columns;

-- Backfill day_of_week for existing bookings
UPDATE bookings SET day_of_week = DAYOFWEEK(booking_date) WHERE day_of_week IS NULL;

-- Backfill outcome from existing status
UPDATE bookings SET outcome = 'completed' WHERE status = 'Completed' AND outcome IS NULL;
UPDATE bookings SET outcome = 'cancelled' WHERE status = 'Cancelled' AND outcome IS NULL;

-- =============================================
-- 2. Slot Score Logs — every score computation for adaptation
-- =============================================
CREATE TABLE IF NOT EXISTS slot_score_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    history_score DECIMAL(5,2) DEFAULT 0.00,
    affinity_score DECIMAL(5,2) DEFAULT 0.00,
    gap_fill_score DECIMAL(5,2) DEFAULT 0.00,
    demand_score DECIMAL(5,2) DEFAULT 0.00,
    no_show_penalty DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(5,2) DEFAULT 0.00,
    is_recommended TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_date (customer_id, slot_date),
    INDEX idx_slot_date (slot_date, slot_time),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. Slot Interaction Logs — tracks shown/selected/skipped
-- =============================================
CREATE TABLE IF NOT EXISTS slot_interaction_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    action ENUM('shown','selected','skipped') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_action (customer_id, action),
    INDEX idx_date_action (slot_date, action),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
