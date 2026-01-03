<?php
// includes/admin_header.php
?>
<header class="header">
    <div class="header-content">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="logo">HRMS - Admin</a>
        <nav class="nav">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>admin/employees.php">Employees</a>
            <a href="<?php echo BASE_URL; ?>admin/attendance.php">Attendance</a>
            <a href="<?php echo BASE_URL; ?>admin/leave_requests.php">Leave Requests</a>
            <a href="<?php echo BASE_URL; ?>admin/payroll.php">Payroll</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </nav>
    </div>
</header>