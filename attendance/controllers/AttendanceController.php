<?php
require_once 'BaseController.php';

class AttendanceController extends BaseController {
    public function markAttendance() {
        $this->checkLogin();
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user_id = $_SESSION['user_id'];
            $date = date('Y-m-d');
            $time_in = $_POST['time_in'];
            $time_out = $_POST['time_out'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';

            // Check if attendance already marked for today
            $check_query = "SELECT id FROM attendance WHERE user_id = :user_id AND date = :date";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(":user_id", $user_id);
            $check_stmt->bindParam(":date", $date);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                // Update existing attendance
                $query = "UPDATE attendance SET time_out = :time_out, status = :status, notes = :notes 
                         WHERE user_id = :user_id AND date = :date";
            } else {
                // Create new attendance record
                $query = "INSERT INTO attendance (user_id, date, time_in, time_out, status, notes) 
                         VALUES (:user_id, :date, :time_in, :time_out, :status, :notes)";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":date", $date);
            $stmt->bindParam(":time_in", $time_in);
            $stmt->bindParam(":time_out", $time_out);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":notes", $notes);

            if($stmt->execute()) {
                $this->jsonResponse(['success' => true, 'message' => 'Attendance recorded successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to record attendance'], 500);
            }
        }
    }

    public function getAttendanceReport($start_date, $end_date) {
        $this->checkLogin();
        
        $query = "SELECT a.*, u.full_name 
                 FROM attendance a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.date BETWEEN :start_date AND :end_date 
                 ORDER BY a.date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($attendance);
    }

    public function getEmployeeAttendance($user_id, $start_date, $end_date) {
        $this->checkLogin();
        
        $query = "SELECT * FROM attendance 
                 WHERE user_id = :user_id 
                 AND date BETWEEN :start_date AND :end_date 
                 ORDER BY date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($attendance);
    }
}
?> 