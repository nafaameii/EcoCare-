-- EcoCare+ Full Database Migration
-- Run this in your MySQL database (phpMyAdmin, etc.)

-- ==========================================
-- 1. Fix users table
-- ==========================================

-- Add status column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role;

-- Add latitude/longitude if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL AFTER latitude;

-- Fix role enum
ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat';

-- Fix any users with wrong role
UPDATE users SET role = 'masyarakat' WHERE role NOT IN ('masyarakat', 'admin');

-- ==========================================
-- 2. Create community tables
-- ==========================================

CREATE TABLE IF NOT EXISTS community_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (report_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_date DATE,
    target_volunteers INT,
    status ENUM('planned', 'active', 'completed') DEFAULT 'planned',
    progress INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_id INT NOT NULL,
    user_id INT NOT NULL,
    category ENUM('tenaga', 'alat', 'dokumentasi', 'transportasi', 'edukasi', 'lainnya') NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    invited_by INT NOT NULL,
    invited_user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_invitation (report_id, invited_by, invited_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_documentations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_id INT NOT NULL,
    user_id INT NOT NULL,
    photo_type ENUM('before', 'during', 'after') NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    caption TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 3. Update reports table statuses
-- ==========================================

ALTER TABLE reports MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru';

-- ==========================================
-- Complete!
-- ==========================================
