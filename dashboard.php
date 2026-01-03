<?php
// employee/dashboard.php
require_once '../includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get employee profile
$stmt = $db->prepare("SELECT ep.*, u.email FROM employee_profiles ep 
                     LEFT JOIN users u ON ep.user_id = u.user_id 
                     WHERE ep.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get recent attendance
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY attendance_date DESC LIMIT 7");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_attendance = $stmt->get_result();

// Get leave request stats
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM leave_requests WHERE user_id = ? GROUP BY status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leave_stats = [];
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $leave_stats[$row['status']] = $row['count'];
}

// while ($row = $stmt->get_result()->fetch_assoc()) {
//     $leave_stats[$row['status']] = $row['count'];
// }

// Get today's attendance
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($profile['first_name'] ?? 'Employee'); ?>!</h1>
            <p class="subtitle">Employee ID: <?php echo htmlspecialchars($_SESSION['employee_id']); ?></p>
        </div>
        
        <!-- Quick Action Cards -->
        <div class="card-grid">
            <a href="profile.php" class="dashboard-card">
                <div class="card-icon">👤</div>
                <h3>My Profile</h3>
                <p>View and update your profile</p>
            </a>
            
            <a href="attendance.php" class="dashboard-card">
                <div class="card-icon">📅</div>
                <h3>Attendance</h3>
                <p>Mark attendance & view history</p>
            </a>
            
            <a href="leave.php" class="dashboard-card">
                <div class="card-icon">🏖️</div>
                <h3>Leave Requests</h3>
                <p>Apply and track leave requests</p>
            </a>
            
            <a href="payroll.php" class="dashboard-card">
                <div class="card-icon">💰</div>
                <h3>Payroll</h3>
                <p>View salary details</p>
            </a>
        </div>
        
        <!-- Today's Attendance -->
        <div class="dashboard-section">
            <h2>Today's Attendance</h2>
            <div class="card">
                <?php if ($today_attendance): ?>
                    <div class="attendance-status">
                        <span class="status-badge status-<?php echo strtolower($today_attendance['status']); ?>">
                            <?php echo $today_attendance['status']; ?>
                        </span>
                        <?php if ($today_attendance['check_in']): ?>
                            <p>Check-in: <strong><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></strong></p>
                        <?php endif; ?>
                        <?php if ($today_attendance['check_out']): ?>
                            <p>Check-out: <strong><?php echo date('h:i A', strtotime($today_attendance['check_out'])); ?></strong></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No attendance marked for today.</p>
                    <a href="attendance.php" class="btn btn-primary">Mark Attendance</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Attendance -->
        <div class="dashboard-section">
            <h2>Recent Attendance (Last 7 Days)</h2>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($recent_attendance->num_rows > 0):
                            while ($att = $recent_attendance->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo formatDate($att['attendance_date']); ?></td>
                                <td><?php echo $att['check_in'] ? date('h:i A', strtotime($att['check_in'])) : '-'; ?></td>
                                <td><?php echo $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($att['status']); ?>">
                                        <?php echo $att['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="4" class="text-center">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Leave Statistics -->
        <div class="dashboard-section">
            <h2>Leave Request Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $leave_stats['Pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $leave_stats['Approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $leave_stats['Rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>