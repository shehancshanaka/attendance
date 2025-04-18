class LeaveRequest {
    public $conn;

    public function __construct() {
        // ...existing code...
    }

    public function getLeaveRequestsForSupervisor() {
        try {
            $query = "SELECT lr.id, e.full_name AS employee_name, lt.name AS leave_type, lr.start_date, lr.end_date, lr.status 
                      FROM leave_requests lr
                      JOIN employees e ON lr.employee_id = e.id
                      JOIN leave_types lt ON lr.leave_type_id = lt.id
                      WHERE lr.supervisor_id = :supervisor_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':supervisor_id', $_SESSION['user_id']);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to fetch leave requests.");
        }
    }
}
