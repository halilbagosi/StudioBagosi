const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Read the mapping files
const galleryMapping = JSON.parse(fs.readFileSync('gallery_mapping.json', 'utf8'));
const coverMapping = JSON.parse(fs.readFileSync('cover_mapping.json', 'utf8'));

console.log('🚀 Starting image upload to R2...');

// Upload gallery images
console.log('\n📸 Uploading gallery images...');
let uploadedCount = 0;
let totalCount = 0;

for (const [imageId, imageInfo] of Object.entries(galleryMapping)) {
    totalCount++;
    const localPath = path.join('gallery', imageInfo.filename);
    const r2Key = `gallery/${imageInfo.filename}`;
    
    try {
        if (fs.existsSync(localPath)) {
            // Upload using wrangler r2 object put
            const command = `wrangler r2 object put studio-bagosi-images/${r2Key} --file=${localPath}`;
            execSync(command, { stdio: 'pipe' });
            uploadedCount++;
            console.log(`✅ Uploaded: ${imageInfo.filename}`);
        } else {
            console.log(`❌ File not found: ${localPath}`);
        }
    } catch (error) {
        console.log(`❌ Failed to upload ${imageInfo.filename}:`, error.message);
    }
}

// Upload cover images
console.log('\n🖼️  Uploading cover images...');
let coverUploaded = 0;
let coverTotal = 0;

for (const [setId, coverInfo] of Object.entries(coverMapping)) {
    coverTotal++;
    const localPath = path.join('covers', coverInfo.filename);
    const r2Key = `covers/${coverInfo.filename}`;
    
    try {
        if (fs.existsSync(localPath)) {
            const command = `wrangler r2 object put studio-bagosi-images/${r2Key} --file=${localPath}`;
            execSync(command, { stdio: 'pipe' });
            coverUploaded++;
            console.log(`✅ Uploaded cover: ${coverInfo.filename}`);
        } else {
            console.log(`❌ Cover file not found: ${localPath}`);
        }
    } catch (error) {
        console.log(`❌ Failed to upload cover ${coverInfo.filename}:`, error.message);
    }
}

console.log('\n📊 Upload Summary:');
console.log(`Gallery Images: ${uploadedCount}/${totalCount} uploaded`);
console.log(`Cover Images: ${coverUploaded}/${coverTotal} uploaded`);
console.log(`Total: ${uploadedCount + coverUploaded}/${totalCount + coverTotal} uploaded`);

if (uploadedCount + coverUploaded > 0) {
    console.log('\n✅ Upload completed! Your images are now in R2 storage.');
    console.log('🔗 Images will be accessible at: https://studio-bagosi-images.r2.dev/');
} else {
    console.log('\n❌ No images were uploaded. Check the file paths and try again.');
} 