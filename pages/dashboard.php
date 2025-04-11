<?php
/**
 * Dashboard page
 */
// Include constants file
require_once __DIR__ . '/../shared/constants.php';

// Get current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$currentYearId = null;

if ($current_academic_year) {
    $currentYearId = $current_academic_year[0]['id'];
    $current_academic_year = $current_academic_year[0];
}

// Stats for current academic year
$stats = [
    'students' => 0,
    'payments' => 0,
    'total_amount' => 0,
    'due_amount' => 0
];

// Count students
if ($currentYearId) {
    $students = db_select(
        "SELECT COUNT(DISTINCT s.id) as count 
         FROM students s 
         JOIN student_enrollments e ON s.id = e.student_id 
         WHERE e.academic_year_id = ? 
         AND s.status = 'active'", 
        [$currentYearId], 
        'i'
    );
    
    if ($students) {
        $stats['students'] = $students[0]['count'];
    }
    
    // Count payments
    $payments = db_select(
        "SELECT COUNT(*) as count, SUM(amount) as total 
         FROM payments 
         WHERE academic_year_id = ?", 
        [$currentYearId], 
        'i'
    );
    
    if ($payments) {
        $stats['payments'] = $payments[0]['count'];
        $stats['total_amount'] = $payments[0]['total'] ?? 0;
    }
    
    // Calculate total due
    $total_due = db_select(
        "SELECT SUM(tuition_fee - discount_amount - scholarship_amount) as total_due 
         FROM student_enrollments 
         WHERE academic_year_id = ?", 
        [$currentYearId], 
        'i'
    );
    
    if ($total_due) {
        $stats['due_amount'] = $total_due[0]['total_due'] - $stats['total_amount'];
    }
}

// Recent payments
$recent_payments = db_select(
    "SELECT p.*, s.first_name, s.last_name, s.student_id as student_id_number, 
            a.name as academic_year, u.name as staff_name
     FROM payments p
     JOIN students s ON p.student_id = s.id
     JOIN academic_years a ON p.academic_year_id = a.id
     JOIN users u ON p.created_by = u.id
     ORDER BY p.payment_date DESC, p.id DESC
     LIMIT 5"
);

// Recent students
$recent_students = db_select(
    "SELECT s.*, e.grade, e.section, a.name as academic_year
     FROM students s
     LEFT JOIN student_enrollments e ON s.id = e.student_id
     LEFT JOIN academic_years a ON e.academic_year_id = a.id
     ORDER BY s.id DESC
     LIMIT 5"
);

// Get payment statistics by month for current year
$payment_stats_by_month = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if ($currentYearId) {
    // Get year from academic year start date
    $year = date('Y', strtotime($current_academic_year['start_date']));
    
    // Initialize all months with zero
    foreach ($months as $index => $month) {
        $month_num = $index + 1;
        $payment_stats_by_month[$month] = 0;
    }
    
    // Get payment data from database
    $payment_data = db_select(
        "SELECT MONTH(payment_date) as month, SUM(amount) as total
         FROM payments
         WHERE academic_year_id = ? AND YEAR(payment_date) = ?
         GROUP BY MONTH(payment_date)",
        [$currentYearId, $year],
        'ii'
    );
    
    if ($payment_data) {
        foreach ($payment_data as $data) {
            $month_index = $data['month'] - 1;
            if (isset($months[$month_index])) {
                $payment_stats_by_month[$months[$month_index]] = $data['total'];
            }
        }
    }
}

// Get payment statistics by payment type
$payment_stats_by_type = [];

if ($currentYearId) {
    // Initialize all types with zero
    foreach ($payment_types as $type) {
        $payment_stats_by_type[ucfirst(str_replace('_', ' ', $type))] = 0;
    }
    
    // Get payment data by type
    $payment_type_data = db_select(
        "SELECT payment_type, SUM(amount) as total
         FROM payments
         WHERE academic_year_id = ?
         GROUP BY payment_type",
        [$currentYearId],
        'i'
    );
    
    if ($payment_type_data) {
        foreach ($payment_type_data as $data) {
            $type = ucfirst(str_replace('_', ' ', $data['payment_type']));
            $payment_stats_by_type[$type] = $data['total'];
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="index.php?page=reports" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
    </div>
</div>

<?php if (!$current_academic_year): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No active academic year found.
        <?php if (is_admin()): ?>
            Please <a href="index.php?page=academic-years" class="alert-link">set up an academic year</a> to continue.
        <?php else: ?>
            Please contact the administrator to set up an academic year.
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Students
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['students']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="index.php?page=students" class="text-decoration-none">View Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Payments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['payments']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="index.php?page=payments" class="text-decoration-none">View Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Collected
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($stats['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="index.php?page=reports" class="text-decoration-none">View Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Outstanding Balance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format(max(0, $stats['due_amount']), 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="index.php?page=reports&action=outstanding" class="text-decoration-none">View Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Monthly Revenue (<?php echo date('Y', strtotime($current_academic_year['start_date'])); ?>)
                </div>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Revenue by Payment Type
                </div>
                <div class="card-body">
                    <canvas id="paymentTypeChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities Row -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Recent Payments
                </div>
                <div class="card-body">
                    <?php if ($recent_payments): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <a href="index.php?page=students&action=view&id=<?php echo $payment['student_id']; ?>">
                                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                                </a>
                                            </td>
                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td>
                                                <a href="index.php?page=payments&action=view&id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=payments" class="btn btn-sm btn-primary">View All Payments</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent payments found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-graduate me-1"></i>
                    Recent Students
                </div>
                <div class="card-body">
                    <?php if ($recent_students): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td>
                                                <a href="index.php?page=students&action=view&id=<?php echo $student['id']; ?>">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($student['grade'] && $student['section']) {
                                                    echo htmlspecialchars($student['grade'] . '-' . $student['section']); 
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $student['status'] === 'active' ? 'bg-success' : 
                                                        ($student['status'] === 'inactive' ? 'bg-warning' : 
                                                        ($student['status'] === 'graduated' ? 'bg-info' : 'bg-secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="index.php?page=students&action=view&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=students" class="btn btn-sm btn-primary">View All Students</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent students found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Initialization -->
    <script>
    // Initialize charts when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Revenue Chart
        var monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        var monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($payment_stats_by_month)); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode(array_values($payment_stats_by_month)); ?>
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Payment Type Chart
        var paymentTypeCtx = document.getElementById('paymentTypeChart').getContext('2d');
        var paymentTypeChart = new Chart(paymentTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($payment_stats_by_type)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($payment_stats_by_type)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(255, 99, 132, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed || 0;
                                return label + ': $' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
<?php endif; ?>
