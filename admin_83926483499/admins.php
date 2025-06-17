<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_admin') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
            $error = 'Ju lutemi plotësoni të gjitha fushat!';
        } elseif ($password !== $confirm_password) {
            $error = 'Fjalëkalimet nuk përputhen!';
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Username ose email ekziston tashmë!';
                } else {
                    // Create new admin
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $full_name, $role]);
                    header('Location: admins.php?success=admin_created');
                    exit;
                }
            } catch(PDOException $e) {
                $error = 'Ndodhi një gabim. Ju lutemi provoni përsëri.';
                error_log("Admin creation error: " . $e->getMessage());
            }
        }
    }

    if ($_POST['action'] === 'delete_admin') {
        $admin_id = $_POST['admin_id'] ?? 0;
        
        if ($admin_id > 0) {
            try {
                // Don't allow deleting the last admin
                $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
                $admin_count = $stmt->fetchColumn();
                
                if ($admin_count <= 1) {
                    $error = 'Nuk mund të fshini adminin e fundit!';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    header('Location: admins.php?success=admin_deleted');
                    exit;
                }
            } catch(PDOException $e) {
                $error = 'Ndodhi një gabim. Ju lutemi provoni përsëri.';
                error_log("Admin deletion error: " . $e->getMessage());
            }
        }
    }
}

// Get all admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure all admin fields have default values if null
foreach ($admins as &$admin) {
    $admin['full_name'] = $admin['full_name'] ?? '';
    $admin['username'] = $admin['username'] ?? '';
    $admin['email'] = $admin['email'] ?? '';
    $admin['role'] = $admin['role'] ?? 'admin';
    $admin['created_at'] = $admin['created_at'] ?? date('Y-m-d H:i:s');
}
unset($admin);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-control {
            border: 2px solid #dee2e6;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .input-group .form-control {
            border-right: none;
        }
        .input-group .btn {
            border: 2px solid #dee2e6;
            border-left: none;
        }
        .input-group .btn:hover {
            background-color: #e9ecef;
        }
        .input-group .btn:focus {
            box-shadow: none;
        }
        .password-mismatch {
            border-color: #dc3545 !important;
        }
        .password-match {
            border-color: #198754 !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Management</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="fas fa-plus"></i> Create New Admin
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'admin_created':
                        echo "Admin account created successfully!";
                        break;
                    case 'admin_deleted':
                        echo "Admin account deleted successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Admins Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($admin['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewAdminModal" 
                                            data-admin='<?php echo htmlspecialchars(json_encode($admin)); ?>'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($admin['id'] !== $_SESSION['admin_id']): ?>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin account?');">
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Admin Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="createAdminForm">
                        <input type="hidden" name="action" value="create_admin">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchError" class="text-danger mt-1" style="display: none;">
                                Passwords do not match!
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="createAdminBtn">Create Admin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Admin Modal -->
    <div class="modal fade" id="viewAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="adminDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle view admin modal
        document.querySelectorAll('[data-bs-target="#viewAdminModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const admin = JSON.parse(this.dataset.admin);
                const details = document.getElementById('adminDetails');
                
                details.innerHTML = `
                    <dl class="row">
                        <dt class="col-sm-4">Full Name</dt>
                        <dd class="col-sm-8">${admin.full_name}</dd>
                        
                        <dt class="col-sm-4">Username</dt>
                        <dd class="col-sm-8">${admin.username}</dd>
                        
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">${admin.email}</dd>
                        
                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8">${new Date(admin.created_at).toLocaleDateString()}</dd>
                    </dl>
                `;
            });
        });

        // Password toggle functionality
        function togglePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const icon = button.querySelector('i');
            
            button.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        // Initialize password toggles
        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

        // Password matching validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatchError = document.getElementById('passwordMatchError');
        const createAdminBtn = document.getElementById('createAdminBtn');
        const createAdminForm = document.getElementById('createAdminForm');

        function checkPasswordMatch() {
            // Only show validation if confirm password field has content
            if (confirmPassword.value.length > 0) {
                if (password.value !== confirmPassword.value) {
                    passwordMatchError.style.display = 'block';
                    createAdminBtn.disabled = true;
                    confirmPassword.classList.add('password-mismatch');
                    confirmPassword.classList.remove('password-match');
                    return false;
                } else {
                    passwordMatchError.style.display = 'none';
                    createAdminBtn.disabled = false;
                    confirmPassword.classList.add('password-match');
                    confirmPassword.classList.remove('password-mismatch');
                    return true;
                }
            } else {
                // If confirm password is empty, hide error and remove classes
                passwordMatchError.style.display = 'none';
                createAdminBtn.disabled = false;
                confirmPassword.classList.remove('password-mismatch', 'password-match');
                return true;
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        // Form submission validation
        createAdminForm.addEventListener('submit', function(e) {
            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Please make sure the passwords match before creating the admin account.');
            }
        });

        // Clear form and validation when modal is closed
        document.getElementById('createAdminModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            passwordMatchError.style.display = 'none';
            confirmPassword.classList.remove('password-mismatch', 'password-match');
            createAdminBtn.disabled = false;
        });
    </script>
</body>
</html> 