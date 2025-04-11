<?php
/**
 * API endpoint for adding a new student
 */
session_start();

// Check if user is logged in
require_once __DIR__ . '/../auth/auth.php';
if (!is_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Validate required fields
$required_fields = ['first_name', 'last_name', 'gender', 'status'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Field '$field' is required"
        ]);
        exit;
    }
}

// Process student data
try {
    // Generate student ID if not provided
    if (empty($_POST['student_id'])) {
        $year = date('y');
        $random = rand(1000, 9999);
        $_POST['student_id'] = "ST{$year}{$random}";
    }
    
    // Check if student ID already exists
    $existing = db_select("SELECT id FROM students WHERE student_id = ?", [$_POST['student_id']], 's');
    if ($existing) {
        echo json_encode([
            'success' => false,
            'message' => 'Student ID already exists'
        ]);
        exit;
    }
    
    // Check if email already exists (if provided)
    if (!empty($_POST['email'])) {
        $existing = db_select("SELECT id FROM students WHERE email = ?", [$_POST['email']], 's');
        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists'
            ]);
            exit;
        }
    }
    
    // Start transaction
    $conn = db_connect();
    $conn->begin_transaction();
    
    // Prepare student data
    $fields = [
        'student_id', 'first_name', 'last_name', 'gender', 'date_of_birth',
        'address', 'city', 'state', 'country', 'phone', 'email',
        'parent_name', 'parent_phone', 'parent_email', 'status'
    ];
    
    $query_parts = [];
    $params = [];
    $types = '';
    
    foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $query_parts[] = $field;
            $params[] = $_POST[$field];
            $types .= 's';
        }
    }
    
    // Insert student
    $query = "INSERT INTO students (" . implode(', ', $query_parts) . ") 
              VALUES (" . implode(', ', array_fill(0, count($query_parts), '?')) . ")";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        throw new Exception("Failed to create student: " . $stmt->error);
    }
    
    $student_id = $stmt->insert_id;
    $stmt->close();
    
    // Create enrollment if academic_year_id is provided
    if (!empty($_POST['academic_year_id']) && !empty($_POST['grade']) && !empty($_POST['section'])) {
        $enrollment_data = [
            'student_id' => $student_id,
            'academic_year_id' => $_POST['academic_year_id'],
            'grade' => $_POST['grade'],
            'section' => $_POST['section'],
            'tuition_fee' => $_POST['tuition_fee'] ?? 0,
            'discount_percentage' => $_POST['discount_percentage'] ?? 0,
            'discount_amount' => $_POST['discount_amount'] ?? 0,
            'scholarship_percentage' => $_POST['scholarship_percentage'] ?? 0,
            'scholarship_amount' => $_POST['scholarship_amount'] ?? 0,
            'enrollment_date' => $_POST['enrollment_date'] ?? date('Y-m-d'),
            'notes' => $_POST['notes'] ?? ''
        ];
        
        $enrollment_query = "INSERT INTO student_enrollments 
                            (student_id, academic_year_id, grade, section, tuition_fee, 
                             discount_percentage, discount_amount, scholarship_percentage, 
                             scholarship_amount, enrollment_date, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($enrollment_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param(
            'iissdddddss',
            $enrollment_data['student_id'],
            $enrollment_data['academic_year_id'],
            $enrollment_data['grade'],
            $enrollment_data['section'],
            $enrollment_data['tuition_fee'],
            $enrollment_data['discount_percentage'],
            $enrollment_data['discount_amount'],
            $enrollment_data['scholarship_percentage'],
            $enrollment_data['scholarship_amount'],
            $enrollment_data['enrollment_date'],
            $enrollment_data['notes']
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Student created successfully',
        'student_id' => $student_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
