<?php
// config/config.php
// Application configuration

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_URL', 'http://localhost/hrms/');
define('SITE_NAME', 'HRMS - Employee Management System');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PROFILE_PIC_DIR', UPLOAD_DIR . 'profile_pictures/');
define('DOCUMENTS_DIR', UPLOAD_DIR . 'documents/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Email configuration (configure with your SMTP details)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@hrms.com');
define('SMTP_FROM_NAME', 'HRMS System');

// Security configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create upload directories if they don't exist
$directories = [
    UPLOAD_DIR,
    PROFILE_PIC_DIR,
    DOCUMENTS_DIR
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Format date
function formatDate($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

// Calculate days between dates
function daysBetween($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    return $start->diff($end)->days + 1;
}
?>