<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth_check.php';
require_once '../config/database.php';

// Handle package actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_package') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $duration = $_POST['duration'] ?? '';
        $photos_count = $_POST['photos_count'] ?? 0;
        $category = $_POST['category'] ?? '';
        
        // Handle features
        $included_features = $_POST['included_features'] ?? '';
        $excluded_features = $_POST['excluded_features'] ?? '';
        $features = json_encode([
            'included' => array_filter(explode("\n", str_replace("\r", "", $included_features))),
            'excluded' => array_filter(explode("\n", str_replace("\r", "", $excluded_features)))
        ]);
        
        if (!empty($name) && !empty($price) && !empty($category)) {
            $stmt = $pdo->prepare("INSERT INTO packages (name, description, price, duration, photos_count, category, features) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $duration, $photos_count, $category, $features]);
            header('Location: packages.php?success=package_created');
            exit;
        }
    }
    
    if ($_POST['action'] === 'update_package') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $duration = $_POST['duration'] ?? '';
        $photos_count = $_POST['photos_count'] ?? 0;
        $category = $_POST['category'] ?? '';
        
        // Handle features
        $included_features = $_POST['included_features'] ?? '';
        $excluded_features = $_POST['excluded_features'] ?? '';
        $features = json_encode([
            'included' => array_filter(explode("\n", str_replace("\r", "", $included_features))),
            'excluded' => array_filter(explode("\n", str_replace("\r", "", $excluded_features)))
        ]);
        
        if (!empty($id) && !empty($name) && !empty($price) && !empty($category)) {
            $stmt = $pdo->prepare("UPDATE packages SET name = ?, description = ?, price = ?, duration = ?, photos_count = ?, category = ?, features = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $duration, $photos_count, $category, $features, $id]);
            header('Location: packages.php?success=package_updated');
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete_package') {
        $package_id = $_POST['package_id'] ?? 0;
        
        if ($package_id > 0) {
            try {
                // Instead of deleting, set the package as inactive
                $stmt = $pdo->prepare("UPDATE packages SET is_active = 0 WHERE id = ?");
                $stmt->execute([$package_id]);
                
                header('Location: packages.php?success=package_deactivated');
                exit;
            } catch(PDOException $e) {
                $error_message = "Error deactivating package: " . $e->getMessage();
            }
        }
    }
}

// Get all active packages
$stmt = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY category, price ASC");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group packages by category
$packages_by_category = [];
foreach ($packages as $package) {
    $packages_by_category[$package['category']][] = $package;
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages Management - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .package-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .package-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .package-card .card-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            flex: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .package-card .price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .package-card .details {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .package-card .features {
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .package-card .features ul {
            margin-bottom: 0;
        }
        .package-card .features li {
            margin-bottom: 0.25rem;
        }
        .package-card .actions {
            margin-top: auto;
        }
        .word-count {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }
        .word-count.warning {
            color: #dc3545;
        }
        .description-container {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Packages Management</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPackageModal">
                <i class="fas fa-plus"></i> New Package
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'package_created':
                        echo "Package created successfully!";
                        break;
                    case 'package_updated':
                        echo "Package updated successfully!";
                        break;
                    case 'package_deactivated':
                        echo "Package deactivated successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Packages by Category -->
        <?php foreach ($packages_by_category as $category => $category_packages): ?>
        <div class="mb-5">
            <h2 class="mb-4"><?php echo ucfirst($category); ?> Packages</h2>
            <div class="row">
                <?php foreach ($category_packages as $package): ?>
                <div class="col-md-4 mb-4">
                    <div class="card package-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($package['description']); ?></p>
                            <p class="price">€<?php echo number_format($package['price'], 2); ?></p>
                            <p class="details">
                                Duration: <?php echo htmlspecialchars($package['duration']); ?> hours<br>
                                Photos: <?php echo $package['photos_count']; ?>
                            </p>
                            <div class="features">
                                <h6>Features:</h6>
                                <?php
                                $features = json_decode($package['features'], true);
                                if ($features) {
                                    echo '<ul class="list-unstyled">';
                                    // Display included features
                                    if (!empty($features['included'])) {
                                        foreach ($features['included'] as $feature) {
                                            echo '<li><i class="fas fa-check text-success me-2"></i>' . htmlspecialchars($feature) . '</li>';
                                        }
                                    }
                                    // Display excluded features
                                    if (!empty($features['excluded'])) {
                                        foreach ($features['excluded'] as $feature) {
                                            echo '<li><i class="fas fa-times text-danger me-2"></i>' . htmlspecialchars($feature) . '</li>';
                                        }
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </div>
                            <div class="actions d-flex gap-2">
                                <button type="button" class="btn btn-primary" style="flex: 0 0 auto;" data-bs-toggle="modal" data-bs-target="#editPackageModal" 
                                        data-package='<?php echo htmlspecialchars(json_encode($package)); ?>'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="" class="d-inline flex-grow-1" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                    <input type="hidden" name="action" value="delete_package">
                                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Create Package Modal -->
    <div class="modal fade" id="createPackageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_package">
                        <div class="mb-3">
                            <label for="name" class="form-label">Package Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <div class="description-container">
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="200" oninput="updateWordCount(this)"></textarea>
                                <div class="word-count">0/50 words</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="wedding">Wedding Packages</option>
                                <option value="other">Other Services</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (€)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (hours)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                        <div class="mb-3">
                            <label for="photos_count" class="form-label">Number of Photos</label>
                            <input type="number" class="form-control" id="photos_count" name="photos_count" required>
                        </div>
                        <div class="mb-3">
                            <label for="included_features" class="form-label">Included Features (one per line)</label>
                            <textarea class="form-control" id="included_features" name="included_features" rows="5" placeholder="4 hours coverage&#10;100 edited photos&#10;Online gallery&#10;Delivery in 2 weeks"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="excluded_features" class="form-label">Excluded Features (one per line)</label>
                            <textarea class="form-control" id="excluded_features" name="excluded_features" rows="5" placeholder="Drone footage&#10;Photo album&#10;Engagement shoot"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Package</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_package">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Package Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <div class="description-container">
                                <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="200" oninput="updateWordCount(this)"></textarea>
                                <div class="word-count">0/50 words</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <option value="wedding">Wedding Packages</option>
                                <option value="other">Other Services</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price (€)</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duration" class="form-label">Duration (hours)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_photos_count" class="form-label">Number of Photos</label>
                            <input type="number" class="form-control" id="edit_photos_count" name="photos_count" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_included_features" class="form-label">Included Features (one per line)</label>
                            <textarea class="form-control" id="edit_included_features" name="included_features" rows="5"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_excluded_features" class="form-label">Excluded Features (one per line)</label>
                            <textarea class="form-control" id="edit_excluded_features" name="excluded_features" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Package</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit package modal
        document.querySelectorAll('[data-bs-target="#editPackageModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const package = JSON.parse(this.dataset.package);
                document.getElementById('edit_id').value = package.id;
                document.getElementById('edit_name').value = package.name;
                document.getElementById('edit_description').value = package.description;
                updateWordCount(document.getElementById('edit_description'));
                document.getElementById('edit_category').value = package.category === 'photography' || package.category === 'event' ? 'other' : package.category;
                document.getElementById('edit_price').value = package.price;
                document.getElementById('edit_duration').value = package.duration;
                document.getElementById('edit_photos_count').value = package.photos_count;
                
                // Handle features
                const features = JSON.parse(package.features);
                document.getElementById('edit_included_features').value = features.included ? features.included.join('\n') : '';
                document.getElementById('edit_excluded_features').value = features.excluded ? features.excluded.join('\n') : '';
            });
        });

        // Word count functionality
        function updateWordCount(textarea) {
            const maxWords = 50;
            const wordCountElement = textarea.parentElement.querySelector('.word-count');
            const words = textarea.value.trim().split(/\s+/).filter(word => word.length > 0);
            const wordCount = words.length;
            
            wordCountElement.textContent = `${wordCount}/${maxWords} words`;
            
            if (wordCount > maxWords) {
                wordCountElement.classList.add('warning');
                // Truncate text to max words
                const truncatedText = words.slice(0, maxWords).join(' ');
                textarea.value = truncatedText;
                wordCountElement.textContent = `${maxWords}/${maxWords} words`;
            } else {
                wordCountElement.classList.remove('warning');
            }
        }

        // Initialize word count for create form
        document.addEventListener('DOMContentLoaded', function() {
            const createDescription = document.getElementById('description');
            if (createDescription) {
                updateWordCount(createDescription);
            }
        });
    </script>
</body>
</html> 