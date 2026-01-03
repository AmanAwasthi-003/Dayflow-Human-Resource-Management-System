<?php
// auth/signup.php
require_once '../config/config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $employee_id = sanitizeInput($_POST['employee_id'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? 'Employee');
        
        // Validation
        if (empty($employee_id)) {
            $errors[] = "Employee ID is required.";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        if (empty($errors)) {
            $db = Database::getInstance()->getConnection();
            
            // Check if employee ID or email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE employee_id = ? OR email = ?");
            $stmt->bind_param("ss", $employee_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Employee ID or Email already exists.";
            } else {
                // Get role_id
                $stmt = $db->prepare("SELECT role_id FROM roles WHERE role_name = ?");
                $stmt->bind_param("s", $role);
                $stmt->execute();
                $role_result = $stmt->get_result();
                $role_data = $role_result->fetch_assoc();
                $role_id = $role_data['role_id'];
                
                // Generate verification token
                $verification_token = bin2hex(random_bytes(32));
                $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (employee_id, email, password_hash, role_id, verification_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiss", $employee_id, $email, $password_hash, $role_id, $verification_token, $token_expiry);
                
                if ($stmt->execute()) {
                    // Send verification email (simplified - implement proper email sending)
                    $verification_link = BASE_URL . "auth/verify_email.php?token=" . $verification_token;
                    
                    // In production, send actual email using PHPMailer or similar
                    // For now, just show the link
                    $success = "Account created successfully! Please verify your email using this link: <a href='$verification_link' target='_blank'>Verify Email</a>";
                    
                    // Log activity
                    $user_id = $db->insert_id;
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'signup', 'User registered', ?)");
                    $stmt->bind_param("is", $user_id, $ip);
                    $stmt->execute();
                } else {
                    $errors[] = "Registration failed. Please try again.";
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
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join our employee management system</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="signupForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="employee_id">Employee ID *</label>
                    <input type="text" id="employee_id" name="employee_id" required 
                           value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small class="password-hint">
                        Must be at least 8 characters with uppercase, lowercase, number and special character
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="Employee">Employee</option>
                        <option value="HR">HR/Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/validation.js"></script>
</body>
</html>