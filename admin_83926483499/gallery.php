<?php
require_once 'auth_check.php';
require_once '../config/database.php';
require_once '../config/image_helpers.php';

// Handle gallery set creation with images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_set') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            header('Location: gallery.php?error=creation_failed&message=' . urlencode('Gallery set name is required'));
            exit;
        }

        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Create gallery set
            $stmt = $pdo->prepare("INSERT INTO gallery_sets (name) VALUES (?)");
            $stmt->execute([$name]);
            $set_id = $pdo->lastInsertId();
            
            // Note: No need to create directories since we're storing images in database as BLOB
            
            // Handle cover photo upload - Store in database as BLOB
            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
                $cover_file = $_FILES['cover_photo'];
                $cover_ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($cover_ext, $allowed_types)) {
                    throw new Exception("Invalid cover photo file type. Allowed types: " . implode(', ', $allowed_types));
                }
                
                // Optimize and compress cover photo
                if (isImageCompressionAvailable()) {
                    $optimized_data = getOptimizedImageData($cover_file['tmp_name'], $cover_file['name']);
                    if ($optimized_data !== false) {
                        $image_data = $optimized_data['data'];
                        $mime_type = $optimized_data['mime_type'];
                        $file_size = $optimized_data['compressed_size'];
                        error_log("Cover photo compressed: {$optimized_data['compression_ratio']}% reduction");
                    } else {
                        throw new Exception("Failed to process cover photo");
                    }
                } else {
                    // Fallback to original method if GD not available
                    $image_data = file_get_contents($cover_file['tmp_name']);
                    if ($image_data === false) {
                        throw new Exception("Failed to read cover photo data");
                    }
                    
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo->file($cover_file['tmp_name']);
                }
                
                // Store in database
                $stmt = $pdo->prepare("UPDATE gallery_sets SET cover_photo_data = ?, cover_photo_mime_type = ?, cover_photo_filename = ? WHERE id = ?");
                $stmt->execute([$image_data, $mime_type, $cover_file['name'], $set_id]);
            }
            
            // Handle gallery images upload
            $uploaded_count = 0;
            $failed_uploads = [];
            
            if (isset($_FILES['images'])) {
                $files = $_FILES['images'];
                $file_count = count($files['name']);
                
                // Prepare the insert statement outside the loop - Store in database as BLOB
                $insert_stmt = $pdo->prepare("INSERT INTO gallery_images (set_id, title, image_data, mime_type, filename, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                
                // Process files in batches to manage memory
                $batch_size = 5; // Reduced batch size for BLOB storage
                for ($i = 0; $i < $file_count; $i += $batch_size) {
                    $end = min($i + $batch_size, $file_count);
                    
                    for ($j = $i; $j < $end; $j++) {
                        if ($files['error'][$j] === UPLOAD_ERR_OK) {
                            try {
                                $file_name = $files['name'][$j];
                                $file_tmp = $files['tmp_name'][$j];
                                $file_type = $files['type'][$j];
                                $file_size = $files['size'][$j];
                                
                                if (strpos($file_type, 'image/') === 0) {
                                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                    
                                    if (!in_array($file_ext, $allowed_types)) {
                                        $failed_uploads[] = $file_name . " (invalid file type)";
                                        continue;
                                    }
                                    
                                    // Check file size (limit to 5MB)
                                    if ($file_size > 5 * 1024 * 1024) {
                                        $failed_uploads[] = $file_name . " (file too large - max 5MB)";
                                        continue;
                                    }
                                    
                                    // Check if file size exceeds MySQL max_allowed_packet (safety check)
                                    if ($file_size > 60 * 1024 * 1024) { // 60MB safety margin
                                        $failed_uploads[] = $file_name . " (file exceeds database limits)";
                                        continue;
                                    }
                                    
                                    // Optimize and compress image
                                    if (isImageCompressionAvailable()) {
                                        $optimized_data = getOptimizedImageData($file_tmp, $file_name);
                                        if ($optimized_data !== false) {
                                            $image_data = $optimized_data['data'];
                                            $mime_type = $optimized_data['mime_type'];
                                            $compressed_size = $optimized_data['compressed_size'];
                                            error_log("Image compressed: {$file_name} - {$optimized_data['compression_ratio']}% reduction");
                                        } else {
                                            $failed_uploads[] = $file_name . " (failed to compress image)";
                                            continue;
                                        }
                                    } else {
                                        // Fallback to original method if GD not available
                                        $image_data = file_get_contents($file_tmp);
                                        if ($image_data === false) {
                                            $failed_uploads[] = $file_name . " (failed to read file data)";
                                            continue;
                                        }
                                        
                                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                                        $mime_type = $finfo->file($file_tmp);
                                        $compressed_size = $file_size;
                                    }
                                    
                                    // Store in database
                                    $insert_stmt->execute([
                                        $set_id, 
                                        pathinfo($file_name, PATHINFO_FILENAME),
                                        $image_data,
                                        $mime_type,
                                        $file_name,
                                        $compressed_size
                                    ]);
                                    $uploaded_count++;
                                } else {
                                    $failed_uploads[] = $file_name . " (not an image)";
                                }
                            } catch (Exception $e) {
                                error_log("Error uploading file {$file_name}: " . $e->getMessage());
                                $failed_uploads[] = $file_name . " (" . $e->getMessage() . ")";
                            }
                        }
                    }
                    
                    // Clear memory after each batch
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
            
            // Verify at least one image was uploaded
            if ($uploaded_count === 0) {
                // Check if cover photo was uploaded
                $stmt = $pdo->prepare("SELECT cover_photo_data FROM gallery_sets WHERE id = ?");
                $stmt->execute([$set_id]);
                $has_cover = $stmt->fetchColumn();
                
                if (!$has_cover) {
                    throw new Exception("No images were successfully uploaded");
                }
            }
            
            $pdo->commit();
            
            // Prepare success message
            $message = "Gallery set created successfully with {$uploaded_count} images";
            if (!empty($failed_uploads)) {
                $message .= ". Failed to upload " . count($failed_uploads) . " images.";
                error_log("Failed uploads: " . implode(", ", $failed_uploads));
            }
            
            header('Location: gallery.php?success=set_created&images=' . $uploaded_count . '&failed=' . count($failed_uploads));
            exit;
        } catch(Exception $e) {
            try {
                $pdo->rollBack();
            } catch(Exception $rollback_error) {
                error_log("Rollback failed: " . $rollback_error->getMessage());
            }
            
            error_log("Gallery creation error: " . $e->getMessage());
            
            // Check for specific MySQL errors
            $error_message = $e->getMessage();
            if (strpos($error_message, 'MySQL server has gone away') !== false) {
                $error_message = "Database connection lost during upload. Please try uploading smaller images or fewer images at once.";
            } elseif (strpos($error_message, 'max_allowed_packet') !== false) {
                $error_message = "Image files are too large for the database. Please reduce image sizes.";
            }
            
            header('Location: gallery.php?error=creation_failed&message=' . urlencode($error_message));
            exit;
        }
    }
    
    // Handle gallery set deletion
    if ($_POST['action'] === 'delete_set') {
        $set_id = $_POST['set_id'] ?? 0;
        
        if ($set_id > 0) {
            try {
                $pdo->beginTransaction();
                
                // Delete images from database
                $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE set_id = ?");
                $stmt->execute([$set_id]);
                
                // Delete gallery set
                $stmt = $pdo->prepare("DELETE FROM gallery_sets WHERE id = ?");
                $stmt->execute([$set_id]);
                
                // Note: No need to delete physical files since we're storing in database
                
                $pdo->commit();
                header('Location: gallery.php?success=set_deleted');
                exit;
            } catch(PDOException $e) {
                $pdo->rollBack();
                header('Location: gallery.php?error=delete_failed');
                exit;
            }
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
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .image-upload {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .image-upload:hover {
            border-color: #0d6efd;
        }
        .image-upload input[type="file"] {
            display: none;
        }
        .btn-delete {
            color: #dc3545;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.2rem;
            transition: color 0.2s;
        }
        .btn-delete:hover {
            color: #bb2d3b;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .preview-item .remove-preview {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gallery-card {
            height: 100%;
            transition: transform 0.3s ease;
        }
        .gallery-card:hover {
            transform: translateY(-5px);
        }
        .gallery-card .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .cover-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gallery Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSetModal">
                <i class="fas fa-plus"></i> Create New Gallery Set
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'set_created':
                        echo "Gallery set created successfully with " . ($_GET['images'] ?? 0) . " images!";
                        if (isset($_GET['failed'])) {
                            echo " Failed to upload " . ($_GET['failed'] ?? 0) . " images.";
                        }
                        break;
                    case 'set_deleted':
                        echo "Gallery set deleted successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                switch ($_GET['error']) {
                    case 'creation_failed':
                        echo "Failed to create gallery set. Please try again.";
                        if (isset($_GET['message'])) {
                            echo "<br>Error details: " . htmlspecialchars($_GET['message']);
                        }
                        break;
                    case 'delete_failed':
                        echo "Failed to delete gallery set. Please try again.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Gallery Sets -->
        <div class="row">
            <?php foreach ($gallery_sets as $set): ?>
            <div class="col-md-4 mb-4">
                <div class="card gallery-card">
                    <?php if ($set['has_cover_photo']): ?>
                        <img src="../serve_image.php?type=cover&id=<?php echo $set['id']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($set['name']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($set['name']); ?></h5>
                            <button type="button" class="btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSetModal<?php echo $set['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <p class="card-text">
                            <small class="text-muted">
                                <?php echo $set['image_count']; ?> images
                            </small>
                        </p>
                        <div class="d-flex justify-content-between gap-2">
                            <a href="gallery_set.php?id=<?php echo $set['id']; ?>" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-images"></i> View Images
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Gallery Set Modal -->
            <div class="modal fade" id="deleteSetModal<?php echo $set['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Gallery Set</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete the gallery set "<?php echo htmlspecialchars($set['name']); ?>"?</p>
                            <p class="text-danger">This action cannot be undone. All images in this gallery set will be permanently deleted.</p>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete_set">
                                <input type="hidden" name="set_id" value="<?php echo $set['id']; ?>">
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete Gallery Set</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create Gallery Set Modal -->
    <div class="modal fade" id="createSetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Gallery Set</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_set">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cover Photo</label>
                            <div class="image-upload" onclick="document.getElementById('cover_photo').click()">
                                <i class="fas fa-image fa-3x mb-3"></i>
                                <h5>Click to select cover photo</h5>
                                <p class="text-muted">or drag and drop an image here</p>
                                <input type="file" id="cover_photo" name="cover_photo" accept="image/*">
                            </div>
                            <img id="cover-preview" class="cover-preview d-none" alt="Cover Preview">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gallery Images</label>
                            <div class="image-upload" onclick="document.getElementById('images').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                <h5>Click to select images</h5>
                                <p class="text-muted">or drag and drop images here</p>
                                <input type="file" id="images" name="images[]" multiple accept="image/*">
                            </div>
                            <div id="preview-container" class="preview-container"></div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Gallery Set</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle cover photo preview
        const coverInput = document.getElementById('cover_photo');
        const coverPreview = document.getElementById('cover-preview');

        coverInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.src = e.target.result;
                    coverPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Handle image selection and preview
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('preview-container');
        const selectedFiles = new Set();

        imageInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        // Handle drag and drop
        const dropZones = document.querySelectorAll('.image-upload');
        dropZones.forEach(dropZone => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                const input = this.querySelector('input[type="file"]');
                
                if (input.id === 'cover_photo') {
                    input.files = files;
                    const event = new Event('change');
                    input.dispatchEvent(event);
                } else {
                    handleFiles(files);
                }
            });
        });

        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.add(file);
                    createPreview(file);
                }
            });
            updateFileInput();
        }

        function createPreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-preview" onclick="removePreview(this, '${file.name}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }

        function removePreview(button, fileName) {
            const previewItem = button.parentElement;
            selectedFiles.forEach(file => {
                if (file.name === fileName) {
                    selectedFiles.delete(file);
                }
            });
            previewItem.remove();
            updateFileInput();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            imageInput.files = dataTransfer.files;
        }
    </script>
</body>
</html> 