<?php
/**
 * Payments page controller
 */

// Constants for payment types and methods
require_once __DIR__ . '/../shared/constants.php';

// Get the current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$currentYearId = null;

if ($current_academic_year) {
    $currentYearId = $current_academic_year[0]['id'];
}

// Get all academic years for enrollment tabs
$academic_years = db_select("SELECT * FROM academic_years ORDER BY start_date DESC");

// Get action and payment ID from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load appropriate view based on action
switch ($action) {
    case 'add':
        include 'payments/add.php';
        break;
        
    case 'view':
        if (!$payment_id) {
            echo '<div class="alert alert-danger">Payment ID is required</div>';
            include 'payments/list.php';
            break;
        }
        include 'payments/view.php';
        break;
        
    default:
        include 'payments/list.php';
        break;
}
?>