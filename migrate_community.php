<?php
require 'config.php';

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. First, add location column to users if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'latitude'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
    }

    // 2. Create community_members table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_member (report_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 3. Create community_actions table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 4. Create community_contributions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_contributions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_id INT NOT NULL,
            user_id INT NOT NULL,
            category ENUM('tenaga', 'alat', 'dokumentasi', 'transportasi', 'edukasi', 'lainnya') NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 5. Create community_comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 6. Create community_invitations table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 7. Create community_documentations table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 8. Create notifications table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 9. Update reports table to add new statuses
    $pdo->exec("
        ALTER TABLE reports 
        MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru'
    ");

    // Commit transaction
    $pdo->commit();

    echo "Migration completed successfully!";

} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    die("Migration failed: " . $e->getMessage());
}
?>
