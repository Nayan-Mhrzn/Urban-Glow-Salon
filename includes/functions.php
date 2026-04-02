<?php
/**
 * Helper Functions - Urban Glow Salon
 */

/**
 * Sanitize user input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Get a global site setting from the database, cached per-request.
 */
function get_site_setting($key, $default = '') {
    global $pdo;
    static $settings_cache = null;

    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            if ($stmt) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings_cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (PDOException $e) {
            // Ignore if table doesn't exist
        }
    }

    return $settings_cache[$key] ?? $default;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format price with currency symbol
 */
function formatPrice($price) {
    return CURRENCY_SYMBOL . ' ' . number_format($price, 0);
}

/**
 * Get time slots for a given date
 */
function getTimeSlots($date, $pdo, $service_id = null) {
    $slots = [];
    $start = strtotime(WORKING_HOURS_START);
    $end = strtotime(WORKING_HOURS_END);
    $interval = SLOT_DURATION_MINUTES * 60;

    // Get booked slots for the date
    $stmt = $pdo->prepare("SELECT booking_time FROM bookings WHERE booking_date = ? AND status != 'Cancelled'");
    $stmt->execute([$date]);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    for ($time = $start; $time < $end; $time += $interval) {
        $timeStr = date('H:i:s', $time);
        $slots[] = [
            'time' => $timeStr,
            'display' => date('h:i A', $time),
            'available' => !in_array($timeStr, $bookedSlots)
        ];
    }

    return $slots;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get cart count for current user
 */
function getCartCount($pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Truncate text to a given length
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
