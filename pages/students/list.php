<?php
/**
 * Student list page
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
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

// Add academic year filter if applicable
if ($currentYearId && !isset($_GET['show_all'])) {
    $join_enrollment = true;
    $conditions[] = "e.academic_year_id = ?";
    $params[] = $currentYearId;
    $types .= 'i';
}

// Add grade filter if applicable
if (!empty($grade_filter)) {
    $join_enrollment = true;
    $conditions[] = "e.grade = ?";
    $params[] = $grade_filter;
    $types .= 's';
}

// Add section filter if applicable
if (!empty($section_filter)) {
    $join_enrollment = true;
    $conditions[] = "e.section = ?";
    $params[] = $section_filter;
    $types .= 's';
}

// Add status filter if applicable
if (!empty($status_filter)) {
    $conditions[] = "s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Add search filter if applicable
if (!empty($search)) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sssss';
}

// Build the query
$query = "SELECT s.*, ";

if (isset($join_enrollment) && $join_enrollment) {
    $query .= "e.grade, e.section, e.tuition_fee, e.discount_amount, e.scholarship_amount, ";
    $query .= "(e.tuition_fee - e.discount_amount - e.scholarship_amount) as net_fee, ";
    
    // Calculate balance - sum of payments for this student in current academic year
    $query .= "COALESCE((
                    SELECT SUM(p.amount) 
                    FROM payments p 
                    WHERE p.student_id = s.id AND p.academic_year_id = e.academic_year_id
                ), 0) as paid_amount, ";
    
    $query .= "((e.tuition_fee - e.discount_amount - e.scholarship_amount) - 
                COALESCE((
                    SELECT SUM(p.amount) 
                    FROM payments p 
                    WHERE p.student_id = s.id AND p.academic_year_id = e.academic_year_id
                ), 0)) as balance ";
    
    $query .= "FROM students s ";
    $query .= "JOIN student_enrollments e ON s.id = e.student_id ";
} else {
    $query .= "NULL as grade, NULL as section, NULL as tuition_fee, NULL as discount_amount, NULL as scholarship_amount, ";
    $query .= "NULL as net_fee, NULL as paid_amount, NULL as balance ";
    $query .= "FROM students s ";
}

// Add conditions
if (!empty($conditions)) {
    $query .= "WHERE " . implode(' AND ', $conditions);
}

// Add order by
$query .= " ORDER BY s.last_name, s.first_name";

// Fetch students
$students = db_select($query, $params, $types);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Students</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=students&action=add" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add New Student
        </a>
    </div>
</div>

<!-- Filter and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="students">
            
            <div class="col-md-2">
                <label for="grade" class="form-label">Grade</label>
                <select class="form-select form-select-sm" id="grade" name="grade">
                    <option value="">All Grades</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $grade_filter === $g ? 'selected' : ''; ?>>
                            <?php echo $g; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="section" class="form-label">Section</label>
                <select class="form-select form-select-sm" id="section" name="section">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $section_filter === $s ? 'selected' : ''; ?>>
                            <?php echo $s; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($student_statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                       placeholder="Name, ID, Email, Phone" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" id="show_all" name="show_all" value="1" 
                           <?php echo isset($_GET['show_all']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_all">
                        Show all students
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="index.php?page=students" class="btn btn-secondary btn-sm">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Academic Year Information -->
<?php if ($currentYear): ?>
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle"></i> 
    Showing students for academic year: <strong><?php echo htmlspecialchars($currentYear['name']); ?></strong>
    (<?php echo date('M d, Y', strtotime($currentYear['start_date'])); ?> - 
    <?php echo date('M d, Y', strtotime($currentYear['end_date'])); ?>)
</div>
<?php endif; ?>

<!-- Students Table -->
<div class="card">
    <div class="card-body">
        <?php if ($students): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Contact</th>
                            <?php if ($currentYearId && !isset($_GET['show_all'])): ?>
                                <th>Tuition</th>
                                <th>Paid</th>
                                <th>Balance</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
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
                                    <?php if (!empty($student['phone'])): ?>
                                        <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?></small><br>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($student['email'])): ?>
                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                
                                <?php if ($currentYearId && !isset($_GET['show_all'])): ?>
                                    <td class="text-end">
                                        <?php 
                                        if ($student['net_fee']) {
                                            echo '$' . number_format($student['net_fee'], 2);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end text-success">
                                        <?php 
                                        if ($student['paid_amount']) {
                                            echo '$' . number_format($student['paid_amount'], 2);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end <?php echo $student['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php 
                                        if ($student['balance'] !== null) {
                                            echo '$' . number_format($student['balance'], 2);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                                
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?page=students&action=view&id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=students&action=edit&id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=payments&action=add&student_id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Add Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <p class="text-muted">
                    Total: <strong><?php echo count($students); ?></strong> students
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No students found.
            </div>
        <?php endif; ?>
    </div>
</div>
