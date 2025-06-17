CREATE TABLE packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_en VARCHAR(255) NOT NULL,
    name_sq VARCHAR(255) NOT NULL,
    description_en TEXT,
    description_sq TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('wedding', 'photography', 'event') NOT NULL,
    features JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 