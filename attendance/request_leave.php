<?php
require_once 'config/database.php';
require_once 'models/Leave.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$leave = new Leave($db);

$message = '';
$error = '';

// Get leave types for the dropdown
try {
    $leave_types = $leave->getLeaveTypes();
} catch (Exception $e) {
    $error = "Error loading leave types: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate input
        if (empty($_POST['leave_type_id'])) {
            throw new Exception("Please select a leave type");
        }
        if (empty($_POST['start_date'])) {
            throw new Exception("Please select a start date");
        }
        if (empty($_POST['end_date'])) {
            throw new Exception("Please select an end date");
        }
        if (empty($_POST['reason'])) {
            throw new Exception("Please provide a reason for your leave");
        }

        // Validate dates
        $start_date = new DateTime($_POST['start_date']);
        $end_date = new DateTime($_POST['end_date']);
        
        if ($end_date < $start_date) {
            throw new Exception("End date cannot be before start date");
        }

        // Set leave properties
        $leave->user_id = getCurrentUserId();
        $leave->leave_type_id = $_POST['leave_type_id'];
        $leave->start_date = $_POST['start_date'];
        $leave->end_date = $_POST['end_date'];
        $leave->reason = $_POST['reason'];

        // Create the leave request
        if ($leave->create()) {
            $message = "Leave request submitted successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Request Leave</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="leave_type_id" class="form-label">Leave Type</label>
                                <select class="form-select" id="leave_type_id" name="leave_type_id" required>
                                    <option value="">Select Leave Type</option>
                                    <?php foreach ($leave_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add date validation
        document.getElementById('start_date').addEventListener('change', function() {
            var startDate = new Date(this.value);
            var endDateInput = document.getElementById('end_date');
            endDateInput.min = this.value;
            
            if (endDateInput.value && new Date(endDateInput.value) < startDate) {
                endDateInput.value = this.value;
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            var endDate = new Date(this.value);
            var startDateInput = document.getElementById('start_date');
            
            if (startDateInput.value && new Date(startDateInput.value) > endDate) {
                startDateInput.value = this.value;
            }
        });
    </script>
</body>
</html> 