<?php
session_start();
require_once 'models/LeaveRequest.php';

// Debug: Check session data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name'])) {
    die("Session data missing. Please log in again.");
}

if ($_SESSION['role_name'] !== 'supervisor') {
    header("Location: supervisor_login.php");
    exit();
}

$leaveRequest = new LeaveRequest();
try {
    $requests = $leaveRequest->getLeaveRequestsForSupervisor();
} catch (Exception $e) {
    die("Error fetching leave requests: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - Supervisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h2>Leave Requests</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No leave requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
