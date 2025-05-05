<?php
// Application Name
define('APP_NAME', 'TESTA');

// Only define constants if they haven't been defined yet
if (!defined('BASE_URL')) {
    // Base URL - Detect automatically
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $path = rtrim($script_name, '/');
    define('BASE_URL', $protocol . $host . $path);
}

// Time Zone
date_default_timezone_set('Africa/Nairobi');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// File Upload Settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['application/pdf']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/papers/');

// Payment Settings
define('CURRENCY', 'KSH');
define('CURRENCY_SYMBOL', 'KSH');

// Other Constants
define('ITEMS_PER_PAGE', 12);
define('MIN_PASSWORD_LENGTH', 8);

// Database configuration
$dbHost = 'localhost'; // or your MySQL server IP
$dbUser = 'root'; // replace with your MySQL username
$dbPass = ''; // replace with your MySQL password
$dbName = 'testa_db'; // replace with your database name

// Create connection
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Session settings (only if session hasn't started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', false); // Set to true if using HTTPS
    session_start();
}
?> 