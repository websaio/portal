<?php
/**
 * Add new payment form
 */
// Get student ID from query string if available
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$student = null;

// If student ID is provided, get student details
if ($student_id) {
    $student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
    if ($student) {
        $student = $student[0];
    }
}

// Get current enrollment if student is selected
$enrollment = null;
if ($student && $currentYearId) {
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

// Calculate balance
$balance = 0;
if ($enrollment) {
    // Calculate total due
    $due = $enrollment['tuition_fee'] - $enrollment['discount_amount'] - $enrollment['scholarship_amount'];
    
    // Calculate total paid
    $paid = db_select(
        "SELECT SUM(amount) as total_paid 
         FROM payments 
         WHERE student_id = ? AND academic_year_id = ?",
        [$student_id, $currentYearId],
        'ii'
    );
    
    $total_paid = $paid[0]['total_paid'] ?? 0;
    $balance = $due - $total_paid;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Payment</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=payments" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-money-bill-wave"></i> Payment Information
    </div>
    <div class="card-body">
        <form id="add-payment-form" method="POST" action="api/add_payment.php">
            <!-- Student Selection -->
            <div class="mb-4">
                <label for="student_id" class="form-label">Student *</label>
                <?php if ($student): ?>
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <div class="form-control-plaintext">
                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                        (<?php echo htmlspecialchars($student['student_id']); ?>)
                        <div class="small text-muted">
                            <?php
                            if ($enrollment) {
                                echo htmlspecialchars($enrollment['grade'] . ' - ' . $enrollment['section'] . ' | ' . $enrollment['academic_year']);
                            } else {
                                echo 'Not enrolled in current academic year';
                            }
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select a Student</option>
                        <?php
                        // Get all active students enrolled in current academic year
                        $students = db_select(
                            "SELECT s.*, e.grade, e.section 
                             FROM students s
                             JOIN student_enrollments e ON s.id = e.student_id
                             WHERE e.academic_year_id = ? AND s.status = 'active'
                             ORDER BY s.last_name, s.first_name",
                            [$currentYearId],
                            'i'
                        );
                        
                        if ($students):
                            foreach ($students as $s):
                        ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?> 
                                (<?php echo htmlspecialchars($s['student_id']); ?>) 
                                - <?php echo htmlspecialchars($s['grade'] . '-' . $s['section']); ?>
                            </option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <?php if ($enrollment): ?>
            <!-- Balance Information -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body p-3">
                            <div class="small text-muted">Tuition Fee</div>
                            <div class="h5">$<?php echo number_format($enrollment['tuition_fee'], 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body p-3">
                            <div class="small text-muted">Total Due (After Discounts)</div>
                            <div class="h5">$<?php echo number_format($enrollment['tuition_fee'] - $enrollment['discount_amount'] - $enrollment['scholarship_amount'], 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body p-3">
                            <div class="small text-muted">Current Balance</div>
                            <div class="h5 <?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                                $<?php echo number_format($balance, 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Payment Details -->
                <div class="col-md-6">
                    <h5 class="mb-3">Payment Details</h5>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date *</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0.01" required
                                   <?php echo ($balance > 0) ? "value=\"$balance\"" : ""; ?>>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type *</label>
                        <select class="form-select" id="payment_type" name="payment_type" required>
                            <?php foreach ($payment_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $type === 'tuition' ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="col-md-6">
                    <h5 class="mb-3">Payment Method</h5>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Method *</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <?php foreach ($payment_methods as $method): ?>
                                <option value="<?php echo $method; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $method)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" 
                               placeholder="Check number, transaction ID, etc.">
                        <div class="form-text">Reference number for check, card, or bank transfer payments.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <!-- Add this inside your form -->
<input type="hidden" id="created_by" name="created_by" value="<?php echo $_SESSION['user_id']; ?>">
            <!-- Hidden field for academic year ID -->
<input type="hidden" id="academic_year_id" name="academic_year_id" value="<?php echo $currentYearId; ?>">
            <div class="mt-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='index.php?page=payments'">
                    Cancel
                
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    document.getElementById('add-payment-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        // Get form data
        const formData = new FormData(this);
        
        // Add current user ID
        const userId = '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>';
        console.log('User ID:', userId); // Debug output
        if (userId) {
            formData.append('created_by', userId);
        } else {
            console.error('No user ID found in session');
            alert('Error: User session not found. Please log in again.');
            return;
        }
        
        // Add academic year ID
        const academicYearId = '<?php echo $currentYearId; ?>';
        console.log('Academic Year ID:', academicYearId); // Debug output
        formData.append('academic_year_id', academicYearId);
        
        // Send AJAX request
        fetch('api/add_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server returned status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Payment created successfully');
                
                // Redirect to payment view or list
                if (data.payment_id) {
                    window.location.href = 'index.php?page=payments&action=view&id=' + data.payment_id;
                } else {
                    window.location.href = 'index.php?page=payments';
                }
            } else {
                // Show error message
                alert('Error: ' + (data.message || 'Unknown error occurred'));
                console.error('API error:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the payment: ' + error.message);
        });
    });
    
    // Handle payment method change
    document.getElementById('payment_method').addEventListener('change', function() {
        const referenceField = document.getElementById('reference_number');
        const referenceLabel = document.querySelector('label[for="reference_number"]');
        
        if (this.value === 'cash') {
            referenceField.required = false;
            referenceLabel.textContent = 'Reference Number';
        } else if (this.value === 'check') {
            referenceField.required = true;
            referenceLabel.textContent = 'Check Number *';
        } else if (this.value === 'bank_transfer') {
            referenceField.required = true;
            referenceLabel.textContent = 'Transaction ID *';
        } else {
            referenceField.required = true;
            referenceLabel.textContent = 'Reference Number *';
        }
    });
});
</script>