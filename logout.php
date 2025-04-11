<?php
/**
 * Logout page for the Tuition Management System
 */

// Start session if not already started
session_start();

// Log the logout
if (isset($_SESSION['user_id'])) {
    error_log("User logged out: ID=" . $_SESSION['user_id']);
}

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for the redirect message
session_start();
$_SESSION['login_message'] = 'You have been successfully logged out.';

// Redirect to login page
header("Location: login.php");
exit;