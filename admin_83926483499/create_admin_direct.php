<?php
require_once '../config/database.php';

try {
    // First, check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    
    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists!";
        exit;
    }
    
    // Create new admin
    $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute(['admin', 'admin@studiobagosi.com', $hashed_password, 'Administrator', 'admin']);
    
    if ($result) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to create admin user!";
    }
    
} catch(PDOException $e) {
    echo "Error creating admin: " . $e->getMessage();
}
?> 