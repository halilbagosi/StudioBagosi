<?php
session_start();
require_once '../config/database.php';

// Check if it's an admin login attempt
$is_admin_login = isset($_GET['admin']) && $_GET['admin'] == 1;

// Handle guest booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guest_booking') {
    $contact = $_POST['contact'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    
    if (!empty($contact) && !empty($package_id) && !empty($event_date) && !empty($event_time)) {
        try {

            
            // Combine date and time
            $event_datetime = $event_date . ' ' . $event_time;
            
            // Create the booking with NULL user_id for guest bookings
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, package_id, booking_date, event_date, status, notes) VALUES (NULL, ?, CURDATE(), ?, 'pending', ?)");
            $notes = 'Guest booking via contact: ' . $contact;
            

            
            $stmt->execute([$package_id, $event_datetime, $notes]);
            

            
            // Redirect with success message
            header('Location: ../index.php?success=guest_booking');
            exit;
        } catch (PDOException $e) {
            $error = "An error occurred while processing your booking. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Only process login if it's not a guest booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'guest_booking')) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if trying to login as admin
            if ($is_admin_login && $user['role'] !== 'admin') {
                $error = "You don't have admin privileges";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'admin';
                    header('Location: ../admin_83926483499/index.php');
                    exit;
                } else {
                    header('Location: ../index.php');
                }
                exit;
            }
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please fill in all fields";
    }
}

// Get package details if package_id is provided
$package = null;
if (isset($_GET['package_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$_GET['package_id']]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin_login ? 'Admin' : 'User'; ?> Login - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">
                            <?php echo $is_admin_login ? 'Admin Login' : 'User Login'; ?>
                        </h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
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
                            <button type="submit" class="btn btn-primary w-100">
                                Log in
                            </button>
                        </form>

                        <?php if (!$is_admin_login && $package): ?>
                        <div class="text-center mt-3">
                            <p class="mb-2">Nuk keni llogari?</p>
                            <a href="register.php" class="text-decoration-none d-block mb-2">Regjistrohu</a>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#guestBookingModal">
                                Vazhdoni pa llogari
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="../index.php" class="text-decoration-none">Kthehu në Kryefaqe</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($package): ?>
    <!-- Guest Booking Modal -->
    <div class="modal fade" id="guestBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vazhdoni pa llogari</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Ju po rezervoni: <strong><?php echo htmlspecialchars($package['name']); ?></strong></p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="guest_booking">
                        <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                        <div class="mb-3">
                            <label for="contact" class="form-label">Email ose Numri i Telefonit</label>
                            <input type="text" class="form-control" id="contact" name="contact" required 
                                   placeholder="Shkruani emailin ose numrin e telefonit tuaj">
                        </div>
                        <div class="mb-3">
                            <label for="event_date" class="form-label">Data e Eventit</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="event_time" class="form-label">Ora e Eventit</label>
                            <input type="time" class="form-control" id="event_time" name="event_time" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Përfundo Rezervimin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
        .btn-link {
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .btn-link:hover {
            color: #0056b3 !important;
            text-decoration: underline;
        }
        .text-primary {
            color: #0d6efd !important;
        }
        .form-control {
            border: 1px solid #ced4da;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 