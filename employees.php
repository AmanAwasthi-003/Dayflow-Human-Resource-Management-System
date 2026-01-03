<?php
// admin/employees.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();

// Get all employees with their profiles
$result = $db->query("SELECT u.user_id, u.employee_id, u.email, u.is_active, 
                      ep.first_name, ep.last_name, ep.department, ep.designation, 
                      ep.phone, ep.date_of_joining
                      FROM users u 
                      LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                      WHERE u.role_id IN (SELECT role_id FROM roles WHERE role_name = 'Employee') 
                      ORDER BY u.created_at DESC");
$employees = $result;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="section-header">
            <h1>Employee Management</h1>
        </div>
        
        <div class="card">
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <h2>All Employees</h2>
                <input type="text" id="searchInput" placeholder="Search employees..." 
                       style="padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; width: 300px;">
            </div>
            
            <table class="data-table" id="employeeTable">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = $employees->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($emp['designation'] ?? '-'); ?></td>
                            <td><?php echo $emp['date_of_joining'] ? formatDate($emp['date_of_joining']) : '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $emp['is_active'] ? 'approved' : 'rejected'; ?>">
                                    <?php echo $emp['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="employee_profile.php?id=<?php echo $emp['user_id']; ?>" class="btn btn-sm btn-primary">View/Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Simple search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeeTable tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>