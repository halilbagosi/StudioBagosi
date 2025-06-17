<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get user info and check if they are an admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user is an admin, redirect to admin dashboard
if ($user['role'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

// Get user's bookings with package details
$stmt = $pdo->prepare("
    SELECT b.*, p.name as package_name, p.price, p.duration, u.full_name, u.email 
    FROM bookings b 
    JOIN packages p ON b.package_id = p.id 
    JOIN users u ON b.user_id = u.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervimet e Mia - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .booking-card {
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }
        .status-pending {
            background-color: #ffeaa7;
            color: #d63031;
        }
        .status-confirmed {
            background-color: #00b894;
            color: white;
        }
        .status-completed {
            background-color: #0984e3;
            color: white;
        }
        .status-cancelled {
            background-color: #636e72;
            color: white;
        }
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 76px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/Logo/navbar.PNG" alt="Studio Bagosi Logo" style="height: 50px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#home">Kryefaqja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#gallery">Galeria</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#packages">Paketat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Kontakti</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_bookings.php">
                            <i class="fas fa-calendar-check"></i> Rezervimet e Mia
                        </a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="auth/logout.php" class="btn btn-primary">
                            <i class="fas fa-sign-out-alt"></i> Dilni
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item ms-3">
                        <a href="auth/login.php" class="btn btn-primary">
                            <i class="fas fa-user"></i> Hyni
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a href="auth/register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Regjistrohu
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Rezervimet e Mia</h1>
                    <p class="lead mb-0">Menaxhoni dhe shikoni rezervimet tuaja të Studio Bagosi</p>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="user-info">
                        <h5 class="mb-1">Mirë se erdhët, <?php echo htmlspecialchars($user['full_name']); ?>!</h5>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bookings Content -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($bookings)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="card booking-card p-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted mb-3">Nuk keni asnjë rezervim</h3>
                        <p class="text-muted mb-4">Filloni të shfletoni paketat tona dhe rezervoni shërbimin tuaj të parë!</p>
                        <a href="index.php#packages" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i> Rezervoni Tani
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4">Rezervimet Tuaja (<?php echo count($bookings); ?>)</h2>
                </div>
            </div>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="card booking-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($booking['package_name']); ?></h5>
                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                <?php 
                                $status_text = [
                                    'pending' => 'Në pritje',
                                    'confirmed' => 'Konfirmuar',
                                    'completed' => 'Përfunduar',
                                    'cancelled' => 'Anulluar'
                                ];
                                echo $status_text[$booking['status']];
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Data e Eventit</small>
                                    <p class="mb-0 fw-semibold">
                                        <i class="fas fa-calendar me-2 text-primary"></i>
                                        <?php echo date('d M Y', strtotime($booking['event_date'])); ?>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Ora</small>
                                    <p class="mb-0 fw-semibold">
                                        <i class="fas fa-clock me-2 text-primary"></i>
                                        <?php echo date('H:i', strtotime($booking['event_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Çmimi</small>
                                    <p class="mb-0 fw-bold text-primary">€<?php echo number_format($booking['price'], 2); ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Kohëzgjatja</small>
                                    <p class="mb-0"><?php echo $booking['duration']; ?> orë</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Data e Rezervimit</small>
                                <p class="mb-0"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                            </div>
                            <?php if (!empty($booking['notes'])): ?>
                            <div class="mb-3">
                                <small class="text-muted">Shënime</small>
                                <p class="mb-0"><?php echo htmlspecialchars($booking['notes']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <?php if ($booking['status'] === 'pending'): ?>
                            <small class="text-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                Do t'ju kontaktojmë së shpejti për konfirmim
                            </small>
                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <small class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Rezervimi është konfirmuar
                            </small>
                            <?php elseif ($booking['status'] === 'completed'): ?>
                            <small class="text-info">
                                <i class="fas fa-star me-1"></i>
                                Shërbimi është përfunduar
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="index.php#packages" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i> Rezervoni Paketa të Reja
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white text-center py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p>&copy; 2024 Studio Bagosi. Të gjitha të drejtat e rezervuara.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 