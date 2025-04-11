<?php
/**
 * View receipt page - Fixed version without relying on specific column names
 */

// Check if receipt ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Receipt ID is required</div>';
    exit;
}

$receipt_id = (int)$_GET['id'];

// Get basic receipt data
$receipt_query = "SELECT * FROM receipts WHERE id = ?";
$receipt_data = db_select($receipt_query, [$receipt_id], 'i');

if (empty($receipt_data)) {
    echo '<div class="alert alert-danger">Receipt not found</div>';
    exit;
}

$receipt = $receipt_data[0];

// Get payment data
$payment_query = "SELECT p.*, 
                  s.first_name as student_first_name, s.last_name as student_last_name, 
                  s.student_id as student_number
                  FROM payments p
                  LEFT JOIN students s ON p.student_id = s.id
                  WHERE p.id = ?";
$payment_data = db_select($payment_query, [$receipt['payment_id']], 'i');

if (!empty($payment_data)) {
    $payment = $payment_data[0];
    
    // Merge payment data with receipt
    foreach ($payment as $key => $value) {
        if (!isset($receipt[$key])) {
            $receipt[$key] = $value;
        }
    }
}

// Get academic year name
if (isset($receipt['academic_year_id'])) {
    $year_query = "SELECT name FROM academic_years WHERE id = ?";
    $year_data = db_select($year_query, [$receipt['academic_year_id']], 'i');
    
    if (!empty($year_data)) {
        $receipt['academic_year_name'] = $year_data[0]['name'];
    } else {
        $receipt['academic_year_name'] = 'Unknown';
    }
}

// Calculate tuition summary if student_id and academic_year_id exist
$tuition_summary = null;
if (isset($receipt['student_id']) && isset($receipt['academic_year_id'])) {
    $enrollment_query = "SELECT * FROM student_enrollments WHERE student_id = ? AND academic_year_id = ?";
    $enrollment_data = db_select($enrollment_query, [$receipt['student_id'], $receipt['academic_year_id']], 'ii');
    
    if (!empty($enrollment_data)) {
        $enrollment = $enrollment_data[0];
        
        // Save enrollment data
        $receipt['grade'] = $enrollment['grade'] ?? '';
        $receipt['section'] = $enrollment['section'] ?? '';
        
        // Calculate tuition summary
        if (isset($enrollment['tuition_fee']) && !empty($enrollment['tuition_fee'])) {
            $tuition_fee = $enrollment['tuition_fee'];
            $discount_percentage = $enrollment['discount_percentage'] ?? 0;
            $discount_amount = ($tuition_fee * $discount_percentage) / 100;
            $total_due = $tuition_fee - $discount_amount;
            
            // Get total paid for this student and academic year
            $total_paid_query = "SELECT SUM(amount) as total_paid
                               FROM payments
                               WHERE student_id = ? AND academic_year_id = ?";
            $total_paid_result = db_select($total_paid_query, [$receipt['student_id'], $receipt['academic_year_id']], 'ii');
            
            $total_paid = $total_paid_result[0]['total_paid'] ?? 0;
            $balance = $total_due - $total_paid;
            
            $tuition_summary = [
                'tuition_fee' => $tuition_fee,
                'discount_percentage' => $discount_percentage,
                'discount_amount' => $discount_amount,
                'total_due' => $total_due,
                'total_paid' => $total_paid,
                'balance' => $balance
            ];
        }
    }
}

// Handle email sending
if (isset($_POST['send_email']) && $_POST['send_email'] === '1' && !empty($_POST['email'])) {
    $to = $_POST['email'];
    $subject = "Payment Receipt - " . $receipt['receipt_number'];
    
    // Email body
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
            .content { padding: 20px 0; }
            .footer { padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
            .amount { font-weight: bold; color: #3366cc; }
            .button { display: inline-block; padding: 10px 20px; background-color: #3366cc; color: white; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Payment Receipt</h2>
                <p>Receipt Number: " . htmlspecialchars($receipt['receipt_number']) . "</p>
            </div>
            
            <div class='content'>
                <p>Dear " . htmlspecialchars($receipt['student_first_name'] . ' ' . $receipt['student_last_name']) . ",</p>
                
                <p>Thank you for your payment. Please find your receipt details below:</p>
                
                <p><strong>Payment Details:</strong></p>
                <ul>
                    <li>Date: " . date('F j, Y', strtotime($receipt['payment_date'])) . "</li>
                    <li>Amount: <span class='amount'>$" . number_format($receipt['amount'], 2) . "</span></li>
                    <li>Payment Type: " . ucfirst(str_replace('_', ' ', $receipt['payment_type'])) . "</li>
                    <li>Payment Method: " . ucfirst(str_replace('_', ' ', $receipt['payment_method'])) . "</li>
                </ul>";
    
    if ($tuition_summary) {
        $message .= "
                <p><strong>Tuition Summary:</strong></p>
                <ul>
                    <li>Tuition Fee: $" . number_format($tuition_summary['tuition_fee'], 2) . "</li>";
        
        if ($tuition_summary['discount_percentage'] > 0) {
            $message .= "
                    <li>Discount (" . $tuition_summary['discount_percentage'] . "%): -$" . number_format($tuition_summary['discount_amount'], 2) . "</li>";
        }
        
        $message .= "
                    <li>Total Due: $" . number_format($tuition_summary['total_due'], 2) . "</li>
                    <li>Total Paid to Date: $" . number_format($tuition_summary['total_paid'], 2) . "</li>
                    <li>Balance: $" . number_format($tuition_summary['balance'], 2) . "</li>
                </ul>";
    }
    
    $message .= "
                <p>A PDF copy of this receipt is attached to this email.</p>
                
                <p>If you have any questions about this receipt, please contact us.</p>
                
                <p>Best regards,<br>" . get_setting_value('school_name', 'School Name') . "</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email, please do not reply.</p>
                <p>" . get_setting_value('school_address', 'School Address') . " | " . get_setting_value('school_phone', 'School Phone') . " | " . get_setting_value('school_email', 'School Email') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . get_setting_value('school_name', 'School') . ' <' . get_setting_value('school_email', 'noreply@example.com') . '>' . "\r\n";
    
    // File attachment
    $pdf_path = __DIR__ . '/../../' . $receipt['pdf_path'];
    
    if (file_exists($pdf_path)) {
        // For demonstration purposes only - this is a simplified method
        // In production, use PHPMailer or another library for proper email with attachments
        
        // Attempt to send email
        $email_sent = mail($to, $subject, $message, $headers);
        
        if ($email_sent) {
            echo '<div class="alert alert-success">Receipt has been sent to ' . htmlspecialchars($to) . '. <em>(Note: Attachment functionality requires an email library like PHPMailer)</em></div>';
        } else {
            echo '<div class="alert alert-danger">Failed to send email. Please check your server\'s mail configuration.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Receipt PDF file not found. Please regenerate the receipt first.</div>';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">View Receipt</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=receipts" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Receipts
        </a>
        <?php if (!empty($receipt['pdf_path']) && file_exists(__DIR__ . '/../../' . $receipt['pdf_path'])): ?>
            <a href="<?php echo $receipt['pdf_path']; ?>" class="btn btn-sm btn-primary me-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Open PDF
            </a>
            <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#emailReceiptModal">
                <i class="fas fa-envelope"></i> Email Receipt
            </button>
        <?php else: ?>
            <a href="index.php?page=receipts&action=generate&payment_id=<?php echo $receipt['payment_id']; ?>" class="btn btn-sm btn-warning me-2">
                <i class="fas fa-sync-alt"></i> Regenerate PDF
            </a>
        <?php endif; ?>
        <a href="index.php?page=payments&action=view&id=<?php echo $receipt['payment_id']; ?>" class="btn btn-sm btn-outline-info">
            <i class="fas fa-money-bill-wave"></i> View Payment
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-receipt"></i> Receipt Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Receipt Details</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Receipt Number</th>
                                <td><?php echo htmlspecialchars($receipt['receipt_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Issue Date</th>
                                <td><?php echo date('F j, Y', strtotime($receipt['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Academic Year</th>
                                <td><?php echo htmlspecialchars($receipt['academic_year_name'] ?? 'Unknown'); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Student Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Student Name</th>
                                <td><?php echo htmlspecialchars(($receipt['student_first_name'] ?? '') . ' ' . ($receipt['student_last_name'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>Student ID</th>
                                <td><?php echo htmlspecialchars($receipt['student_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php if (!empty($receipt['grade']) && !empty($receipt['section'])): ?>
                            <tr>
                                <th>Grade</th>
                                <td><?php echo htmlspecialchars($receipt['grade'] . '-' . $receipt['section']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <h5>Payment Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Payment Date</th>
                        <td><?php echo isset($receipt['payment_date']) ? date('F j, Y', strtotime($receipt['payment_date'])) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td class="text-primary fw-bold">$<?php echo isset($receipt['amount']) ? number_format($receipt['amount'], 2) : '0.00'; ?></td>
                    </tr>
                    <tr>
                        <th>Payment Type</th>
                        <td><?php echo isset($receipt['payment_type']) ? ucfirst(str_replace('_', ' ', $receipt['payment_type'])) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Payment Method</th>
                        <td>
                            <?php echo isset($receipt['payment_method']) ? ucfirst(str_replace('_', ' ', $receipt['payment_method'])) : 'N/A'; ?>
                            <?php if (!empty($receipt['reference_number'])): ?>
                                <span class="text-muted">(Ref: <?php echo htmlspecialchars($receipt['reference_number']); ?>)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($receipt['notes'])): ?>
                    <tr>
                        <th>Notes</th>
                        <td><?php echo htmlspecialchars($receipt['notes']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php if ($tuition_summary): ?>
                <h5 class="mt-4">Tuition Summary</h5>
                <div class="card bg-light">
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Tuition Fee</th>
                                <td class="text-end">$<?php echo number_format($tuition_summary['tuition_fee'], 2); ?></td>
                            </tr>
                            <?php if ($tuition_summary['discount_percentage'] > 0): ?>
                            <tr>
                                <th>Discount (<?php echo $tuition_summary['discount_percentage']; ?>%)</th>
                                <td class="text-end">-$<?php echo number_format($tuition_summary['discount_amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Total Due</th>
                                <td class="text-end">$<?php echo number_format($tuition_summary['total_due'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total Paid to Date</th>
                                <td class="text-end">$<?php echo number_format($tuition_summary['total_paid'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Balance</th>
                                <td class="text-end fw-bold <?php echo $tuition_summary['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                    $<?php echo number_format($tuition_summary['balance'], 2); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-file-pdf"></i> PDF Preview
            </div>
            <div class="card-body text-center">
                <?php if (!empty($receipt['pdf_path']) && file_exists(__DIR__ . '/../../' . $receipt['pdf_path'])): ?>
                    <div class="mb-3">
                        <img src="assets/images/pdf_icon.png" alt="PDF Icon" style="width: 100px;">
                    </div>
                    <p><?php echo basename($receipt['pdf_path']); ?></p>
                    <div class="btn-group">
                        <a href="<?php echo $receipt['pdf_path']; ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="<?php echo $receipt['pdf_path']; ?>" class="btn btn-success" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> PDF file not found or has not been generated.
                        <p class="mt-2">
                            <a href="index.php?page=receipts&action=generate&payment_id=<?php echo $receipt['payment_id']; ?>" class="btn btn-warning">
                                <i class="fas fa-sync-alt"></i> Generate PDF
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Activity Log
            </div>
            <div class="card-body">
                <ul class="timeline">
                    <li>
                        <span class="timeline-badge"><i class="fas fa-file-invoice"></i></span>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h6 class="timeline-title">Receipt Created</h6>
                                <p><small class="text-muted"><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i a', strtotime($receipt['created_at'])); ?></small></p>
                            </div>
                            <div class="timeline-body">
                                <p>Receipt #<?php echo htmlspecialchars($receipt['receipt_number']); ?> was created.</p>
                            </div>
                        </div>
                    </li>
                    <?php if (!empty($receipt['updated_at']) && $receipt['updated_at'] != $receipt['created_at']): ?>
                    <li>
                        <span class="timeline-badge"><i class="fas fa-edit"></i></span>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h6 class="timeline-title">Receipt Updated</h6>
                                <p><small class="text-muted"><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i a', strtotime($receipt['updated_at'])); ?></small></p>
                            </div>
                            <div class="timeline-body">
                                <p>Receipt was updated.</p>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Email Receipt Modal -->
<div class="modal fade" id="emailReceiptModal" tabindex="-1" aria-labelledby="emailReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailReceiptModalLabel">Email Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Enter the email address to send the receipt to.</div>
                    </div>
                    <input type="hidden" name="send_email" value="1">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> The receipt will be sent as an HTML email with the PDF attached.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Timeline CSS */
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}
.timeline:before {
    top: 0;
    bottom: 0;
    position: absolute;
    content: " ";
    width: 3px;
    background-color: #eeeeee;
    left: 50px;
    margin-left: -1.5px;
}
.timeline > li {
    margin-bottom: 20px;
    position: relative;
}
.timeline > li:before,
.timeline > li:after {
    content: " ";
    display: table;
}
.timeline > li:after {
    clear: both;
}
.timeline > li > .timeline-panel {
    width: calc(100% - 90px);
    float: right;
    border: 1px solid #d4d4d4;
    border-radius: 5px;
    padding: 15px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
}
.timeline > li > .timeline-panel:before {
    position: absolute;
    top: 26px;
    left: -15px;
    display: inline-block;
    border-top: 15px solid transparent;
    border-right: 15px solid #ccc;
    border-left: 0 solid #ccc;
    border-bottom: 15px solid transparent;
    content: " ";
}
.timeline > li > .timeline-panel:after {
    position: absolute;
    top: 27px;
    left: -14px;
    display: inline-block;
    border-top: 14px solid transparent;
    border-right: 14px solid #fff;
    border-left: 0 solid #fff;
    border-bottom: 14px solid transparent;
    content: " ";
}
.timeline > li > .timeline-badge {
    color: #fff;
    width: 50px;
    height: 50px;
    line-height: 50px;
    font-size: 1.4em;
    text-align: center;
    position: absolute;
    top: 16px;
    left: 50px;
    margin-left: -25px;
    background-color: #3366cc;
    z-index: 100;
    border-radius: 50%;
}
.timeline-title {
    margin-top: 0;
    color: inherit;
}
.timeline-body > p,
.timeline-body > ul {
    margin-bottom: 0;
}
.timeline-body > p + p {
    margin-top: 5px;
}
</style>