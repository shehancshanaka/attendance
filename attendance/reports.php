<?php
session_start();
require_once 'models/User.php';
require_once 'controllers/AttendanceController.php';
require_once 'controllers/LeaveController.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user = new User();
$attendanceController = new AttendanceController();
$leaveController = new LeaveController();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// Get report data
$attendance_report = $attendanceController->getAttendanceReport($start_date, $end_date);
$leave_report = $leaveController->getLeaveRequests();
$users = $user->getUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Reports</h2>
                    <div class="text-end">
                        <span class="text-muted">Welcome, <?php echo $_SESSION['username']; ?></span>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>">
                        </div>
                        <?php if($_SESSION['role_name'] === 'admin'): ?>
                        <div class="col-md-3">
                            <label for="user_id" class="form-label">Employee</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">All Employees</option>
                                <?php foreach($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" 
                                        <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo $u['full_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Attendance Report -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attendance Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($attendance_report as $record): ?>
                                    <tr>
                                        <td><?php echo $record['date']; ?></td>
                                        <td><?php echo $record['full_name']; ?></td>
                                        <td><?php echo $record['time_in']; ?></td>
                                        <td><?php echo $record['time_out']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $record['status'] === 'present' ? 'success' : 
                                                    ($record['status'] === 'late' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['notes']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Leave Report -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Leave Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="leaveTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($leave_report as $request): ?>
                                    <tr>
                                        <td><?php echo $request['full_name']; ?></td>
                                        <td><?php echo $request['leave_type']; ?></td>
                                        <td><?php echo $request['start_date']; ?></td>
                                        <td><?php echo $request['end_date']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $request['status'] === 'approved' ? 'success' : 
                                                    ($request['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $request['reason']; ?></td>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#attendanceTable').DataTable();
            $('#leaveTable').DataTable();
        });
    </script>
</body>
</html> 