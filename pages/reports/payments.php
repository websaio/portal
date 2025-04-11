<?php
/**
 * Payments report page
 */

// Include constants file if not already included
require_once __DIR__ . '/../../shared/constants.php';

// Define required variables for filtering
$payment_types_const = isset($payment_types) ? $payment_types : ['tuition', 'registration', 'uniform', 'books', 'transportation', 'other'];
$payment_methods_const = isset($payment_methods) ? $payment_methods : ['cash', 'check', 'bank_transfer', 'credit_card', 'debit_card'];
$grades = isset($grades) ? $grades : ['Nursery', 'KG1', 'KG2', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

// Set selected academic year
$selected_year = null;
if ($academic_year_id) {
    $selected_year = db_select("SELECT * FROM academic_years WHERE id = ?", [$academic_year_id], 'i');
    if ($selected_year) {
        $selected_year = $selected_year[0];
    }
}

// Process filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : ($selected_year ? $selected_year['start_date'] : '');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : ($selected_year ? $selected_year['end_date'] : '');
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

// Add date range filter
if (!empty($start_date)) {
    $conditions[] = "p.payment_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $conditions[] = "p.payment_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

// Add payment type filter
if (!empty($payment_type)) {
    $conditions[] = "p.payment_type = ?";
    $params[] = $payment_type;
    $types .= 's';
}

// Add payment method filter
if (!empty($payment_method)) {
    $conditions[] = "p.payment_method = ?";
    $params[] = $payment_method;
    $types .= 's';
}

// Add grade filter
if (!empty($grade)) {
    $conditions[] = "e.grade = ?";
    $params[] = $grade;
    $types .= 's';
}

// Add academic year filter
if ($academic_year_id) {
    $conditions[] = "p.academic_year_id = ?";
    $params[] = $academic_year_id;
    $types .= 'i';
}

// Build the query
$query = "SELECT p.*, s.first_name, s.last_name, s.student_id as student_code,
                 a.name as academic_year, e.grade, e.section
          FROM payments p
          JOIN students s ON p.student_id = s.id
          JOIN academic_years a ON p.academic_year_id = a.id
          LEFT JOIN student_enrollments e ON p.student_id = e.student_id AND p.academic_year_id = e.academic_year_id";

// Add conditions
if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

// Add order by
$query .= " ORDER BY p.payment_date DESC";

// Execute query
$payments = db_select($query, $params, $types);

// Calculate summary
$total_amount = 0;
$payment_types = [];
$payment_methods = [];

if ($payments) {
    foreach ($payments as $payment) {
        $total_amount += $payment['amount'];
        
        // Count by payment type
        $type = $payment['payment_type'];
        if (!isset($payment_types[$type])) {
            $payment_types[$type] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $payment_types[$type]['count']++;
        $payment_types[$type]['amount'] += $payment['amount'];
        
        // Count by payment method
        $method = $payment['payment_method'];
        if (!isset($payment_methods[$method])) {
            $payment_methods[$method] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $payment_methods[$method]['count']++;
        $payment_methods[$method]['amount'] += $payment['amount'];
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Payments Report</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportCSV">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="reportsDropdown" 
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-chart-bar"></i> Report Type
            </button>
            <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                <li><a class="dropdown-item active" href="index.php?page=reports&type=payments">Payments Report</a></li>
                <li><a class="dropdown-item" href="index.php?page=reports&type=payments_by_type">Payments by Type</a></li>
                <li><a class="dropdown-item" href="index.php?page=reports&type=payments_by_method">Payments by Method</a></li>
                <li><a class="dropdown-item" href="index.php?page=reports&type=payments_by_month">Payments by Month</a></li>
                <li><a class="dropdown-item" href="index.php?page=reports&type=students">Students Report</a></li>
                <li><a class="dropdown-item" href="index.php?page=reports&type=outstanding_balance">Outstanding Balance</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="type" value="payments">
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <select class="form-select" id="academic_year" name="academic_year">
                        <option value="">All Academic Years</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?php echo $year['id']; ?>" 
                                    <?php echo $academic_year_id == $year['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="payment_type" class="form-label">Payment Type</label>
                    <select class="form-select" id="payment_type" name="payment_type">
                        <option value="">All Types</option>
                        <?php foreach ($payment_types_const as $type): ?>
                            <option value="<?php echo $type; ?>" 
                                    <?php echo $payment_type === $type ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">All Methods</option>
                        <?php foreach ($payment_methods_const as $method): ?>
                            <option value="<?php echo $method; ?>" 
                                    <?php echo $payment_method === $method ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $method)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label for="grade" class="form-label">Grade</label>
                    <select class="form-select" id="grade" name="grade">
                        <option value="">All</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?php echo $g; ?>" 
                                    <?php echo $grade === $g ? 'selected' : ''; ?>>
                                <?php echo $g; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Generate Report
                    </button>
                    <a href="index.php?page=reports&type=payments" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h5 class="card-title">Total Payments</h5>
                <p class="card-text h2"><?php echo count($payments); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h5 class="card-title">Total Amount</h5>
                <p class="card-text h2">$<?php echo number_format($total_amount, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h5 class="card-title">Average Payment</h5>
                <p class="card-text h2">
                    $<?php echo count($payments) > 0 ? number_format($total_amount / count($payments), 2) : '0.00'; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Summary Breakdowns -->
<div class="row mb-4">
    <?php if (!empty($payment_types)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> By Payment Type
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_types as $type => $data): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $type)); ?></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td>$<?php echo number_format($data['amount'], 2); ?></td>
                                    <td><?php echo number_format(($data['amount'] / $total_amount) * 100, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($payment_methods)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> By Payment Method
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Count</th>
                                <th>Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_methods as $method => $data): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $method)); ?></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td>$<?php echo number_format($data['amount'], 2); ?></td>
                                    <td><?php echo number_format(($data['amount'] / $total_amount) * 100, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Report Title -->
<div class="d-none d-print-block mb-4">
    <h2 class="text-center">Payments Report</h2>
    <?php if ($selected_year): ?>
        <h4 class="text-center">
            Academic Year: <?php echo htmlspecialchars($selected_year['name']); ?>
            (<?php echo date('M d, Y', strtotime($selected_year['start_date'])); ?> - 
            <?php echo date('M d, Y', strtotime($selected_year['end_date'])); ?>)
        </h4>
    <?php endif; ?>
    <p class="text-center">Generated on <?php echo date('F d, Y \a\t h:i A'); ?></p>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table"></i> Payments
    </div>
    <div class="card-body">
        <?php if ($payments): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($payment['student_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($payment['grade'] . '-' . $payment['section']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo $payment['reference_number'] ? htmlspecialchars($payment['reference_number']) : '-'; ?></td>
                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No payments found matching the selected criteria.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export to CSV
    document.getElementById('exportCSV').addEventListener('click', function() {
        // Get table
        const table = document.getElementById('paymentsTable');
        if (!table) return;
        
        // Create CSV content
        let csv = [];
        
        // Add header
        const header = [];
        for (const cell of table.rows[0].cells) {
            header.push(cell.textContent.trim());
        }
        csv.push(header.join(','));
        
        // Add rows
        for (let i = 1; i < table.rows.length; i++) {
            const row = [];
            for (const cell of table.rows[i].cells) {
                // Replace commas with spaces to avoid CSV issues
                row.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            }
            csv.push(row.join(','));
        }
        
        // Create file
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        // Create download link
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'payments_report.csv');
        link.click();
    });
});
</script>