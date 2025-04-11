<?php
/**
 * View student details
 */

// Get student data
$student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');

if (!$student) {
    echo '<div class="alert alert-danger">Student not found</div>';
    return;
}

$student = $student[0];

// Get current enrollment
$enrollment = null;
if ($currentYearId) {
    $enrollment = db_select(
        "SELECT e.*, a.name as academic_year 
         FROM student_enrollments e
         JOIN academic_years a ON e.academic_year_id = a.id
         WHERE e.student_id = ? AND e.academic_year_id = ?",
        [$student_id, $currentYearId],
        'ii'
    );
    
    if ($enrollment) {
        $enrollment = $enrollment[0];
    }
}

// Get all enrollments
$enrollments = db_select(
    "SELECT e.*, a.name as academic_year 
     FROM student_enrollments e
     JOIN academic_years a ON e.academic_year_id = a.id
     WHERE e.student_id = ?
     ORDER BY a.start_date DESC",
    [$student_id],
    'i'
);

// Get payments
$payments = db_select(
    "SELECT p.*, a.name as academic_year, u.name as staff_name
     FROM payments p
     JOIN academic_years a ON p.academic_year_id = a.id
     JOIN users u ON p.created_by = u.id
     WHERE p.student_id = ?
     ORDER BY p.payment_date DESC",
    [$student_id],
    'i'
);

// Calculate payment statistics
$total_paid = 0;
$total_due = 0;

if ($enrollment) {
    // Calculate total due
    $total_due = $enrollment['tuition_fee'] - $enrollment['discount_amount'] - $enrollment['scholarship_amount'];
    
    // Calculate total paid for current academic year
    if ($payments) {
        foreach ($payments as $payment) {
            if ($payment['academic_year_id'] == $currentYearId) {
                $total_paid += $payment['amount'];
            }
        }
    }
}

$balance = $total_due - $total_paid;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=students" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
        <a href="index.php?page=students&action=edit&id=<?php echo $student_id; ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-edit"></i> Edit Student
        </a>
    </div>
</div>

<div class="row">
    <!-- Student Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-graduate"></i> Student Information
                </div>
                <span class="badge <?php 
                    echo $student['status'] === 'active' ? 'bg-success' : 
                        ($student['status'] === 'inactive' ? 'bg-warning' : 
                         ($student['status'] === 'graduated' ? 'bg-info' : 'bg-secondary')); 
                ?>">
                    <?php echo ucfirst($student['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Student ID:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['student_id']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Name:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Gender:</div>
                    <div class="col-md-8"><?php echo ucfirst($student['gender']); ?></div>
                </div>
                <?php if (!empty($student['date_of_birth'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Date of Birth:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($student['date_of_birth'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($student['address']) || !empty($student['city']) || !empty($student['state'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Address:</div>
                    <div class="col-md-8">
                        <?php 
                        $address_parts = array_filter([
                            $student['address'], 
                            $student['city'], 
                            $student['state'],
                            $student['country']
                        ]);
                        echo htmlspecialchars(implode(', ', $address_parts));
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($student['phone'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Phone:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['phone']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($student['email'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Email:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['email']); ?></div>
                </div>
                <?php endif; ?>
                
                <h6 class="mt-4 mb-3 border-top pt-3">Parent/Guardian Information</h6>
                
                <?php if (!empty($student['parent_name'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Name:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['parent_name']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($student['parent_phone'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Phone:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['parent_phone']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($student['parent_email'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Email:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($student['parent_email']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Current Enrollment -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-graduation-cap"></i> Current Enrollment
            </div>
            <div class="card-body">
                <?php if ($enrollment): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Academic Year:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($enrollment['academic_year']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Grade & Section:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($enrollment['grade'] . ' - ' . $enrollment['section']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Enrollment Date:</div>
                        <div class="col-md-8"><?php echo date('F d, Y', strtotime($enrollment['enrollment_date'])); ?></div>
                    </div>
                    
                    <h6 class="mt-4 mb-3 border-top pt-3">Tuition Details</h6>
                    
                    <div class="row mb-2">
                        <div class="col-md-8">Tuition Fee:</div>
                        <div class="col-md-4 text-end"><?php echo '$' . number_format($enrollment['tuition_fee'], 2); ?></div>
                    </div>
                    
                    <?php if ($enrollment['discount_amount'] > 0): ?>
                    <div class="row mb-2">
                        <div class="col-md-8">Discount (<?php echo $enrollment['discount_percentage']; ?>%):</div>
                        <div class="col-md-4 text-end text-danger">- <?php echo '$' . number_format($enrollment['discount_amount'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($enrollment['scholarship_amount'] > 0): ?>
                    <div class="row mb-2">
                        <div class="col-md-8">Scholarship (<?php echo $enrollment['scholarship_percentage']; ?>%):</div>
                        <div class="col-md-4 text-end text-danger">- <?php echo '$' . number_format($enrollment['scholarship_amount'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row fw-bold mb-2 border-top pt-2">
                        <div class="col-md-8">Total Due:</div>
                        <div class="col-md-4 text-end"><?php echo '$' . number_format($total_due, 2); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-8">Total Paid:</div>
                        <div class="col-md-4 text-end text-success"><?php echo '$' . number_format($total_paid, 2); ?></div>
                    </div>
                    
                    <div class="row fw-bold">
                        <div class="col-md-8">Balance:</div>
                        <div class="col-md-4 text-end <?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo '$' . number_format($balance, 2); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($enrollment['notes'])): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="mb-2">Notes:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($enrollment['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 d-flex justify-content-end">
                        <a href="index.php?page=payments&action=add&student_id=<?php echo $student_id; ?>" class="btn btn-primary">
                            <i class="fas fa-money-bill-wave"></i> Add Payment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Student is not enrolled in the current academic year.
                    </div>
                    <div class="mt-3">
                        <a href="index.php?page=students&action=edit&id=<?php echo $student_id; ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Enroll Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for historical data -->
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="studentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab" aria-controls="enrollments" aria-selected="true">
                    <i class="fas fa-history"></i> Enrollment History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                    <i class="fas fa-money-bill"></i> Payment History
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="studentTabsContent">
            <!-- Enrollment History Tab -->
            <div class="tab-pane fade show active" id="enrollments" role="tabpanel" aria-labelledby="enrollments-tab">
                <?php if ($enrollments): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Grade</th>
                                    <th>Enrollment Date</th>
                                    <th>Tuition Fee</th>
                                    <th>Discount</th>
                                    <th>Scholarship</th>
                                    <th>Net Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enroll): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enroll['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($enroll['grade'] . ' - ' . $enroll['section']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enroll['enrollment_date'])); ?></td>
                                        <td><?php echo '$' . number_format($enroll['tuition_fee'], 2); ?></td>
                                        <td>
                                            <?php if ($enroll['discount_amount'] > 0): ?>
                                                <?php echo $enroll['discount_percentage'] . '%'; ?>
                                                <small class="text-muted">
                                                    ($<?php echo number_format($enroll['discount_amount'], 2); ?>)
                                                </small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($enroll['scholarship_amount'] > 0): ?>
                                                <?php echo $enroll['scholarship_percentage'] . '%'; ?>
                                                <small class="text-muted">
                                                    ($<?php echo number_format($enroll['scholarship_amount'], 2); ?>)
                                                </small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $net = $enroll['tuition_fee'] - $enroll['discount_amount'] - $enroll['scholarship_amount'];
                                            echo '$' . number_format($net, 2);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No enrollment history found.</div>
                <?php endif; ?>
            </div>
            
            <!-- Payment History Tab -->
            <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                <?php if ($payments): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Academic Year</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                        <td class="fw-bold"><?php echo '$' . number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['staff_name']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="index.php?page=payments&action=view&id=<?php echo $payment['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php 
                                                // Check if this payment has a receipt
                                                $receipt = db_select("SELECT id FROM receipts WHERE payment_id = ?", [$payment['id']], 'i');
                                                if ($receipt): 
                                                ?>
                                                    <a href="index.php?page=receipts&action=view&id=<?php echo $receipt[0]['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success" title="View Receipt">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="index.php?page=receipts&action=generate&payment_id=<?php echo $payment['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" title="Generate Receipt">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No payment history found.</div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="index.php?page=payments&action=add&student_id=<?php echo $student_id; ?>" class="btn btn-primary">
                        <i class="fas fa-money-bill-wave"></i> Add New Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('#studentTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>
