<?php
/**
 * Add academic year form
 */

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $is_current = isset($_POST['is_current']) ? 1 : 0;
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($start_date)) {
        $errors[] = 'Start date is required';
    }
    
    if (empty($end_date)) {
        $errors[] = 'End date is required';
    }
    
    if ($start_date && $end_date && strtotime($start_date) >= strtotime($end_date)) {
        $errors[] = 'End date must be after start date';
    }
    
    // Check if name already exists
    if (!empty($name)) {
        $existing = db_select("SELECT id FROM academic_years WHERE name = ?", [$name], 's');
        if ($existing) {
            $errors[] = 'An academic year with this name already exists';
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn = db_connect();
        $conn->begin_transaction();
        
        try {
            // If setting this as current, unset others
            if ($is_current) {
                $stmt = $conn->prepare("UPDATE academic_years SET is_current = 0");
                $stmt->execute();
            }
            
            // Insert academic year
            $stmt = $conn->prepare("INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $name, $start_date, $end_date, $is_current);
            $stmt->execute();
            
            if ($stmt->affected_rows <= 0) {
                throw new Exception("Failed to create academic year");
            }
            
            $year_id = $stmt->insert_id;
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to the academic year list
            header('Location: index.php?page=academic_years&action=view&id=' . $year_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Academic Year</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=academic_years" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Academic Years
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-calendar-alt"></i> Academic Year Information
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Academic Year Name *</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required
                       placeholder="e.g., 2025-2026">
                <div class="form-text">Enter a unique name for this academic year.</div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo isset($start_date) ? htmlspecialchars($start_date) : ''; ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End Date *</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo isset($end_date) ? htmlspecialchars($end_date) : ''; ?>" required>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_current" name="is_current" 
                       <?php echo (isset($is_current) && $is_current) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_current">Set as current academic year</label>
                <div class="form-text">If checked, this will be set as the current academic year for all operations.</div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end">
                <a href="index.php?page=academic_years" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Academic Year
                </button>
            </div>
        </form>
    </div>
</div>