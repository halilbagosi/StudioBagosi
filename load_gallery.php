<?php
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$response = ['success' => false];

if ($type === 'photos') {
    $photos = [];
    $images_dir = 'images/';
    
    if (is_dir($images_dir)) {
        $files = scandir($images_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                $photos[] = [
                    'path' => $images_dir . $file,
                    'title' => pathinfo($file, PATHINFO_FILENAME)
                ];
            }
        }
    }
    
    $response = [
        'success' => true,
        'photos' => $photos
    ];
} elseif ($type === 'videos') {
    $videos = [];
    $clips_dir = 'clips/';
    
    if (is_dir($clips_dir)) {
        $files = scandir($clips_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov'])) {
                // Generate thumbnail if it doesn't exist
                $thumbnail_path = 'images/thumbnails/' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                if (!file_exists($thumbnail_path)) {
                    // You might want to implement video thumbnail generation here
                    // For now, we'll use a default thumbnail
                    $thumbnail_path = 'images/default-video-thumbnail.jpg';
                }
                
                $videos[] = [
                    'path' => $clips_dir . $file,
                    'title' => pathinfo($file, PATHINFO_FILENAME),
                    'thumbnail' => $thumbnail_path
                ];
            }
        }
    }
    
    $response = [
        'success' => true,
        'videos' => $videos
    ];
}

echo json_encode($response);
?> 