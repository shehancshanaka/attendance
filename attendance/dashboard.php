<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Leave.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$leave = new Leave($db);

// Get user's leaves
$user_leaves = $leave->getEmployeeLeaves(getCurrentUserId());

// Get supervisor's leaves if user is a supervisor
$supervisor_leaves = null;
if($user->hasPermission('manage_leaves')) {
    $supervisor_leaves = $leave->getSupervisedLeaves(getCurrentUserId());
}

// Get all leaves if user is admin
$all_leaves = null;
if($user->hasPermission('view_reports')) {
    $all_leaves = $leave->getAllLeaves();
}

// Get leave balance
$leave_balance = [];
$leave_types = $leave->getLeaveTypes();
foreach ($leave_types as $type) {
    $leave_balance[$type['name']] = $leave->getLeaveBalance(getCurrentUserId(), $type['id']);
}

// Get leave requests based on user role
if (isAdmin()) {
    $leave_requests = $leave->getAllLeaves();
} else if (isSupervisor()) {
    $leave_requests = $leave->getSupervisedLeaves(getCurrentUserId());
} else {
    $leave_requests = $leave->getEmployeeLeaves(getCurrentUserId());
}

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'] ?? null;
    $action = $_POST['action'] ?? '';
    
    if ($leave_id && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $success = $leave->approveLeave($leave_id, getCurrentUserId());
            $message = $success ? "Leave request approved successfully" : "Failed to approve leave request";
        } else {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            $success = $leave->rejectLeave($leave_id, getCurrentUserId(), $rejection_reason);
            $message = $success ? "Leave request rejected successfully" : "Failed to reject leave request";
        }
        
        // Refresh the page to show updated status
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .leave-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .status-pending {
            color: #ffc107;
        }
        
        .status-approved {
            color: #28a745;
        }
        
        .status-rejected {
            color: #dc3545;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Leave System</h4>
                    <button class="btn btn-outline-light btn-sm d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_leave.php">
                            <i class="fas fa-calendar-plus me-2"></i> Request Leave
                        </a>
                    </li>
                    <?php if($user->hasPermission('manage_leaves')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_leaves.php">
                            <i class="fas fa-tasks me-2"></i> Manage Leaves
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if($user->hasPermission('manage_users')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                    </li>
                    <?php endif; ?>
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
                    <h2>Dashboard</h2>
                    <div class="text-end">
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    </div>
                </div>
                
                <!-- Leave Balance Card -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Leave Balance</h5>
                                <div class="row">
                                    <?php foreach ($leave_balance as $type => $balance): ?>
                                    <div class="col-6">
                                        <h3 class="text-primary"><?php echo $balance; ?></h3>
                                        <p class="text-muted"><?php echo htmlspecialchars($type); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a href="request_leave.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i> Request New Leave
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- My Leaves -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">My Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_leaves as $leave): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                        <td>
                                            <span class="status-<?php echo $leave['status']; ?>">
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Supervisor's View -->
                <?php if($supervisor_leaves): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Employee Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supervisor_leaves as $leave): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                        <td>
                                            <span class="status-<?php echo $leave['status']; ?>">
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($leave['status'] == 'pending'): ?>
                                            <a href="approve_leave.php?id=<?php echo $leave['id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="reject_leave.php?id=<?php echo $leave['id']; ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isSupervisor()): ?>
                <!-- Supervisor's Leave Management Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Employee Leave Requests</h5>
                        <a href="supervisor_leaves.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-check me-2"></i>Manage All Leaves
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($leave_requests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No leave requests found</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($leave_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($request['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($request['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                        <td>
                                            <span class="status-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                                <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
                                                    <br>
                                                    <small class="text-muted">Reason: <?php echo htmlspecialchars($request['rejection_reason']); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $request['id']; ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>

                                            <!-- Rejection Modal -->
                                            <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject Leave Request</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <div class="mb-3">
                                                                    <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                                                                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html> 