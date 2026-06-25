<?php
require 'config.php';

echo "<h1>🔧 EcoCare+ Community System Migration</h1>";
echo "<pre>";

try {
    echo "✅ Starting migration\n";
    
    // 1. Fix and update users table
    echo "\n📝 Step 1: Updating users table\n";
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat'");
        echo "  ✅ Fixed role enum\n";
    } catch (PDOException $e) {
        echo "  ℹ️ Role enum already correct: " . $e->getMessage() . "\n";
    }
    
    // Add status column if not exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role");
        echo "  ✅ Added status column\n";
    } else {
        echo "  ℹ️ Status column already exists\n";
    }
    
    // 2. Create community_members table
    echo "\n📝 Step 2: Creating community_members table\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_member (report_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ community_members created\n";
    
    // 3. Create community_actions table
    echo "\n📝 Step 3: Creating community_actions table\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            created_by INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            target_volunteers INT,
            current_volunteers INT DEFAULT 0,
            start_date DATE,
            end_date DATE,
            progress INT DEFAULT 0,
            status ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ community_actions created\n";
    
    // 4. Create community_contributions table
    echo "\n📝 Step 4: Creating community_contributions table\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_contributions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_id INT NOT NULL,
            user_id INT NOT NULL,
            category ENUM('tenaga', 'alat', 'transportasi', 'dokumentasi', 'edukasi', 'dana', 'lainnya') NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ community_contributions created\n";
    
    // 5. Create community_comments table
    echo "\n📝 Step 5: Creating community_comments table\n";
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
    echo "  ✅ community_comments created\n";
    
    // 6. Update reports status enum
    echo "\n📝 Step 6: Updating reports status options\n";
    try {
        $pdo->exec("
            ALTER TABLE reports 
            MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru'
        ");
        echo "  ✅ Updated reports status enum\n";
    } catch (PDOException $e) {
        echo "  ℹ️ Reports status enum already up to date: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "\n✅ All tables created and updated!\n";
    echo "\nNext steps:\n";
    echo "1. <a href='javascript:window.history.back()'>Go back to the report detail page</a>\n";
    echo "2. Refresh the page\n";
    echo "3. Click [Ikut Menindaklanjuti] to join community\n";
    
} catch (PDOException $e) {
    echo "\n❌ MIGRATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Back to Home</a> | <a href='admin_users.php'>Admin Users</a></p>";
?>