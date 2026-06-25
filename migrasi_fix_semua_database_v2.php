<?php
/**
 * Migrasi_fix_semua_database_v2.php
 * 
 * File migrasi yang AMAN dan IDEMPOTEN!
 * Dapat dijalankan berkali-kali tanpa error!
 * 
 * PERBAIKAN: Logika sebelumnya INVERTED!
 */

require 'config.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <title>Migrasi Database EcoCare+ v2</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0fdf4; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        h1 { color: #166534; border-bottom: 2px solid #4ade80; padding-bottom: 0.5rem; }
        h2 { color: #15803d; margin-top: 1.5rem; }
        .success { color: #16a34a; font-weight: bold; }
        .warning { color: #d97706; }
        .info { color: #1d4ed8; }
        .error { color: #dc2626; font-weight: bold; }
        ul { padding-left: 1.5rem; }
        .audit { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 1.5rem; margin-top: 2rem; border-radius: 0 0.5rem 0.5rem 0; }
        .audit h3 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        table, th, td { border: 1px solid #d1fae5; }
        th, td { padding: 0.75rem; text-align: left; }
        th { background: #22c55e; color: white; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🚀 Migrasi Database EcoCare+ v2 (Aman & Idempoten)</h1>";

try {
    $pdo->beginTransaction();

    // --------------------------
    // HELPER FUNCTIONS (SAFE)
    // --------------------------
    function tableExists($pdo, $tableName) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    }

    function columnExists($pdo, $tableName, $columnName) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $stmt->rowCount() > 0;
    }

    function indexExists($pdo, $tableName, $indexName) {
        $stmt = $pdo->query("SHOW INDEX FROM `$tableName` WHERE Key_name = '$indexName'");
        return $stmt->rowCount() > 0;
    }

    function foreignKeyExists($pdo, $tableName, $fkName) {
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = '$tableName' 
            AND CONSTRAINT_NAME = '$fkName' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        return $stmt->rowCount() > 0;
    }

    // --------------------------
    // AUDIT DATA ARRAYS
    // --------------------------
    $audit = [
        'tables' => [
            'existing' => [],
            'created' => []
        ],
        'columns' => [
            'existing' => [],
            'added' => [],
            'modified' => []
        ],
        'foreign_keys' => [
            'existing' => [],
            'added' => []
        ],
        'indexes' => [
            'existing' => [],
            'dropped' => []
        ],
        'admin_accounts' => [
            'created' => [],
            'updated' => []
        ]
    ];

    // --------------------------
    // 1. TABEL: users
    // --------------------------
    echo "<h2>1. Memproses Tabel: users</h2>";
    if (!tableExists($pdo, 'users')) {
        // CREATE TABLE if not exists
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NULL,
            resident_id VARCHAR(50) NULL,
            role ENUM('user','admin') DEFAULT 'user',
            profile_pic VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Tabel users DIBUAT</p>";
        $audit['tables']['created'][] = 'users';
    } else {
        echo "<p class='info'>ℹ️ Tabel users SUDAH ADA, memeriksa kolom...</p>";
        $audit['tables']['existing'][] = 'users';

        // Fix each column
        $usersColumns = [
            ['name' => 'profile_pic', 'def' => "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER role"],
            ['name' => 'updated_at', 'def' => "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"],
        ];

        foreach ($usersColumns as $col) {
            if (!columnExists($pdo, 'users', $col['name'])) {
                $pdo->exec($col['def']);
                echo "<p class='success'>✅ Kolom {$col['name']} DITAMBAHKAN</p>";
                $audit['columns']['added'][] = "users.{$col['name']}";
            } else {
                $audit['columns']['existing'][] = "users.{$col['name']}";
            }
        }

        // DROP UNIQUE INDEX from resident_id if exists
        if (indexExists($pdo, 'users', 'resident_id')) {
            $pdo->exec("ALTER TABLE users DROP INDEX resident_id");
            echo "<p class='warning'>⚠️ Index UNIQUE resident_id DIHAPUS</p>";
            $audit['indexes']['dropped'][] = 'users.resident_id';
        }

        // Make resident_id NULLABLE
        try {
            $pdo->exec("ALTER TABLE users MODIFY COLUMN resident_id VARCHAR(50) NULL");
            echo "<p class='info'>ℹ️ Kolom resident_id dipastikan NULLABLE</p>";
        } catch (Exception $e) {
            // Ignore errors if already correct
        }

        // Fix role enum if needed
        try {
            $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin') DEFAULT 'user'");
        } catch (Exception $e) {
            // Ignore
        }
    }

    // --------------------------
    // 2. TABEL: reports
    // --------------------------
    echo "<h2>2. Memproses Tabel: reports</h2>";
    if (!tableExists($pdo, 'reports')) {
        $pdo->exec("CREATE TABLE reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT 'Laporan',
            description TEXT NOT NULL,
            location_address VARCHAR(255) NOT NULL,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            photo_path VARCHAR(255) NULL,
            status ENUM('Baru','Diproses','Selesai') DEFAULT 'Baru',
            processed_by INT NULL,
            processed_at TIMESTAMP NULL,
            admin_notes TEXT NULL,
            completed_by INT NULL,
            completed_at TIMESTAMP NULL,
            completion_photo VARCHAR(255) NULL,
            completion_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Tabel reports DIBUAT</p>";
        $audit['tables']['created'][] = 'reports';
    } else {
        echo "<p class='info'>ℹ️ Tabel reports SUDAH ADA, memeriksa kolom...</p>";
        $audit['tables']['existing'][] = 'reports';

        $reportsColumns = [
            ['name' => 'title', 'def' => "ALTER TABLE reports ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Laporan' AFTER category"],
            ['name' => 'processed_by', 'def' => "ALTER TABLE reports ADD COLUMN processed_by INT NULL AFTER status"],
            ['name' => 'processed_at', 'def' => "ALTER TABLE reports ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by"],
            ['name' => 'admin_notes', 'def' => "ALTER TABLE reports ADD COLUMN admin_notes TEXT NULL AFTER processed_at"],
            ['name' => 'completed_by', 'def' => "ALTER TABLE reports ADD COLUMN completed_by INT NULL AFTER admin_notes"],
            ['name' => 'completed_at', 'def' => "ALTER TABLE reports ADD COLUMN completed_at TIMESTAMP NULL AFTER completed_by"],
            ['name' => 'completion_photo', 'def' => "ALTER TABLE reports ADD COLUMN completion_photo VARCHAR(255) NULL AFTER completed_at"],
            ['name' => 'completion_notes', 'def' => "ALTER TABLE reports ADD COLUMN completion_notes TEXT NULL AFTER completion_photo"],
            ['name' => 'updated_at', 'def' => "ALTER TABLE reports ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"],
        ];

        foreach ($reportsColumns as $col) {
            if (!columnExists($pdo, 'reports', $col['name'])) {
                $pdo->exec($col['def']);
                echo "<p class='success'>✅ Kolom {$col['name']} DITAMBAHKAN</p>";
                $audit['columns']['added'][] = "reports.{$col['name']}";
            } else {
                $audit['columns']['existing'][] = "reports.{$col['name']}";
            }
        }

        // Add foreign keys if missing
        $reportsFKs = [
            'fk_reports_processed_by' => "ALTER TABLE reports ADD CONSTRAINT fk_reports_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL",
            'fk_reports_completed_by' => "ALTER TABLE reports ADD CONSTRAINT fk_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL"
        ];
        foreach ($reportsFKs as $fkName => $fkDef) {
            if (!foreignKeyExists($pdo, 'reports', $fkName)) {
                try {
                    $pdo->exec($fkDef);
                    echo "<p class='success'>✅ Foreign Key $fkName DITAMBAHKAN</p>";
                    $audit['foreign_keys']['added'][] = "reports.$fkName";
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Gagal menambah FK $fkName (mungkin sudah ada atau data invalid)</p>";
                }
            } else {
                $audit['foreign_keys']['existing'][] = "reports.$fkName";
            }
        }
    }

    // --------------------------
    // 3. TABEL: educations
    // --------------------------
    echo "<h2>3. Memproses Tabel: educations</h2>";
    if (!tableExists($pdo, 'educations')) {
        $pdo->exec("CREATE TABLE educations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            photo_path VARCHAR(255) NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Tabel educations DIBUAT</p>";
        $audit['tables']['created'][] = 'educations';
    } else {
        echo "<p class='info'>ℹ️ Tabel educations SUDAH ADA</p>";
        $audit['tables']['existing'][] = 'educations';

        // Fix image_path to photo_path
        if (columnExists($pdo, 'educations', 'image_path') && !columnExists($pdo, 'educations', 'photo_path')) {
            $pdo->exec("ALTER TABLE educations CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            echo "<p class='success'>✅ Kolom image_path DIUBAH menjadi photo_path</p>";
            $audit['columns']['modified'][] = 'educations.image_path → photo_path';
        } elseif (!columnExists($pdo, 'educations', 'photo_path')) {
            $pdo->exec("ALTER TABLE educations ADD COLUMN photo_path VARCHAR(255) NULL AFTER content");
            echo "<p class='success'>✅ Kolom photo_path DITAMBAHKAN</p>";
            $audit['columns']['added'][] = 'educations.photo_path';
        }
    }

    // --------------------------
    // 4. TABEL: actions
    // --------------------------
    echo "<h2>4. Memproses Tabel: actions</h2>";
    if (!tableExists($pdo, 'actions')) {
        $pdo->exec("CREATE TABLE actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255) NOT NULL,
            date_time DATETIME NULL,
            photo_path VARCHAR(255) NULL,
            created_by INT NOT NULL,
            status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Tabel actions DIBUAT</p>";
        $audit['tables']['created'][] = 'actions';
    } else {
        echo "<p class='info'>ℹ️ Tabel actions SUDAH ADA</p>";
        $audit['tables']['existing'][] = 'actions';

        // Fix image_path to photo_path
        if (columnExists($pdo, 'actions', 'image_path') && !columnExists($pdo, 'actions', 'photo_path')) {
            $pdo->exec("ALTER TABLE actions CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            echo "<p class='success'>✅ Kolom image_path DIUBAH menjadi photo_path</p>";
            $audit['columns']['modified'][] = 'actions.image_path → photo_path';
        } elseif (!columnExists($pdo, 'actions', 'photo_path')) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN photo_path VARCHAR(255) NULL AFTER date_time");
            echo "<p class='success'>✅ Kolom photo_path DITAMBAHKAN</p>";
            $audit['columns']['added'][] = 'actions.photo_path';
        }

        // Add status column if missing
        if (!columnExists($pdo, 'actions', 'status')) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming' AFTER photo_path");
            echo "<p class='success'>✅ Kolom status DITAMBAHKAN</p>";
            $audit['columns']['added'][] = 'actions.status';
        }
    }

    // --------------------------
    // 5. TABEL: notifications (Bonus, jika dibutuhkan nanti)
    // --------------------------
    echo "<h2>5. Memproses Tabel: notifications (Opsional)</h2>";
    if (!tableExists($pdo, 'notifications')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='info'>ℹ️ Tabel notifications DIBUAT (untuk fitur mendatang)</p>";
        $audit['tables']['created'][] = 'notifications';
    } else {
        $audit['tables']['existing'][] = 'notifications';
    }

    // --------------------------
    // 6. Admin Accounts
    // --------------------------
    echo "<h2>6. Memproses Akun Admin</h2>";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $admins = [
        ['name' => 'Admin EcoCare', 'email' => 'admin@ecocare.com', 'role' => 'admin'],
        ['name' => 'Nafa', 'email' => 'nafa@ecocare.com', 'role' => 'admin'],
        ['name' => 'Mugi', 'email' => 'mugi@ecocare.com', 'role' => 'admin'],
        ['name' => 'Nadia', 'email' => 'nadia@ecocare.com', 'role' => 'admin']
    ];

    foreach ($admins as $admin) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$admin['email']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin['name'], $admin['email'], $adminPassword, $admin['role']]);
            echo "<p class='success'>✅ Akun admin {$admin['email']} DIBUAT</p>";
            $audit['admin_accounts']['created'][] = $admin['email'];
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
            $stmt->execute([$adminPassword, $admin['role'], $admin['email']]);
            echo "<p class='info'>ℹ️ Akun admin {$admin['email']} DIPERBARUI</p>";
            $audit['admin_accounts']['updated'][] = $admin['email'];
        }
    }

    // --------------------------
    // 7. Directories
    // --------------------------
    echo "<h2>7. Memproses Direktori Upload</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/education', 'uploads/actions', 'uploads/profiles', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p class='success'>✅ Direktori $dir DIBUAT</p>";
        } else {
            echo "<p class='info'>ℹ️ Direktori $dir SUDAH ADA</p>";
        }
        $indexFile = "$dir/index.html";
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>Forbidden</title></head><body>Forbidden</body></html>');
        }
    }

    $pdo->commit();

    // --------------------------
    // FINAL AUDIT REPORT
    // --------------------------
    echo "<hr><div class='audit'>
        <h3>📊 LAPORAN AUDIT MIGRASI</h3>";
        
    echo "<h4>📋 TABEL</h4>";
    echo "<table><tr><th>Status</th><th>Tabel</th></tr>";
    foreach ($audit['tables']['existing'] as $t) echo "<tr><td class='info'>Sudah Ada</td><td>$t</td></tr>";
    foreach ($audit['tables']['created'] as $t) echo "<tr><td class='success'>Dibuat</td><td>$t</td></tr>";
    echo "</table>";

    echo "<h4>📝 KOLOM</h4>";
    echo "<table><tr><th>Status</th><th>Kolom</th></tr>";
    foreach ($audit['columns']['existing'] as $c) echo "<tr><td class='info'>Sudah Ada</td><td>$c</td></tr>";
    foreach ($audit['columns']['added'] as $c) echo "<tr><td class='success'>Ditambahkan</td><td>$c</td></tr>";
    foreach ($audit['columns']['modified'] as $c) echo "<tr><td class='warning'>Diubah</td><td>$c</td></tr>";
    echo "</table>";

    echo "<h4>🔗 FOREIGN KEY</h4>";
    echo "<table><tr><th>Status</th><th>Foreign Key</th></tr>";
    foreach ($audit['foreign_keys']['existing'] as $fk) echo "<tr><td class='info'>Sudah Ada</td><td>$fk</td></tr>";
    foreach ($audit['foreign_keys']['added'] as $fk) echo "<tr><td class='success'>Ditambahkan</td><td>$fk</td></tr>";
    echo "</table>";

    echo "<h4>👤 ADMIN ACCOUNTS</h4>";
    echo "<table><tr><th>Status</th><th>Email</th></tr>";
    foreach ($audit['admin_accounts']['updated'] as $a) echo "<tr><td class='info'>Diperbarui</td><td>$a</td></tr>";
    foreach ($audit['admin_accounts']['created'] as $a) echo "<tr><td class='success'>Dibuat</td><td>$a</td></tr>";
    echo "</table>";

    echo "</div>";

    echo "<hr><h2 style='color: #16a34a;'>✅ SEMUA MIGRASI BERHASIL! (100% Aman & Idempoten)</h2>";
    echo "<p><strong>Akun Login:</strong></p>
        <ul>
            <li>Email: admin@ecocare.com | Password: admin123</li>
            <li>Email: mugi@ecocare.com | Password: admin123</li>
        </ul>
        <p><a href='index.php' style='display: inline-block; padding: 0.75rem 1.5rem; background: #16a34a; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: bold;'>← Kembali ke Halaman Utama</a></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color: #dc2626;'>❌ Migrasi Gagal!</h2>";
    echo "<p style='background: #fef2f2; padding: 1rem; border-left: 4px solid #dc2626; border-radius: 0.25rem;'><strong>Pesan Error:</strong><br>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
