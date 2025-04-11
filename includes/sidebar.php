<?php
/**
 * Sidebar navigation
 */

// Get current page for highlighting active menu item
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>" href="index.php?page=students">
                    <i class="fas fa-user-graduate"></i>
                    Students
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>" href="index.php?page=payments">
                    <i class="fas fa-money-bill-wave"></i>
                    Payments
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'receipts' ? 'active' : ''; ?>" href="index.php?page=receipts">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Receipts
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="index.php?page=reports">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        
        <ul class="nav flex-column mb-2">
            <?php if (is_admin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'academic-years' ? 'active' : ''; ?>" href="index.php?page=academic-years">
                    <i class="fas fa-calendar-alt"></i>
                    Academic Years
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="index.php?page=users">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" href="index.php?page=settings">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
        
        <!-- Current Academic Year (Mobile Only) -->
        <?php 
        $current_academic_year = db_select("SELECT * FROM academic_years WHERE is_current = 1");
        if ($current_academic_year):
            $current_academic_year = $current_academic_year[0];
        ?>
        <div class="d-md-none mt-4 p-3 border-top">
            <div class="text-muted small mb-1">Current Academic Year:</div>
            <div class="fw-bold">
                <i class="fas fa-calendar-alt"></i> 
                <?php echo htmlspecialchars($current_academic_year['name']); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>
