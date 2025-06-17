<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Get gallery set ID
$set_id = $_GET['id'] ?? 0;

if ($set_id <= 0) {
    header('Location: gallery.php');
    exit;
}

// Get gallery set details
$stmt = $pdo->prepare("SELECT * FROM gallery_sets WHERE id = ?");
$stmt->execute([$set_id]);
$gallery_set = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gallery_set) {
    header('Location: gallery.php');
    exit;
}

// Get images for this gallery set
$stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE set_id = ? ORDER BY id DESC");
$stmt->execute([$set_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure gallery set name and description are not null
$gallery_set['name'] = $gallery_set['name'] ?? '';
$gallery_set['description'] = $gallery_set['description'] ?? '';
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .gallery-image {
            position: relative;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gallery-image img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-image:hover img {
            transform: scale(1.05);
        }
        .gallery-image .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gallery-image:hover .overlay {
            opacity: 1;
        }
        .gallery-image .overlay button {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
            transition: transform 0.2s ease;
        }
        .gallery-image .overlay button:hover {
            transform: scale(1.2);
        }
        .modal-image {
            width: 100%;
            height: auto;
            max-height: none;
            object-fit: contain;
        }
        .modal-dialog {
            max-width: 90vw;
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
        }
        .modal-title {
            color: white;
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?php echo htmlspecialchars($gallery_set['name']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($gallery_set['description']); ?></p>
            </div>
            <a href="gallery.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Gallery
            </a>
        </div>

        <?php if (empty($images)): ?>
            <div class="alert alert-info">
                No images in this gallery set yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($images as $image): ?>
                <div class="col-md-4">
                    <div class="gallery-image">
                        <img src="../serve_image.php?type=gallery&id=<?php echo $image['id']; ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>"
                             data-bs-toggle="modal"
                             data-bs-target="#imageModal<?php echo $image['id']; ?>">
                        <div class="overlay">
                            <button type="button" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal<?php echo $image['id']; ?>">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Image Modal -->
                <div class="modal fade" id="imageModal<?php echo $image['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <img src="../serve_image.php?type=gallery&id=<?php echo $image['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>"
                                     class="modal-image">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 