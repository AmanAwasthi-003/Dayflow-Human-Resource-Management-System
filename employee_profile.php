<?php
// admin/employee_profile.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$errors = [];

// Get employee data
$stmt = $db->prepare("SELECT ep.*, u.email, u.employee_id, u.is_active 
                     FROM employee_profiles ep 
                     RIGHT JOIN users u ON ep.user_id = u.user_id 
                     WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    redirect('admin/employees.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
    $date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $designation = sanitizeInput($_POST['designation'] ?? '');
    $date_of_joining = sanitizeInput($_POST['date_of_joining'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Update user status
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $is_active, $user_id);
    $stmt->execute();
    
    // Check if profile exists
    $stmt = $db->prepare("SELECT profile_id FROM employee_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile_exists = $stmt->get_result()->num_rows > 0;
    
    if ($profile_exists) {
        $stmt = $db->prepare("UPDATE employee_profiles SET 
                             first_name = ?, last_name = ?, phone = ?, address = ?, 
                             city = ?, state = ?, zip_code = ?, date_of_birth = ?, 
                             gender = ?, department = ?, designation = ?, date_of_joining = ? 
                             WHERE user_id = ?");
        $stmt->bind_param("ssssssssssssi", $first_name, $last_name, $phone, $address, 
                         $city, $state, $zip_code, $date_of_birth, $gender, 
                         $department, $designation, $date_of_joining, $user_id);
    } else {
        $stmt = $db->prepare("INSERT INTO employee_profiles 
                             (user_id, first_name, last_name, phone, address, city, state, 
                              zip_code, date_of_birth, gender, department, designation, date_of_joining) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssssss", $user_id, $first_name, $last_name, $phone, 
                         $address, $city, $state, $zip_code, $date_of_birth, 
                         $gender, $department, $designation, $date_of_joining);
    }
    
    if ($stmt->execute()) {
        $message = "Employee profile updated successfully!";
        // Refresh data
        $stmt = $db->prepare("SELECT ep.*, u.email, u.employee_id, u.is_active 
                             FROM employee_profiles ep 
                             RIGHT JOIN users u ON ep.user_id = u.user_id 
                             WHERE u.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();
    } else {
        $errors[] = "Failed to update profile. Please try again.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="section-header">
            <h1>Edit Employee Profile</h1>
            <a href="employees.php" class="btn btn-secondary">Back to List</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Employee Information</h2>
            <form method="POST" action="" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($employee['email']); ?>" disabled>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               value="<?php echo $employee['date_of_birth'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select</option>
                            <option value="Male" <?php echo ($employee['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($employee['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($employee['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state"
                               value="<?php echo htmlspecialchars($employee['state'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">ZIP Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                               value="<?php echo htmlspecialchars($employee['zip_code'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department"
                               value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" id="designation" name="designation"
                               value="<?php echo htmlspecialchars($employee['designation'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_joining">Date of Joining</label>
                        <input type="date" id="date_of_joining" name="date_of_joining"
                               value="<?php echo $employee['date_of_joining'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?php echo $employee['is_active'] ? 'checked' : ''; ?>>
                        Active Employee
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>