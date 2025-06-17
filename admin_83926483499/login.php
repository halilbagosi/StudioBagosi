<?php
session_start();
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt - Username: " . $username);
    
    if (!empty($username) && !empty($password)) {
        try {
            // Check admin credentials in admins table
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            error_log("Admin query result: " . print_r($admin, true));
            
            if ($admin && password_verify($password, $admin['password'])) {
                error_log("Password verified successfully");
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                error_log("Session set: " . print_r($_SESSION, true));
                
                // Redirect to admin dashboard
                header('Location: index.php');
                exit;
            } else {
                error_log("Login failed - Invalid credentials");
                $error = 'Kredencialet e gabuara!';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'Ndodhi një gabim. Ju lutemi provoni përsëri.';
        }
    } else {
        error_log("Login failed - Empty fields");
        $error = 'Ju lutemi plotësoni të gjitha fushat!';
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Studio Bagosi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 5px;
            padding: 0.75rem;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            border-radius: 5px;
            background: #1a237e;
            border: none;
        }
        .btn-primary:hover {
            background: #0d47a1;
        }
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../images/Logo/navbar.PNG" alt="Studio Bagosi Logo">
            <h2>Admin Login</h2>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 