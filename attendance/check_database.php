<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Database Structure Check</h2>";
    
    // Check if tables exist
    $tables = ['users', 'roles', 'permissions', 'role_permissions', 'leave_types', 'leave_requests'];
    
    foreach($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            
            // Show table structure
            echo "<h3>Structure of '$table':</h3>";
            $columns = $conn->query("DESCRIBE $table");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while($row = $columns->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Check sample data
    echo "<h2>Sample Data Check</h2>";
    
    // Check leave_types
    $stmt = $conn->query("SELECT * FROM leave_types");
    if($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Leave types exist</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Max Days</th></tr>";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['max_days'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<p style='color: red;'>✗ No leave types found</p>";
    }
    
    // Check users
    $stmt = $conn->query("SELECT * FROM users LIMIT 5");
    if($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users exist</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['role_id'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<p style='color: red;'>✗ No users found</p>";
    }
    
    // Check database connection
    echo "<h2>Database Connection Check</h2>";
    if($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        echo "<p>Database: " . $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 