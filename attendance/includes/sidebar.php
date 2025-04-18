<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar bg-dark text-white" style="min-height: 100vh; padding: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Leave System</h4>
        <button class="btn btn-outline-light btn-sm d-md-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="nav flex-column">
        <?php if($_SESSION['role_name'] === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'admin_dashboard.php' ? 'active' : ''; ?>" 
               href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'create_admin.php' ? 'active' : ''; ?>" 
               href="create_admin.php">
                <i class="fas fa-user-plus me-2"></i> Create Admin
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" 
               href="manage_users.php">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
               href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'request_leave.php' ? 'active' : ''; ?>" 
               href="request_leave.php">
                <i class="fas fa-calendar-plus me-2"></i> Request Leave
            </a>
        </li>
        
        <?php if($_SESSION['role_name'] === 'admin' || $_SESSION['role_name'] === 'supervisor'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_leaves.php' ? 'active' : ''; ?>" 
               href="manage_leaves.php">
                <i class="fas fa-tasks me-2"></i> Manage Leaves
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" 
               href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
    });
</script> 