<?php
/**
 * Main index file for the Tuition Management System
 */

// Start session
session_start();

// Include database connection and helper functions
require_once 'config/database.php';

// Check if functions.php exists
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else {
    // Error message if function file doesn't exist
    die("Error: Critical system files are missing. Please contact the administrator.");
}

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit;
}

// Get the current user information if get_logged_in_user function exists
$current_user = null;
if (function_exists('get_logged_in_user')) {
    $current_user = get_logged_in_user();
    
    // If user data couldn't be retrieved, force logout to reset the session
    if (!$current_user && isset($_SESSION['user_id'])) {
        // Log the issue
        error_log("Unable to retrieve user data for session user_id: " . $_SESSION['user_id']);
        
        // Clear all session data
        session_unset();
        session_destroy();
        
        // Start a new session for the redirect
        session_start();
        
        // Add a message to show on login page
        $_SESSION['login_message'] = "Your session has expired. Please log in again.";
        
        // Redirect to login
        header("Location: login.php");
        exit;
    }
}

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Validate the page to prevent directory traversal
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

// Define paths for includes
$header_path = 'includes/header.php';
$sidebar_path = 'includes/sidebar.php';
$footer_path = 'includes/footer.php';

// Define the path to the page file
$page_path = 'pages/' . $page . '.php';

// Check if the page file exists
if (!file_exists($page_path)) {
    $page = 'dashboard';
    $page_path = 'pages/dashboard.php';
    
    // If dashboard doesn't exist either, show error
    if (!file_exists($page_path)) {
        die("Error: Dashboard page is missing. Please contact the administrator.");
    }
}

// Get the current academic year if db_select function exists
$current_academic_year = null;
if (function_exists('db_select')) {
    $current_academic_year_query = "SELECT * FROM academic_years WHERE is_current = 1";
    $current_academic_year_result = db_select($current_academic_year_query);
    $current_academic_year = $current_academic_year_result[0] ?? null;
}

// Simple HTML layout if include files are missing
if (!file_exists($header_path) || !file_exists($sidebar_path) || !file_exists($footer_path)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tuition Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                padding-top: 20px;
            }
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 100;
                padding: 48px 0 0;
                box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
                background-color: #f8f9fa;
            }
            .nav-link {
                font-weight: 500;
                color: #333;
            }
            .nav-link.active {
                color: #3366cc;
            }
            .nav-link:hover {
                color: #5882e0;
            }
            .navbar-brand {
                padding-top: .75rem;
                padding-bottom: .75rem;
                font-size: 1rem;
                background-color: #3366cc;
                color: white;
            }
        </style>
    </head>
    <body>
        <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">Tuition Management</a>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-nav">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="logout.php">Sign out</a>
                </div>
            </div>
        </header>
        
        <div class="container-fluid">
            <div class="row">
                <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'students' ? 'active' : ''; ?>" href="index.php?page=students">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'payments' ? 'active' : ''; ?>" href="index.php?page=payments">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    Payments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'receipts' ? 'active' : ''; ?>" href="index.php?page=receipts">
                                    <i class="fas fa-receipt me-2"></i>
                                    Receipts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'reports' ? 'active' : ''; ?>" href="index.php?page=reports">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>" href="index.php?page=settings">
                                    <i class="fas fa-cog me-2"></i>
                                    Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
                
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <?php 
                    // Include the requested page
                    include $page_path;
                    ?>
                </main>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
    </body>
    </html>
    <?php
} else {
    // Use the template files if they exist
    include_once $header_path;
    include_once $sidebar_path;
    ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php 
        // Include the requested page
        include $page_path;
        ?>
    </main>
    <?php
    include_once $footer_path;
}
?>