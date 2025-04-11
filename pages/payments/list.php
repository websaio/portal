<?php
/**
 * Payment list page
 */

// Get the current academic year
$currentYear = null;
if ($currentYearId) {
    $currentYear = db_select("SELECT * FROM academic_years WHERE id = ?", [$currentYearId], 'i');
    if ($currentYear) {
        $currentYear = $currentYear[0];
    }
}

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? intval($_GET['student_id']) : '';
$payment_type_filter = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';
$payment_method_filter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

// Add academic year filter if applicable
if ($currentYearId && !isset($_GET['show_all'])) {
    $conditions[] = "p.academic_year_id = ?";
    $params[] = $currentYearId;
    $types .= 'i';
}

// Add student filter if applicable
if (!empty($student_filter)) {
    $conditions[] = "p.student_id = ?";
    $params[] = $student_filter;
    $types .= 'i';
}

// Add payment type filter if applicable
if (!empty($payment_type_filter)) {
    $conditions[] = "p.payment_type = ?";
    $params[] = $payment_type_filter;
    $types .= 's';
}

// Add payment method filter if applicable
if (!empty($payment_method_filter)) {
    $conditions[] = "p.payment_method = ?";
    $params[] = $payment_method_filter;
    $types .= 's';
}

// Add date range filter if applicable
if (!empty($date_from)) {
    $conditions[] = "p.payment_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $conditions[] = "p.payment_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Add search filter if applicable
if (!empty($search)) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR p.reference_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

// Build the query
$query = "SELECT p.*, s.first_name, s.last_name, s.student_id AS student_code, 
                 a.name AS academic_year, u.name AS staff_name
          FROM payments p
          JOIN students s ON p.student_id = s.id
          JOIN academic_years a ON p.academic_year_id = a.id
          JOIN users u ON p.created_by = u.id";

// Add conditions
if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

// Add order by
$query .= " ORDER BY p.payment_date DESC, p.id DESC";

// Fetch payments
$payments = db_select($query, $params, $types);

// Calculate total amount
$total_amount = 0;
if ($payments) {
    foreach ($payments as $payment) {
        $total_amount += $payment['amount'];
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Payments</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=payments&action=add" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add New Payment
        </a>
    </div>
</div>

<!-- Filter and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="payments">
            
            <?php if (!empty($student_filter)): ?>
            <input type="hidden" name="student_id" value="<?php echo $student_filter; ?>">
            <?php else: ?>
            <div class="col-md-3">
                <label for="payment_type" class="form-label">Payment Type</label>
                <select class="form-select form-select-sm" id="payment_type" name="payment_type">
                    <option value="">All Types</option>
                    <?php foreach ($payment_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $payment_type_filter === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select form-select-sm" id="payment_method" name="payment_method">
                    <option value="">All Methods</option>
                    <?php foreach ($payment_methods as $method): ?>
                        <option value="<?php echo $method; ?>" <?php echo $payment_method_filter === $method ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $method)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                       placeholder="Name, ID, Reference" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-12 d-flex justify-content-end">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" id="show_all" name="show_all" value="1" 
                           <?php echo isset($_GET['show_all']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_all">
                        Show all academic years
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="index.php?page=payments" class="btn btn-secondary btn-sm">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Academic Year Information -->
<?php if ($currentYear && !isset($_GET['show_all'])): ?>
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle"></i> 
    Showing payments for academic year: <strong><?php echo htmlspecialchars($currentYear['name']); ?></strong>
    (<?php echo date('M d, Y', strtotime($currentYear['start_date'])); ?> - 
    <?php echo date('M d, Y', strtotime($currentYear['end_date'])); ?>)
</div>
<?php endif; ?>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <?php if ($payments): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <a href="index.php?page=students&action=view&id=<?php echo $payment['student_id']; ?>">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </a>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['student_code']); ?></small>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo $payment['reference_number'] ? htmlspecialchars($payment['reference_number']) : '-'; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['staff_name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></small>
                                </td>
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
            
            <div class="mt-3 d-flex justify-content-between">
                <p class="text-muted">
                    Total: <strong><?php echo count($payments); ?></strong> payments
                </p>
                <p class="text-muted">
                    Total Amount: <strong>$<?php echo number_format($total_amount, 2); ?></strong>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No payments found.
            </div>
        <?php endif; ?>
    </div>
</div>