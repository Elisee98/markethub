<?php
/**
 * MarketHub Configuration Template
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'MarketHub');
define('SITE_URL', 'https://yourdomain.com/');
define('ASSETS_URL', SITE_URL . 'assets/');

// Email Configuration
define('SMTP_HOST', 'your-smtp-host.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@yourdomain.com');
define('SMTP_PASS', 'your-email-password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'MarketHub');

// Security Settings
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', 'uploads/');

// Application Settings
define('DEBUG_MODE', false); // Set to false in production
define('MAINTENANCE_MODE', false);
define('ITEMS_PER_PAGE', 12);
define('CURRENCY', 'RWF');
define('CURRENCY_SYMBOL', 'RWF');

// Payment Settings (configure when ready)
define('PAYMENT_GATEWAY', 'stripe'); // stripe, paypal, etc.
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');

// Social Media (optional)
define('FACEBOOK_URL', 'https://facebook.com/markethub');
define('TWITTER_URL', 'https://twitter.com/markethub');
define('INSTAGRAM_URL', 'https://instagram.com/markethub');

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $database = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please try again later.");
    }
}

// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Timezone
date_default_timezone_set('Africa/Kigali');

?>
