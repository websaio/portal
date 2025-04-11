<?php
/**
 * API routes for student management
 */
require_once __DIR__ . '/../../config/database.php';

/**
 * Handle GET requests for students
 * 
 * @param array $parts URL parts
 * @param array $data Request data
 * @return array Response
 */
function student_get_handler($parts, $data) {
    // Get all students
    if (count($parts) === 1 || empty($parts[1])) {
        // Optional filters
        $where_clause = "WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($data['status'])) {
            $where_clause .= " AND s.status = ?";
            $params[] = $data['status'];
            $types .= "s";
        }
        
        if (!empty($data['grade'])) {
            $where_clause .= " AND e.grade = ?";
            $params[] = $data['grade'];
            $types .= "s";
        }
        
        if (!empty($data['section'])) {
            $where_clause .= " AND e.section = ?";
            $params[] = $data['section'];
            $types .= "s";
        }
        
        // Get academic year
        $academic_year_id = null;
        if (!empty($data['academic_year_id'])) {
            $academic_year_id = $data['academic_year_id'];
        } else {
            // Get current academic year
            $current_year = db_select("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
            if ($current_year) {
                $academic_year_id = $current_year[0]['id'];
            }
        }
        
        if ($academic_year_id) {
            $where_clause .= " AND e.academic_year_id = ?";
            $params[] = $academic_year_id;
            $types .= "i";
        }
        
        $query = "SELECT s.*, e.grade, e.section, e.tuition_fee, e.discount_percentage,
                         e.discount_amount, e.scholarship_percentage, e.scholarship_amount
                  FROM students s
                  LEFT JOIN student_enrollments e ON s.id = e.student_id";
                  
        if ($academic_year_id) {
            $query .= " AND e.academic_year_id = " . intval($academic_year_id);
        }
        
        $query .= " $where_clause ORDER BY s.last_name, s.first_name";
        
        $students = db_select($query, $params, $types);
        
        return [
            'success' => true,
            'data' => $students ?: []
        ];
    }
    
    // Get a specific student
    if (is_numeric($parts[1])) {
        $student_id = intval($parts[1]);
        
        // Get student details
        $query = "SELECT * FROM students WHERE id = ?";
        $student = db_select($query, [$student_id], 'i');
        
        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student not found'
            ];
        }
        
        // Get enrollments
        $query = "SELECT e.*, a.name as academic_year 
                  FROM student_enrollments e
                  JOIN academic_years a ON e.academic_year_id = a.id
                  WHERE e.student_id = ?
                  ORDER BY a.start_date DESC";
        $enrollments = db_select($query, [$student_id], 'i');
        
        // Get payments
        $query = "SELECT p.*, a.name as academic_year 
                  FROM payments p
                  JOIN academic_years a ON p.academic_year_id = a.id
                  WHERE p.student_id = ?
                  ORDER BY p.payment_date DESC";
        $payments = db_select($query, [$student_id], 'i');
        
        return [
            'success' => true,
            'data' => [
                'student' => $student[0],
                'enrollments' => $enrollments ?: [],
                'payments' => $payments ?: []
            ]
        ];
    }
    
    // Invalid request
    return [
        'success' => false,
        'message' => 'Invalid request'
    ];
}

/**
 * Handle POST requests for students
 * 
 * @param array $parts URL parts
 * @param array $data Request data
 * @return array Response
 */
function student_post_handler($parts, $data) {
    // Create a new student
    if (count($parts) === 1 || empty($parts[1])) {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'gender'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => 'Field ' . $field . ' is required'
                ];
            }
        }
        
        // Generate student ID if not provided
        if (empty($data['student_id'])) {
            $year = date('y');
            $random = rand(1000, 9999);
            $data['student_id'] = "ST{$year}{$random}";
        }
        
        // Check if student ID already exists
        $existing = db_select("SELECT id FROM students WHERE student_id = ?", [$data['student_id']], 's');
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Student ID already exists'
            ];
        }
        
        // Check if email already exists (if provided)
        if (!empty($data['email'])) {
            $existing = db_select("SELECT id FROM students WHERE email = ?", [$data['email']], 's');
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }
        }
        
        // Prepare query
        $fields = [
            'student_id', 'first_name', 'last_name', 'gender', 'date_of_birth',
            'address', 'city', 'state', 'country', 'phone', 'email',
            'parent_name', 'parent_phone', 'parent_email', 'status'
        ];
        
        $query_parts = [];
        $params = [];
        $types = '';
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $query_parts[] = $field;
                $params[] = $data[$field];
                $types .= 's';
            }
        }
        
        // Insert student
        $query = "INSERT INTO students (" . implode(', ', $query_parts) . ") 
                  VALUES (" . implode(', ', array_fill(0, count($query_parts), '?')) . ")";
        
        $result = db_execute($query, $params, $types);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to create student'
            ];
        }
        
        $student_id = db_last_insert_id();
        
        // Create enrollment if academic_year_id is provided
        if (!empty($data['academic_year_id']) && !empty($data['grade']) && !empty($data['section'])) {
            $enrollment_data = [
                'student_id' => $student_id,
                'academic_year_id' => $data['academic_year_id'],
                'grade' => $data['grade'],
                'section' => $data['section'],
                'tuition_fee' => $data['tuition_fee'] ?? 0,
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'scholarship_percentage' => $data['scholarship_percentage'] ?? 0,
                'scholarship_amount' => $data['scholarship_amount'] ?? 0,
                'enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
                'notes' => $data['notes'] ?? ''
            ];
            
            $enrollment_query = "INSERT INTO student_enrollments 
                                (student_id, academic_year_id, grade, section, tuition_fee, 
                                 discount_percentage, discount_amount, scholarship_percentage, 
                                 scholarship_amount, enrollment_date, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $enrollment_params = [
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
            ];
            
            db_execute($enrollment_query, $enrollment_params, 'iissdddddss');
        }
        
        // Get the new student
        $new_student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
        
        return [
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $new_student[0]
        ];
    }
    
    // Invalid request
    return [
        'success' => false,
        'message' => 'Invalid request'
    ];
}

/**
 * Handle PUT requests for students
 * 
 * @param array $parts URL parts
 * @param array $data Request data
 * @return array Response
 */
function student_put_handler($parts, $data) {
    // Update a student
    if (count($parts) > 1 && is_numeric($parts[1])) {
        $student_id = intval($parts[1]);
        
        // Check if student exists
        $student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student not found'
            ];
        }
        
        // Check if email already exists for another student (if provided)
        if (!empty($data['email'])) {
            $existing = db_select(
                "SELECT id FROM students WHERE email = ? AND id != ?", 
                [$data['email'], $student_id], 
                'si'
            );
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Email already exists for another student'
                ];
            }
        }
        
        // Check if student ID already exists for another student (if provided)
        if (!empty($data['student_id'])) {
            $existing = db_select(
                "SELECT id FROM students WHERE student_id = ? AND id != ?", 
                [$data['student_id'], $student_id], 
                'si'
            );
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Student ID already exists for another student'
                ];
            }
        }
        
        // Prepare query
        $fields = [
            'student_id', 'first_name', 'last_name', 'gender', 'date_of_birth',
            'address', 'city', 'state', 'country', 'phone', 'email',
            'parent_name', 'parent_phone', 'parent_email', 'status'
        ];
        
        $set_parts = [];
        $params = [];
        $types = '';
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $set_parts[] = "$field = ?";
                $params[] = $data[$field];
                $types .= 's';
            }
        }
        
        if (empty($set_parts)) {
            return [
                'success' => false,
                'message' => 'No data provided for update'
            ];
        }
        
        // Add student ID to params
        $params[] = $student_id;
        $types .= 'i';
        
        // Update student
        $query = "UPDATE students SET " . implode(', ', $set_parts) . " WHERE id = ?";
        $result = db_execute($query, $params, $types);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to update student'
            ];
        }
        
        // Update enrollment if academic_year_id is provided
        if (!empty($data['academic_year_id'])) {
            // Check if enrollment exists
            $enrollment = db_select(
                "SELECT * FROM student_enrollments WHERE student_id = ? AND academic_year_id = ?",
                [$student_id, $data['academic_year_id']],
                'ii'
            );
            
            $enrollment_fields = [
                'grade', 'section', 'tuition_fee', 'discount_percentage', 
                'discount_amount', 'scholarship_percentage', 'scholarship_amount', 
                'enrollment_date', 'notes'
            ];
            
            // Collect enrollment data to update
            $enrollment_data = [];
            foreach ($enrollment_fields as $field) {
                if (isset($data[$field])) {
                    $enrollment_data[$field] = $data[$field];
                }
            }
            
            if (!empty($enrollment_data)) {
                if ($enrollment) {
                    // Update existing enrollment
                    $set_parts = [];
                    $params = [];
                    $types = '';
                    
                    foreach ($enrollment_data as $field => $value) {
                        $set_parts[] = "$field = ?";
                        $params[] = $value;
                        $types .= ($field === 'tuition_fee' || 
                                  strpos($field, 'percentage') !== false || 
                                  strpos($field, 'amount') !== false) ? 'd' : 's';
                    }
                    
                    // Add student ID and academic_year_id to params
                    $params[] = $student_id;
                    $params[] = $data['academic_year_id'];
                    $types .= 'ii';
                    
                    $query = "UPDATE student_enrollments SET " . implode(', ', $set_parts) . 
                             " WHERE student_id = ? AND academic_year_id = ?";
                    db_execute($query, $params, $types);
                } else {
                    // Create new enrollment
                    $enrollment_data['student_id'] = $student_id;
                    $enrollment_data['academic_year_id'] = $data['academic_year_id'];
                    
                    // Set defaults for required fields
                    if (!isset($enrollment_data['grade'])) $enrollment_data['grade'] = '';
                    if (!isset($enrollment_data['section'])) $enrollment_data['section'] = '';
                    if (!isset($enrollment_data['tuition_fee'])) $enrollment_data['tuition_fee'] = 0;
                    if (!isset($enrollment_data['enrollment_date'])) $enrollment_data['enrollment_date'] = date('Y-m-d');
                    
                    $fields = array_keys($enrollment_data);
                    $values = array_values($enrollment_data);
                    $types = '';
                    
                    foreach ($values as $value) {
                        if (is_numeric($value)) {
                            $types .= is_int($value) ? 'i' : 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                    
                    $query = "INSERT INTO student_enrollments (" . implode(', ', $fields) . ") 
                             VALUES (" . implode(', ', array_fill(0, count($fields), '?')) . ")";
                    db_execute($query, $values, $types);
                }
            }
        }
        
        // Get the updated student
        $updated_student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
        
        return [
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $updated_student[0]
        ];
    }
    
    // Invalid request
    return [
        'success' => false,
        'message' => 'Invalid request'
    ];
}

/**
 * Handle DELETE requests for students
 * 
 * @param array $parts URL parts
 * @param array $data Request data
 * @return array Response
 */
function student_delete_handler($parts, $data) {
    // Delete a student
    if (count($parts) > 1 && is_numeric($parts[1])) {
        $student_id = intval($parts[1]);
        
        // Check if student exists
        $student = db_select("SELECT * FROM students WHERE id = ?", [$student_id], 'i');
        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student not found'
            ];
        }
        
        // Delete the student
        $query = "DELETE FROM students WHERE id = ?";
        $result = db_execute($query, [$student_id], 'i');
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to delete student'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Student deleted successfully'
        ];
    }
    
    // Invalid request
    return [
        'success' => false,
        'message' => 'Invalid request'
    ];
}
