<?php
// auth/logout.php
require_once '../config/config.php';

if (isLoggedIn()) {
    $db = Database::getInstance()->getConnection();
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'logout', 'User logged out', ?)");
    $stmt->bind_param("is", $user_id, $ip);
    $stmt->execute();
    
    // Delete session record
    $stmt = $db->prepare("DELETE FROM sessions WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    
    $stmt->close();
}

// Destroy session
session_unset();
session_destroy();

redirect('auth/login.php');
?>