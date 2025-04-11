<?php
/**
 * Add user form
 */

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (!empty($email)) {
        $existing = db_select("SELECT id FROM users WHERE email = ?", [$email], 's');
        if ($existing) {
            $errors[] = 'A user with this email already exists';
        }
    }
    
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $result = db_execute(
            "INSERT INTO users (name, email, password, is_admin, is_active) VALUES (?, ?, ?, ?, ?)",
            [$name, $email, $hashed_password, $is_admin, $is_active],
            'sssii'
        );
        
        if ($result) {
            // Get the new user ID
            $new_user = db_select("SELECT id FROM users WHERE email = ?", [$email], 's');
            if ($new_user) {
                $user_id = $new_user[0]['id'];
                // Redirect to user view
                header('Location: index.php?page=users&action=view&id=' . $user_id);
                exit;
            } else {
                // Redirect to user list
                header('Location: index.php?page=users');
                exit;
            }
        } else {
            $errors[] = 'Failed to create user. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New User</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=users" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus"></i> User Information
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Password must be at least 6 characters long.</div>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" 
                               <?php echo (isset($is_admin) && $is_admin) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_admin">Administrator</label>
                        <div class="form-text">Administrators have full access to all features.</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?php echo (!isset($is_active) || $is_active) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                        <div class="form-text">Inactive users cannot log in to the system.</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end">
                <a href="index.php?page=users" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>