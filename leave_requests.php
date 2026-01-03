<?php
// admin/leave_requests.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();
$message = '';
$errors = [];

// Get all leave requests
$filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';

if ($filter === 'all') {
    $stmt = $db->query("SELECT lr.*, ep.first_name, ep.last_name, u.employee_id 
                       FROM leave_requests lr 
                       JOIN users u ON lr.user_id = u.user_id 
                       LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                       ORDER BY lr.created_at DESC");
} else {
    $stmt = $db->prepare("SELECT lr.*, ep.first_name, ep.last_name, u.employee_id 
                         FROM leave_requests lr 
                         JOIN users u ON lr.user_id = u.user_id 
                         LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                         WHERE lr.status = ?
                         ORDER BY lr.created_at DESC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $stmt = $stmt->get_result();
}

$leave_requests = $stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="section-header">
            <h1>Leave Request Management</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <div class="card">
            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <a href="?status=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                <a href="?status=Pending" class="btn <?php echo $filter === 'Pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                <a href="?status=Approved" class="btn <?php echo $filter === 'Approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
                <a href="?status=Rejected" class="btn <?php echo $filter === 'Rejected' ? 'btn-primary' : 'btn-secondary'; ?>">Rejected</a>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leave_requests->num_rows > 0): ?>
                        <?php while ($leave = $leave_requests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                    <br><small><?php echo htmlspecialchars($leave['employee_id']); ?></small>
                                </td>
                                <td><?php echo $leave['leave_type']; ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['total_days']; ?></td>
                                <td><?php echo htmlspecialchars(substr($leave['reason'] ?? '-', 0, 50)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($leave['status']); ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($leave['created_at'], 'd-m-Y H:i'); ?></td>
                                <td>
                                    <?php if ($leave['status'] === 'Pending'): ?>
                                        <a href="leave_action.php?id=<?php echo $leave['leave_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    <?php else: ?>
                                        <a href="leave_action.php?id=<?php echo $leave['leave_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No leave requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>