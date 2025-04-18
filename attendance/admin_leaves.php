<?php
session_start();
require_once 'models/User.php';
require_once 'models/Leave.php';
require_once 'config/database.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$leave = new Leave($db);

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['leave_id'])) {
        $leave_id = $_POST['leave_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            if ($leave->approveLeave($leave_id, $_SESSION['user_id'])) {
                $_SESSION['success_message'] = "Leave request approved successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to approve leave request.";
            }
        } elseif ($action === 'reject' && isset($_POST['rejection_reason'])) {
            if ($leave->rejectLeave($leave_id, $_SESSION['user_id'], $_POST['rejection_reason'])) {
                $_SESSION['success_message'] = "Leave request rejected successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to reject leave request.";
            }
        }
        
        header("Location: admin_leaves.php");
        exit();
    }
}

// Get all leave requests
$all_leaves = $leave->getAllLeaves();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Leaves - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            border-radius: 5px;
            padding: 10px 15px;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .main-content {
            padding: 20px;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Admin Panel</h4>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_leaves.php">
                            <i class="fas fa-calendar-alt me-2"></i> Manage Leaves
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
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
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>All Leave Requests</h2>
                    <a href="admin_dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php foreach ($all_leaves as $leave): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['start_date']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                            <td>
                                                <span class="status-<?php echo $leave['status']; ?>">
                                                    <?php echo ucfirst($leave['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($leave['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $leave['id']; ?>">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                    
                                                    <!-- Reject Modal -->
                                                    <div class="modal fade" id="rejectModal<?php echo $leave['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Leave Request</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                                        <input type="hidden" name="action" value="reject">
                                                                        <div class="mb-3">
                                                                            <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                                                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger">Reject Leave</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 