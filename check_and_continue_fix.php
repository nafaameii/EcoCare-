
<?php
require 'config.php';

echo "<h1>Status Database dan Lanjutan Perbaikan</h1>";
echo "<pre>";

try {
    $pdo->beginTransaction();

    echo "\n--- 1. Membaca struktur tabel educations ---\n";
    $stmt = $pdo->query("DESCRIBE educations");
    $eduCols = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $eduCols[] = $col['Field'];
        echo "- {$col['Field']}: {$col['Type']}\n";
    }

    if (!in_array('photo_path', $eduCols)) {
        echo "\nMenambah kolom photo_path ke educations...\n";
        $pdo->exec("ALTER TABLE educations ADD COLUMN photo_path VARCHAR(255) NULL AFTER content");
    } else {
        echo "- Kolom photo_path di educations sudah ada ✓\n";
    }

    echo "\n--- 2. Membaca struktur tabel community_members ---\n";
    $stmt = $pdo->query("DESCRIBE community_members");
    $cmCols = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cmCols[] = $col['Field'];
        echo "- {$col['Field']}: {$col['Type']}\n";
    }

    if (!in_array('status', $cmCols)) {
        echo "\nMenambah kolom status ke community_members...\n";
        $pdo->exec("ALTER TABLE community_members ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER user_id");
    } else {
        echo "- Kolom status di community_members sudah ada ✓\n";
    }

    echo "\n--- 3. Membaca struktur tabel community_actions ---\n";
    $stmt = $pdo->query("DESCRIBE community_actions");
    $caCols = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $caCols[] = $col['Field'];
        echo "- {$col['Field']}: {$col['Type']}\n";
    }

    $requiredCaColumns = [
        'location' => 'VARCHAR(255) NULL AFTER description',
        'target_date' => 'DATE NULL AFTER location',
        'start_date' => 'DATE NULL AFTER target_date',
        'end_date' => 'DATE NULL AFTER start_date',
        'target_volunteers' => 'INT NULL AFTER end_date',
        'photo_path' => 'VARCHAR(255) NULL AFTER target_volunteers',
        'progress' => 'INT DEFAULT 0 AFTER status'
    ];
    
    foreach ($requiredCaColumns as $column => $def) {
        if (!in_array($column, $caCols)) {
            echo "\nMenambah kolom $column ke community_actions...\n";
            $pdo->exec("ALTER TABLE community_actions ADD COLUMN $column $def");
        } else {
            echo "- Kolom $column di community_actions sudah ada ✓\n";
        }
    }

    echo "\n--- 4. Memeriksa tabel action_participants ---\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'action_participants'");
    if (!$stmt->fetch()) {
        echo "Membuat tabel action_participants...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS action_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action_id INT NOT NULL,
                user_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_participant (action_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        echo "- Tabel action_participants sudah ada ✓\n";
    }

    echo "\n--- 5. Memeriksa tabel community_contributions ---\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_contributions'");
    if (!$stmt->fetch()) {
        echo "Membuat tabel community_contributions...\n";
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
    } else {
        echo "- Tabel community_contributions sudah ada ✓\n";
    }

    echo "\n--- 6. Memeriksa tabel community_comments ---\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_comments'");
    if (!$stmt->fetch()) {
        echo "Membuat tabel community_comments...\n";
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
    } else {
        echo "- Tabel community_comments sudah ada ✓\n";
    }

    echo "\n--- 7. Memeriksa direktori upload ---\n";
    $dirs = ['uploads/education', 'uploads/actions'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "✓ Direktori $dir dibuat\n";
        } else {
            echo "- Direktori $dir sudah ada ✓\n";
        }
    }

    $pdo->commit();
    echo "\n✅ SEMUA PERBAIKAN BERHASIL DILAKUKAN!\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "</pre>";
?>
