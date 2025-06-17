-- Database Updates for BLOB Image Storage
-- Run this script to modify the existing database for storing images as BLOB data

USE studio_bagosi;

-- Update gallery_sets table to store cover photo as BLOB
ALTER TABLE gallery_sets 
ADD COLUMN cover_photo_data LONGBLOB AFTER cover_photo,
ADD COLUMN cover_photo_mime_type VARCHAR(50) AFTER cover_photo_data,
ADD COLUMN cover_photo_filename VARCHAR(255) AFTER cover_photo_mime_type;

-- Update gallery_images table to store image data as BLOB
ALTER TABLE gallery_images 
ADD COLUMN image_data LONGBLOB AFTER image_path,
ADD COLUMN mime_type VARCHAR(50) AFTER image_data,
ADD COLUMN filename VARCHAR(255) AFTER mime_type,
ADD COLUMN file_size INT AFTER filename;

-- Create index for better performance when serving images
ALTER TABLE gallery_images ADD INDEX idx_set_id (set_id);
ALTER TABLE gallery_sets ADD INDEX idx_created (created_at);

-- Note: The old 'image_path' and 'cover_photo' columns are kept for backward compatibility
-- They can be removed later after confirming the BLOB storage works properly 