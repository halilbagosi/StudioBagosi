<?php
require_once 'config/database.php';

// Get all gallery sets
$stmt = $pdo->query("SELECT * FROM gallery_sets ORDER BY created_at DESC");
$gallery_sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the selected set ID from URL
$set_id = isset($_GET['set']) ? (int)$_GET['set'] : ($gallery_sets[0]['id'] ?? 0);

// Find the selected gallery set
$selected_set = null;
foreach ($gallery_sets as $set) {
    if ($set['id'] == $set_id) {
        $selected_set = $set;
        break;
    }
}

// If no valid set is found, redirect to the first set
if (!$selected_set && !empty($gallery_sets)) {
    header('Location: gallery.php?set=' . $gallery_sets[0]['id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Bagosi - <?php echo htmlspecialchars($selected_set['name']); ?> Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/Logo/logo.png" alt="Studio Bagosi Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" data-lang="home">Kryefaqja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-lang="gallery">Galeria</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#packages" data-lang="packages">Paketat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact" data-lang="contact">Kontakti</a>
                    </li>
                    <li class="nav-item ms-3">
                        <button class="btn btn-outline-primary language-btn" onclick="toggleLanguage()">
                            <span class="current-lang">EN</span>
                        </button>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_bookings.php">
                            <i class="fas fa-calendar-check"></i> Rezervimet e Mia
                        </a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="auth/logout.php" class="btn btn-primary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item ms-3">
                        <a href="auth/login.php" class="btn btn-primary">
                            <i class="fas fa-user"></i> User Login
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gallery Header -->
    <section class="gallery-header py-5 mt-5">
        <div class="container">
            <h1 class="text-center mb-4" data-lang="gallery-title"><?php echo htmlspecialchars($selected_set['name']); ?> Gallery</h1>
            <div class="text-center mb-5">
                <a href="index.php" class="btn btn-outline-primary me-3">
                    <i class="fas fa-arrow-left me-2"></i>
                    <span data-lang="back-to-home">Back to Home</span>
                </a>
                <div class="btn-group">
                    <?php foreach ($gallery_sets as $set): ?>
                    <a href="?set=<?php echo $set['id']; ?>" 
                       class="btn btn-outline-primary <?php echo $set['id'] == $set_id ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($set['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="gallery-grid-section py-5">
        <div class="container">
            <div class="row g-4">
                <?php
                // Get images for the selected gallery set
                $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE set_id = ? ORDER BY id DESC");
                $stmt->execute([$set_id]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($images as $image) {
                    echo '<div class="col-md-4 col-lg-3">';
                    echo '<div class="gallery-item" data-bs-toggle="modal" data-bs-target="#galleryModal" data-image="' . $image['image_path'] . '">';
                    echo '<img src="' . $image['image_path'] . '" alt="' . htmlspecialchars($image['title']) . '" class="img-fluid rounded">';
                    echo '<div class="gallery-item-overlay">';
                    echo '<h5 class="text-white mb-2">' . htmlspecialchars($image['title']) . '</h5>';
                    if (!empty($image['description'])) {
                        echo '<p class="text-white-50">' . htmlspecialchars($image['description']) . '</p>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Gallery Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="" alt="Gallery Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Initialize gallery modal
        const galleryModal = document.getElementById('galleryModal');
        if (galleryModal) {
            galleryModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-image');
                const modalImg = this.querySelector('img');
                modalImg.src = imageSrc;
            });
        }
    </script>
</body>
</html> 