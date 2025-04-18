<?php
session_start();
require_once 'models/User.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if(isset($_POST['user_id'])) {
    $user = new User();
    $userData = $user->getUserById($_POST['user_id']);
    
    if($userData) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $userData]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
}
?> 