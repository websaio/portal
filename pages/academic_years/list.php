<?php
/**
 * Academic Years list page
 */

// Get all academic years
$academic_years = db_select("SELECT * FROM academic_years ORDER BY start_date DESC");

// Handle set current year action
if (isset($_GET['set_current']) && is_numeric($_GET['set_current'])) {
    $year_id = intval($_GET['set_current']);
    
    // Start transaction
    $conn = db_connect();
    $conn->begin_transaction();
    
    try {
        // First, unset current for all years
        $stmt = $conn->prepare("UPDATE academic_years SET is_current = 0");
        $stmt->execute();
        
        // Then set the selected year as current
        $stmt = $conn->prepare("UPDATE academic_years SET is_current = 1 WHERE id = ?");
        $stmt->bind_param('i', $year_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Show success message
        echo '<div class="alert alert-success">Current academic year updated successfully.</div>';
        
        // Reload academic years
        $academic_years = db_select("SELECT * FROM academic_years ORDER BY start_date DESC");
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Academic Years</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=academic_years&action=add" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add New Academic Year
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($academic_years): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="30%">Name</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th width="25%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academic_years as $year): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($year['name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($year['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($year['end_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($year['is_current']): ?>
                                        <span class="badge bg-success">Current</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?page=academic_years&action=view&id=<?php echo $year['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=academic_years&action=edit&id=<?php echo $year['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$year['is_current']): ?>
                                        <a href="index.php?page=academic_years&set_current=<?php echo $year['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Set as Current"
                                           onclick="return confirm('Set this as the current academic year?');">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No academic years found.
            </div>
            <p>
                <a href="index.php?page=academic_years&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Academic Year
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>