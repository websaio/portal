<?php
/**
 * Academic Years page controller
 */

// Check if user is admin
if (!is_admin()) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    return;
}

// Get action and ID from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$year_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load appropriate view based on action
switch ($action) {
    case 'add':
        include 'academic_years/add.php';
        break;
        
    case 'edit':
        if (!$year_id) {
            echo '<div class="alert alert-danger">Academic Year ID is required</div>';
            include 'academic_years/list.php';
            break;
        }
        include 'academic_years/edit.php';
        break;
        
    case 'view':
        if (!$year_id) {
            echo '<div class="alert alert-danger">Academic Year ID is required</div>';
            include 'academic_years/list.php';
            break;
        }
        include 'academic_years/view.php';
        break;
        
    default:
        include 'academic_years/list.php';
        break;
}
?>