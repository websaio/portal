<?php
/**
 * Add new student form
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Student</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=students" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus"></i> Student Information
    </div>
    <div class="card-body">
        <form id="add-student-form" method="POST" action="api/add_student.php">
            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <h5 class="mb-3">Personal Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender *</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               placeholder="Leave blank to auto-generate">
                        <div class="form-text">If left blank, a student ID will be automatically generated.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-6">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="Iraq">
                    </div>
                </div>
                
                <!-- Contact Information & Enrollment -->
                <div class="col-md-6">
                    <h5 class="mb-3">Contact Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="parent_phone" class="form-label">Parent Phone</label>
                            <input type="tel" class="form-control" id="parent_phone" name="parent_phone">
                        </div>
                        <div class="col-md-6">
                            <label for="parent_email" class="form-label">Parent Email</label>
                            <input type="email" class="form-control" id="parent_email" name="parent_email">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Enrollment Information</h5>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="graduated">Graduated</option>
                            <option value="transferred">Transferred</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="academic_year_id" class="form-label">Academic Year *</label>
                            <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?php echo $year['id']; ?>" <?php echo ($currentYearId == $year['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="grade" class="form-label">Grade *</label>
                            <select class="form-select" id="grade" name="grade" required>
                                <option value="">Select Grade</option>
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="section" class="form-label">Section *</label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $s): ?>
                                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tuition_fee" class="form-label">Tuition Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="tuition_fee" name="tuition_fee" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="discount_percentage" class="form-label">Discount %</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                       step="0.01" min="0" max="100" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="discount_amount" class="form-label">Discount Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                       step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="scholarship_percentage" class="form-label">Scholarship %</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="scholarship_percentage" name="scholarship_percentage" 
                                       step="0.01" min="0" max="100" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="scholarship_amount" class="form-label">Scholarship Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="scholarship_amount" name="scholarship_amount" 
                                       step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="enrollment_date" class="form-label">Enrollment Date</label>
                        <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='index.php?page=students'">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Calculate discount amount when percentage changes
document.getElementById('discount_percentage').addEventListener('input', function() {
    const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
    const discountPercentage = parseFloat(this.value) || 0;
    const discountAmount = (tuitionFee * discountPercentage / 100).toFixed(2);
    document.getElementById('discount_amount').value = discountAmount;
});

// Calculate discount percentage when amount changes
document.getElementById('discount_amount').addEventListener('input', function() {
    const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
    const discountAmount = parseFloat(this.value) || 0;
    let discountPercentage = 0;
    
    if (tuitionFee > 0) {
        discountPercentage = (discountAmount / tuitionFee * 100).toFixed(2);
    }
    
    document.getElementById('discount_percentage').value = discountPercentage;
});

// Calculate scholarship amount when percentage changes
document.getElementById('scholarship_percentage').addEventListener('input', function() {
    const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
    const scholarshipPercentage = parseFloat(this.value) || 0;
    const scholarshipAmount = (tuitionFee * scholarshipPercentage / 100).toFixed(2);
    document.getElementById('scholarship_amount').value = scholarshipAmount;
});

// Calculate scholarship percentage when amount changes
document.getElementById('scholarship_amount').addEventListener('input', function() {
    const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
    const scholarshipAmount = parseFloat(this.value) || 0;
    let scholarshipPercentage = 0;
    
    if (tuitionFee > 0) {
        scholarshipPercentage = (scholarshipAmount / tuitionFee * 100).toFixed(2);
    }
    
    document.getElementById('scholarship_percentage').value = scholarshipPercentage;
});

// Recalculate discount and scholarship when tuition fee changes
document.getElementById('tuition_fee').addEventListener('input', function() {
    // Trigger discount calculation
    const discountPercentageEvent = new Event('input', {
        bubbles: true,
        cancelable: true,
    });
    document.getElementById('discount_percentage').dispatchEvent(discountPercentageEvent);
    
    // Trigger scholarship calculation
    const scholarshipPercentageEvent = new Event('input', {
        bubbles: true,
        cancelable: true,
    });
    document.getElementById('scholarship_percentage').dispatchEvent(scholarshipPercentageEvent);
});

// Form submission
document.getElementById('add-student-form').addEventListener('submit', function(e) {
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
    fetch('api/add_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Student created successfully');
            
            // Redirect to student list or view
            window.location.href = 'index.php?page=students&action=view&id=' + data.student_id;
        } else {
            // Show error message
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the student');
    });
});
</script>
