<?php
class BaseController {
    protected $db;
    protected $user;

    public function __construct() {
        require_once 'config/database.php';
        require_once 'models/User.php';
        
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User();
    }

    protected function checkAdmin() {
        if(!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
            header("Location: admin_login.php");
            exit();
        }
    }

    protected function checkLogin() {
        if(!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
    }

    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}
?> 