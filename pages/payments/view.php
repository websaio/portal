<?php
/**
 * View payment details
 */

// Get payment data
$payment = db_select(
    "SELECT p.*, s.first_name, s.last_name, s.student_id as student_code, 
            a.name as academic_year, u.name as staff_name
     FROM payments p
     JOIN students s ON p.student_id = s.id
     JOIN academic_years a ON p.academic_year_id = a.id
     JOIN users u ON p.created_by = u.id
     WHERE p.id = ?", 
    [$payment_id], 
    'i'
);

if (!$payment) {
    echo '<div class="alert alert-danger">Payment not found</div>';
    return;
}

$payment = $payment[0];

// Check if payment has a receipt
$receipt = db_select(
    "SELECT * FROM receipts WHERE payment_id = ?", 
    [$payment_id], 
    'i'
);

$has_receipt = !empty($receipt);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Payment Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=payments" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
        <?php if ($has_receipt): ?>
            <a href="index.php?page=receipts&action=view&id=<?php echo $receipt[0]['id']; ?>" 
               class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-invoice-dollar"></i> View Receipt
            </a>
        <?php else: ?>
            <a href="index.php?page=receipts&action=generate&payment_id=<?php echo $payment_id; ?>" 
               class="btn btn-sm btn-primary">
                <i class="fas fa-receipt"></i> Generate Receipt
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Payment Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-money-bill-wave"></i> Payment Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Date:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Amount:</div>
                    <div class="col-md-8 fw-bold text-success">$<?php echo number_format($payment['amount'], 2); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Type:</div>
                    <div class="col-md-8"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Method:</div>
                    <div class="col-md-8"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></div>
                </div>
                
                <?php if ($payment['reference_number']): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Reference Number:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($payment['reference_number']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($payment['notes']): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Notes:</div>
                    <div class="col-md-8"><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Academic Year:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($payment['academic_year']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Created By:</div>
                    <div class="col-md-8">
                        <?php echo htmlspecialchars($payment['staff_name']); ?>
                        <div class="small text-muted">
                            <?php echo date('F d, Y \a\t h:i A', strtotime($payment['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-graduate"></i> Student Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Student ID:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($payment['student_code']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Name:</div>
                    <div class="col-md-8">
                        <a href="index.php?page=students&action=view&id=<?php echo $payment['student_id']; ?>">
                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                        </a>
                    </div>
                </div>
                
                <?php
                // Get student enrollment info
                $enrollment = db_select(
                    "SELECT e.*, a.name as academic_year 
                     FROM student_enrollments e
                     JOIN academic_years a ON e.academic_year_id = a.id
                     WHERE e.student_id = ? AND e.academic_year_id = ?",
                    [$payment['student_id'], $payment['academic_year_id']],
                    'ii'
                );
                
                if ($enrollment):
                    $enrollment = $enrollment[0];
                ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Grade & Section:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($enrollment['grade'] . ' - ' . $enrollment['section']); ?></div>
                </div>
                
                <hr class="my-3">
                
                <h6 class="mb-3">Payment Summary for <?php echo htmlspecialchars($payment['academic_year']); ?></h6>
                
                <?php
                // Calculate total due
                $total_due = $enrollment['tuition_fee'] - $enrollment['discount_amount'] - $enrollment['scholarship_amount'];
                
                // Get all payments
                $all_payments = db_select(
                    "SELECT SUM(amount) as total_paid 
                     FROM payments 
                     WHERE student_id = ? AND academic_year_id = ?",
                    [$payment['student_id'], $payment['academic_year_id']],
                    'ii'
                );
                
                $total_paid = $all_payments[0]['total_paid'] ?? 0;
                $balance = $total_due - $total_paid;
                ?>
                
                <div class="row mb-2">
                    <div class="col-md-8">Tuition Fee:</div>
                    <div class="col-md-4 text-end">$<?php echo number_format($enrollment['tuition_fee'], 2); ?></div>
                </div>
                
                <?php if ($enrollment['discount_amount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-md-8">Discount (<?php echo $enrollment['discount_percentage']; ?>%):</div>
                    <div class="col-md-4 text-end text-danger">-$<?php echo number_format($enrollment['discount_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($enrollment['scholarship_amount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-md-8">Scholarship (<?php echo $enrollment['scholarship_percentage']; ?>%):</div>
                    <div class="col-md-4 text-end text-danger">-$<?php echo number_format($enrollment['scholarship_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row fw-bold mb-2 border-top pt-2">
                    <div class="col-md-8">Total Due:</div>
                    <div class="col-md-4 text-end">$<?php echo number_format($total_due, 2); ?></div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-md-8">Total Paid:</div>
                    <div class="col-md-4 text-end text-success">$<?php echo number_format($total_paid, 2); ?></div>
                </div>
                
                <div class="row fw-bold">
                    <div class="col-md-8">Balance:</div>
                    <div class="col-md-4 text-end <?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                        $<?php echo number_format($balance, 2); ?>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Information (if available) -->
<?php if ($has_receipt): ?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-file-invoice-dollar"></i> Receipt Information
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Receipt Number:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($receipt[0]['receipt_number']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Receipt Date:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($receipt[0]['receipt_date'])); ?></div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Created By:</div>
                    <div class="col-md-8">
                        <?php 
                        $created_by = db_select("SELECT name FROM users WHERE id = ?", [$receipt[0]['created_by']], 'i');
                        echo $created_by ? htmlspecialchars($created_by[0]['name']) : 'Unknown'; 
                        ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Created On:</div>
                    <div class="col-md-8"><?php echo date('F d, Y \a\t h:i A', strtotime($receipt[0]['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-center mt-3">
            <a href="<?php echo htmlspecialchars($receipt[0]['pdf_path']); ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> View PDF Receipt
            </a>
            
            <?php if (!$receipt[0]['is_emailed']): ?>
            <a href="index.php?page=receipts&action=email&id=<?php echo $receipt[0]['id']; ?>" class="btn btn-success ms-2">
                <i class="fas fa-envelope"></i> Email Receipt
            </a>
            <?php endif; ?>
            
            <a href="index.php?page=receipts&action=print&id=<?php echo $receipt[0]['id']; ?>" class="btn btn-secondary ms-2">
                <i class="fas fa-print"></i> Print Receipt
            </a>
        </div>
    </div>
</div>
<?php endif; ?>