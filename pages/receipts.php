<?php
/**
 * Receipts page controller
 */

// Get the current academic year
$current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
$currentYearId = null;

if ($current_academic_year) {
    $currentYearId = $current_academic_year[0]['id'];
}

// Get action and receipt/payment ID from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$receipt_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : null;

// Load appropriate view based on action
switch ($action) {
    case 'view':
        if (!$receipt_id) {
            echo '<div class="alert alert-danger">Receipt ID is required</div>';
            include 'receipts/list.php';
            break;
        }
        include 'receipts/view.php';
        break;
        
    case 'generate':
        if (!$payment_id) {
            echo '<div class="alert alert-danger">Payment ID is required</div>';
            include 'receipts/list.php';
            break;
        }
        include 'receipts/generate.php';
        break;
        
    case 'print':
        if (!$receipt_id) {
            echo '<div class="alert alert-danger">Receipt ID is required</div>';
            include 'receipts/list.php';
            break;
        }
        include 'receipts/print.php';
        break;
        
    case 'email':
        if (!$receipt_id) {
            echo '<div class="alert alert-danger">Receipt ID is required</div>';
            include 'receipts/list.php';
            break;
        }
        include 'receipts/email.php';
        break;
        
    default:
        include 'receipts/list.php';
        break;
}
?>