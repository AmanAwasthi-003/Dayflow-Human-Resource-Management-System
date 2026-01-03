<?php
// admin/leave_action.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$errors = [];

// Get leave request details
$stmt = $db->prepare("SELECT lr.*, ep.first_name, ep.last_name, u.employee_id, u.email 
                     FROM leave_requests lr 
                     JOIN users u ON lr.user_id = u.user_id 
                     LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                     WHERE lr.leave_id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();

if (!$leave) {
    redirect('admin/leave_requests.php');
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = sanitizeInput($_POST['action'] ?? '');
    $comments = sanitizeInput($_POST['admin_comments'] ?? '');
    
    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'Approved' : 'Rejected';
        $admin_id = $_SESSION['user_id'];
        
        $stmt = $db->prepare("UPDATE leave_requests SET status = ?, admin_comments = ?, approved_by = ?, approved_at = NOW() WHERE leave_id = ?");
        $stmt->bind_param("ssii", $status, $comments, $admin_id, $leave_id);
        
        if ($stmt->execute()) {
            // If approved, mark attendance as leave
            if ($status === 'Approved') {
                $start_date = $leave['start_date'];
                $end_date = $leave['end_date'];
                $user_id = $leave['user_id'];
                
                // Generate dates between start and end
                $current = strtotime($start_date);
                $end = strtotime($end_date);
                
                while ($current <= $end) {
                    $date = date('Y-m-d', $current);
                    
                    // Insert or update attendance
                    $stmt2 = $db->prepare("INSERT INTO attendance (user_id, attendance_date, status, remarks) 
                                          VALUES (?, ?, 'Leave', 'Approved leave') 
                                          ON DUPLICATE KEY UPDATE status = 'Leave', remarks = 'Approved leave'");
                    $stmt2->bind_param("is", $user_id, $date);
                    $stmt2->execute();
                    
                    $current = strtotime('+1 day', $current);
                }
            }
            
            $message = "Leave request has been " . strtolower($status) . " successfully!";
            
            // Refresh leave data
            $stmt = $db->prepare("SELECT lr.*, ep.first_name, ep.last_name, u.employee_id 
                                 FROM leave_requests lr 
                                 JOIN users u ON lr.user_id = u.user_id 
                                 LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                                 WHERE lr.leave_id = ?");
            $stmt->bind_param("i", $leave_id);
            $stmt->execute();
            $leave = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to process leave request. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Details - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="section-header">
            <h1>Leave Request Details</h1>
            <a href="leave_requests.php" class="btn btn-secondary">Back to List</a>
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
            <div class="details-grid">
                <div class="detail-item">
                    <span class="label">Employee Name:</span>
                    <span class="value"><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Employee ID:</span>
                    <span class="value"><?php echo htmlspecialchars($leave['employee_id']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($leave['email']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Leave Details</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <span class="label">Leave Type:</span>
                    <span class="value"><?php echo $leave['leave_type']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Start Date:</span>
                    <span class="value"><?php echo formatDate($leave['start_date']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">End Date:</span>
                    <span class="value"><?php echo formatDate($leave['end_date']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Days:</span>
                    <span class="value"><?php echo $leave['total_days']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Status:</span>
                    <span class="value">
                        <span class="status-badge status-<?php echo strtolower($leave['status']); ?>">
                            <?php echo $leave['status']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Applied On:</span>
                    <span class="value"><?php echo formatDate($leave['created_at'], 'd-m-Y H:i'); ?></span>
                </div>
                <div class="detail-item full-width">
                    <span class="label">Reason:</span>
                    <span class="value"><?php echo htmlspecialchars($leave['reason'] ?? 'No reason provided'); ?></span>
                </div>
                <?php if ($leave['admin_comments']): ?>
                    <div class="detail-item full-width">
                        <span class="label">Admin Comments:</span>
                        <span class="value"><?php echo htmlspecialchars($leave['admin_comments']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($leave['status'] === 'Pending'): ?>
            <div class="card">
                <h2>Action Required</h2>
                <form method="POST" action="" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="admin_comments">Comments</label>
                        <textarea id="admin_comments" name="admin_comments" rows="4" placeholder="Add your comments (optional)"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 16px;">
                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve Leave</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject Leave</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>