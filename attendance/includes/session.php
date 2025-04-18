<?php
// Set session security parameters before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 minutes)
$session_timeout = 1800;

// Check if session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Last request was more than 30 minutes ago
    session_unset();
    session_destroy();
    header("Location: index.php?session=expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'admin';
}

// Function to check if user is supervisor
function isSupervisor() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'supervisor';
}

// Function to check if user has a specific permission
function hasPermission($permission) {
    if (!isset($_SESSION['permissions'])) {
        return false;
    }
    return in_array($permission, $_SESSION['permissions']);
}

// Function to require admin access
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Function to get current role
function getCurrentRole() {
    return $_SESSION['role_name'] ?? null;
}
?> 