<?php
/**
 * Students page controller
 */

// Constants for grades and sections
require_once __DIR__ . '/../shared/constants.php';

// Get the current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$currentYearId = null;

if ($current_academic_year) {
    $currentYearId = $current_academic_year[0]['id'];
}

// Get all academic years for enrollment tabs
$academic_years = db_select("SELECT * FROM academic_years ORDER BY start_date DESC");

// Get action and student ID from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load appropriate view based on action
switch ($action) {
    case 'add':
        include 'students/add.php';
        break;
        
    case 'edit':
        if (!$student_id) {
            echo '<div class="alert alert-danger">Student ID is required</div>';
            include 'students/list.php';
            break;
        }
        include 'students/edit.php';
        break;
        
    case 'view':
        if (!$student_id) {
            echo '<div class="alert alert-danger">Student ID is required</div>';
            include 'students/list.php';
            break;
        }
        include 'students/view.php';
        break;
        
    default:
        include 'students/list.php';
        break;
}
?>
