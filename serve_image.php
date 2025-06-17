<?php
/**
 * Image Serving Script for Studio Bagosi
 * Serves images stored as BLOB data in the database
 */

require_once 'config/database.php';

// Get parameters
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0 || !in_array($type, ['gallery', 'cover'])) {
    // Serve a placeholder image or 404
    http_response_code(404);
    header('Content-Type: image/png');
    // Create a simple 1x1 transparent PNG
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    exit;
}

try {
    if ($type === 'gallery') {
        // Serve gallery image
        $stmt = $pdo->prepare("SELECT image_data, mime_type, filename FROM gallery_images WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        

        
        if ($image && $image['image_data']) {
            // Set appropriate headers
            header('Content-Type: ' . ($image['mime_type'] ?? 'image/jpeg'));
            header('Content-Length: ' . strlen($image['image_data']));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            // Set filename for downloads
            if ($image['filename']) {
                header('Content-Disposition: inline; filename="' . $image['filename'] . '"');
            }
            
            // Output image data
            echo $image['image_data'];
        } else {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo 'Image not found';
        }
        
    } elseif ($type === 'cover') {
        // Serve cover photo
        $stmt = $pdo->prepare("SELECT cover_photo_data, cover_photo_mime_type, cover_photo_filename FROM gallery_sets WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        

        
        if ($image && $image['cover_photo_data']) {
            // Set appropriate headers
            header('Content-Type: ' . ($image['cover_photo_mime_type'] ?? 'image/jpeg'));
            header('Content-Length: ' . strlen($image['cover_photo_data']));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            // Set filename for downloads
            if ($image['cover_photo_filename']) {
                header('Content-Disposition: inline; filename="' . $image['cover_photo_filename'] . '"');
            }
            
            // Output image data
            echo $image['cover_photo_data'];
        } else {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo 'Cover image not found';
        }
    }
    
} catch (Exception $e) {
    error_log("Error serving image: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error serving image';
}