-- EcoCare+ Complete Database Migration
-- Fixes all table structures for consistency

-- Use the correct database
USE ecocare;

-- ==========================================
-- 1. Fix users table
-- ==========================================
ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat';

-- ==========================================
-- 2. Fix reports table
-- ==========================================
ALTER TABLE reports MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru';

-- ==========================================
-- 3. Fix community_actions table
-- ==========================================
ALTER TABLE community_actions 
ADD COLUMN IF NOT EXISTS location TEXT AFTER description,
ADD COLUMN IF NOT EXISTS target_date DATE AFTER target_volunteers,
ADD COLUMN IF NOT EXISTS target_volunteers INT AFTER description,
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) AFTER target_date,
MODIFY COLUMN progress INT DEFAULT 0;

-- ==========================================
-- 4. Fix educations table (add image_path column which was missing)
-- ==========================================
CREATE TABLE IF NOT EXISTS educations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 5. Create action_participants table (for users joining actions)
-- ==========================================
CREATE TABLE IF NOT EXISTS action_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (action_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Optional: Check and add columns if needed
-- ==========================================
SET @dbname = DATABASE();
SET @tablename = "actions";
SET @columnname = "image_path";

SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            table_schema = @dbname
            AND table_name = @tablename
            AND column_name = @columnname
    ) > 0,
    "SELECT 1",
    CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(255) NULL")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ==========================================
-- Migration complete!
-- ==========================================
SELECT 'Database migration complete!' AS message;