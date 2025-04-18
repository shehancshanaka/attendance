<?php
require_once 'config/database.php';
require_once 'Holiday.php';

class Leave {
    private $conn;
    private $table_name = "leave_requests";

    public $id;
    public $user_id;
    public $leave_type_id;
    public $start_date;
    public $end_date;
    public $reason;
    public $status;
    public $approved_by;
    public $created_at;
    public $updated_at;
    public $duration;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function calculateWorkingDays($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Include end date
        
        $workingDays = 0;
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            // Skip weekends
            if ($date->format('N') >= 6) { // 6 = Saturday, 7 = Sunday
                continue;
            }
            
            // Skip holidays
            if (Holiday::isHoliday($date)) {
                continue;
            }
            
            $workingDays++;
        }
        
        return $workingDays;
    }

    public function create() {
        try {
            // Validate required fields
            if (empty($this->user_id)) {
                throw new Exception("User ID is required");
            }
            if (empty($this->leave_type_id)) {
                throw new Exception("Leave type is required");
            }
            if (empty($this->start_date)) {
                throw new Exception("Start date is required");
            }
            if (empty($this->end_date)) {
                throw new Exception("End date is required");
            }

            // Calculate working days (excluding weekends and holidays)
            $this->duration = $this->calculateWorkingDays($this->start_date, $this->end_date);

            // Check for overlapping leave requests
            $overlap_query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                             WHERE user_id = :user_id 
                             AND status != 'rejected'
                             AND (
                                 (start_date BETWEEN :start_date1 AND :end_date1)
                                 OR (end_date BETWEEN :start_date2 AND :end_date2)
                                 OR (start_date <= :start_date3 AND end_date >= :end_date3)
                             )";
            
            $overlap_stmt = $this->conn->prepare($overlap_query);
            $overlap_stmt->bindParam(":user_id", $this->user_id);
            $overlap_stmt->bindParam(":start_date1", $this->start_date);
            $overlap_stmt->bindParam(":end_date1", $this->end_date);
            $overlap_stmt->bindParam(":start_date2", $this->start_date);
            $overlap_stmt->bindParam(":end_date2", $this->end_date);
            $overlap_stmt->bindParam(":start_date3", $this->start_date);
            $overlap_stmt->bindParam(":end_date3", $this->end_date);
            
            if (!$overlap_stmt->execute()) {
                throw new Exception("Failed to check for overlapping leaves");
            }
            
            $overlap = $overlap_stmt->fetch(PDO::FETCH_ASSOC);
            if ($overlap['count'] > 0) {
                throw new Exception("You already have a leave request for these dates");
            }

            // Insert the leave request
            $query = "INSERT INTO " . $this->table_name . "
                    (user_id, leave_type_id, start_date, end_date, reason, status, created_at)
                    VALUES
                    (:user_id, :leave_type_id, :start_date, :end_date, :reason, 'pending', NOW())";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":leave_type_id", $this->leave_type_id);
            $stmt->bindParam(":start_date", $this->start_date);
            $stmt->bindParam(":end_date", $this->end_date);
            $stmt->bindParam(":reason", $this->reason);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            } else {
                $error = $stmt->errorInfo();
                throw new Exception("Failed to create leave request: " . $error[2]);
            }
        } catch(PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        } catch(Exception $e) {
            throw $e;
        }
    }

    public function updateStatus($status, $approved_by = null) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET status = :status,
                        approved_by = :approved_by,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":approved_by", $approved_by);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Leave Status Update Error: " . $e->getMessage());
            return false;
        }
    }

    public function getEmployeeLeaves($user_id) {
        try {
            $query = "SELECT l.*, lt.name as leave_type_name, u.full_name as employee_name
                     FROM " . $this->table_name . " l
                     JOIN leave_types lt ON l.leave_type_id = lt.id
                     JOIN users u ON l.user_id = u.id
                     WHERE l.user_id = :user_id
                     ORDER BY l.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Employee Leaves Error: " . $e->getMessage());
            return [];
        }
    }

    public function getPendingLeaves() {
        try {
            $query = "SELECT l.*, lt.name as leave_type_name, u.full_name as employee_name
                     FROM " . $this->table_name . " l
                     JOIN leave_types lt ON l.leave_type_id = lt.id
                     JOIN users u ON l.user_id = u.id
                     WHERE l.status = 'pending'
                     ORDER BY l.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Pending Leaves Error: " . $e->getMessage());
            return [];
        }
    }

    public function getLeaveTypes() {
        try {
            $query = "SELECT * FROM leave_types ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Leave Types Error: " . $e->getMessage());
            return [];
        }
    }

    public function getLeaveBalance($user_id, $leave_type_id) {
        try {
            $query = "SELECT lt.max_days - COALESCE(SUM(DATEDIFF(l.end_date, l.start_date) + 1), 0) as balance
                     FROM leave_types lt
                     LEFT JOIN " . $this->table_name . " l ON l.leave_type_id = lt.id AND l.user_id = :user_id AND l.status = 'approved'
                     WHERE lt.id = :leave_type_id
                     GROUP BY lt.id, lt.max_days";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":leave_type_id", $leave_type_id);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row ? $row['balance'] : 0;
        } catch(PDOException $e) {
            error_log("Get Leave Balance Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllLeaves() {
        try {
            $query = "SELECT l.*, lt.name as leave_type_name, u.full_name as employee_name,
                     s.full_name as supervisor_name,
                     l.rejection_reason, l.status, l.approved_by, l.approved_at, l.rejected_by, l.rejected_at
                     FROM " . $this->table_name . " l
                     JOIN leave_types lt ON l.leave_type_id = lt.id
                     JOIN users u ON l.user_id = u.id
                     LEFT JOIN users s ON u.supervisor_id = s.id
                     ORDER BY l.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get All Leaves Error: " . $e->getMessage());
            return [];
        }
    }

    public function approveLeave($leave_id, $approved_by) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET status = 'approved', 
                         approved_by = :approved_by, 
                         approved_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :leave_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":leave_id", $leave_id);
            $stmt->bindParam(":approved_by", $approved_by);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in approveLeave: " . $e->getMessage());
            return false;
        }
    }

    public function rejectLeave($leave_id, $rejected_by, $rejection_reason) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET status = 'rejected', 
                         rejected_by = :rejected_by, 
                         rejection_reason = :rejection_reason, 
                         rejected_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :leave_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":leave_id", $leave_id);
            $stmt->bindParam(":rejected_by", $rejected_by);
            $stmt->bindParam(":rejection_reason", $rejection_reason);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in rejectLeave: " . $e->getMessage());
            return false;
        }
    }

    public function getSupervisedLeaves($supervisor_id) {
        try {
            $query = "SELECT l.*, lt.name as leave_type_name, u.full_name as employee_name,
                     l.rejection_reason, l.status, l.approved_by, l.approved_at, l.rejected_by, l.rejected_at
                     FROM " . $this->table_name . " l
                     JOIN leave_types lt ON l.leave_type_id = lt.id
                     JOIN users u ON l.user_id = u.id
                     WHERE u.supervisor_id = :supervisor_id
                     ORDER BY l.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":supervisor_id", $supervisor_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get Supervised Leaves Error: " . $e->getMessage());
            return [];
        }
    }
}
?> 