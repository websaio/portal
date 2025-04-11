<?php
/**
 * Authentication utilities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is authenticated
 * 
 * @return bool True if authenticated, false otherwise
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if a user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function is_admin() {
    return is_authenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require authentication for a page
 * Redirects to login page if not authenticated
 */
function require_auth() {
    if (!is_authenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin role for a page
 * Redirects to dashboard if authenticated but not admin
 */
function require_admin() {
    require_auth();
    
    if (!is_admin()) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

/**
 * Get the current authenticated user
 * 
 * @return array|null User data or null if not authenticated
 */
function get_logged_in_user() {
    if (!is_authenticated()) {
        return null;
    }
    
    require_once __DIR__ . '/../config/database.php';
    
    $user = db_select("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']], 'i');
    
    return $user ? $user[0] : null;
}

/**
 * Authenticate a user with username/email and password
 * 
 * @param string $username Username or email
 * @param string $password Plain text password
 * @return array|false User data or false if authentication fails
 */
function authenticate($username, $password) {
    require_once __DIR__ . '/../config/database.php';
    
    // Check if username is an email
    $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
    
    // Build query based on input type
    if ($is_email) {
        $user = db_select("SELECT * FROM users WHERE email = ?", [$username], 's');
    } else {
        $user = db_select("SELECT * FROM users WHERE username = ?", [$username], 's');
    }
    
    if (!$user) {
        return false;
    }
    
    $user = $user[0];
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        return $user;
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}

/**
 * Create a new user
 * 
 * @param array $user_data User data (name, email, username, password, role)
 * @return int|false User ID or false on failure
 */
function create_user($user_data) {
    require_once __DIR__ . '/../config/database.php';
    
    // Validate required fields
    $required = ['name', 'email', 'username', 'password', 'role'];
    foreach ($required as $field) {
        if (!isset($user_data[$field]) || empty($user_data[$field])) {
            return false;
        }
    }
    
    // Check if username or email already exists
    $existing = db_select(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        [$user_data['username'], $user_data['email']],
        'ss'
    );
    
    if ($existing) {
        return false;
    }
    
    // Hash password
    $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $result = db_execute(
        "INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)",
        [
            $user_data['name'],
            $user_data['email'],
            $user_data['username'],
            $hashed_password,
            $user_data['role']
        ],
        'sssss'
    );
    
    if ($result) {
        return db_last_insert_id();
    }
    
    return false;
}

/**
 * Update a user's password
 * 
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return bool True on success, false on failure
 */
function update_password($user_id, $current_password, $new_password) {
    require_once __DIR__ . '/../config/database.php';
    
    // Get user
    $user = db_select("SELECT * FROM users WHERE id = ?", [$user_id], 'i');
    
    if (!$user) {
        return false;
    }
    
    $user = $user[0];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return false;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $result = db_execute(
        "UPDATE users SET password = ? WHERE id = ?",
        [$hashed_password, $user_id],
        'si'
    );
    
    return $result !== false;
}