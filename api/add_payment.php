<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Make sure to include necessary files
require_once __DIR__ . '/../../shared/db.php'; // Adjust path if needed
session_start();

// Log request and POST data
file_put_contents('api_debug.log', date('Y-m-d H:i:s') . " - API called\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Extract and validate form data
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $academic_year_id = isset($_POST['academic_year_id']) ? (int)$_POST['academic_year_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
    $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $reference_number = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Log the processed data
    file_put_contents('api_debug.log', 
                      date('Y-m-d H:i:s') . " - Processed data:\n" . 
                      "student_id: $student_id\n" .
                      "academic_year_id: $academic_year_id\n" .
                      "amount: $amount\n" .
                      "payment_date: $payment_date\n" .
                      "payment_type: $payment_type\n" .
                      "payment_method: $payment_method\n" .
                      "reference_number: $reference_number\n" .
                      "notes: $notes\n" .
                      "created_by: $created_by\n\n", 
                      FILE_APPEND);
    
    // Basic validation
    $errors = [];
    if (empty($student_id)) {
        $errors[] = 'Student is required';
    }
    if (empty($academic_year_id)) {
        $errors[] = 'Academic year is required';
    }
    if (empty($amount) || $amount <= 0) {
        $errors[] = 'Valid amount is required';
    }
    
    if (empty($errors)) {
        try {
            // Get database connection
            $conn = db_connect();
            
            // Insert the payment
            $query = "INSERT INTO payments (
                          student_id, 
                          academic_year_id, 
                          amount, 
                          payment_date, 
                          payment_type, 
                          payment_method, 
                          reference_number, 
                          notes, 
                          created_by,
                          created_at
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param('iidsssssi', 
                $student_id, 
                $academic_year_id, 
                $amount, 
                $payment_date, 
                $payment_type, 
                $payment_method, 
                $reference_number, 
                $notes, 
                $created_by
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $payment_id = $stmt->insert_id;
            
            // Log the successful insertion
            file_put_contents('api_debug.log', 
                              date('Y-m-d H:i:s') . " - Payment inserted successfully. ID: $payment_id\n\n", 
                              FILE_APPEND);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Payment created successfully',
                'payment_id' => $payment_id
            ]);
            
        } catch (Exception $e) {
            // Log the error
            file_put_contents('api_debug.log', 
                              date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n\n", 
                              FILE_APPEND);
            
            // Return error response
            echo json_encode([
                'success' => false,
                'message' => 'Error creating payment: ' . $e->getMessage()
            ]);
        }
    } else {
        // Return validation error response
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed: ' . implode(', ', $errors),
            'errors' => $errors
        ]);
    }
} else {
    // Return method error response
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is supported.'
    ]);
}