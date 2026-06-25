-- Database: ecocare
-- Created for EcoCare+ MVP Phase 1 (PRODUCTION READY)

CREATE DATABASE IF NOT EXISTS ecocare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecocare;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    resident_id VARCHAR(50) UNIQUE NOT NULL,
    role ENUM('masyarakat', 'admin') DEFAULT 'masyarakat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('Sampah', 'Saluran Air Tersumbat', 'Genangan Air', 'Lingkungan Kurang Terawat') NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255),
    location_address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('Baru', 'Diproses', 'Selesai') DEFAULT 'Baru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert ONLY PRODUCTION ADMIN (password: admin123)
INSERT INTO users (name, email, password, phone, resident_id, role) VALUES 
('Admin EcoCare', 'admin@ecocare.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', '9876543210987654', 'admin');
