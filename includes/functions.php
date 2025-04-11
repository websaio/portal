<?php
/**
 * Helper functions for the Tuition Management System
 */

/**
 * Get current logged-in user
 * 
 * @return array|null User data or null if not logged in
 */
function get_logged_in_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $user = db_select("SELECT * FROM users WHERE id = ?", [$user_id], 'i');
    
    return $user[0] ?? null;
}

/**
 * Check if current user has admin role
 * 
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    $user = get_logged_in_user();
    return $user && $user['role'] === 'admin';
}

/**
 * Get setting value from database
 * 
 * @param string $name Setting name
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function get_setting_value($name, $default = '') {
    $setting = db_select("SELECT value FROM settings WHERE name = ?", [$name], 's');
    return $setting[0]['value'] ?? $default;
}

/**
 * Format date in user-friendly format
 * 
 * @param string $date Date string
 * @param string $format Format (short, medium, full)
 * @return string Formatted date
 */
function format_date($date, $format = 'medium') {
    if (empty($date)) {
        return '';
    }
    
    $date_obj = new DateTime($date);
    
    switch ($format) {
        case 'short':
            return $date_obj->format('m/d/Y');
        case 'full':
            return $date_obj->format('l, F j, Y');
        case 'datetime':
            return $date_obj->format('M j, Y g:i a');
        case 'medium':
        default:
            return $date_obj->format('M j, Y');
    }
}

/**
 * Format amount as currency
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function format_currency($amount, $currency = 'USD') {
    if ($currency === 'USD') {
        return '$' . number_format($amount, 2);
    }
    
    return number_format($amount, 2) . ' ' . $currency;
}

/**
 * Generate a unique ID
 * 
 * @param string $prefix Prefix for the ID
 * @return string Unique ID
 */
function generate_unique_id($prefix = '') {
    return $prefix . uniqid();
}

/**
 * Sanitize output for HTML display
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Debug function to print variable and exit
 * 
 * @param mixed $var Variable to debug
 * @param bool $exit Whether to exit after printing
 */
function debug($var, $exit = true) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    
    if ($exit) {
        exit;
    }
}

/**
 * Log message to error log
 * 
 * @param string $message Message to log
 * @param string $type Log type (info, warning, error)
 */
function log_message($message, $type = 'info') {
    $log_prefix = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($type) . ': ';
    error_log($log_prefix . $message);
}

/**
 * Redirect to another page
 * 
 * @param string $url URL to redirect to
 * @param int $status HTTP status code
 */
function redirect($url, $status = 302) {
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Check if string starts with substring
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if string starts with substring
 */
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if string ends with substring
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if string ends with substring
 */
function ends_with($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Get academic years list
 * 
 * @param bool $include_inactive Whether to include inactive academic years
 * @return array Academic years
 */
function get_academic_years($include_inactive = true) {
    $query = "SELECT * FROM academic_years";
    
    if (!$include_inactive) {
        $query .= " WHERE is_active = 1";
    }
    
    $query .= " ORDER BY start_date DESC";
    
    return db_select($query);
}

/**
 * Get current academic year
 * 
 * @return array|null Current academic year or null if not set
 */
function get_current_academic_year() {
    $current_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
    return $current_year[0] ?? null;
}

/**
 * Format grade name
 * 
 * @param string $grade Grade code
 * @return string Formatted grade name
 */
function format_grade($grade) {
    if (strtolower($grade) === 'kg1') {
        return 'KG 1';
    } elseif (strtolower($grade) === 'kg2') {
        return 'KG 2';
    } elseif (strtolower($grade) === 'n') {
        return 'Nursery';
    } else {
        return 'Grade ' . $grade;
    }
}

/**
 * Get student enrollment
 * 
 * @param int $student_id Student ID
 * @param int|null $academic_year_id Academic year ID (current year if null)
 * @return array|null Enrollment data or null if not found
 */
function get_student_enrollment($student_id, $academic_year_id = null) {
    if ($academic_year_id === null) {
        $current_year = get_current_academic_year();
        
        if (!$current_year) {
            return null;
        }
        
        $academic_year_id = $current_year['id'];
    }
    
    $enrollment = db_select(
        "SELECT e.*, 
                (SELECT SUM(amount) FROM payments 
                 WHERE student_id = e.student_id AND academic_year_id = e.academic_year_id) as total_paid
         FROM student_enrollments e
         WHERE e.student_id = ? AND e.academic_year_id = ?",
        [$student_id, $academic_year_id],
        'ii'
    );
    
    return $enrollment[0] ?? null;
}

/**
 * Calculate student's tuition balance
 * 
 * @param int $student_id Student ID
 * @param int|null $academic_year_id Academic year ID (current year if null)
 * @return array Balance information
 */
function calculate_student_balance($student_id, $academic_year_id = null) {
    $enrollment = get_student_enrollment($student_id, $academic_year_id);
    
    if (!$enrollment) {
        return [
            'tuition_fee' => 0,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'total_due' => 0,
            'total_paid' => 0,
            'balance' => 0
        ];
    }
    
    $tuition_fee = $enrollment['tuition_fee'] ?? 0;
    $discount_percentage = $enrollment['discount_percentage'] ?? 0;
    $discount_amount = ($tuition_fee * $discount_percentage) / 100;
    $total_due = $tuition_fee - $discount_amount;
    $total_paid = $enrollment['total_paid'] ?? 0;
    $balance = $total_due - $total_paid;
    
    return [
        'tuition_fee' => $tuition_fee,
        'discount_percentage' => $discount_percentage,
        'discount_amount' => $discount_amount,
        'total_due' => $total_due,
        'total_paid' => $total_paid,
        'balance' => $balance
    ];
}

/**
 * Check if a file exists and is readable
 * 
 * @param string $path File path
 * @return bool True if file exists and is readable
 */
function file_exists_and_readable($path) {
    return file_exists($path) && is_readable($path);
}

/**
 * Create directory if it doesn't exist
 * 
 * @param string $path Directory path
 * @param int $permissions Directory permissions
 * @param bool $recursive Whether to create parent directories
 * @return bool True if directory exists or was created
 */
function create_directory_if_not_exists($path, $permissions = 0755, $recursive = true) {
    if (is_dir($path)) {
        return true;
    }
    
    return mkdir($path, $permissions, $recursive);
}

/**
 * Generate a secure random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if the current request is AJAX
 * 
 * @return bool True if request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $status HTTP status code
 */
function send_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Convert a date to database format (Y-m-d)
 * 
 * @param string $date Date string
 * @return string Date in Y-m-d format
 */
function to_database_date($date) {
    if (empty($date)) {
        return null;
    }
    
    $date_obj = new DateTime($date);
    return $date_obj->format('Y-m-d');
}

/**
 * Get file extension
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function get_file_extension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}

/**
 * Check if user has permission to access a resource
 * 
 * @param string $permission Permission name
 * @return bool True if user has permission
 */
function has_permission($permission) {
    $user = get_logged_in_user();
    
    if (!$user) {
        return false;
    }
    
    // Admin has all permissions
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Define permission mappings
    $role_permissions = [
        'staff' => [
            'view_students',
            'view_payments',
            'add_payment',
            'generate_receipt',
            'view_reports'
        ]
    ];
    
    // Check if user's role has the requested permission
    return isset($role_permissions[$user['role']]) && 
           in_array($permission, $role_permissions[$user['role']]);
}

/**
 * Add audit log entry
 * 
 * @param string $action Action performed
 * @param string $entity_type Entity type (e.g., student, payment)
 * @param int $entity_id Entity ID
 * @param string $details Additional details
 * @return int|null Log ID or null on failure
 */
function add_audit_log($action, $entity_type, $entity_id, $details = '') {
    $user_id = $_SESSION['user_id'] ?? 0;
    
    return db_insert(
        "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) 
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$user_id, $action, $entity_type, $entity_id, $details],
        'issss'
    );
}