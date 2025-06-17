<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() === 0) {
        echo "Admins table does not exist. Creating it now...\n";
        
        // Create admins table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'super_admin') DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "Admins table created successfully.\n";
    } else {
        echo "Admins table exists.\n";
    }
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE admins");
    echo "\nTable structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    // Check if any admins exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $count = $stmt->fetchColumn();
    echo "\nNumber of admin users: " . $count . "\n";
    
    if ($count > 0) {
        // Show admin users
        $stmt = $pdo->query("SELECT id, username, email, full_name, role FROM admins");
        echo "\nAdmin users:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Email: " . $row['email'] . ", Role: " . $row['role'] . "\n";
        }
    } else {
        echo "\nNo admin users found. Creating default admin...\n";
        
        // Create default admin
        $username = 'admin';
        $email = 'admin@studiobagosi.com';
        $password = 'admin';
        $full_name = 'Admin User';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([$username, $email, $hashed_password, $full_name]);
        
        echo "Default admin created successfully.\n";
        echo "Username: admin\n";
        echo "Password: admin\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 