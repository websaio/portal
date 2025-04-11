<?php
/**
 * Receipt listing page - shows a table of all receipts
 */

// Get current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$current_academic_year = $current_academic_year[0] ?? null;

// Filter parameters
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : ($current_academic_year ? $current_academic_year['id'] : 0);
$month = isset($_GET['month']) ? $_GET['month'] : '';
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';

// Build query with only essential fields - avoiding u.first_name which is causing errors
$query = "SELECT r.*, 
                 p.payment_date, p.amount, p.payment_type, p.payment_method,
                 s.id as student_id, s.student_id as student_number, 
                 CONCAT(s.first_name, ' ', s.last_name) as student_fullname,
                 ay.name as academic_year_name
          FROM receipts r
          LEFT JOIN payments p ON r.payment_id = p.id
          LEFT JOIN students s ON p.student_id = s.id
          LEFT JOIN academic_years ay ON r.academic_year_id = ay.id
          WHERE 1=1";

$params = [];
$types = '';

// Add filters
if ($student_id > 0) {
    $query .= " AND p.student_id = ?";
    $params[] = $student_id;
    $types .= 'i';
}

if ($academic_year_id > 0) {
    $query .= " AND r.academic_year_id = ?";
    $params[] = $academic_year_id;
    $types .= 'i';
}

if (!empty($month)) {
    $query .= " AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= 's';
}

if (!empty($payment_type)) {
    $query .= " AND p.payment_type = ?";
    $params[] = $payment_type;
    $types .= 's';
}

// Add order by
$query .= " ORDER BY r.created_at DESC";

// Get receipts
$receipts = !empty($params) ? db_select($query, $params, $types) : db_select($query);

// Get all students
$students = db_select("SELECT id, first_name, last_name, student_id FROM students ORDER BY first_name, last_name");

// Get all academic years
$academic_years = db_select("SELECT id, name, is_current FROM academic_years ORDER BY start_date DESC");

// Get payment types from constants
require_once __DIR__ . '/../../shared/constants.php';
$payment_types = isset($payment_types) ? $payment_types : ['tuition', 'registration', 'uniform', 'books', 'transportation', 'other'];
?>

<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Receipts</h5>
    </div>
    <div class="card-body">
        <form method="get" class="row align-items-end g-3">
            <input type="hidden" name="page" value="receipts">
            
            <div class="col-md-3">
                <label for="student_id" class="form-label">Student</label>
                <select name="student_id" id="student_id" class="form-select">
                    <option value="">All Students</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo $student_id == $student['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                            (<?php echo htmlspecialchars($student['student_id']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="academic_year_id" class="form-label">Academic Year</label>
                <select name="academic_year_id" id="academic_year_id" class="form-select">
                    <option value="">All Academic Years</option>
                    <?php foreach ($academic_years as $year): ?>
                        <option value="<?php echo $year['id']; ?>" <?php echo $academic_year_id == $year['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year['name']); ?>
                            <?php if ($year['is_current']): ?> (Current)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="month" class="form-label">Month</label>
                <input type="month" name="month" id="month" class="form-control" value="<?php echo $month; ?>">
            </div>
            
            <div class="col-md-2">
                <label for="payment_type" class="form-label">Payment Type</label>
                <select name="payment_type" id="payment_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($payment_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $payment_type == $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($receipts)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No receipts found. Try changing your filters.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Receipts List</h5>
                </div>
                <div class="col-auto">
                    <span class="badge bg-primary"><?php echo count($receipts); ?> receipts found</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipts as $receipt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($receipt['receipt_number']); ?></td>
                            <td><?php echo isset($receipt['payment_date']) ? date('M j, Y', strtotime($receipt['payment_date'])) : 'N/A'; ?></td>
                            <td>
                                <?php if (isset($receipt['student_fullname'])): ?>
                                    <?php echo htmlspecialchars($receipt['student_fullname']); ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($receipt['student_number'] ?? 'N/A'); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($receipt['payment_type'])): ?>
                                    <span class="badge bg-info text-dark">
                                        <?php echo ucfirst(str_replace('_', ' ', $receipt['payment_type'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-primary">$<?php echo isset($receipt['amount']) ? number_format($receipt['amount'], 2) : '0.00'; ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($receipt['academic_year_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?page=receipts&action=view&id=<?php echo $receipt['id']; ?>" class="btn btn-outline-primary" title="View Receipt">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!empty($receipt['pdf_path']) && file_exists(__DIR__ . '/../../' . $receipt['pdf_path'])): ?>
                                        <a href="<?php echo $receipt['pdf_path']; ?>" target="_blank" class="btn btn-outline-success" title="Open PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?page=receipts&action=generate&payment_id=<?php echo $receipt['payment_id']; ?>" class="btn btn-outline-warning" title="Generate PDF">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Receipt" 
                                            onclick="confirmDelete(<?php echo $receipt['id']; ?>, '<?php echo htmlspecialchars($receipt['receipt_number']); ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete receipt <span id="receiptNumberToDelete" class="fw-bold"></span>?</p>
                <p class="text-danger">This action cannot be undone and will permanently remove the receipt.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteButton" class="btn btn-danger">Delete Receipt</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, receiptNumber) {
        document.getElementById('receiptNumberToDelete').textContent = receiptNumber;
        document.getElementById('confirmDeleteButton').href = 'index.php?page=receipts&action=delete&id=' + id;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
    }
</script>

<style>
    .badge {
        font-weight: normal;
        font-size: 0.85em;
    }
</style>