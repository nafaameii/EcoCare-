<?php
require 'config.php';

echo "<h1>Migrasi Database EcoCare+ - Full</h1>";
echo "<pre>";

try {
    // Start transaction
    $pdo->beginTransaction();
    echo "✓ Transaction started\n";

    // 1. Fix users table role and add status and location
    echo "\n--- Fixing users table ---\n";

    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role");
        echo "✓ Added status column to users table\n";
    } else {
        echo "- status column already exists\n";
    }

    // Check if latitude column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'latitude'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
        echo "✓ Added latitude/longitude to users table\n";
    } else {
        echo "- latitude/longitude already exist\n";
    }

    // Fix role enum
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat'");
        echo "✓ Updated users role enum\n";
    } catch (PDOException $e) {
        echo "- Role enum already correct or error: " . $e->getMessage() . "\n";
    }

    // Fix any existing users with wrong role
    $pdo->exec("UPDATE users SET role = 'masyarakat' WHERE role NOT IN ('masyarakat', 'admin')");
    echo "✓ Fixed any incorrect user roles\n";

    // 2. Create community tables
    echo "\n--- Creating community tables ---\n";

    // community_members
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
    echo "✓ community_members table created\n";

    // community_actions
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
    echo "✓ community_actions table created\n";

    // community_contributions
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
    echo "✓ community_contributions table created\n";

    // community_comments
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
    echo "✓ community_comments table created\n";

    // community_invitations
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
    echo "✓ community_invitations table created\n";

    // community_documentations
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
    echo "✓ community_documentations table created\n";

    // notifications
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
    echo "✓ notifications table created\n";

    // 3. Update reports table statuses
    echo "\n--- Updating reports table ---\n";
    try {
        $pdo->exec("
            ALTER TABLE reports 
            MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru'
        ");
        echo "✓ Updated reports statuses\n";
    } catch (PDOException $e) {
        echo "- Reports statuses already up to date: " . $e->getMessage() . "\n";
    }

    // 4. Add sample data (optional)
    echo "\n--- Adding sample data (if tables are empty) ---\n";

    // Get admin user ID to use as sample creator
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    $adminId = $admin['id'] ?? 1;

    // Get a sample report or create one
    $stmt = $pdo->query("SELECT id FROM reports LIMIT 1");
    $report = $stmt->fetch();
    if (!$report) {
        $pdo->exec("
            INSERT INTO reports (user_id, category, description, location_address, latitude, longitude, status) 
            VALUES ($adminId, 'Sampah', 'Sampah menumpuk di sungai', 'Jl. Sungai No. 123', -6.2088, 106.8456, 'Baru')
        ");
        $stmt = $pdo->query("SELECT LAST_INSERT_ID() as id");
        $report = $stmt->fetch();
        echo "✓ Created sample report\n";
    }
    $reportId = $report['id'];

    // Add sample community members (if none exist)
    $stmt = $pdo->query("SELECT COUNT(*) FROM community_members");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT IGNORE INTO community_members (report_id, user_id) VALUES ($reportId, $adminId)");
        echo "✓ Added sample community member\n";
    }

    // Add sample community action (if none exist)
    $stmt = $pdo->query("SELECT COUNT(*) FROM community_actions");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO community_actions (report_id, created_by, title, description, target_date, target_volunteers, status, progress) 
            VALUES ($reportId, $adminId, 'Bersih-bersih Sungai', 'Aksi membersihkan sungai bersama warga sekitar', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 20, 'planned', 0)
        ");
        echo "✓ Added sample community action\n";
    }

    // Commit transaction
    $pdo->commit();
    echo "\n✅ SEMUA MIGRASI BERHASIL DILAKUKAN!\n";
    echo "\nYou can now use all community features!\n";

} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    echo "\n❌ Migrasi gagal: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Kembali ke Beranda</a> | <a href='admin_users.php'>Ke Halaman Kelola Pengguna</a></p>";
?>
