<?php
/**
 * Users management page controller
 */

// Check if user is admin
if (!is_admin()) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    return;
}

// Get action and user ID from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load appropriate view based on action
switch ($action) {
    case 'add':
        include 'users/add.php';
        break;
        
    case 'edit':
        if (!$user_id) {
            echo '<div class="alert alert-danger">User ID is required</div>';
            include 'users/list.php';
            break;
        }
        include 'users/edit.php';
        break;
        
    case 'view':
        if (!$user_id) {
            echo '<div class="alert alert-danger">User ID is required</div>';
            include 'users/list.php';
            break;
        }
        include 'users/view.php';
        break;
        
    default:
        include 'users/list.php';
        break;
}
?>