<?php
/**
 * Login page for the Tuition Management System
 */

// Start session
session_start();

// Clear session if user is already logged in to prevent redirect loops
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    session_unset();
    session_destroy();
    session_start();
}

// Include database connection
require_once 'config/database.php';

// Process login form
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Query to find user
        $query = "SELECT * FROM users WHERE email = ?";
        $result = db_select($query, [$email], 's');
        
        if (empty($result)) {
            // No user found with that email
            $error = 'Invalid email or password';
            
            // Log failed login attempt
            error_log("Failed login attempt for email: " . $email);
        } else {
            $user = $result[0];
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if user is active
                if (isset($user['status']) && $user['status'] !== 'active') {
                    $error = 'Your account is not active. Please contact administrator.';
                    error_log("Login attempt for inactive account: " . $email);
                } else {
                    // Login successful
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    
                    // Set user name - handle if first_name/last_name fields don't exist
                    if (isset($user['first_name']) && isset($user['last_name'])) {
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    } else if (isset($user['name'])) {
                        $_SESSION['user_name'] = $user['name'];
                    } else {
                        $_SESSION['user_name'] = $email;
                    }
                    
                    // Set user role if it exists
                    $_SESSION['user_role'] = $user['role'] ?? 'user';
                    
                    // Try to update last login time if the column exists
                    try {
                        $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        db_execute($update_query, [$user['id']], 'i');
                    } catch (Exception $e) {
                        // Log the issue but don't prevent login
                        error_log("Database error: " . $e->getMessage());
                    }
                    
                    // Log successful login
                    error_log("Successful login for user: " . $email);
                    
                    // Redirect to dashboard
                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = 'Invalid email or password';
                error_log("Failed login attempt (password mismatch) for email: " . $email);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tuition Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: #3366cc;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        
        .card-header h3 {
            margin-bottom: 0;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: #3366cc;
            border-color: #3366cc;
            width: 100%;
            padding: 12px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #254e9c;
            border-color: #254e9c;
        }
        
        .school-logo {
            max-width: 100px;
            margin-bottom: 15px;
        }
        
        .text-primary {
            color: #3366cc !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="assets/images/logo.png" alt="School Logo" class="school-logo">
            <h2 class="text-primary">Tuition Management System</h2>
            <p class="text-muted">United International College</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-lock me-2"></i> Login</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['login_message'])): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($_SESSION['login_message']); ?>
                    </div>
                    <?php unset($_SESSION['login_message']); ?>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                        <label for="email">Email</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <small>&copy; <?php echo date('Y'); ?> United International College. All rights reserved.</small>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>