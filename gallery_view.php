<?php
// Static gallery data (previously from database)
$gallery_sets = [
    1 => [
        'id' => 1,
        'name' => 'Martesa Tradicionale',
        'description' => 'Koleksion i momenteve më të bukura nga martesat tradicionale shqiptare',
        'images' => [
            ['title' => 'Ceremonia e Kurorës', 'description' => 'Momenti i shenjtë i kurorëzimit'],
            ['title' => 'Vallëzimi i Parë', 'description' => 'Vallëzimi i parë si bashkëshortë'],
            ['title' => 'Familja e Re', 'description' => 'Portret familjar pas ceremonisë'],
        ]
    ],
    2 => [
        'id' => 2,
        'name' => 'Martesa Moderne',
        'description' => 'Fotografi moderne dhe krijuese për çiftet e reja',
        'images' => [
            ['title' => 'Sesion Fotografi', 'description' => 'Fotografi kreative në natyrë'],
            ['title' => 'Detaje Dasme', 'description' => 'Detaje të bukura nga dita e dasmës'],
            ['title' => 'Portrete Çifti', 'description' => 'Portrete romantike të çiftit'],
        ]
    ],
    3 => [
        'id' => 3,
        'name' => 'Portrete Profesionale',
        'description' => 'Sesione fotografike profesionale dhe portrete artistike',
        'images' => [
            ['title' => 'Portret Profesional', 'description' => 'Portret profesional në studio'],
            ['title' => 'Fotografi Artistike', 'description' => 'Fotografi artistike dhe kreative'],
            ['title' => 'Sesion Individual', 'description' => 'Sesion fotografik individual'],
        ]
    ]
];

// Get the gallery set ID from URL parameter
$set_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Get the selected gallery set
$selected_set = $gallery_sets[$set_id] ?? $gallery_sets[1];
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Bagosi - <?php echo htmlspecialchars($selected_set['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <style>
        .gallery-header {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 100px 0 50px 0;
            margin-top: 76px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 20px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            transform: translateY(0);
        }
        
        .placeholder-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img id="navbarLogo" src="images/Logo/BAGOSI_Light.png" alt="Studio Bagosi Logo" style="height: 50px;">
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
                    <li class="nav-item ms-3">
                        <button type="button" class="navbar-dark-mode-toggle" id="darkModeToggle" title="Toggle Dark Mode" aria-label="Toggle Dark Mode">
                            <i class="fas fa-moon moon-icon"></i>
                            <i class="fas fa-sun sun-icon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Gallery Header -->
    <section class="gallery-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white">Kryefaqja</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="index.php#gallery" class="text-white">Galeria</a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo htmlspecialchars($selected_set['name']); ?>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="display-4 mb-3"><?php echo htmlspecialchars($selected_set['name']); ?></h1>
                    <p class="lead"><?php echo htmlspecialchars($selected_set['description']); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Content -->
    <section class="py-5">
        <div class="container">
            <!-- Gallery Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($gallery_sets as $set): ?>
                        <a href="gallery_view.php?id=<?php echo $set['id']; ?>" 
                           class="btn <?php echo $set['id'] == $selected_set['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo htmlspecialchars($set['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Gallery Images -->
            <div class="row">
                <div class="col-12">
                    <div class="gallery-grid">
                        <?php foreach ($selected_set['images'] as $index => $image): ?>
                        <div class="gallery-item">
                            <div class="placeholder-image">
                                <div class="text-center">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <br>
                                    <?php echo htmlspecialchars($image['title']); ?>
                                </div>
                            </div>
                            <div class="gallery-item-overlay">
                                <h5><?php echo htmlspecialchars($image['title']); ?></h5>
                                <p class="mb-0"><?php echo htmlspecialchars($image['description']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="bg-light p-4 rounded">
                        <h3>Ju pëlqeu ajo që pamë?</h3>
                        <p>Na kontaktoni për të diskutuar projektin tuaj të ardhshëm</p>
                        <a href="index.php#contact" class="btn btn-primary btn-lg">Na Kontaktoni</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>"></script>
</body>
</html> 