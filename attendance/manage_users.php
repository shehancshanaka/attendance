<?php
session_start();
require_once 'models/User.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$user = new User();
$success = '';
$error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $user->username = trim($_POST['username']);
                $user->password = $_POST['password'];
                $user->email = trim($_POST['email']);
                $user->full_name = trim($_POST['full_name']);
                $user->role_id = $_POST['role_id'];
                $user->supervisor_id = $_POST['supervisor_id'] ?: null;
                
                if(empty($user->username) || empty($user->password) || empty($user->email) || empty($user->full_name) || empty($user->role_id)) {
                    $error = "All required fields must be filled";
                } else {
                    if($user->create()) {
                        $success = "User created successfully";
                    } else {
                        $error = "Failed to create user - Username may already exist";
                    }
                }
                break;
                
            case 'update':
                $user->id = $_POST['user_id'];
                $user->username = trim($_POST['username']);
                $user->email = trim($_POST['email']);
                $user->full_name = trim($_POST['full_name']);
                $user->role_id = $_POST['role_id'];
                $user->supervisor_id = $_POST['supervisor_id'] ?: null;
                $user->status = $_POST['status'];
                
                // Only update password if provided
                if(!empty($_POST['password'])) {
                    $user->password = $_POST['password'];
                }
                
                if(empty($user->username) || empty($user->email) || empty($user->full_name) || empty($user->role_id)) {
                    $error = "All required fields must be filled";
                } else {
                    if($user->update()) {
                        $success = "User updated successfully";
                    } else {
                        $error = "Failed to update user - Username may already exist";
                    }
                }
                break;
                
            case 'delete':
                $user->id = $_POST['user_id'];
                if($user->delete()) {
                    $success = "User deleted successfully";
                } else {
                    $error = "Failed to delete user";
                }
                break;

            case 'make_admin':
                if(isset($_POST['user_id'])) {
                    $admin_role_id = $user->getRoleIdByName('admin');
                    if($admin_role_id) {
                        if($user->updateRole($_POST['user_id'], $admin_role_id)) {
                            $success = "User has been granted admin privileges";
                        } else {
                            $error = "Failed to grant admin privileges";
                        }
                    } else {
                        $error = "Admin role not found";
                    }
                }
                break;
        }
    }
}

// Get all users
$users = $user->getAllUsers();

// Get all roles
$roles = $user->getRoles();

// Get all supervisors
$supervisors = $user->getSupervisors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Admin Menu</h5>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="admin_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="manage_users.php">
                                    <i class="fas fa-users me-2"></i> Manage Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_leaves.php">
                                    <i class="fas fa-calendar-alt me-2"></i> Manage Leaves
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">
                                    <i class="fas fa-chart-bar me-2"></i> Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Users</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </button>
                </div>
                
                <?php if($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Supervisor</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr data-user-id="<?php echo $user['id']; ?>">
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="role-cell"><?php echo htmlspecialchars($user['role_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['supervisor_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if($user['role_name'] !== 'admin'): ?>
                                                <button class="btn btn-sm btn-outline-success make-admin-btn" onclick="makeAdmin(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-user-shield"></i> Make Admin
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="supervisor_id" class="form-label">Supervisor (Optional)</label>
                            <select class="form-select" id="supervisor_id" name="supervisor_id">
                                <option value="">Select Supervisor</option>
                                <?php foreach($supervisors as $supervisor): ?>
                                    <option value="<?php echo $supervisor['id']; ?>"><?php echo $supervisor['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (Leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role_id" class="form-label">Role</label>
                            <select class="form-select" id="edit_role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_supervisor_id" class="form-label">Supervisor (Optional)</label>
                            <select class="form-select" id="edit_supervisor_id" name="supervisor_id">
                                <option value="">Select Supervisor</option>
                                <?php foreach($supervisors as $supervisor): ?>
                                    <option value="<?php echo $supervisor['id']; ?>"><?php echo $supervisor['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable();
        });
        
        function editUser(userId) {
            // Fetch user data via AJAX
            $.ajax({
                url: 'get_user.php',
                method: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('#edit_user_id').val(response.data.id);
                        $('#edit_username').val(response.data.username);
                        $('#edit_email').val(response.data.email);
                        $('#edit_full_name').val(response.data.full_name);
                        $('#edit_role_id').val(response.data.role_id);
                        $('#edit_supervisor_id').val(response.data.supervisor_id);
                        $('#edit_status').val(response.data.status);
                        
                        $('#editUserModal').modal('show');
                    } else {
                        alert('Failed to load user data');
                    }
                },
                error: function() {
                    alert('Error loading user data');
                }
            });
        }
        
        function deleteUser(userId) {
            $('#delete_user_id').val(userId);
            $('#deleteUserModal').modal('show');
        }

        function makeAdmin(userId) {
            if(confirm('Are you sure you want to make this user an admin?')) {
                $.ajax({
                    url: 'manage_users.php',
                    method: 'POST',
                    data: {
                        action: 'make_admin',
                        user_id: userId
                    },
                    success: function(response) {
                        // Update the role cell in the table
                        const row = $(`tr[data-user-id="${userId}"]`);
                        row.find('.role-cell').text('Admin');
                        
                        // Hide the Make Admin button
                        row.find('.make-admin-btn').hide();
                        
                        // Show success message
                        alert('User has been granted admin privileges');
                    },
                    error: function() {
                        alert('Error making user admin');
                    }
                });
            }
        }
    </script>
</body>
</html> 