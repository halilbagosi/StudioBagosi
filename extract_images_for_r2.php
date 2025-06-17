<?php
/**
 * Extract Images from MySQL BLOBs for Cloudflare R2 Migration
 * This script extracts all gallery images and cover photos from the database
 * and saves them as files for upload to Cloudflare R2
 */

require_once 'config/database.php';

// Create directories for extracted images
$extractDir = 'r2_migration';
$galleryDir = $extractDir . '/gallery';
$coverDir = $extractDir . '/covers';

if (!is_dir($extractDir)) mkdir($extractDir, 0755, true);
if (!is_dir($galleryDir)) mkdir($galleryDir, 0755, true);
if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);

echo "🚀 Starting image extraction for Cloudflare R2 migration...\n\n";

try {
    // Extract gallery images
    echo "📸 Extracting gallery images...\n";
    $stmt = $pdo->query("SELECT id, set_id, title, image_data, mime_type FROM gallery_images WHERE image_data IS NOT NULL");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $imageMapping = [];
    $extractedImages = 0;
    
    foreach ($images as $image) {
        if (!empty($image['image_data'])) {
            // Determine file extension from mime type
            $extension = 'jpg'; // default
            if (strpos($image['mime_type'], 'png') !== false) $extension = 'png';
            elseif (strpos($image['mime_type'], 'gif') !== false) $extension = 'gif';
            elseif (strpos($image['mime_type'], 'webp') !== false) $extension = 'webp';
            
            // Create filename: gallery/set_[setId]/image_[imageId].[ext]
            $filename = "gallery/set_{$image['set_id']}/image_{$image['id']}.{$extension}";
            $localPath = $galleryDir . "/image_{$image['id']}.{$extension}";
            
            // Save the image data to file
            file_put_contents($localPath, $image['image_data']);
            
            // Store mapping for SQL updates later
            $imageMapping[] = [
                'id' => $image['id'],
                'filename' => $filename,
                'local_path' => $localPath,
                'size' => strlen($image['image_data'])
            ];
            
            $extractedImages++;
            echo "  ✅ Extracted image {$image['id']}: {$filename} (" . number_format(strlen($image['image_data'])) . " bytes)\n";
        }
    }
    
    echo "\n📁 Extracting cover photos...\n";
    $stmt = $pdo->query("SELECT id, name, cover_photo_data, cover_photo_mime_type FROM gallery_sets WHERE cover_photo_data IS NOT NULL");
    $covers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $coverMapping = [];
    $extractedCovers = 0;
    
    foreach ($covers as $cover) {
        if (!empty($cover['cover_photo_data'])) {
            // Determine file extension from mime type
            $extension = 'jpg'; // default
            if (strpos($cover['cover_photo_mime_type'], 'png') !== false) $extension = 'png';
            elseif (strpos($cover['cover_photo_mime_type'], 'gif') !== false) $extension = 'gif';
            elseif (strpos($cover['cover_photo_mime_type'], 'webp') !== false) $extension = 'webp';
            
            // Create filename: covers/cover_[setId].[ext]
            $filename = "covers/cover_{$cover['id']}.{$extension}";
            $localPath = $coverDir . "/cover_{$cover['id']}.{$extension}";
            
            // Save the cover data to file
            file_put_contents($localPath, $cover['cover_photo_data']);
            
            // Store mapping for SQL updates later
            $coverMapping[] = [
                'id' => $cover['id'],
                'filename' => $filename,
                'local_path' => $localPath,
                'size' => strlen($cover['cover_photo_data'])
            ];
            
            $extractedCovers++;
            echo "  ✅ Extracted cover {$cover['id']}: {$filename} (" . number_format(strlen($cover['cover_photo_data'])) . " bytes)\n";
        }
    }
    
    // Generate mapping files for migration
    echo "\n📋 Generating migration mapping files...\n";
    
    // Gallery images mapping
    $galleryMappingFile = $extractDir . '/gallery_mapping.json';
    file_put_contents($galleryMappingFile, json_encode($imageMapping, JSON_PRETTY_PRINT));
    echo "  ✅ Gallery mapping saved to: {$galleryMappingFile}\n";
    
    // Cover photos mapping
    $coverMappingFile = $extractDir . '/cover_mapping.json';
    file_put_contents($coverMappingFile, json_encode($coverMapping, JSON_PRETTY_PRINT));
    echo "  ✅ Cover mapping saved to: {$coverMappingFile}\n";
    
    // Generate SQLite migration data
    echo "\n🗄️ Generating SQLite migration data...\n";
    
    // Export all non-BLOB data for SQLite migration
    $migrationData = [
        'users' => [],
        'packages' => [],
        'gallery_sets' => [],
        'gallery_images' => [],
        'bookings' => [],
        'admins' => []
    ];
    
    // Users
    $stmt = $pdo->query("SELECT id, username, email, password, full_name, role, created_at FROM users");
    $migrationData['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Packages
    $stmt = $pdo->query("SELECT id, name, description, price, category, features, is_active, created_at FROM packages");
    $migrationData['packages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gallery sets (with R2 URLs)
    $stmt = $pdo->query("SELECT id, name, description, created_at FROM gallery_sets");
    $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sets as &$set) {
        // Find corresponding cover mapping
        foreach ($coverMapping as $cover) {
            if ($cover['id'] == $set['id']) {
                $set['cover_photo_url'] = "https://images.studiobagosi.com/{$cover['filename']}";
                break;
            }
        }
    }
    $migrationData['gallery_sets'] = $sets;
    
    // Gallery images (with R2 URLs)
    $stmt = $pdo->query("SELECT id, set_id, title, description, mime_type, created_at FROM gallery_images");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as &$image) {
        // Find corresponding image mapping
        foreach ($imageMapping as $mapping) {
            if ($mapping['id'] == $image['id']) {
                $image['image_url'] = "https://images.studiobagosi.com/{$mapping['filename']}";
                break;
            }
        }
    }
    $migrationData['gallery_images'] = $images;
    
    // Bookings
    $stmt = $pdo->query("SELECT id, user_id, package_id, booking_date, event_date, status, notes, created_at FROM bookings");
    $migrationData['bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Admins
    $stmt = $pdo->query("SELECT id, username, email, password, full_name, role, created_at FROM admins");
    $migrationData['admins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrationFile = $extractDir . '/migration_data.json';
    file_put_contents($migrationFile, json_encode($migrationData, JSON_PRETTY_PRINT));
    echo "  ✅ Migration data saved to: {$migrationFile}\n";
    
    // Generate R2 upload script
    $uploadScript = $extractDir . '/upload_to_r2.js';
    $uploadScriptContent = <<<JS
// Cloudflare R2 Upload Script
// Run this after setting up your R2 bucket and Wrangler CLI

import { readFile, readdir } from 'fs/promises';
import { join } from 'path';

const BUCKET_NAME = 'studio-bagosi-images';

async function uploadFile(localPath, r2Path) {
  try {
    const fileData = await readFile(localPath);
    console.log(`Uploading \${localPath} → \${r2Path}`);
    
    // Use wrangler to upload to R2
    // You'll need to implement this based on your Wrangler setup
    // Example: await uploadToR2(BUCKET_NAME, r2Path, fileData);
    
  } catch (error) {
    console.error(`Error uploading \${localPath}:`, error);
  }
}

async function uploadAllImages() {
  const galleryMapping = JSON.parse(await readFile('./gallery_mapping.json', 'utf8'));
  const coverMapping = JSON.parse(await readFile('./cover_mapping.json', 'utf8'));
  
  console.log('🚀 Starting R2 upload...');
  
  // Upload gallery images
  for (const image of galleryMapping) {
    await uploadFile(image.local_path, image.filename);
  }
  
  // Upload cover photos
  for (const cover of coverMapping) {
    await uploadFile(cover.local_path, cover.filename);
  }
  
  console.log('✅ All images uploaded to R2!');
}

// Run the upload
uploadAllImages().catch(console.error);
JS;
    
    file_put_contents($uploadScript, $uploadScriptContent);
    echo "  ✅ R2 upload script saved to: {$uploadScript}\n";
    
    // Summary
    echo "\n🎉 Extraction completed!\n\n";
    echo "📊 Summary:\n";
    echo "  • Gallery images extracted: {$extractedImages}\n";
    echo "  • Cover photos extracted: {$extractedCovers}\n";
    echo "  • Total files created: " . ($extractedImages + $extractedCovers) . "\n";
    
    $totalSize = 0;
    foreach ($imageMapping as $image) $totalSize += $image['size'];
    foreach ($coverMapping as $cover) $totalSize += $cover['size'];
    echo "  • Total size: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n\n";
    
    echo "📂 Files created in '{$extractDir}/' directory:\n";
    echo "  • gallery/          - Gallery images\n";
    echo "  • covers/           - Cover photos\n";
    echo "  • gallery_mapping.json - Gallery image mappings\n";
    echo "  • cover_mapping.json   - Cover photo mappings\n";
    echo "  • migration_data.json  - SQLite migration data\n";
    echo "  • upload_to_r2.js      - R2 upload script\n\n";
    
    echo "🔥 Next steps for Cloudflare migration:\n";
    echo "  1. Set up Cloudflare D1 database\n";
    echo "  2. Set up Cloudflare R2 bucket\n";
    echo "  3. Upload images to R2 using the mapping files\n";
    echo "  4. Import migration_data.json to D1\n";
    echo "  5. Deploy your Cloudflare Workers\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🚀 Ready for Cloudflare migration!\n";
?> 