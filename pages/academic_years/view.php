<?php
/**
 * View academic year details
 */

// Get academic year data
$year = db_select("SELECT * FROM academic_years WHERE id = ?", [$year_id], 'i');

if (!$year) {
    echo '<div class="alert alert-danger">Academic year not found</div>';
    return;
}

$year = $year[0];

// Get statistics
$total_students = db_select(
    "SELECT COUNT(*) as total FROM student_enrollments WHERE academic_year_id = ?", 
    [$year_id], 
    'i'
);
$total_students = $total_students[0]['total'] ?? 0;

$total_payments = db_select(
    "SELECT COUNT(*) as total, SUM(amount) as sum FROM payments WHERE academic_year_id = ?", 
    [$year_id], 
    'i'
);
$payment_count = $total_payments[0]['total'] ?? 0;
$payment_sum = $total_payments[0]['sum'] ?? 0;

// Get grades breakdown
$grades = db_select(
    "SELECT grade, COUNT(*) as count FROM student_enrollments 
     WHERE academic_year_id = ? 
     GROUP BY grade 
     ORDER BY FIELD(grade, 'Nursery', 'KG1', 'KG2', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12')", 
    [$year_id], 
    'i'
);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Academic Year Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=academic_years" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Academic Years
        </a>
        <a href="index.php?page=academic_years&action=edit&id=<?php echo $year_id; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-alt"></i> Academic Year Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Name:</div>
                    <div class="col-md-8"><?php echo htmlspecialchars($year['name']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Start Date:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($year['start_date'])); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">End Date:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($year['end_date'])); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Status:</div>
                    <div class="col-md-8">
                        <?php if ($year['is_current']): ?>
                            <span class="badge bg-success">Current Academic Year</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <a href="index.php?page=academic_years&set_current=<?php echo $year_id; ?>" 
                               class="btn btn-sm btn-outline-success ms-2" 
                               onclick="return confirm('Set this as the current academic year?');">
                                Set as Current
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Created On:</div>
                    <div class="col-md-8"><?php echo date('F d, Y', strtotime($year['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Statistics
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 text-center">
                            <div class="h1 mb-0"><?php echo $total_students; ?></div>
                            <div class="small text-muted">Enrolled Students</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 text-center">
                            <div class="h1 mb-0"><?php echo $payment_count; ?></div>
                            <div class="small text-muted">Payments Recorded</div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <div class="border rounded p-3 text-center">
                            <div class="h3 mb-0">$<?php echo number_format($payment_sum, 2); ?></div>
                            <div class="small text-muted">Total Amount Collected</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($total_students > 0): ?>
                <div class="mt-3">
                    <h5>Enrollment by Grade</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Grade</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($grades): ?>
                                    <?php foreach ($grades as $grade): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                            <td><?php echo $grade['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-tasks"></i> Quick Actions
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="index.php?page=students&academic_year=<?php echo $year_id; ?>" class="btn btn-outline-primary w-100 py-3">
                    <i class="fas fa-user-graduate fa-2x mb-2"></i><br>
                    View Students
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="index.php?page=payments&academic_year=<?php echo $year_id; ?>" class="btn btn-outline-success w-100 py-3">
                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i><br>
                    View Payments
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="index.php?page=receipts&academic_year=<?php echo $year_id; ?>" class="btn btn-outline-info w-100 py-3">
                    <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i><br>
                    View Receipts
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="index.php?page=reports&academic_year=<?php echo $year_id; ?>" class="btn btn-outline-secondary w-100 py-3">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                    Generate Reports
                </a>
            </div>
        </div>
    </div>
</div>