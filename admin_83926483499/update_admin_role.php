<?php
require_once '../config/database.php';

try {
    // Update admin role
    $stmt = $pdo->prepare("UPDATE admins SET role = 'admin' WHERE username = ?");
    $result = $stmt->execute(['admin']);
    
    if ($result) {
        echo "Admin role updated successfully!<br>";
        echo "Username: admin<br>";
        echo "New role: admin<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to update admin role!";
    }
    
} catch(PDOException $e) {
    echo "Error updating admin role: " . $e->getMessage();
}
?> 