<?php
require_once 'config/database.php';

// Create admin user with properly hashed password
$username = 'admin';
$email = 'admin@studiobagosi.com';
$password = 'admin';
$full_name = 'Admin User';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // First, check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        echo "Admin password updated successfully!";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([$username, $email, $hashed_password, $full_name]);
        echo "Admin user created successfully!";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 