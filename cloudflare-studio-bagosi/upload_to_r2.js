// Cloudflare R2 Upload Script
// Run this after setting up your R2 bucket and Wrangler CLI

import { readFile, readdir } from 'fs/promises';
import { join } from 'path';

const BUCKET_NAME = 'studio-bagosi-images';

async function uploadFile(localPath, r2Path) {
  try {
    const fileData = await readFile(localPath);
    console.log(`Uploading ${localPath} → ${r2Path}`);
    
    // Use wrangler to upload to R2
    // You'll need to implement this based on your Wrangler setup
    // Example: await uploadToR2(BUCKET_NAME, r2Path, fileData);
    
  } catch (error) {
    console.error(`Error uploading ${localPath}:`, error);
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