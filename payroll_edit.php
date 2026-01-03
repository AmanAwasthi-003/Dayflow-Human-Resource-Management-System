<?php
// admin/payroll_edit.php
require_once '../includes/auth_check.php';
requireRole(['HR', 'Admin']);

$db = Database::getInstance()->getConnection();
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$errors = [];

// Get employee data
$stmt = $db->prepare("SELECT u.employee_id, u.email, ep.first_name, ep.last_name, ep.department, ep.designation
                     FROM users u 
                     LEFT JOIN employee_profiles ep ON u.user_id = ep.user_id 
                     WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    redirect('admin/payroll.php');
}

// Get current payroll
$stmt = $db->prepare("SELECT * FROM payroll WHERE user_id = ? ORDER BY effective_from DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_payroll = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
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
    
    if (empty($effective_from)) {
        $errors[] = "Effective date is required.";
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
            // Refresh current payroll
            $stmt = $db->prepare("SELECT * FROM payroll WHERE user_id = ? ORDER BY effective_from DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $current_payroll = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update payroll. Please try again.";
        }
        $stmt->close();
    }
}
// $stmt = $conn->prepare("SELECT * FROM users");

// if (!$stmt) {
//     die("Prepare failed: " . $conn->error);
// }

// $stmt->execute();
// $stmt->close();


$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payroll - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="section-header">
            <h1>Manage Employee Payroll</h1>
            <a href="payroll.php" class="btn btn-secondary">Back to List</a>
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
                    <span class="label">Employee ID:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Department:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['department'] ?? '-'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Designation:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['designation'] ?? '-'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2><?php echo $current_payroll ? 'Update Salary Structure' : 'Add Salary Structure'; ?></h2>
            
            <form method="POST" action="" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <h3 style="color: var(--success-color); margin-top: 20px;">Earnings</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="basic_salary">Basic Salary (₹) *</label>
                        <input type="number" id="basic_salary" name="basic_salary" step="0.01" required
                               value="<?php echo $current_payroll['basic_salary'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="hra">HRA (₹)</label>
                        <input type="number" id="hra" name="hra" step="0.01"
                               value="<?php echo $current_payroll['hra'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="transport_allowance">Transport Allowance (₹)</label>
                        <input type="number" id="transport_allowance" name="transport_allowance" step="0.01"
                               value="<?php echo $current_payroll['transport_allowance'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="medical_allowance">Medical Allowance (₹)</label>
                        <input type="number" id="medical_allowance" name="medical_allowance" step="0.01"
                               value="<?php echo $current_payroll['medical_allowance'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="other_allowances">Other Allowances (₹)</label>
                        <input type="number" id="other_allowances" name="other_allowances" step="0.01"
                               value="<?php echo $current_payroll['other_allowances'] ?? ''; ?>">
                    </div>
                </div>
                
                <h3 style="color: var(--danger-color); margin-top: 30px;">Deductions</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="provident_fund">Provident Fund (₹)</label>
                        <input type="number" id="provident_fund" name="provident_fund" step="0.01"
                               value="<?php echo $current_payroll['provident_fund'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="professional_tax">Professional Tax (₹)</label>
                        <input type="number" id="professional_tax" name="professional_tax" step="0.01"
                               value="<?php echo $current_payroll['professional_tax'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="income_tax">Income Tax/TDS (₹)</label>
                        <input type="number" id="income_tax" name="income_tax" step="0.01"
                               value="<?php echo $current_payroll['income_tax'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="other_deductions">Other Deductions (₹)</label>
                        <input type="number" id="other_deductions" name="other_deductions" step="0.01"
                               value="<?php echo $current_payroll['other_deductions'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <label for="effective_from">Effective From *</label>
                    <input type="date" id="effective_from" name="effective_from" required
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Payroll</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>