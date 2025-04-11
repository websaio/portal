<?php
/**
 * Receipt generation page
 */

// Database connection
$conn = db_connect();

// Check if payment ID is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    echo '<div class="alert alert-danger">Payment ID is required</div>';
    exit;
}

$payment_id = (int)$_GET['payment_id'];

// Start transaction
try {
    $conn->begin_transaction();
    
    // Get payment data
    $payment_query = "SELECT p.*, s.id as student_id, s.first_name, s.last_name, s.student_id as student_number 
                      FROM payments p 
                      LEFT JOIN students s ON p.student_id = s.id
                      WHERE p.id = ?";
    $payment_stmt = $conn->prepare($payment_query);
    
    if (!$payment_stmt) {
        throw new Exception("Prepare failed for payment: " . $conn->error);
    }
    
    $payment_stmt->bind_param('i', $payment_id);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    
    if ($payment_result->num_rows === 0) {
        throw new Exception("Payment not found");
    }
    
    $payment = $payment_result->fetch_assoc();
    $payment_stmt->close();
    
    // Check if receipt already exists for this payment
    $check_receipt_query = "SELECT * FROM receipts WHERE payment_id = ?";
    $check_receipt_stmt = $conn->prepare($check_receipt_query);
    
    if (!$check_receipt_stmt) {
        throw new Exception("Prepare failed for receipt check: " . $conn->error);
    }
    
    $check_receipt_stmt->bind_param('i', $payment_id);
    $check_receipt_stmt->execute();
    $receipt_result = $check_receipt_stmt->get_result();
    
    if ($receipt_result->num_rows > 0) {
        // Receipt already exists, redirect to view it
        $receipt = $receipt_result->fetch_assoc();
        $check_receipt_stmt->close();
        $conn->commit();
        
        header("Location: index.php?page=receipts&action=view&id=" . $receipt['id']);
        exit;
    }
    
    $check_receipt_stmt->close();
    
    // Generate unique receipt number
    $receipt_prefix = get_setting_value('receipt_prefix', 'REC-');
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    
    // Get the latest receipt number for today
    $latest_query = "SELECT receipt_number FROM receipts WHERE DATE(created_at) = CURDATE() ORDER BY id DESC LIMIT 1";
    $latest_result = $conn->query($latest_query);
    
    if ($latest_result && $latest_result->num_rows > 0) {
        $latest = $latest_result->fetch_assoc();
        $latest_number = (int)substr($latest['receipt_number'], -4);
        $next_number = $latest_number + 1;
    } else {
        $next_number = 1;
    }
    
    $receipt_number = $receipt_prefix . $year . $month . $day . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    
    // Create receipt record
    $receipt_insert = "INSERT INTO receipts (receipt_number, payment_id, student_id, academic_year_id, created_by, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
    $receipt_stmt = $conn->prepare($receipt_insert);
    
    if (!$receipt_stmt) {
        throw new Exception("Prepare failed for receipt insert: " . $conn->error);
    }
    
    $created_by = $_SESSION['user_id'];
    $receipt_stmt->bind_param('siiis', $receipt_number, $payment_id, $payment['student_id'], $payment['academic_year_id'], $created_by);
    
    if (!$receipt_stmt->execute()) {
        throw new Exception("Failed to create receipt: " . $receipt_stmt->error);
    }
    
    $receipt_id = $receipt_stmt->insert_id;
    $receipt_stmt->close();
    
    // Get receipt data
    $get_receipt_query = "SELECT * FROM receipts WHERE id = ?";
    $get_receipt_stmt = $conn->prepare($get_receipt_query);
    
    if (!$get_receipt_stmt) {
        throw new Exception("Prepare failed for get receipt: " . $conn->error);
    }
    
    $get_receipt_stmt->bind_param('i', $receipt_id);
    $get_receipt_stmt->execute();
    $receipt_result = $get_receipt_stmt->get_result();
    
    if ($receipt_result->num_rows === 0) {
        throw new Exception("Failed to retrieve receipt");
    }
    
    $receipt = $receipt_result->fetch_assoc();
    $get_receipt_stmt->close();
    
    // Log receipt creation
    log_message("Receipt generated with ID: " . $receipt_id . " for payment ID: " . $payment_id);
    
    // Generate PDF
    require_once __DIR__ . '/../../includes/receipt_pdf.php';
    $pdf = new ReceiptPDF($receipt);
    $pdf_path = $pdf->generatePDF();
    
    // Update receipt with PDF path
    $update_query = "UPDATE receipts SET pdf_path = ? WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed for update: " . $conn->error);
    }
    
    $update_stmt->bind_param('si', $pdf_path, $receipt_id);
    $update_success = $update_stmt->execute();
    
    if (!$update_success) {
        throw new Exception("Failed to update receipt with PDF path: " . $update_stmt->error);
    }
    
    $update_stmt->close();
    
    // Add audit log
    add_audit_log('generate_receipt', 'receipt', $receipt_id, 'Generated receipt for payment #' . $payment_id);
    
    // Commit transaction
    $conn->commit();
    
    // Show success message and redirect
    echo '<div class="alert alert-success">Receipt generated successfully!</div>';
    echo '<script>
        setTimeout(function() {
            window.location.href = "index.php?page=receipts&action=view&id=' . $receipt_id . '";
        }, 1500);
    </script>';
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log the error
    log_message("Receipt generation error: " . $e->getMessage(), 'error');
    
    // Display error message
    echo '<div class="alert alert-danger">Error generating receipt: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p><a href="index.php?page=payments" class="btn btn-primary">Back to Payments</a></p>';
}