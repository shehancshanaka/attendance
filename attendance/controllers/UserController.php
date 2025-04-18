<?php
require_once 'BaseController.php';

class UserController extends BaseController {
    public function createUser() {
        $this->checkAdmin();
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $email = $_POST['email'];
            $full_name = $_POST['full_name'];
            $role_id = $_POST['role_id'];
            $supervisor_id = $_POST['supervisor_id'] ?? null;

            // Check if username exists
            $check_query = "SELECT id FROM users WHERE username = :username";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(":username", $username);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Username already exists'], 400);
            }

            $query = "INSERT INTO users (username, password, email, full_name, role_id, supervisor_id) 
                     VALUES (:username, :password, :email, :full_name, :role_id, :supervisor_id)";
            
            $stmt = $this->db->prepare($query);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":full_name", $full_name);
            $stmt->bindParam(":role_id", $role_id);
            $stmt->bindParam(":supervisor_id", $supervisor_id);

            if($stmt->execute()) {
                $this->jsonResponse(['success' => true, 'message' => 'User created successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create user'], 500);
            }
        }
    }

    public function updateUser($user_id) {
        $this->checkAdmin();
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $full_name = $_POST['full_name'];
            $role_id = $_POST['role_id'];
            $supervisor_id = $_POST['supervisor_id'] ?? null;
            $status = $_POST['status'];

            $query = "UPDATE users SET 
                     email = :email,
                     full_name = :full_name,
                     role_id = :role_id,
                     supervisor_id = :supervisor_id,
                     status = :status
                     WHERE id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":full_name", $full_name);
            $stmt->bindParam(":role_id", $role_id);
            $stmt->bindParam(":supervisor_id", $supervisor_id);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":user_id", $user_id);

            if($stmt->execute()) {
                $this->jsonResponse(['success' => true, 'message' => 'User updated successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update user'], 500);
            }
        }
    }

    public function deleteUser($user_id) {
        $this->checkAdmin();
        
        $query = "UPDATE users SET status = 'inactive' WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            $this->jsonResponse(['success' => true, 'message' => 'User deactivated successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to deactivate user'], 500);
        }
    }

    public function getUsers() {
        $this->checkLogin();
        
        $query = "SELECT u.*, r.name as role_name, s.full_name as supervisor_name 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 LEFT JOIN users s ON u.supervisor_id = s.id 
                 WHERE u.status = 'active'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($users);
    }

    public function getUser($user_id) {
        $this->checkLogin();
        
        $query = "SELECT u.*, r.name as role_name, s.full_name as supervisor_name 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 LEFT JOIN users s ON u.supervisor_id = s.id 
                 WHERE u.id = :user_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->jsonResponse($user);
    }

    public function getSupervisedLeaveRequests() {
        $this->checkLogin();

        // Ensure the logged-in user is a supervisor
        $user_id = $_SESSION['user_id'];
        $query = "SELECT role_id FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role_id'] != 'supervisor_role_id') { // Replace 'supervisor_role_id' with the actual ID for supervisors
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        // Fetch leave requests for employees supervised by the logged-in supervisor
        $query = "SELECT lr.*, e.full_name as employee_name 
                  FROM leave_requests lr
                  JOIN users e ON lr.employee_id = e.id
                  WHERE e.supervisor_id = :supervisor_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":supervisor_id", $user_id);
        $stmt->execute();

        $leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($leave_requests);
    }
}
?>