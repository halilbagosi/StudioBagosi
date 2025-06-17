-- Create database
CREATE DATABASE IF NOT EXISTS studio_bagosi;
USE studio_bagosi;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Packages table
CREATE TABLE IF NOT EXISTS packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL, -- in hours
    photos_count INT NOT NULL,
    features TEXT,
    category ENUM('wedding', 'photography', 'event', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    package_id INT,
    booking_date DATE NOT NULL,
    event_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (package_id) REFERENCES packages(id)
);

-- Gallery sets table
CREATE TABLE IF NOT EXISTS gallery_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cover_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gallery images table
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    set_id INT,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(100),
    description TEXT,
    is_featured BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (set_id) REFERENCES gallery_sets(id)
);

-- Insert default packages if not exists
INSERT IGNORE INTO packages (name, description, price, duration, photos_count, category, features) VALUES
('Basic Wedding', 'Perfect for intimate ceremonies', 500.00, 4, 100, 'wedding', '["4 hours coverage", "100 edited photos", "Online gallery", "Delivery in 2 weeks"]'),
('Premium Wedding', 'Complete wedding coverage', 800.00, 8, 300, 'wedding', '["8 hours coverage", "300 edited photos", "Online gallery", "Engagement session", "Delivery in 1 week"]'),
('Luxury Wedding', 'Full wedding experience', 1200.00, 12, 500, 'wedding', '["12 hours coverage", "500 edited photos", "Online gallery", "Engagement session", "Wedding album", "Delivery in 5 days"]'),
('Portrait Session', 'Professional portrait photography', 200.00, 2, 50, 'photography', '["2 hours session", "50 edited photos", "Online gallery", "Delivery in 1 week"]'),
('Family Session', 'Capture precious family moments', 300.00, 3, 75, 'photography', '["3 hours session", "75 edited photos", "Online gallery", "Family album", "Delivery in 1 week"]'),
('Engagement Session', 'Perfect for couples', 250.00, 2, 60, 'photography', '["2 hours session", "60 edited photos", "Online gallery", "2 locations", "Delivery in 1 week"]'),
('Corporate Event', 'Professional event coverage', 400.00, 4, 100, 'event', '["4 hours coverage", "100 edited photos", "Online gallery", "Delivery in 1 week"]'),
('Birthday Party', 'Capture special moments', 300.00, 3, 75, 'event', '["3 hours coverage", "75 edited photos", "Online gallery", "Delivery in 1 week"]'),
('Special Event', 'Custom event coverage', 350.00, 3, 60, 'event', '["3 hours coverage", "60 edited photos", "Online gallery", "Custom requirements", "Delivery in 1 week"]');

-- Insert default gallery sets if not exists
INSERT IGNORE INTO gallery_sets (name, description) VALUES
('Rome', 'Wedding photography in Rome'),
('Kallm', 'Wedding photography in Kallm');

-- Create media table
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    type ENUM('photo', 'video') NOT NULL,
    path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
); 