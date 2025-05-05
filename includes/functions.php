<?php
/**
 * Utility functions for the application
 */

/**
 * Sanitize user input
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitize_input($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

/**
 * Format date to a readable format
 * @param string $date The date to format
 * @return string The formatted date
 */
function format_date($date) {
    return date('M d, Y H:i', strtotime($date));
}

/**
 * Format currency
 * @param float $amount The amount to format
 * @return string The formatted amount
 */
function format_currency($amount) {
    return 'KSH ' . number_format($amount, 2);
}

/**
 * Redirect to a specific page
 * @param string $page The page to redirect to
 */
function redirect($page) {
    header('Location: ' . BASE_URL . '/index.php?page=' . $page);
    exit;
}

/**
 * Check if a string is a valid email
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if a string is a valid phone number
 * @param string $phone The phone number to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_phone($phone) {
    return preg_match('/^\+?[0-9]{10,15}$/', $phone);
}

/**
 * Generate a random string
 * @param int $length The length of the string
 * @return string The generated string
 */
function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if a file is a valid PDF
 * @param string $file The file to check
 * @return bool True if valid, false otherwise
 */
function is_valid_pdf($file) {
    $allowed_types = ['application/pdf'];
    return in_array($file['type'], $allowed_types);
}

/**
 * Check if a file size is within limits
 * @param string $file The file to check
 * @param int $max_size The maximum size in bytes
 * @return bool True if within limits, false otherwise
 */
function is_valid_file_size($file, $max_size = 10485760) { // 10MB default
    return $file['size'] <= $max_size;
}
?> 