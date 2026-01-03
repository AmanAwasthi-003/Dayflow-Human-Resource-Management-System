<?php
// employee/leave.php
require_once '../includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];

// Handle leave request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $leave_type = sanitizeInput($_POST['leave_type'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    // Validation
    if (empty($leave_type) || empty($start_date) || empty($end_date)) {
        $errors[] = "All fields are required.";
    }
    
    if (strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "End date must be after start date.";
    }
    
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Start date cannot be in the past.";
    }
    
    if (empty($errors)) {
        $total_days = daysBetween($start_date, $end_date);
        
        $stmt = $db->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", $user_id, $leave_type, $start_date, $end_date, $total_days, $reason);
        
        if ($stmt->execute()) {
            $message = "Leave request submitted successfully!";
        } else {
            $errors[] = "Failed to submit leave request. Please try again.";
        }
        $stmt->close();
    }
}

// Get all leave requests
$stmt = $db->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leave_requests = $stmt->get_result();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_header.php'; ?>
    
    <div class="container">
        <h1>Leave Management</h1>
        
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
        
        <!-- Apply for Leave -->
        <div class="card">
            <h2>Apply for Leave</h2>
            
            <form method="POST" action="" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="leave_type">Leave Type *</label>
                        <select id="leave_type" name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="Paid">Paid Leave</option>
                            <option value="Sick">Sick Leave</option>
                            <option value="Unpaid">Unpaid Leave</option>
                            <option value="Casual">Casual Leave</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reason">Reason</label>
                    <textarea id="reason" name="reason" rows="4" placeholder="Enter reason for leave (optional)"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Leave Request</button>
            </form>
        </div>
        
        <!-- Leave Request History -->
        <div class="card" style="margin-top: 30px;">
            <h2>Leave Request History</h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Admin Comments</th>
                        <th>Applied On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leave_requests->num_rows > 0): ?>
                        <?php while ($leave = $leave_requests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $leave['leave_type']; ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($leave['status']); ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($leave['reason'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($leave['admin_comments'] ?? '-'); ?></td>
                                <td><?php echo formatDate($leave['created_at'], 'd-m-Y H:i'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No leave requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>