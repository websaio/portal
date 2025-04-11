<?php
/**
 * Users list page
 */

// Get all users
$users = db_select("SELECT * FROM users ORDER BY name");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if trying to delete own account
    if ($delete_id === $_SESSION['user_id']) {
        echo '<div class="alert alert-danger">You cannot delete your own account.</div>';
    } else {
        // Delete user
        $result = db_execute("DELETE FROM users WHERE id = ?", [$delete_id], 'i');
        
        if ($result) {
            echo '<div class="alert alert-success">User deleted successfully.</div>';
            // Reload users
            $users = db_select("SELECT * FROM users ORDER BY name");
        } else {
            echo '<div class="alert alert-danger">Failed to delete user.</div>';
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Users</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=users&action=add" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($users): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge bg-danger">Administrator</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Staff</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?page=users&action=view&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <a href="index.php?page=users&delete=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No users found.
            </div>
        <?php endif; ?>
    </div>
</div>