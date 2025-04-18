-- Create the database
CREATE DATABASE IF NOT EXISTS leavs_system;
USE leavs_system;

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    supervisor_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (supervisor_id) REFERENCES users(id)
);

-- Create leave_types table
CREATE TABLE IF NOT EXISTS leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    max_days INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create leave_requests table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    rejected_by INT,
    rejection_reason TEXT,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id)
);

-- Create attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_date (user_id, date)
);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('admin', 'System Administrator with full access'),
('supervisor', 'Supervisor with leave approval rights'),
('employee', 'Regular employee with basic access');

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('manage_users', 'Can create, edit, and delete users'),
('manage_leaves', 'Can approve or reject leave requests'),
('view_reports', 'Can view system reports'),
('manage_attendance', 'Can manage attendance records'),
('request_leave', 'Can request leave'),
('view_own_leaves', 'Can view own leave requests'),
('view_own_attendance', 'Can view own attendance records');

-- Assign permissions to roles
INSERT INTO role_permissions (role_id, permission_id) VALUES
-- Admin permissions
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7),
-- Supervisor permissions
(2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7),
-- Employee permissions
(3, 5), (3, 6), (3, 7);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 1);

-- Insert default supervisor user (password: supervisor123)
INSERT INTO users (username, password, email, full_name, role_id) VALUES
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor@example.com', 'Supervisor User', 2);

-- Insert default employee user (password: employee123)
INSERT INTO users (username, password, email, full_name, role_id, supervisor_id) VALUES
('employee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee@example.com', 'Employee User', 3, 2);

-- Insert default leave types
INSERT INTO leave_types (name, description, max_days) VALUES
('Annual Leave', 'Regular annual leave', 20),
('Sick Leave', 'Leave for health reasons', 10),
('Maternity Leave', 'Leave for maternity', 90),
('Paternity Leave', 'Leave for paternity', 5),
('Unpaid Leave', 'Leave without pay', 0);

-- Create indexes for better performance
CREATE INDEX idx_leave_requests_user_id ON leave_requests(user_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_leave_requests_supervisor ON users(supervisor_id);
CREATE INDEX idx_attendance_user_id ON attendance(user_id);
CREATE INDEX idx_attendance_date ON attendance(date); 