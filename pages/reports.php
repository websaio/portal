<?php
/**
 * Reports page controller
 */

// Get report type from request
$report_type = isset($_GET['type']) ? $_GET['type'] : 'payments';

// Get the current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$currentYearId = null;

if ($current_academic_year) {
    $currentYearId = $current_academic_year[0]['id'];
}

// Get all academic years for filtering
$academic_years = db_select("SELECT * FROM academic_years ORDER BY start_date DESC");

// Get academic year ID from request or use current
$academic_year_id = isset($_GET['academic_year']) ? intval($_GET['academic_year']) : $currentYearId;

// Load appropriate report based on type
switch ($report_type) {
    case 'students':
        include 'reports/students.php';
        break;
        
    case 'payments_by_type':
        include 'reports/payments_by_type.php';
        break;
        
    case 'payments_by_method':
        include 'reports/payments_by_method.php';
        break;
        
    case 'payments_by_month':
        include 'reports/payments_by_month.php';
        break;
        
    case 'outstanding_balance':
        include 'reports/outstanding_balance.php';
        break;
        
    default:
        include 'reports/payments.php';
        break;
}
?>