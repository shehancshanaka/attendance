<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $email;
    public $full_name;
    public $role_id;
    public $supervisor_id;
    public $status;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        try {
            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id
                     WHERE u.username = :username AND u.status = 'active'
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                
                if(password_verify($password, $row['password'])) {
                    $this->id = $row['id'];
                    $this->username = $row['username'];
                    $this->email = $row['email'];
                    $this->full_name = $row['full_name'];
                    $this->role_id = $row['role_id'];
                    $this->supervisor_id = $row['supervisor_id'];
                    $this->status = $row['status'];
                    
                    return [
                        'success' => true,
                        'role_name' => $row['role_name']
                    ];
                }
            }
            
            return ['success' => false];
        } catch(PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false];
        }
    }

    public function hasPermission($permission_name) {
        try {
            $query = "SELECT COUNT(*) as count 
                     FROM role_permissions rp
                     JOIN permissions p ON rp.permission_id = p.id
                     WHERE rp.role_id = :role_id AND p.name = :permission_name";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":role_id", $this->role_id);
            $stmt->bindParam(":permission_name", $permission_name);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row['count'] > 0;
        } catch(PDOException $e) {
            error_log("Permission Check Error: " . $e->getMessage());
            return false;
        }
    }

    public function create() {
        try {
            // Check if username exists
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":username", $this->username);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . "
                    SET username = :username,
                        password = :password,
                        email = :email,
                        full_name = :full_name,
                        role_id = :role_id,
                        supervisor_id = :supervisor_id,
                        status = 'active'";

            $stmt = $this->conn->prepare($query);

            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":role_id", $this->role_id);
            $stmt->bindParam(":supervisor_id", $this->supervisor_id);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("User Creation Error: " . $e->getMessage());
            return false;
        }
    }

    public function getRoles() {
        try {
            $query = "SELECT * FROM roles ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Roles Error: " . $e->getMessage());
            return [];
        }
    }

    public function getSupervisors() {
        try {
            $query = "SELECT id, full_name FROM users WHERE role_id = 2 AND status = 'active' ORDER BY full_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get Supervisors Error: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalUsers() {
        try {
            $query = "SELECT COUNT(*) as total FROM users";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row['total'];
        } catch(PDOException $e) {
            error_log("Get Total Users Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveUsers() {
        try {
            $query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row['total'];
        } catch(PDOException $e) {
            error_log("Get Active Users Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getPendingLeaveRequests() {
        try {
            $query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row['total'];
        } catch(PDOException $e) {
            error_log("Get Pending Leave Requests Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getSupervisorEmployees() {
        $query = "SELECT id, username, full_name, email 
                 FROM " . $this->table_name . "
                 WHERE supervisor_id = :supervisor_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":supervisor_id", $this->id);
        $stmt->execute();

        return $stmt;
    }

    public function getAllUsers() {
        try {
            $query = "SELECT u.*, r.name as role_name, s.full_name as supervisor_name 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     LEFT JOIN users s ON u.supervisor_id = s.id 
                     ORDER BY u.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getAllUsers: " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        try {
            // Check if username exists for other users
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE username = :username AND id != :id";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":username", $this->username);
            $check_stmt->bindParam(":id", $this->id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . "
                    SET username = :username,
                        email = :email,
                        full_name = :full_name,
                        role_id = :role_id,
                        supervisor_id = :supervisor_id,
                        status = :status";

            // Only update password if it's provided
            if(!empty($this->password)) {
                $query .= ", password = :password";
            }

            $query .= " WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":role_id", $this->role_id);
            $stmt->bindParam(":supervisor_id", $this->supervisor_id);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":id", $this->id);

            if(!empty($this->password)) {
                $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
                $stmt->bindParam(":password", $hashed_password);
            }

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("User Update Error: " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("User Delete Error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id) {
        try {
            $query = "SELECT u.*, r.name as role_name, s.full_name as supervisor_name 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     LEFT JOIN users s ON u.supervisor_id = s.id 
                     WHERE u.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return false;
        }
    }

    public function updateRole($user_id, $role_id) {
        try {
            $query = "UPDATE " . $this->table_name . " SET role_id = :role_id WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":role_id", $role_id);
            $stmt->bindParam(":user_id", $user_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Role Update Error: " . $e->getMessage());
            return false;
        }
    }

    public function getRoleIdByName($role_name) {
        try {
            $query = "SELECT id FROM roles WHERE name = :role_name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":role_name", $role_name);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ? $row['id'] : false;
        } catch(PDOException $e) {
            error_log("Get Role ID Error: " . $e->getMessage());
            return false;
        }
    }

    public function getRolePermissions($role_id) {
        try {
            $query = "SELECT p.name 
                     FROM permissions p 
                     JOIN role_permissions rp ON p.id = rp.permission_id 
                     WHERE rp.role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":role_id", $role_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) {
            error_log("Get Role Permissions Error: " . $e->getMessage());
            return [];
        }
    }

    public function assignRolePermissions($role_id, $permissions) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // First, remove existing permissions
            $delete_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->bindParam(":role_id", $role_id);
            $delete_stmt->execute();

            // Then, insert new permissions
            $insert_query = "INSERT INTO role_permissions (role_id, permission_id) 
                           SELECT :role_id, p.id 
                           FROM permissions p 
                           WHERE p.name = :permission_name";
            $insert_stmt = $this->conn->prepare($insert_query);

            foreach($permissions as $permission) {
                $insert_stmt->bindParam(":role_id", $role_id);
                $insert_stmt->bindParam(":permission_name", $permission);
                $insert_stmt->execute();
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Assign Role Permissions Error: " . $e->getMessage());
            return false;
        }
    }
}
?> 