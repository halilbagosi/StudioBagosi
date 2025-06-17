<?php
/**
 * Image Helper Functions for Studio Bagosi
 * Functions to optimize and compress images before storing in database
 */

/**
 * Compress and resize image to reduce file size
 * @param string $image_data - Raw image data
 * @param string $mime_type - Image MIME type
 * @param int $max_width - Maximum width (default: 1920)
 * @param int $max_height - Maximum height (default: 1080)
 * @param int $quality - JPEG quality (default: 85)
 * @return string|false - Compressed image data or false on failure
 */
function compressImage($image_data, $mime_type, $max_width = 1920, $max_height = 1080, $quality = 85) {
    try {
        // Create image resource from data
        $image = imagecreatefromstring($image_data);
        if (!$image) {
            return false;
        }
        
        // Get original dimensions
        $original_width = imagesx($image);
        $original_height = imagesy($image);
        
        // Calculate new dimensions while maintaining aspect ratio
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        // Only resize if image is larger than maximum dimensions
        if ($ratio < 1) {
            $new_width = round($original_width * $ratio);
            $new_height = round($original_height * $ratio);
            
            // Create new image with calculated dimensions
            $resized_image = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG and GIF
            if (in_array($mime_type, ['image/png', 'image/gif'])) {
                imagealphablending($resized_image, false);
                imagesavealpha($resized_image, true);
                $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
                imagefilledrectangle($resized_image, 0, 0, $new_width, $new_height, $transparent);
            }
            
            // Resize the image
            imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
            
            // Clean up original image
            imagedestroy($image);
            $image = $resized_image;
        }
        
        // Output compressed image
        ob_start();
        
        switch ($mime_type) {
            case 'image/jpeg':
                imagejpeg($image, null, $quality);
                break;
            case 'image/png':
                // PNG compression level (0-9, 9 is max compression)
                $png_quality = round((100 - $quality) / 10);
                imagepng($image, null, $png_quality);
                break;
            case 'image/gif':
                imagegif($image);
                break;
            default:
                // Default to JPEG for unknown types
                imagejpeg($image, null, $quality);
                break;
        }
        
        $compressed_data = ob_get_contents();
        ob_end_clean();
        
        // Clean up
        imagedestroy($image);
        
        return $compressed_data;
        
    } catch (Exception $e) {
        error_log("Image compression error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get optimized image data and info
 * @param string $temp_file_path - Path to temporary uploaded file
 * @param string $original_filename - Original filename
 * @return array|false - Array with compressed data and info, or false on failure
 */
function getOptimizedImageData($temp_file_path, $original_filename) {
    try {
        // Read original image data
        $original_data = file_get_contents($temp_file_path);
        if ($original_data === false) {
            return false;
        }
        
        // Get MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($temp_file_path);
        
        // Get original file size
        $original_size = filesize($temp_file_path);
        
        // Compress image
        $compressed_data = compressImage($original_data, $mime_type);
        
        if ($compressed_data === false) {
            // If compression fails, use original data
            $compressed_data = $original_data;
            $compressed_size = $original_size;
        } else {
            $compressed_size = strlen($compressed_data);
        }
        
        return [
            'data' => $compressed_data,
            'mime_type' => $mime_type,
            'filename' => $original_filename,
            'original_size' => $original_size,
            'compressed_size' => $compressed_size,
            'compression_ratio' => $original_size > 0 ? round((1 - $compressed_size / $original_size) * 100, 1) : 0
        ];
        
    } catch (Exception $e) {
        error_log("Image optimization error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if GD extension is available
 * @return bool
 */
function isImageCompressionAvailable() {
    return extension_loaded('gd') && function_exists('imagecreatefromstring');
}
?> 