<?php
// employee/profile.php
require_once '../includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
    
    
    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        } elseif ($_FILES['profile_picture']['size'] > MAX_FILE_SIZE) {
            $errors[] = "File size must be less than 5MB.";
        } else {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = PROFILE_PIC_DIR . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }
    }
    
    if (empty($errors)) {
        // Check if profile exists
        $stmt = $db->prepare("SELECT profile_id FROM employee_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing profile
            if ($profile_picture) {
                $stmt = $db->prepare("UPDATE employee_profiles SET phone = ?, address = ?, city = ?, state = ?, zip_code = ?, profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssi", $phone, $address, $city, $state, $zip_code, $profile_picture, $user_id);
            } else {
                $stmt = $db->prepare("UPDATE employee_profiles SET phone = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $phone, $address, $city, $state, $zip_code, $user_id);
            }
        } else {
            // Create new profile
            $stmt = $db->prepare("INSERT INTO employee_profiles (user_id, phone, address, city, state, zip_code,profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $user_id, $phone, $address, $city, $state, $zip_code, $profile_picture);
        }
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

// Get profile data
$stmt = $db->prepare("SELECT ep.*, u.email, u.employee_id FROM employee_profiles ep 
                     LEFT JOIN users u ON ep.user_id = u.user_id 
                     WHERE ep.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get payroll data
$stmt = $db->prepare("SELECT * FROM payroll WHERE user_id = ? ORDER BY effective_from DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payroll = $stmt->get_result()->fetch_assoc();

// Get documents
$stmt = $db->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documents = $stmt->get_result();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_header.php'; ?>
    
    <div class="container">
        <h1>My Profile</h1>
        
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
        
        <div class="profile-container">
            <!-- Profile Picture and Basic Info -->
            <div class="card profile-card">
                <div class="profile-header">
                    <div class="profile-picture">
                        <?php if ($profile && $profile['profile_picture']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/profile_pictures/<?php echo $profile['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="profile-placeholder">
                                <?php echo strtoupper(substr($profile['first_name'] ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($profile['first_name'] ?? 'Not Set') . ' ' . htmlspecialchars($profile['last_name'] ?? ''); ?></h2>
                        <p><?php echo htmlspecialchars($profile['designation'] ?? 'Professor'); ?></p>
                        <p class="text-muted">Employee ID: <?php echo htmlspecialchars($_SESSION['employee_id']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Personal Details -->
            <div class="card">
                <h3>Personal Details</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['email'] ?? 'Not Set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Phone:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not Set'); ?></span>
                    </div>
                    <div class="detail-item full-width">
                        <span class="label">Address:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['address'] ?? 'Not Set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">City:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['city'] ?? 'Not Set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">State:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['state'] ?? 'Not Set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">ZIP Code:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['zip_code'] ?? 'Not Set'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Job Details -->
            <div class="card">
                <h3>Job Details</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="label">Department: </span>
                        <span class="value"><?php echo htmlspecialchars($profile['department'] ?? 'IT'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Designation:</span>
                        <span class="value"><?php echo htmlspecialchars($profile['designation'] ?? 'Professor'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Date of Joining:</span>
                        <span class="value"><?php echo $profile && $profile['date_of_joining'] ? formatDate($profile['date_of_joining']) : '01/01/2026'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Form -->
            <div class="card">
                <h3>Edit Profile</h3>
                <form method="POST" action="" enctype="multipart/form-data" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <small>Max file size: 5MB (JPG, JPEG, PNG, GIF)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($profile['state'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="zip_code">ZIP Code</label>
                            <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($profile['zip_code'] ?? ''); ?>">
                        </div>

                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>