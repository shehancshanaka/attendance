-- Create database


-- Create roles table
CREATE TABLE  roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
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
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (supervisor_id) REFERENCES users(id)
);

-- Create leave_types table
CREATE TABLE IF NOT EXISTS leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    max_days INT NOT NULL
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
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
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default roles
INSERT INTO roles (id, name, description) VALUES 
(1, 'admin', 'System Administrator with full access'),
(2, 'supervisor', 'Can manage employees and approve leaves'),
(3, 'employee', 'Regular employee');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role_id) VALUES 
('admin', '$2y$10$8KzW9XqHq3K9XqHq3K9XqHq3K9XqHq3K9XqHq3K9XqHq3K9XqHq3K9XqH', 'admin@example.com', 'System Administrator', 1);

-- Insert default leave types
INSERT INTO leave_types (name, description, max_days) VALUES 
('Annual Leave', 'Regular paid time off', 20),
('Sick Leave', 'For medical reasons', 10),
('Maternity Leave', 'For expecting mothers', 90),
('Paternity Leave', 'For new fathers', 5),
('Unpaid Leave', 'Leave without pay', 30); 