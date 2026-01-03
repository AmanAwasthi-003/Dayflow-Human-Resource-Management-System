<?php
// employee/attendance.php
require_once '../includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    if ($action === 'check_in') {
        // Check if already checked in today
        $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE user_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "You have already checked in today.";
        } else {
            $stmt = $db->prepare("INSERT INTO attendance (user_id, attendance_date, check_in, status) VALUES (?, ?, ?, 'Present')");
            $stmt->bind_param("iss", $user_id, $today, $current_time);
            
            if ($stmt->execute()) {
                $message = "Check-in successful at " . date('h:i A');
            } else {
                $error = "Check-in failed. Please try again.";
            }
        }
        $stmt->close();
    } elseif ($action === 'check_out') {
        $stmt = $db->prepare("SELECT attendance_id, check_out FROM attendance WHERE user_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Please check in first.";
        } else {
            $att = $result->fetch_assoc();
            if ($att['check_out']) {
                $error = "You have already checked out today.";
            } else {
                $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE attendance_id = ?");
                $stmt->bind_param("si", $current_time, $att['attendance_id']);
                
                if ($stmt->execute()) {
                    $message = "Check-out successful at " . date('h:i A');
                } else {
                    $error = "Check-out failed. Please try again.";
                }
            }
        }
        $stmt->close();
    }
}

// Get today's attendance
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();

// Get attendance history (last 30 days)
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY attendance_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$attendance_history = $stmt->get_result();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_header.php'; ?>
    
    <div class="container">
        <h1>Attendance Management</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Today's Attendance -->
        <div class="card">
            <h2>Today's Attendance - <?php echo date('l, F j, Y'); ?></h2>
            
            <?php if ($today_attendance): ?>
                <div class="attendance-info">
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="status-badge status-<?php echo strtolower($today_attendance['status']); ?>">
                            <?php echo $today_attendance['status']; ?>
                        </span>
                    </div>
                    
                    <?php if ($today_attendance['check_in']): ?>
                        <div class="info-row">
                            <span class="label">Check-in Time:</span>
                            <span class="value"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($today_attendance['check_out']): ?>
                        <div class="info-row">
                            <span class="label">Check-out Time:</span>
                            <span class="value"><?php echo date('h:i A', strtotime($today_attendance['check_out'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <?php if (!$today_attendance['check_out']): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="check_out">
                            <button type="submit" class="btn btn-danger">Check Out</button>
                        </form>
                    <?php else: ?>
                        <p class="text-success">You have completed your attendance for today.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>You have not checked in yet today.</p>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="check_in">
                    <button type="submit" class="btn btn-primary">Check In</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Attendance History -->
        <div class="card" style="margin-top: 30px;">
            <h2>Attendance History (Last 30 Days)</h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendance_history->num_rows > 0): ?>
                        <?php while ($att = $attendance_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($att['attendance_date']); ?></td>
                                <td><?php echo date('l', strtotime($att['attendance_date'])); ?></td>
                                <td><?php echo $att['check_in'] ? date('h:i A', strtotime($att['check_in'])) : '-'; ?></td>
                                <td><?php echo $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($att['status']); ?>">
                                        <?php echo $att['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($att['remarks'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No attendance records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>