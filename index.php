<?php
// index.php
require_once 'config/config.php';

// Redirect to appropriate page if logged in
if (isLoggedIn()) {
    if (hasRole('HR') || hasRole('Admin')) {
        redirect('admin/dashboard.php');
    } else {
        redirect('employee/dashboard.php');
    }
}

// Otherwise redirect to login
redirect('auth/login.php');
?>