<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_role') {
        $user_id = $_POST['user_id'] ?? 0;
        $role = $_POST['role'] ?? '';
        
        if (!empty($user_id) && !empty($role)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            header('Location: users.php?success=role_updated');
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;
        
        if (!empty($user_id)) {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Don't allow deleting the last admin
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $admin_count = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_role = $stmt->fetchColumn();
                
                if ($user_role === 'admin' && $admin_count <= 1) {
                    header('Location: users.php?error=last_admin');
                    exit;
                }
                
                // Delete related bookings first
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Then delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                header('Location: users.php?success=user_deleted');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error deleting user: " . $e->getMessage());
                header('Location: users.php?error=delete_failed');
                exit;
            }
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure all user fields have default values if null
foreach ($users as &$user) {
    $user['full_name'] = $user['full_name'] ?? '';
    $user['username'] = $user['username'] ?? '';
    $user['email'] = $user['email'] ?? '';
    $user['phone'] = $user['phone'] ?? '';
    $user['role'] = $user['role'] ?? 'user';
    $user['created_at'] = $user['created_at'] ?? date('Y-m-d H:i:s');
}
unset($user); // Break the reference
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Users Management</h2>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'user_deleted':
                        echo "User deleted successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewUserModal" 
                                            data-user='<?php echo htmlspecialchars(json_encode($user)); ?>'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="userDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle view user modal
        document.querySelectorAll('[data-bs-target="#viewUserModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const user = JSON.parse(this.dataset.user);
                const details = document.getElementById('userDetails');
                
                details.innerHTML = `
                    <dl class="row">
                        <dt class="col-sm-4">Full Name</dt>
                        <dd class="col-sm-8">${user.full_name}</dd>
                        
                        <dt class="col-sm-4">Username</dt>
                        <dd class="col-sm-8">${user.username}</dd>
                        
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">${user.email}</dd>
                        
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">${user.phone || 'Not provided'}</dd>
                        
                        <dt class="col-sm-4">Joined</dt>
                        <dd class="col-sm-8">${new Date(user.created_at).toLocaleDateString()}</dd>
                    </dl>
                `;
            });
        });
    </script>
</body>
</html> 