<?php
// employee/payroll.php
require_once '../includes/auth_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get current payroll details
$stmt = $db->prepare("SELECT * FROM payroll WHERE user_id = ? ORDER BY effective_from DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payroll = $stmt->get_result()->fetch_assoc();

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_header.php'; ?>
    
    <div class="container">
        <h1>Salary Details</h1>
        
        <?php if ($payroll): ?>
            <div class="card">
                <h2>Current Salary Structure</h2>
                <p class="text-muted">Effective from: <?php echo formatDate($payroll['effective_from']); ?></p>
                
                <div style="margin-top: 32px;">
                    <!-- Earnings -->
                    <div class="payroll-section">
                        <h3 style="color: var(--success-color); margin-bottom: 16px;">Earnings</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Basic Salary:</span>
                                <span class="value">₹<?php echo number_format($payroll['basic_salary'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">HRA:</span>
                                <span class="value">₹<?php echo number_format($payroll['hra'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Transport Allowance:</span>
                                <span class="value">₹<?php echo number_format($payroll['transport_allowance'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Medical Allowance:</span>
                                <span class="value">₹<?php echo number_format($payroll['medical_allowance'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Other Allowances:</span>
                                <span class="value">₹<?php echo number_format($payroll['other_allowances'], 2); ?></span>
                            </div>
                        </div>
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--border-color);">
                            <div class="detail-item">
                                <span class="label" style="font-size: 18px; font-weight: 700;">Gross Salary:</span>
                                <span class="value" style="font-size: 24px; font-weight: 700; color: var(--success-color);">
                                    ₹<?php echo number_format($payroll['gross_salary'], 2); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductions -->
                    <div class="payroll-section" style="margin-top: 32px;">
                        <h3 style="color: var(--danger-color); margin-bottom: 16px;">Deductions</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Provident Fund:</span>
                                <span class="value">₹<?php echo number_format($payroll['provident_fund'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Professional Tax:</span>
                                <span class="value">₹<?php echo number_format($payroll['professional_tax'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Income Tax (TDS):</span>
                                <span class="value">₹<?php echo number_format($payroll['income_tax'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Other Deductions:</span>
                                <span class="value">₹<?php echo number_format($payroll['other_deductions'], 2); ?></span>
                            </div>
                        </div>
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--border-color);">
                            <div class="detail-item">
                                <span class="label" style="font-size: 18px; font-weight: 700;">Total Deductions:</span>
                                <span class="value" style="font-size: 24px; font-weight: 700; color: var(--danger-color);">
                                    ₹<?php echo number_format($payroll['total_deductions'], 2); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Salary -->
                    <div class="payroll-section" style="margin-top: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 12px; color: white;">
                        <div class="detail-item">
                            <span style="font-size: 20px; font-weight: 600;">Net Salary (Take Home):</span>
                            <span style="font-size: 32px; font-weight: 700;">
                                ₹<?php echo number_format($payroll['net_salary'], 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-center">No salary information available. Please contact HR.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>