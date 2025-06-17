<?php
require_once '../config/database.php';

try {
    // Check for admin user
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Admin user exists:<br>";
        echo "ID: " . $admin['id'] . "<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Role: " . $admin['role'] . "<br>";
        echo "Created at: " . $admin['created_at'] . "<br>";
    } else {
        echo "Admin user does not exist!";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 