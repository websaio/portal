<?php
/**
 * API endpoint for updating a student
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

// Check if student ID is provided
if (empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Student ID is required'
    ]);
    exit;
}

$student_id = (int)$_POST['id'];

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
    // Check if student exists
    $student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
        exit;
    }
    
    // Check if student ID already exists for another student
    if (!empty($_POST['student_id'])) {
        $existing = db_select(
            "SELECT id FROM students WHERE student_id = ? AND id != ?", 
            [$_POST['student_id'], $student_id], 
            'si'
        );
        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Student ID already exists for another student'
            ]);
            exit;
        }
    }
    
    // Check if email already exists for another student
    if (!empty($_POST['email'])) {
        $existing = db_select(
            "SELECT id FROM students WHERE email = ? AND id != ?", 
            [$_POST['email'], $student_id], 
            'si'
        );
        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists for another student'
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
    
    $set_parts = [];
    $params = [];
    $types = '';
    
    foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $set_parts[] = "$field = ?";
            $params[] = $_POST[$field];
            $types .= 's';
        }
    }
    
    // Add student ID to params
    $params[] = $student_id;
    $types .= 'i';
    
    // Update student
    $query = "UPDATE students SET " . implode(', ', $set_parts) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    
    // Update enrollments
    if (isset($_POST['enrollment']) && is_array($_POST['enrollment'])) {
        foreach ($_POST['enrollment'] as $year_id => $data) {
            // Check if both grade and section are filled or both are empty
            $has_grade = !empty($data['grade']);
            $has_section = !empty($data['section']);
            
            // If one is filled and the other isn't, skip this record
            if ($has_grade != $has_section) {
                continue;
            }
            
            // If both are empty, skip this record
            if (!$has_grade && !$has_section) {
                continue;
            }
            
            // Check if enrollment exists
            $enrollment = db_select(
                "SELECT * FROM student_enrollments WHERE student_id = ? AND academic_year_id = ?",
                [$student_id, $year_id],
                'ii'
            );
            
            // Set default values
            $data['tuition_fee'] = isset($data['tuition_fee']) && $data['tuition_fee'] !== '' ? $data['tuition_fee'] : 0;
            $data['discount_percentage'] = isset($data['discount_percentage']) && $data['discount_percentage'] !== '' ? $data['discount_percentage'] : 0;
            $data['discount_amount'] = isset($data['discount_amount']) && $data['discount_amount'] !== '' ? $data['discount_amount'] : 0;
            $data['scholarship_percentage'] = isset($data['scholarship_percentage']) && $data['scholarship_percentage'] !== '' ? $data['scholarship_percentage'] : 0;
            $data['scholarship_amount'] = isset($data['scholarship_amount']) && $data['scholarship_amount'] !== '' ? $data['scholarship_amount'] : 0;
            $data['enrollment_date'] = isset($data['enrollment_date']) && $data['enrollment_date'] !== '' ? $data['enrollment_date'] : date('Y-m-d');
            $data['notes'] = isset($data['notes']) ? $data['notes'] : '';
            
            if ($enrollment) {
                // Update existing enrollment
                $update_query = "UPDATE student_enrollments 
                                 SET grade = ?, section = ?, tuition_fee = ?, 
                                     discount_percentage = ?, discount_amount = ?, 
                                     scholarship_percentage = ?, scholarship_amount = ?, 
                                     enrollment_date = ?, notes = ? 
                                 WHERE student_id = ? AND academic_year_id = ?";
                
                $stmt = $conn->prepare($update_query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param(
                    'ssdddddssii',
                    $data['grade'],
                    $data['section'],
                    $data['tuition_fee'],
                    $data['discount_percentage'],
                    $data['discount_amount'],
                    $data['scholarship_percentage'],
                    $data['scholarship_amount'],
                    $data['enrollment_date'],
                    $data['notes'],
                    $student_id,
                    $year_id
                );
                
                $stmt->execute();
                $stmt->close();
                
            } else {
                // Create new enrollment
                $insert_query = "INSERT INTO student_enrollments 
                                (student_id, academic_year_id, grade, section, tuition_fee, 
                                 discount_percentage, discount_amount, scholarship_percentage, 
                                 scholarship_amount, enrollment_date, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param(
                    'iissdddddss',
                    $student_id,
                    $year_id,
                    $data['grade'],
                    $data['section'],
                    $data['tuition_fee'],
                    $data['discount_percentage'],
                    $data['discount_amount'],
                    $data['scholarship_percentage'],
                    $data['scholarship_amount'],
                    $data['enrollment_date'],
                    $data['notes']
                );
                
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully'
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
