<?php
// includes/auth_check.php
// Middleware to check if user is authenticated

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Check session timeout
if (isset($_SESSION['logged_in_at'])) {
    $elapsed = time() - $_SESSION['logged_in_at'];
    if ($elapsed > SESSION_LIFETIME) {
        // Session expired
        session_unset();
        session_destroy();
        redirect('auth/login.php?timeout=1');
    }
}

// Update last activity time
$_SESSION['logged_in_at'] = time();

// Function to check if user has required role
function requireRole($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        die('Access denied. You do not have permission to view this page.');
    }
}
?>