<?php
// auth/login.php
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('HR') || hasRole('Admin')) {
        redirect('admin/dashboard.php');
    } else {
        redirect('employee/dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        } else {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT u.user_id, u.employee_id, u.email, u.password_hash, u.email_verified, u.is_active, r.role_name 
                                  FROM users u 
                                  JOIN roles r ON u.role_id = r.role_id 
                                  WHERE u.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errors[] = "Invalid email or password.";
            } else {
                $user = $result->fetch_assoc();
                
                if ($user['is_active'] == 0) {
                    $errors[] = "Your account has been deactivated. Please contact HR.";
                } elseif ($user['email_verified'] == 0) {
                    $errors[] = "Please verify your email before logging in.";
                } elseif (!password_verify($password, $user['password_hash'])) {
                    $errors[] = "Invalid email or password.";
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role_name'];
                    $_SESSION['logged_in_at'] = time();
                    
                    // Create session record
                    $session_id = session_id();
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    
                    $stmt = $db->prepare("INSERT INTO sessions (session_id, user_id, ip_address, user_agent) 
                                         VALUES (?, ?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP");
                    $stmt->bind_param("siss", $session_id, $user['user_id'], $ip, $user_agent);
                    $stmt->execute();
                    
                    // Log activity
                    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                         VALUES (?, 'login', 'User logged in', ?)");
                    $stmt->bind_param("is", $user['user_id'], $ip);
                    $stmt->execute();
                    
                    // Redirect based on role
                    if ($user['role_name'] === 'HR' || $user['role_name'] === 'Admin') {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('employee/dashboard.php');
                    }
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to continue to HRMS</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            </div>
        </div>
    </div>
</body>
</html>