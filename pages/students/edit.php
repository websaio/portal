<?php
/**
 * Edit student form
 */

// Get student data
$student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');

if (!$student) {
    echo '<div class="alert alert-danger">Student not found</div>';
    return;
}

$student = $student[0];

// Get enrollments
$enrollments = db_select(
    "SELECT e.*, a.name as academic_year 
     FROM student_enrollments e
     JOIN academic_years a ON e.academic_year_id = a.id
     WHERE e.student_id = ?
     ORDER BY a.start_date DESC",
    [$student_id],
    'i'
);

// Organize enrollments by academic year ID for easy access
$enrollmentsByYear = [];
if ($enrollments) {
    foreach ($enrollments as $enrollment) {
        $enrollmentsByYear[$enrollment['academic_year_id']] = $enrollment;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Student</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=students&action=view&id=<?php echo $student_id; ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Student
        </a>
        <a href="index.php?page=students" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-list"></i> All Students
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-edit"></i> Edit Student Information
    </div>
    <div class="card-body">
        <form id="edit-student-form" method="POST" action="api/update_student.php">
            <input type="hidden" name="id" value="<?php echo $student_id; ?>">
            
            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <h5 class="mb-3">Personal Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender *</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo $student['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                   value="<?php echo $student['date_of_birth'] ? date('Y-m-d', strtotime($student['date_of_birth'])) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID *</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city"
                                   value="<?php echo htmlspecialchars($student['city']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state"
                                   value="<?php echo htmlspecialchars($student['state']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country"
                               value="<?php echo htmlspecialchars($student['country']); ?>">
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-md-6">
                    <h5 class="mb-3">Contact Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name"
                               value="<?php echo htmlspecialchars($student['parent_name']); ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="parent_phone" class="form-label">Parent Phone</label>
                            <input type="tel" class="form-control" id="parent_phone" name="parent_phone"
                                   value="<?php echo htmlspecialchars($student['parent_phone']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="parent_email" class="form-label">Parent Email</label>
                            <input type="email" class="form-control" id="parent_email" name="parent_email"
                                   value="<?php echo htmlspecialchars($student['parent_email']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="graduated" <?php echo $student['status'] === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                            <option value="transferred" <?php echo $student['status'] === 'transferred' ? 'selected' : ''; ?>>Transferred</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Enrollment Information -->
            <h5 class="mb-3">Enrollment Information</h5>
            
            <div class="card mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="enrollmentTabs" role="tablist">
                        <?php foreach ($academic_years as $index => $year): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($index === 0 && $year['id'] == $currentYearId) || 
                                                             ($index === 0 && $currentYearId === null) ? 'active' : ''; ?>" 
                                        id="year-tab-<?php echo $year['id']; ?>" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#year-content-<?php echo $year['id']; ?>" 
                                        type="button" role="tab" 
                                        aria-controls="year-content-<?php echo $year['id']; ?>" 
                                        aria-selected="<?php echo ($index === 0 && $year['id'] == $currentYearId) || 
                                                            ($index === 0 && $currentYearId === null) ? 'true' : 'false'; ?>">
                                    <?php echo htmlspecialchars($year['name']); ?>
                                    <?php if (isset($enrollmentsByYear[$year['id']])): ?>
                                        <i class="fas fa-check-circle text-success ms-1"></i>
                                    <?php endif; ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="enrollmentTabsContent">
                        <?php foreach ($academic_years as $index => $year): 
                            $enrollment = isset($enrollmentsByYear[$year['id']]) ? $enrollmentsByYear[$year['id']] : null;
                        ?>
                            <div class="tab-pane fade <?php echo ($index === 0 && $year['id'] == $currentYearId) || 
                                                           ($index === 0 && $currentYearId === null) ? 'show active' : ''; ?>" 
                                 id="year-content-<?php echo $year['id']; ?>" 
                                 role="tabpanel" 
                                 aria-labelledby="year-tab-<?php echo $year['id']; ?>">
                                
                                <div class="alert <?php echo $enrollment ? 'alert-info' : 'alert-warning'; ?> mb-3">
                                    <?php if ($enrollment): ?>
                                        <i class="fas fa-info-circle"></i> Student is already enrolled in this academic year.
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle"></i> Student is not enrolled in this academic year. Fill in the information below to enroll.
                                    <?php endif; ?>
                                </div>
                                
                                <input type="hidden" name="enrollment[<?php echo $year['id']; ?>][academic_year_id]" value="<?php echo $year['id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="grade-<?php echo $year['id']; ?>" class="form-label">Grade</label>
                                        <select class="form-select" id="grade-<?php echo $year['id']; ?>" name="enrollment[<?php echo $year['id']; ?>][grade]">
                                            <option value="">Select Grade</option>
                                            <?php foreach ($grades as $g): ?>
                                                <option value="<?php echo $g; ?>" <?php echo $enrollment && $enrollment['grade'] === $g ? 'selected' : ''; ?>>
                                                    <?php echo $g; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="section-<?php echo $year['id']; ?>" class="form-label">Section</label>
                                        <select class="form-select" id="section-<?php echo $year['id']; ?>" name="enrollment[<?php echo $year['id']; ?>][section]">
                                            <option value="">Select Section</option>
                                            <?php foreach ($sections as $s): ?>
                                                <option value="<?php echo $s; ?>" <?php echo $enrollment && $enrollment['section'] === $s ? 'selected' : ''; ?>>
                                                    <?php echo $s; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tuition_fee-<?php echo $year['id']; ?>" class="form-label">Tuition Fee</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control tuition-fee" 
                                               id="tuition_fee-<?php echo $year['id']; ?>" 
                                               name="enrollment[<?php echo $year['id']; ?>][tuition_fee]" 
                                               step="0.01" min="0"
                                               value="<?php echo $enrollment ? $enrollment['tuition_fee'] : ''; ?>"
                                               data-year-id="<?php echo $year['id']; ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="discount_percentage-<?php echo $year['id']; ?>" class="form-label">Discount %</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control discount-percentage" 
                                                   id="discount_percentage-<?php echo $year['id']; ?>" 
                                                   name="enrollment[<?php echo $year['id']; ?>][discount_percentage]" 
                                                   step="0.01" min="0" max="100"
                                                   value="<?php echo $enrollment ? $enrollment['discount_percentage'] : '0'; ?>"
                                                   data-year-id="<?php echo $year['id']; ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="discount_amount-<?php echo $year['id']; ?>" class="form-label">Discount Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control discount-amount" 
                                                   id="discount_amount-<?php echo $year['id']; ?>" 
                                                   name="enrollment[<?php echo $year['id']; ?>][discount_amount]" 
                                                   step="0.01" min="0"
                                                   value="<?php echo $enrollment ? $enrollment['discount_amount'] : '0'; ?>"
                                                   data-year-id="<?php echo $year['id']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="scholarship_percentage-<?php echo $year['id']; ?>" class="form-label">Scholarship %</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control scholarship-percentage" 
                                                   id="scholarship_percentage-<?php echo $year['id']; ?>" 
                                                   name="enrollment[<?php echo $year['id']; ?>][scholarship_percentage]" 
                                                   step="0.01" min="0" max="100"
                                                   value="<?php echo $enrollment ? $enrollment['scholarship_percentage'] : '0'; ?>"
                                                   data-year-id="<?php echo $year['id']; ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="scholarship_amount-<?php echo $year['id']; ?>" class="form-label">Scholarship Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control scholarship-amount" 
                                                   id="scholarship_amount-<?php echo $year['id']; ?>" 
                                                   name="enrollment[<?php echo $year['id']; ?>][scholarship_amount]" 
                                                   step="0.01" min="0"
                                                   value="<?php echo $enrollment ? $enrollment['scholarship_amount'] : '0'; ?>"
                                                   data-year-id="<?php echo $year['id']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="enrollment_date-<?php echo $year['id']; ?>" class="form-label">Enrollment Date</label>
                                    <input type="date" class="form-control" 
                                           id="enrollment_date-<?php echo $year['id']; ?>" 
                                           name="enrollment[<?php echo $year['id']; ?>][enrollment_date]"
                                           value="<?php echo $enrollment ? date('Y-m-d', strtotime($enrollment['enrollment_date'])) : date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes-<?php echo $year['id']; ?>" class="form-label">Notes</label>
                                    <textarea class="form-control" 
                                              id="notes-<?php echo $year['id']; ?>" 
                                              name="enrollment[<?php echo $year['id']; ?>][notes]" 
                                              rows="3"><?php echo $enrollment ? $enrollment['notes'] : ''; ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='index.php?page=students&action=view&id=<?php echo $student_id; ?>'">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs
    const triggerTabList = [].slice.call(document.querySelectorAll('#enrollmentTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Setup discount and scholarship calculations for each year
    const academicYears = <?php echo json_encode(array_column($academic_years, 'id')); ?>;
    
    academicYears.forEach(function(yearId) {
        // Setup calculation handlers
        setupCalculations(yearId);
    });
    
    // Form submission
    document.getElementById('edit-student-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        // Get form data
        const formData = new FormData(this);
        
        // Send AJAX request
        fetch('api/update_student.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Student updated successfully');
                
                // Redirect back to student view
                window.location.href = 'index.php?page=students&action=view&id=' + <?php echo $student_id; ?>;
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the student');
        });
    });
});

function setupCalculations(yearId) {
    // Get elements
    const tuitionFeeInput = document.getElementById(`tuition_fee-${yearId}`);
    const discountPercentageInput = document.getElementById(`discount_percentage-${yearId}`);
    const discountAmountInput = document.getElementById(`discount_amount-${yearId}`);
    const scholarshipPercentageInput = document.getElementById(`scholarship_percentage-${yearId}`);
    const scholarshipAmountInput = document.getElementById(`scholarship_amount-${yearId}`);
    
    // Calculate discount amount when percentage changes
    discountPercentageInput.addEventListener('input', function() {
        const tuitionFee = parseFloat(tuitionFeeInput.value) || 0;
        const discountPercentage = parseFloat(this.value) || 0;
        const discountAmount = (tuitionFee * discountPercentage / 100).toFixed(2);
        discountAmountInput.value = discountAmount;
    });

    // Calculate discount percentage when amount changes
    discountAmountInput.addEventListener('input', function() {
        const tuitionFee = parseFloat(tuitionFeeInput.value) || 0;
        const discountAmount = parseFloat(this.value) || 0;
        let discountPercentage = 0;
        
        if (tuitionFee > 0) {
            discountPercentage = (discountAmount / tuitionFee * 100).toFixed(2);
        }
        
        discountPercentageInput.value = discountPercentage;
    });

    // Calculate scholarship amount when percentage changes
    scholarshipPercentageInput.addEventListener('input', function() {
        const tuitionFee = parseFloat(tuitionFeeInput.value) || 0;
        const scholarshipPercentage = parseFloat(this.value) || 0;
        const scholarshipAmount = (tuitionFee * scholarshipPercentage / 100).toFixed(2);
        scholarshipAmountInput.value = scholarshipAmount;
    });

    // Calculate scholarship percentage when amount changes
    scholarshipAmountInput.addEventListener('input', function() {
        const tuitionFee = parseFloat(tuitionFeeInput.value) || 0;
        const scholarshipAmount = parseFloat(this.value) || 0;
        let scholarshipPercentage = 0;
        
        if (tuitionFee > 0) {
            scholarshipPercentage = (scholarshipAmount / tuitionFee * 100).toFixed(2);
        }
        
        scholarshipPercentageInput.value = scholarshipPercentage;
    });
    
    // Recalculate when tuition fee changes
    tuitionFeeInput.addEventListener('input', function() {
        // Trigger discount calculation
        const discountPercentageEvent = new Event('input', {
            bubbles: true,
            cancelable: true,
        });
        discountPercentageInput.dispatchEvent(discountPercentageEvent);
        
        // Trigger scholarship calculation
        const scholarshipPercentageEvent = new Event('input', {
            bubbles: true,
            cancelable: true,
        });
        scholarshipPercentageInput.dispatchEvent(scholarshipPercentageEvent);
    });
}
</script>
