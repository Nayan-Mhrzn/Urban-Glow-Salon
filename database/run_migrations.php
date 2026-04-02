<?php
/**
 * Run database migrations for admin panel upgrade
 */
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. Create staff_services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        service_id INT NOT NULL,
        FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
        UNIQUE KEY unique_staff_service (staff_id, service_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created staff_services table\n";

    // 2. Add staff_id to bookings
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN staff_id INT DEFAULT NULL");
        echo "Added staff_id to bookings\n";
    } catch (Exception $e) {
        echo "staff_id already exists or: " . $e->getMessage() . "\n";
    }

    // 3. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created settings table\n";

    // 4. Insert default settings
    $defaults = [
        ['site_name', 'Urban Glow Salon'],
        ['site_tagline', 'Premium Grooming For All'],
        ['site_email', 'info@urbanglowsalon.com'],
        ['site_phone', '+977 9800000000'],
        ['site_address', 'Kathmandu, Nepal'],
        ['opening_time', '09:00'],
        ['closing_time', '20:00'],
        ['payment_cod', '1'],
        ['payment_esewa', '0'],
        ['payment_khalti', '0'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $d) {
        $stmt->execute($d);
    }
    echo "Inserted default settings\n";

    echo "\nAll migrations completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
