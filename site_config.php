<?php
// Environment setting
define('ENVIRONMENT', 'production'); // Change to 'production' for live server

// Session configuration MUST be set before ANY session_start, 
// including those in other files
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration
    ini_set('session.cache_limiter', 'nocache');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    // Start the session
    session_start();
}

// Cache control based on environment
if (ENVIRONMENT === 'development') {
    // Disable caching for development
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Past date to ensure no caching
    
    // Enable error reporting for development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    // Normal caching for production
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

if (ENVIRONMENT === 'development') {
    // Force session regeneration in development
    if (!isset($_SESSION['last_regeneration']) || 
        (time() - $_SESSION['last_regeneration']) > 30) { // 30 seconds
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Site configuration
define('SITE_URL', 'https://ratcha.net/demos'); // Change this to your domain

// Set the application subdirectory
$app_subdir = 'demos';
define('BASE_PATH', '/' . trim($app_subdir, '/'));
define('APP_NAME', 'CPMS');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');

// Error log path
ini_set('error_log', __DIR__ . '/logs/error.log');

// Time zone
date_default_timezone_set('Asia/Jakarta'); 
