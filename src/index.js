/**
 * Studio Bagosi - Cloudflare Worker
 * Main entry point for the application
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const path = url.pathname;
    const method = request.method;

    // CORS headers for all responses
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    };

    // Handle preflight requests
    if (method === 'OPTIONS') {
      return new Response(null, { 
        status: 200, 
        headers: corsHeaders 
      });
    }

    try {
      // API Routes
      if (path.startsWith('/api/')) {
        return await handleAPI(request, env, path, method);
      }
      
      // Static pages
      if (path === '/' || path === '/index.html') {
        return new Response(await getHomePage(), {
          headers: { 
            'Content-Type': 'text/html',
            ...corsHeaders
          }
        });
      }

      if (path === '/gallery') {
        return new Response(await getGalleryPage(), {
          headers: { 
            'Content-Type': 'text/html',
            ...corsHeaders
          }
        });
      }

      // 404 for unknown routes
      return new Response('Page not found', { 
        status: 404,
        headers: corsHeaders
      });

    } catch (error) {
      console.error('Worker error:', error);
      return new Response('Internal Server Error', { 
        status: 500,
        headers: corsHeaders
      });
    }
  },
};

async function handleAPI(request, env, path, method) {
  const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
  };

  // Health check endpoint
  if (path === '/api/health') {
    return Response.json({ 
      status: 'ok', 
      timestamp: new Date().toISOString(),
      services: {
        database: env.DB ? 'connected' : 'not configured',
        storage: env.IMAGES_BUCKET ? 'connected' : 'not configured'
      }
    }, { headers: corsHeaders });
  }

  // Initialize database with schema and seed data
  if (path === '/api/init-db' && env.DB && method === 'POST') {
    try {
      // Create tables
      await env.DB.prepare(`CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        phone TEXT,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`).run();

      await env.DB.prepare(`CREATE TABLE IF NOT EXISTS packages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        category TEXT NOT NULL,
        features TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`).run();

      await env.DB.prepare(`CREATE TABLE IF NOT EXISTS gallery_sets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        cover_photo_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`).run();

      await env.DB.prepare(`CREATE TABLE IF NOT EXISTS gallery_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        set_id INTEGER,
        image_url TEXT NOT NULL,
        filename TEXT,
        title TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (set_id) REFERENCES gallery_sets(id)
      )`).run();

      await env.DB.prepare(`CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        package_id INTEGER,
        booking_date TEXT NOT NULL,
        event_date TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (package_id) REFERENCES packages(id)
      )`).run();

      // Insert seed data
      await env.DB.prepare(`INSERT OR IGNORE INTO packages (name, description, price, category, features, is_active) VALUES (?, ?, ?, ?, ?, ?)`)
        .bind('Dasma Standarte', 'Perfect choice for couples seeking the essentials', 700.00, 'wedding', '{"included":["Online Gallery","50 Printed and Edited Photos","Standard Video Editing","1 USB"]}', 1)
        .run();

      await env.DB.prepare(`INSERT OR IGNORE INTO packages (name, description, price, category, features, is_active) VALUES (?, ?, ?, ?, ?, ?)`)
        .bind('Dasma Premium', 'Premium package with enhanced features', 1300.00, 'wedding', '{"included":["Online Gallery","80 Printed and Edited Photos","Premium Video Editing","2 USB","2 Camera Filming","Drone Filming"]}', 1)
        .run();

      await env.DB.prepare(`INSERT OR IGNORE INTO gallery_sets (name, description, cover_photo_url) VALUES (?, ?, ?)`)
        .bind('Rome Wedding', 'Beautiful wedding photography in Rome', 'https://studio-bagosi-images.r2.dev/covers/cover_4.jpg')
        .run();

      return Response.json({ 
        success: true,
        message: 'Database initialized successfully'
      }, { headers: corsHeaders });

    } catch (error) {
      return Response.json({ 
        success: false,
        error: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Test database connection (when D1 is set up)
  if (path === '/api/test-db' && env.DB) {
    try {
      const result = await env.DB.prepare('SELECT 1 as test').first();
      return Response.json({ 
        database: 'working', 
        test: result 
      }, { headers: corsHeaders });
    } catch (error) {
      return Response.json({ 
        database: 'error', 
        message: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Test R2 connection (when R2 is set up)
  if (path === '/api/test-r2' && env.IMAGES_BUCKET) {
    try {
      // Try to list objects (won't fail if bucket is empty)
      const objects = await env.IMAGES_BUCKET.list({ limit: 1 });
      return Response.json({ 
        storage: 'working', 
        objects: objects.objects.length 
      }, { headers: corsHeaders });
    } catch (error) {
      return Response.json({ 
        storage: 'error', 
        message: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Get packages
  if (path === '/api/packages' && env.DB) {
    try {
      const result = await env.DB.prepare('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC').all();
      return Response.json({ 
        success: true,
        packages: result.results 
      }, { headers: corsHeaders });
    } catch (error) {
      return Response.json({ 
        success: false,
        error: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Get gallery sets
  if (path === '/api/gallery' && env.DB) {
    try {
      const result = await env.DB.prepare('SELECT * FROM gallery_sets ORDER BY created_at DESC').all();
      return Response.json({ 
        success: true,
        gallery_sets: result.results 
      }, { headers: corsHeaders });
    } catch (error) {
      return Response.json({ 
        success: false,
        error: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Get images for a specific gallery set
  if (path.startsWith('/api/gallery/') && path.endsWith('/images') && env.DB) {
    try {
      const setId = path.split('/')[3]; // Extract set ID from path
      const result = await env.DB.prepare('SELECT * FROM gallery_images WHERE set_id = ? ORDER BY created_at ASC')
        .bind(setId).all();
      return Response.json({ 
        success: true,
        images: result.results 
      }, { headers: corsHeaders });
    } catch (error) {
      return Response.json({ 
        success: false,
        error: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Update data with correct image URLs
  if (path === '/api/update-data' && env.DB && method === 'POST') {
    try {
      // Update gallery set with correct cover URL
      await env.DB.prepare(`UPDATE gallery_sets SET cover_photo_url = ? WHERE id = ?`)
        .bind('http://localhost:8787/api/image/covers/cover_18.jpg', 1)
        .run();

      // Add some gallery images
      await env.DB.prepare(`INSERT OR IGNORE INTO gallery_images (set_id, image_url, filename, title) VALUES (?, ?, ?, ?)`)
        .bind(1, 'http://localhost:8787/api/image/gallery/image_101.jpg', 'image_101.jpg', 'Beautiful Wedding Moment 1')
        .run();

      await env.DB.prepare(`INSERT OR IGNORE INTO gallery_images (set_id, image_url, filename, title) VALUES (?, ?, ?, ?)`)
        .bind(1, 'http://localhost:8787/api/image/gallery/image_102.jpg', 'image_102.jpg', 'Beautiful Wedding Moment 2')
        .run();

      return Response.json({ 
        success: true,
        message: 'Data updated successfully'
      }, { headers: corsHeaders });

    } catch (error) {
      return Response.json({ 
        success: false,
        error: error.message 
      }, { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  // Serve images from R2
  if (path.startsWith('/api/image/') && env.IMAGES_BUCKET) {
    try {
      // Extract the image path (everything after /api/image/)
      const imagePath = path.substring('/api/image/'.length);
      
      const object = await env.IMAGES_BUCKET.get(imagePath);
      if (object === null) {
        return new Response('Image not found', { 
          status: 404,
          headers: corsHeaders 
        });
      }

      // Get the content type based on file extension
      const ext = imagePath.split('.').pop().toLowerCase();
      const contentType = ext === 'jpg' || ext === 'jpeg' ? 'image/jpeg' : 
                         ext === 'png' ? 'image/png' : 
                         ext === 'gif' ? 'image/gif' : 'image/jpeg';

      return new Response(object.body, {
        headers: {
          'Content-Type': contentType,
          'Cache-Control': 'public, max-age=31536000',
          ...corsHeaders
        }
      });
    } catch (error) {
      return new Response('Error serving image', { 
        status: 500,
        headers: corsHeaders 
      });
    }
  }

  return Response.json({ 
    error: 'API endpoint not found' 
  }, { 
    status: 404,
    headers: corsHeaders 
  });
}

async function getHomePage() {
  return `
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Bagosi - Cloudflare Edition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }
        .status-card {
            transition: transform 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-5px);
        }
        .status-ok { border-left: 4px solid #28a745; }
        .status-pending { border-left: 4px solid #ffc107; }
        .status-error { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">
                <i class="fas fa-camera"></i> Studio Bagosi
            </h1>
            <p class="lead mb-4">Now powered by Cloudflare Workers + D1 + R2</p>
            <p class="text-light">Migration in progress...</p>
        </div>
    </div>

    <div class="container py-5">
        <h2 class="text-center mb-5">System Status</h2>
        <div class="row" id="status-container">
            <div class="col-md-4 mb-4">
                <div class="card status-card status-pending">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-server"></i> Worker
                        </h5>
                        <p class="card-text">Cloudflare Worker is running</p>
                        <span class="badge bg-warning">Active</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card status-card" id="db-status">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-database"></i> Database (D1)
                        </h5>
                        <p class="card-text">SQLite database status</p>
                        <span class="badge bg-secondary" id="db-badge">Checking...</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card status-card" id="r2-status">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-cloud"></i> Storage (R2)
                        </h5>
                        <p class="card-text">Object storage status</p>
                        <span class="badge bg-secondary" id="r2-badge">Checking...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <button class="btn btn-primary me-3" onclick="checkStatus()">
                <i class="fas fa-sync-alt"></i> Refresh Status
            </button>
            <a href="/gallery" class="btn btn-outline-primary">
                <i class="fas fa-images"></i> View Gallery
            </a>
        </div>
    </div>

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/api/health');
                const data = await response.json();
                
                // Update database status
                const dbCard = document.getElementById('db-status');
                const dbBadge = document.getElementById('db-badge');
                if (data.services.database === 'connected') {
                    dbCard.className = 'card status-card status-ok';
                    dbBadge.className = 'badge bg-success';
                    dbBadge.textContent = 'Connected';
                } else {
                    dbCard.className = 'card status-card status-pending';
                    dbBadge.className = 'badge bg-warning';
                    dbBadge.textContent = 'Not Configured';
                }

                // Update R2 status
                const r2Card = document.getElementById('r2-status');
                const r2Badge = document.getElementById('r2-badge');
                if (data.services.storage === 'connected') {
                    r2Card.className = 'card status-card status-ok';
                    r2Badge.className = 'badge bg-success';
                    r2Badge.textContent = 'Connected';
                } else {
                    r2Card.className = 'card status-card status-pending';
                    r2Badge.className = 'badge bg-warning';
                    r2Badge.textContent = 'Not Configured';
                }
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }

        // Check status on page load
        document.addEventListener('DOMContentLoaded', checkStatus);
    </script>
</body>
</html>
  `;
}

async function getGalleryPage() {
  return `
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Studio Bagosi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-camera"></i> Studio Bagosi
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="text-center mb-5">Gallery</h1>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i>
            Gallery will be available after D1 and R2 setup is complete.
        </div>
        <div class="text-center">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
  `;
} 