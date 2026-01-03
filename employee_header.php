<?php
// includes/employee_header.php
?>
<header class="header">
    <div class="header-content">
        <a href="<?php echo BASE_URL; ?>employee/dashboard.php" class="logo">HRMS</a>
        <nav class="nav">
            <a href="<?php echo BASE_URL; ?>employee/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>employee/profile.php">Profile</a>
            <a href="<?php echo BASE_URL; ?>employee/attendance.php">Attendance</a>
            <a href="<?php echo BASE_URL; ?>employee/leave.php">Leave</a>
            <a href="<?php echo BASE_URL; ?>employee/payroll.php">Payroll</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </nav>
    </div>
</header>