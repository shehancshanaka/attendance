<?php
require_once 'BaseController.php';

class LeaveController extends BaseController {
    public function requestLeave() {
        $this->checkLogin();
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user_id = $_SESSION['user_id'];
            $leave_type_id = $_POST['leave_type_id'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $reason = $_POST['reason'];

            $query = "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason) 
                     VALUES (:user_id, :leave_type_id, :start_date, :end_date, :reason)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":leave_type_id", $leave_type_id);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
            $stmt->bindParam(":reason", $reason);

            if($stmt->execute()) {
                $this->jsonResponse(['success' => true, 'message' => 'Leave request submitted successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to submit leave request'], 500);
            }
        }
    }

    public function approveLeave($request_id) {
        $this->checkAdmin();
        
        $query = "UPDATE leave_requests SET status = 'approved', approved_by = :approved_by 
                 WHERE id = :request_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":approved_by", $_SESSION['user_id']);
        $stmt->bindParam(":request_id", $request_id);

        if($stmt->execute()) {
            $this->jsonResponse(['success' => true, 'message' => 'Leave request approved']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to approve leave request'], 500);
        }
    }

    public function rejectLeave($request_id) {
        $this->checkAdmin();
        
        $query = "UPDATE leave_requests SET status = 'rejected', approved_by = :approved_by 
                 WHERE id = :request_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":approved_by", $_SESSION['user_id']);
        $stmt->bindParam(":request_id", $request_id);

        if($stmt->execute()) {
            $this->jsonResponse(['success' => true, 'message' => 'Leave request rejected']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to reject leave request'], 500);
        }
    }

    public function getLeaveRequests() {
        $this->checkLogin();
        
        $query = "SELECT lr.*, u.full_name, lt.name as leave_type 
                 FROM leave_requests lr 
                 JOIN users u ON lr.user_id = u.id 
                 JOIN leave_types lt ON lr.leave_type_id = lt.id 
                 ORDER BY lr.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($requests);
    }
}
?> 