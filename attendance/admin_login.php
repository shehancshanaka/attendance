<?php
session_start();
require_once 'models/User.php';

// If admin is already logged in, redirect to admin dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if(empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            $user = new User();
            $result = $user->login($username, $password);
            
            if ($result['success'] && strtolower($result['role_name']) === 'admin') {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['role_name'] = $result['role_name'];
                $_SESSION['role_id'] = $user->role_id;
                $_SESSION['last_activity'] = time();
                
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid admin credentials";
            }
        }
    } catch(Exception $e) {
        $error = "An error occurred. Please try again later.";
        error_log("Admin Login Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .admin-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-login-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .admin-login-header p {
            color: #666;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-admin-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
        }
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-login-container">
            <div class="admin-login-header">
                <h1><i class="fas fa-lock"></i> Admin Login</h1>
                <p>Access the administrative dashboard</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Admin Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required 
                               autocomplete="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required 
                               autocomplete="current-password">
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-admin-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login as Admin
                    </button>
                </div>
            </form>
            
            <div class="back-to-login">
                <a href="index.php" class="text-muted">
                    <i class="fas fa-arrow-left me-2"></i>Back to regular login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 