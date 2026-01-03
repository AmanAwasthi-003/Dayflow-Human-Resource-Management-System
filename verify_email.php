<?php
// auth/verify_email.php
require_once '../config/config.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    $db = Database::getInstance()->getConnection();
    
    // Check if token exists and is valid
    $stmt = $db->prepare("SELECT user_id, email_verified, token_expiry FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "Invalid verification token.";
    } else {
        $user = $result->fetch_assoc();
        
        if ($user['email_verified'] == 1) {
            $message = "Email already verified. You can login now.";
            $success = true;
        } elseif (strtotime($user['token_expiry']) < time()) {
            $message = "Verification token has expired. Please request a new one.";
        } else {
            // Verify email
            $stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE verification_token = ?");
            $stmt->bind_param("s", $token);
            
            if ($stmt->execute()) {
                $message = "Email verified successfully! You can now login.";
                $success = true;
                
                // Log activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'email_verified', 'Email verified successfully', ?)");
                $stmt->bind_param("is", $user['user_id'], $ip);
                $stmt->execute();
            } else {
                $message = "Verification failed. Please try again.";
            }
        }
    }
    $stmt->close();
} else {
    $message = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Email Verification</h1>
            </div>
            
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <p><?php echo $message; ?></p>
            </div>
            
            <?php if ($success): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="signup.php" class="btn btn-secondary">Back to Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>