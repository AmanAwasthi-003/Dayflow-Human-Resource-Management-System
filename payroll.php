<?php
// admin/payroll.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();
$message = '';
$errors = [];

// Handle payroll update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $user_id = intval($_POST['user_id']);
    $basic_salary = floatval($_POST['basic_salary']);
    $hra = floatval($_POST['hra']);
    $transport_allowance = floatval($_POST['transport_allowance']);
    $medical_allowance = floatval($_POST['medical_allowance']);
    $other_allowances = floatval($_POST['other_allowances']);
    $provident_fund = floatval($_POST['provident_fund']);
    $professional_tax = floatval($_POST['professional_tax']);
    $income_tax = floatval($_POST['income_tax']);
    $other_deductions = floatval($_POST['other_deductions']);
    $effective_from = sanitizeInput($_POST['effective_from']);
    
    // Check if payroll exists
    $stmt = $db->prepare("SELECT payroll_id FROM payroll WHERE user_id = ? ORDER BY effective_from DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $payroll_exists = $stmt->get_result()->fetch_assoc();
    
    if ($payroll_exists && strtotime($effective_from) <= strtotime($payroll_exists['effective_from'] ?? '1970-01-01')) {
        $errors[] = "Effective date must be after the last payroll entry date.";
    } else {
        $stmt = $db->prepare("INSERT INTO payroll (user_id, basic_salary, hra, transport_allowance, 
                             medical_allowance, other_allowances, provident_fund, professional_tax, 
                             income_tax, other_deductions, effective_from) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iddddddddds", $user_id, $basic_salary, $hra, $transport_allowance, 
                         $medical_allowance, $other_allowances, $provident_fund, $professional_tax, 
                         $income_tax, $other_deductions, $effective_from);
        
        if ($stmt->execute()) {
            $message = "Payroll updated successfully!";
        } else {
            $errors[] = "Failed to update payroll. Please try again.";
        }
        $stmt->close();
    }
}

// Get all employees with their current payroll
$result = $db->query("SELECT u.user_id, u.employee_id, ep.first_name, ep.last_name, 
                     p.basic_salary, p.net_salary, p.effective_from
                     FROM users u 
                     LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                     LEFT JOIN (
                         SELECT user_id, basic_salary, net_salary, effective_from,
                         ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY effective_from DESC) as rn
                         FROM payroll
                     ) p ON u.user_id = p.user_id AND p.rn = 1
                     WHERE u.role_id IN (SELECT role_id FROM roles WHERE role_name = 'Employee') 
                     ORDER BY u.employee_id");
$employees = $result;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <h1>Payroll Management</h1>
        
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
            <h2>Employee Payroll Summary</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Basic Salary</th>
                        <th>Net Salary</th>
                        <th>Effective From</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = $employees->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')); ?></td>
                            <td>₹<?php echo $emp['basic_salary'] ? number_format($emp['basic_salary'], 2) : '-'; ?></td>
                            <td>₹<?php echo $emp['net_salary'] ? number_format($emp['net_salary'], 2) : '-'; ?></td>
                            <td><?php echo $emp['effective_from'] ? formatDate($emp['effective_from']) : '-'; ?></td>
                            <td>
                                <a href="payroll_edit.php?id=<?php echo $emp['user_id']; ?>" class="btn btn-sm btn-primary">
                                    <?php echo $emp['basic_salary'] ? 'Edit' : 'Add'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>