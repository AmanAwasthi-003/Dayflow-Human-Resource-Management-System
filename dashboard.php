<?php
// admin/dashboard.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();

// Get total employees
$result = $db->query("SELECT COUNT(*) as total FROM users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name = 'Employee')");
$total_employees = $result->fetch_assoc()['total'];

// Get today's attendance stats
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE attendance_date = ? GROUP BY status");
$stmt->bind_param("s", $today);
$stmt->execute();
$attendance_stats = [];
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $leave_stats[$row['status']] = $row['count'];
}

// while ($row = $stmt->get_result()->fetch_assoc()) {
//     $attendance_stats[$row['status']] = $row['count'];
// }

// Get pending leave requests
$result = $db->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'Pending'");
$pending_leaves = $result->fetch_assoc()['total'];

// Get recent leave requests
$result = $db->query("SELECT lr.*, ep.first_name, ep.last_name, u.employee_id 
                      FROM leave_requests lr 
                      JOIN users u ON lr.user_id = u.user_id 
                      LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                      WHERE lr.status = 'Pending' 
                      ORDER BY lr.created_at DESC LIMIT 5");
$recent_leaves = $result;

// Get all employees
$result = $db->query("SELECT u.user_id, u.employee_id, u.email, ep.first_name, ep.last_name, ep.department, ep.designation 
                      FROM users u 
                      LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                      WHERE u.role_id IN (SELECT role_id FROM roles WHERE role_name = 'Employee') 
                      ORDER BY u.created_at DESC LIMIT 10");
$employees = $result;

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>HR Dashboard</h1>
            <p class="subtitle">Employee Management Overview</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-value"><?php echo $total_employees; ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-value"><?php echo $attendance_stats['Present'] ?? 0; ?></div>
                <div class="stat-label">Present Today</div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-value"><?php echo $pending_leaves; ?></div>
                <div class="stat-label">Pending Leave Requests</div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-value"><?php echo $attendance_stats['Absent'] ?? 0; ?></div>
                <div class="stat-label">Absent Today</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-section">
            <h2>Quick Actions</h2>
            <div class="card-grid">
                <a href="employees.php" class="dashboard-card">
                    <div class="card-icon">👥</div>
                    <h3>Manage Employees</h3>
                    <p>View and manage all employees</p>
                </a>
                
                <a href="attendance.php" class="dashboard-card">
                    <div class="card-icon">📊</div>
                    <h3>Attendance Reports</h3>
                    <p>View attendance statistics</p>
                </a>
                
                <a href="leave_requests.php" class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Leave Requests</h3>
                    <p>Approve or reject leave requests</p>
                </a>
                
                <a href="payroll.php" class="dashboard-card">
                    <div class="card-icon">💵</div>
                    <h3>Payroll Management</h3>
                    <p>Manage employee salaries</p>
                </a>
            </div>
        </div>
        
        <!-- Pending Leave Requests -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Pending Leave Requests</h2>
                <a href="leave_requests.php" class="btn btn-secondary">View All</a>
            </div>
            <div class="card">
                <?php if ($recent_leaves->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($leave = $recent_leaves->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                        <br><small><?php echo htmlspecialchars($leave['employee_id']); ?></small>
                                    </td>
                                    <td><?php echo $leave['leave_type']; ?></td>
                                    <td><?php echo formatDate($leave['start_date']); ?></td>
                                    <td><?php echo formatDate($leave['end_date']); ?></td>
                                    <td><?php echo $leave['total_days']; ?></td>
                                    <td>
                                        <a href="leave_action.php?id=<?php echo $leave['leave_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No pending leave requests</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Employees -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Employees</h2>
                <a href="employees.php" class="btn btn-secondary">View All</a>
            </div>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['designation'] ?? '-'); ?></td>
                                <td>
                                    <a href="employee_profile.php?id=<?php echo $emp['user_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>