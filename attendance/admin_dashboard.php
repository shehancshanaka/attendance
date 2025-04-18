<?php
session_start();
require_once 'models/User.php';
require_once 'models/Leave.php';
require_once 'config/database.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$leave = new Leave($db);

// Get summary statistics
$totalUsers = $user->getTotalUsers();
$activeUsers = $user->getActiveUsers();
$pendingRequests = $leave->getPendingLeaves();
$totalLeaveRequests = $leave->getAllLeaves();

// Convert arrays to counts
$pendingRequestsCount = is_array($pendingRequests) ? count($pendingRequests) : 0;
$totalLeaveRequestsCount = is_array($totalLeaveRequests) ? count($totalLeaveRequests) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reusing your existing styles */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        /* ... existing styles ... */
        
        .dashboard-card {
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .main-content {
            padding-bottom: 80px; /* Add padding to prevent footer overlap */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Admin Panel</h4>
                    <button class="btn btn-outline-light btn-sm d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_leaves.php">
                            <i class="fas fa-calendar-alt me-2"></i> Manage Leaves
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Admin Dashboard</h2>
                    <div class="text-end">
                        <span class="text-muted">Welcome, <?php echo $_SESSION['username']; ?></span>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                                <a href="manage_users.php" class="text-white">Manage Users →</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Users</h5>
                                <h2 class="mb-0"><?php echo $activeUsers; ?></h2>
                                <a href="active_users.php" class="text-white">View Details →</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Requests</h5>
                                <h2 class="mb-0"><?php echo $pendingRequestsCount; ?></h2>
                                <a href="manage_leaves.php?filter=pending" class="text-white">Review →</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Leaves</h5>
                                <h2 class="mb-0"><?php echo $totalLeaveRequestsCount; ?></h2>
                                <a href="reports.php" class="text-white">View Reports →</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="manage_users.php" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i> Create New User
                                    </a>
                                    <a href="admin_leaves.php" class="btn btn-info">
                                        <i class="fas fa-calendar-check me-2"></i> Review Leave Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body activity-feed">
                                <!-- Add PHP code to display recent activities -->
                                <div class="activity-item border-bottom py-2">
                                    <small class="text-muted">Just now</small>
                                    <div>New leave request from John Doe</div>
                                </div>
                                <!-- Add more activity items -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html> 