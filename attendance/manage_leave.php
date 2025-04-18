<?php
session_start();
require_once 'models/User.php';
require_once 'models/Leave.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user = new User();
$leave = new Leave();

// Check if user has permission to manage leaves
if(!$user->hasPermission('manage_leaves')) {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

if(isset($_GET['id']) && isset($_GET['action'])) {
    $leave_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Get leave details using the Leave class method
    $leave_details = $leave->getLeaveById($leave_id);
    
    if($leave_details) {
        // Check if the current user is the supervisor of the employee
        if($leave_details['supervisor_id'] == $_SESSION['user_id']) {
            $leave->id = $leave_id;
            $leave->status = $action;
            $leave->supervisor_id = $_SESSION['user_id'];
            $leave->supervisor_comment = $_POST['comment'] ?? '';
            
            if($leave->updateStatus()) {
                $success = "Leave request has been " . $action . "ed successfully";
            } else {
                $error = "Failed to update leave request";
            }
        } else {
            $error = "You are not authorized to manage this leave request";
        }
    } else {
        $error = "Leave request not found";
    }
}

// Get leave details for display using the Leave class method
$leave_details = $leave->getLeaveById($_GET['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management System - Manage Leave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        
        .leave-details {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_leave.php">
                            <i class="fas fa-calendar-plus me-2"></i> Request Leave
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_leaves.php">
                            <i class="fas fa-tasks me-2"></i> Manage Leaves
                        </a>
                    </li>
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
                    <h2>Manage Leave Request</h2>
                    <div class="text-end">
                        <span class="text-muted">Welcome, <?php echo $_SESSION['username']; ?></span>
                    </div>
                </div>
                
                <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if($leave_details): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="card-title">Leave Details</h5>
                                <div class="leave-details">
                                    <p><strong>Employee:</strong> <?php echo $leave_details['employee_name']; ?></p>
                                    <p><strong>Leave Type:</strong> <?php echo $leave_details['leave_type_name']; ?></p>
                                    <p><strong>Start Date:</strong> <?php echo date('d M Y', strtotime($leave_details['start_date'])); ?></p>
                                    <p><strong>End Date:</strong> <?php echo date('d M Y', strtotime($leave_details['end_date'])); ?></p>
                                    <p><strong>Reason:</strong> <?php echo $leave_details['reason']; ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge status-<?php echo $leave_details['status']; ?>">
                                            <?php echo ucfirst($leave_details['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($leave_details['status'] == 'pending'): ?>
                        <form method="POST" action="manage_leave.php?id=<?php echo $leave_details['id']; ?>&action=approve">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment (Optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i> Approve
                                </button>
                                <a href="manage_leave.php?id=<?php echo $leave_details['id']; ?>&action=reject" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i> Reject
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    Leave request not found or you don't have permission to manage it.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html> 