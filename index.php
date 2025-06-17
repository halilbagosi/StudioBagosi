<?php
session_start();
require_once 'config/database.php';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_package') {
    if (isset($_SESSION['user_id'])) {
        $package_id = $_POST['package_id'] ?? '';
        $event_date = $_POST['event_date'] ?? '';
        $event_time = $_POST['event_time'] ?? '';
        
        if (!empty($package_id) && !empty($event_date) && !empty($event_time)) {
            // Combine date and time
            $event_datetime = $event_date . ' ' . $event_time;
            
            // Create the booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, package_id, booking_date, event_date, status, notes) VALUES (?, ?, CURDATE(), ?, 'pending', 'Direct booking from website')");
            $stmt->execute([$_SESSION['user_id'], $package_id, $event_datetime]);
            
            header('Location: index.php?success=booking_created');
            exit;
        }
    }
}

// Get all gallery sets with image counts
$stmt = $pdo->query("
    SELECT gs.*, COUNT(gi.id) as image_count,
           CASE 
               WHEN gs.cover_photo_data IS NOT NULL THEN 1 
               ELSE 0 
           END as has_cover_photo
    FROM gallery_sets gs 
    LEFT JOIN gallery_images gi ON gs.id = gi.set_id 
    GROUP BY gs.id 
    ORDER BY gs.created_at DESC
");
$gallery_sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get success message if any
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'booking_created':
            $success_message = 'Rezervimi u krijua me sukses! Do t\'ju kontaktojmë së shpejti.';
            break;
        case 'guest_booking':
            $success_message = 'Rezervimi juaj është marrë! Do t\'ju kontaktojmë së shpejti për të konfirmuar detajet.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Bagosi - Filmime/Fotografi Martesore</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .gallery-card {
            height: 100%;
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .gallery-card:hover {
            transform: translateY(-5px);
        }
        .gallery-card .card-img-top {
            height: 300px;
            object-fit: cover;
        }
        .gallery-card .card-body {
            padding: 1.5rem;
        }
        .gallery-card .card-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .gallery-card .card-text {
            color:rgb(57, 72, 85);
            margin-bottom: 1rem;
        }
        .gallery-card .btn {
            width: 100%;
            padding: 0.75rem;
        }
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .section-title p {
            color:rgb(255, 255, 255);
            max-width: 600px;
            margin: 0 auto;
        }
        .section-title p1 {
            color:rgb(9, 29, 133);
            max-width: 600px;
            margin: 0 auto;
        }
        .glass-pane {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 15px;
        }
        
        .package-category {
            transition: transform 0.3s ease;
        }
        
        .package-category:hover {
            transform: translateY(-5px);
        }
        
        .card {
            transition: all 0.3s ease;
            transform-origin: center;
            border-radius: 15px;
            overflow: hidden;
            border: none;
        }
        
        .card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .price {
            font-size: 2rem;
            font-weight: bold;
            color: #1a237e;
        }
        
        .card-title {
            color: #1a237e;
            font-weight: 600;
        }
        
        .card-text {
            color: #666;
        }
        
        .list-unstyled li {
            margin-bottom: 0.5rem;
            color: #555;
        }

        .btn {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(13, 71, 161, 0.2);
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 71, 161, 0.3);
        }

        .package-category .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .package-category .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }
        
        .package-category .card-title {
            color: #1a237e;
            font-weight: 600;
            margin-bottom: 1rem;
            height: 3rem;
            display: flex;
            align-items: center;
            font-size: 1.8rem;
        }
        
        .package-category .card-text {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            line-height: 1.1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            min-height: 3.3rem; /* 3 lines * 1.1 line-height */
            max-height: 3.3rem;
        }
        
        .package-category .package-price {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .package-category .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1a237e;
            display: block;
            line-height: 1;
        }
        
        .package-category .features-list {
            margin-bottom: 1rem;
            flex-grow: 1;
        }
        
        .package-category .list-unstyled {
            margin: 0;
            padding: 0;
        }
        
        .package-category .list-unstyled li {
            margin-bottom: 0.3rem;
            color: #555;
            display: flex;
            align-items: center;
            font-size: 0.75rem;
        }
        
        .package-category .list-unstyled li i {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .package-category .btn {
            margin-top: auto;
            width: 100%;
        }

        /* Mobile navbar button styles */
        @media (max-width: 991.98px) {
            .navbar-nav .nav-item {
                margin: 0.25rem 0;
            }
            .navbar-nav .btn {
                margin: 0.25rem 0;
                width: 100%;
            }
            .navbar-nav .ms-2,
            .navbar-nav .ms-3 {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="images/Logo/navbar.PNG" alt="Studio Bagosi Logo" style="height: 50px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Kryefaqja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Galeria</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#packages">Paketat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontakti</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_bookings.php">
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

    <!-- Hero Section -->
    <section id="home" class="hero-section d-flex align-items-center">
        <video class="hero-video" autoplay muted loop playsinline preload="auto">
            <source src="clips/trailer.mp4" type="video/mp4">
            Shfletuesi juaj nuk mbështet tagun video.
        </video>
        <div class="hero-overlay"></div>
        <div class="container text-center">
            <h1 class="display-1 fw-bold mb-4">Përjetësoni momentet tuaja më speciale!</h1>
            <p class="lead mb-5">Shërbime filmike dhe fotografike për të ruajtur eventet tuaja më të rëndësishme.</p>
            <a href="#packages" class="btn btn-primary btn-lg">Rezervoni Tani</a>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Galeria Jonë</h2>
                <p1>Eksploroni koleksionin tonë të momenteve më të bukura.</p1>
            </div>
            
            <div class="row">
                <?php foreach ($gallery_sets as $set): ?>
                <div class="col-md-4 mb-4">
                    <div class="card gallery-card">
                        <?php if ($set['has_cover_photo']): ?>
                            <img src="serve_image.php?type=cover&id=<?php echo $set['id']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($set['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($set['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($set['description']); ?></p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php echo $set['image_count']; ?> imazhe
                                </small>
                            </p>
                            <a href="gallery_view.php?id=<?php echo $set['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-images"></i> Shiko Galerinë
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="py-5" style="background: linear-gradient(135deg, #1a237e, #0d47a1);">
        <div class="container">
            <div class="section-title text-white">
                <h2>Paketat Tona</h2>
                <p>Zgjidhni paketën perfekte për rastin tuaj të veçantë</p>
            </div>

            <?php
            // Get all packages grouped by category
            $stmt = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY category, price ASC");
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group packages by category
            $packages_by_category = [];
            foreach ($packages as $package) {
                if ($package['category'] === 'photography' || $package['category'] === 'event') {
                    $package['category'] = 'other';
                }
                $packages_by_category[$package['category']][] = $package;
            }

            // Update category titles
            $category_titles = [
                'wedding' => 'Paketat e Dasmës',
                'other' => 'Shërbime të Tjera'
            ];

            foreach ($packages_by_category as $category => $category_packages):
            ?>
            <div class="package-category mb-5">
                <div class="glass-pane p-4 rounded-4">
                    <h3 class="text-white mb-4 text-center"><?php echo $category_titles[$category]; ?></h3>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($category_packages as $package): ?>
                        <div class="col">
                            <div class="card h-100 border-0" style="background: rgba(255, 255, 255, 0.9);">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <p class="card-text"><?php echo htmlspecialchars($package['description']); ?></p>
                                    <div class="package-price">
                                        <span class="price">€<?php echo number_format($package['price'], 2); ?></span>
                                    </div>
                                    <div class="features-list">
                                        <ul class="list-unstyled">
                                            <?php
                                            $features = json_decode($package['features'], true);
                                            if ($features) {
                                                if (!empty($features['included'])) {
                                                    foreach ($features['included'] as $feature) {
                                                        echo '<li><i class="fas fa-check text-success"></i>' . htmlspecialchars($feature) . '</li>';
                                                    }
                                                }
                                                if (!empty($features['excluded'])) {
                                                    foreach ($features['excluded'] as $feature) {
                                                        echo '<li><i class="fas fa-times text-danger"></i>' . htmlspecialchars($feature) . '</li>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-primary book-now-btn" data-package-id="<?php echo $package['id']; ?>">Rezervo Tani</button>
                                    <?php else: ?>
                                    <a href="auth/login.php?package_id=<?php echo $package['id']; ?>" class="btn btn-primary">Rezervo Tani</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-card">
                        <h2 class="text-center mb-4">Na Kontaktoni</h2>
                        <form id="contactForm">
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Emri juaj" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Email juaj" required>
                            </div>
                            <div class="mb-3">
                                <input type="tel" class="form-control" placeholder="Telefoni juaj">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="5" placeholder="Mesazhi juaj" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Dërgo Mesazhin</button>
                        </form>
                        <div class="text-center mt-4">
                            <div class="social-links">
                                <a href="https://www.facebook.com/share/1AWXLV8FWh/?mibextid=wwXIfr"><i class="fab fa-facebook"></i></a>
                                <a href="https://www.instagram.com/studiobagosi?igsh=MXgzam02Ymk1Zmdvaw=="><i class="fab fa-instagram"></i></a>
                                <a href="https://youtube.com/@studiobagosi?si=foj0IrEr7dpIbCIm"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Studio Bagosi</h5>
                    <p>Kapim momentet tuaja të veçanta me pasion dhe krijimtari.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 Studio Bagosi. Të gjitha të drejtat e rezervuara.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Success Message Modal -->
    <?php if ($success_message): ?>
    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sukses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rezervo Paketën</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="book_package">
                        <input type="hidden" name="package_id" id="booking_package_id">
                        <div class="mb-3">
                            <label for="event_date" class="form-label">Data e Eventit</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="event_time" class="form-label">Ora e Eventit</label>
                            <input type="time" class="form-control" id="event_time" name="event_time" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Konfirmo Rezervimin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 