<?php
session_start();
require_once 'config/database.php';

// Get gallery set ID
$set_id = $_GET['id'] ?? 0;

if ($set_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get gallery set details
$stmt = $pdo->prepare("SELECT * FROM gallery_sets WHERE id = ?");
$stmt->execute([$set_id]);
$gallery_set = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gallery_set) {
    header('Location: index.php');
    exit;
}

// Get images for this gallery set
$stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE set_id = ? ORDER BY id DESC");
$stmt->execute([$set_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gallery_set['name']); ?> - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .gallery-image {
            position: relative;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .gallery-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-image:hover img {
            transform: scale(1.05);
        }
        .modal-image {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
        }
        .modal-dialog {
            max-width: 80vw;
            margin: 1.75rem auto;
        }
        .modal-content {
            background-color: #000;
        }
        .modal-header {
            border-bottom: 1px solid #333;
            background-color: rgba(0,0,0,0.8);
        }
        .modal-body {
            padding: 0;
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .modal-title {
            color: white;
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .back-to-gallery {
            background-color: #6c757d;
            color: white;
            padding: 0.5rem 1.25rem;
            border: none;
            transition: all 0.3s ease;
        }
        .back-to-gallery:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .modal-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 1rem;
            cursor: pointer;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }
        .modal-nav-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        .modal-nav-btn.prev {
            left: 0;
        }
        .modal-nav-btn.next {
            right: 0;
        }
        @media (max-width: 768px) {
            .gallery-image {
                margin: 0 auto 20px;
                max-width: 100%;
            }
            .col-md-4 {
                display: flex;
                justify-content: center;
            }
            .modal-dialog {
                max-width: 100%;
                margin: 0;
                height: 100vh;
                display: flex;
                align-items: center;
            }
            .modal-content {
                height: 100%;
                border-radius: 0;
                width: 100vw;
                margin: 0;
            }
            .modal-body {
                height: calc(100% - 56px); /* Subtract header height */
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                width: 100%;
            }
            .modal-image {
                max-height: 100%;
                width: 100%;
                object-fit: contain;
            }
            .modal-nav-btn {
                padding: 0.5rem;
            }
            .modal {
                padding: 0 !important;
            }
            .modal-header {
                width: 100%;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/Logo/navbar.PNG" alt="Studio Bagosi Logo" style="height: 50px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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

    <div class="container py-5 mt-5" style="padding-top: 7rem !important;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?php echo htmlspecialchars($gallery_set['name']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($gallery_set['description']); ?></p>
            </div>
            <a href="index.php#gallery" class="btn back-to-gallery">
                <i class="fas fa-arrow-left"></i> Kthehu në Galeri
            </a>
        </div>

        <?php if (empty($images)): ?>
            <div class="alert alert-info">
                Nuk ka ende imazhe në këtë galeri.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($images as $image): ?>
                <div class="col-md-4">
                    <div class="gallery-image">
                        <img src="serve_image.php?type=gallery&id=<?php echo $image['id']; ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>"
                             data-bs-toggle="modal"
                             data-bs-target="#imageModal<?php echo $image['id']; ?>">
                    </div>
                </div>

                <!-- Image Modal -->
                <div class="modal fade" id="imageModal<?php echo $image['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <button class="modal-nav-btn prev" onclick="navigateImage(-1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <img src="serve_image.php?type=gallery&id=<?php echo $image['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>"
                                     class="modal-image">
                                <button class="modal-nav-btn next" onclick="navigateImage(1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all images
            const images = document.querySelectorAll('.gallery-image img');
            let currentIndex = 0;
            let currentModal = null;

            // Function to navigate between images
            window.navigateImage = function(direction) {
                if (!currentModal) return;

                // Calculate new index
                currentIndex = (currentIndex + direction + images.length) % images.length;

                // Get the new image
                const newImage = images[currentIndex];

                // Update current modal content
                const currentImage = currentModal.querySelector('.modal-image');

                // Add fade out effect
                currentImage.style.opacity = '0';
                setTimeout(() => {
                    // Update content
                    currentImage.src = newImage.src;
                    currentImage.alt = newImage.alt;
                    
                    // Add fade in effect
                    currentImage.style.opacity = '1';
                }, 300);
            };

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (!currentModal) return;

                if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                }
            });

            // Store current modal and index when opened
            document.querySelectorAll('.gallery-image').forEach((galleryImage, index) => {
                galleryImage.addEventListener('click', function() {
                    currentIndex = index;
                });
            });

            // Track current modal
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('show.bs.modal', function() {
                    currentModal = this;
                });
            });

            // Add transition styles
            const style = document.createElement('style');
            style.textContent = `
                .modal-image {
                    transition: opacity 0.3s ease;
                }
                .modal-header {
                    border-bottom: none;
                    padding: 0.5rem;
                }
                .modal-header .btn-close {
                    margin: 0;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html> 