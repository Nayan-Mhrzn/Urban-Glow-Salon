<?php
/**
 * Site Configuration - Urban Glow Salon
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Site settings
define('SITE_NAME', 'Urban Glow Salon');
define('SITE_TAGLINE', 'Premium Grooming For All');
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
$siteUrl = $isLocalhost ? 'http://localhost/Urban%20Glow%20Salon' : 'https://nayanmaharjan.com.np';
define('SITE_URL', $siteUrl);
define('SITE_ROOT', dirname(__DIR__));

// Directory paths
define('CONFIG_PATH', SITE_ROOT . '/config');
define('CORE_PATH', SITE_ROOT . '/core');
define('PARTIALS_PATH', SITE_ROOT . '/partials');
define('ASSETS_PATH', SITE_URL . '/assets');
define('UPLOADS_PATH', SITE_ROOT . '/images');
define('IMAGES_PATH', SITE_URL . '/images');

// Working hours
define('WORKING_HOURS_START', '09:00');
define('WORKING_HOURS_END', '20:00');
define('SLOT_DURATION_MINUTES', 30);

// Currency
define('CURRENCY_SYMBOL', 'Rs.');

// Include database connection
require_once CONFIG_PATH . '/db_connect.php';
require_once CORE_PATH . '/functions.php';
require_once CORE_PATH . '/auth.php';
