<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Leave.php';
require_once 'includes/session.php';

// Check if user is logged in and is either admin or supervisor
if (!isLoggedIn() || (!isAdmin() && !isSupervisor())) {
    header("Location: index.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$leave = new Leave($db);

// Get leave requests based on user role
if (isAdmin()) {
    $leave_requests = $leave->getAllLeaves();
} else {
    $leave_requests = $leave->getSupervisedLeaves(getCurrentUserId());
}

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'] ?? null;
    $action = $_POST['action'] ?? '';
    
    if ($leave_id && in_array($action, ['approve', 'reject'])) {
        $leave->id = $leave_id;
        if ($action === 'approve') {
            $success = $leave->approveLeave($leave_id, getCurrentUserId());
            $message = $success ? "Leave request approved successfully" : "Failed to approve leave request";
        } else {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            $success = $leave->rejectLeave($leave_id, getCurrentUserId(), $rejection_reason);
            $message = $success ? "Leave request rejected successfully" : "Failed to reject leave request";
        }
    }
}

// Refresh leave requests after action
if (isAdmin()) {
    $leave_requests = $leave->getAllLeaves();
} else {
    $leave_requests = $leave->getSupervisedLeaves(getCurrentUserId());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-pending { color: #ffc107; }
        .status-approved { color: #28a745; }
        .status-rejected { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Leave Requests</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
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
                                <?php if (isAdmin()): ?>
                                <th>Supervisor</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                    </span>
                                </td>
                                <?php if (isAdmin()): ?>
                                <td><?php echo htmlspecialchars($request['supervisor_name'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 