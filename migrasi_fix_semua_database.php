<?php
/**
 * Migrasi_fix_semua_database.php
 * 
 * File migrasi untuk memperbaiki seluruh masalah database:
 * - Membuat tabel yang hilang
 * - Menambahkan kolom yang hilang
 * - Memperbaiki constraint
 * - Menyesuaikan nama kolom
 */

require 'config.php';

echo "<h2>Mulai Migrasi Database...</h2>";

try {
    $pdo->beginTransaction();

    // 1. Pastikan tabel users
    echo "<h3>1. Memperbaiki tabel users</h3>";
    
    $check = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$check->fetch()) {
        // Periksa dan tambahkan kolom yang hilang
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('profile_pic', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER role");
            echo "<p>✅ Kolom profile_pic ditambahkan</p>";
        }
        
        if (!in_array('updated_at', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "<p>✅ Kolom updated_at ditambahkan</p>";
        }
        
        // Hapus constraint UNIQUE dari resident_id
        try {
            $pdo->exec("ALTER TABLE users DROP INDEX resident_id");
            echo "<p>✅ Constraint UNIQUE resident_id dihapus</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Constraint resident_id tidak ditemukan atau sudah dihapus</p>";
        }
        
        // Ubah resident_id menjadi NULLABLE
        $pdo->exec("ALTER TABLE users MODIFY COLUMN resident_id VARCHAR(50) NULL");
        echo "<p>✅ Kolom resident_id diubah menjadi NULLABLE</p>";
        
        // Pastikan role ENUM sesuai (user/admin)
        try {
            $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin') DEFAULT 'user'");
            echo "<p>✅ Kolom role diperbaiki</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Kolom role sudah sesuai</p>";
        }
    } else {
        // Buat tabel users jika tidak ada
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
        echo "<p>✅ Tabel users dibuat</p>";
    }

    // 2. Perbaiki tabel reports
    echo "<h3>2. Memperbaiki tabel reports</h3>";
    
    $check = $pdo->query("SHOW TABLES LIKE 'reports'");
    if (!$check->fetch()) {
        $columns = $pdo->query("SHOW COLUMNS FROM reports")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('title', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Laporan' AFTER category");
            echo "<p>✅ Kolom title ditambahkan</p>";
        }
        
        if (!in_array('processed_by', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN processed_by INT NULL AFTER status");
            echo "<p>✅ Kolom processed_by ditambahkan</p>";
        }
        
        if (!in_array('processed_at', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by");
            echo "<p>✅ Kolom processed_at ditambahkan</p>";
        }
        
        if (!in_array('admin_notes', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN admin_notes TEXT NULL AFTER processed_at");
            echo "<p>✅ Kolom admin_notes ditambahkan</p>";
        }
        
        if (!in_array('completed_by', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN completed_by INT NULL AFTER admin_notes");
            echo "<p>✅ Kolom completed_by ditambahkan</p>";
        }
        
        if (!in_array('completed_at', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN completed_at TIMESTAMP NULL AFTER completed_by");
            echo "<p>✅ Kolom completed_at ditambahkan</p>";
        }
        
        if (!in_array('completion_photo', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN completion_photo VARCHAR(255) NULL AFTER completed_at");
            echo "<p>✅ Kolom completion_photo ditambahkan</p>";
        }
        
        if (!in_array('completion_notes', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN completion_notes TEXT NULL AFTER completion_photo");
            echo "<p>✅ Kolom completion_notes ditambahkan</p>";
        }
        
        if (!in_array('updated_at', $columns)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "<p>✅ Kolom updated_at ditambahkan</p>";
        }
        
        // Tambahkan foreign key
        try {
            $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_reports_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "<p>✅ FK processed_by ditambahkan</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ FK processed_by sudah ada</p>";
        }
        
        try {
            $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "<p>✅ FK completed_by ditambahkan</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ FK completed_by sudah ada</p>";
        }
    } else {
        // Buat tabel reports jika tidak ada
        $pdo->exec("CREATE TABLE reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL,
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
        echo "<p>✅ Tabel reports dibuat</p>";
    }

    // 3. Buat tabel educations
    echo "<h3>3. Memperbaiki tabel educations</h3>";
    
    $check = $pdo->query("SHOW TABLES LIKE 'educations'");
    if (!$check->fetch()) {
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
        echo "<p>✅ Tabel educations dibuat</p>";
    } else {
        $columns = $pdo->query("SHOW COLUMNS FROM educations")->fetchAll(PDO::FETCH_COLUMN);
        // Pastikan nama kolom foto adalah photo_path (tidak image_path)
        if (in_array('image_path', $columns) && !in_array('photo_path', $columns)) {
            $pdo->exec("ALTER TABLE educations CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            echo "<p>✅ Kolom image_path diubah menjadi photo_path</p>";
        }
        if (!in_array('photo_path', $columns) && !in_array('image_path', $columns)) {
            $pdo->exec("ALTER TABLE educations ADD COLUMN photo_path VARCHAR(255) NULL AFTER content");
            echo "<p>✅ Kolom photo_path ditambahkan</p>";
        }
    }

    // 4. Buat tabel actions
    echo "<h3>4. Memperbaiki tabel actions</h3>";
    
    $check = $pdo->query("SHOW TABLES LIKE 'actions'");
    if (!$check->fetch()) {
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
        echo "<p>✅ Tabel actions dibuat</p>";
    } else {
        $columns = $pdo->query("SHOW COLUMNS FROM actions")->fetchAll(PDO::FETCH_COLUMN);
        // Pastikan nama kolom foto adalah photo_path (tidak image_path)
        if (in_array('image_path', $columns) && !in_array('photo_path', $columns)) {
            $pdo->exec("ALTER TABLE actions CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            echo "<p>✅ Kolom image_path diubah menjadi photo_path</p>";
        }
        if (!in_array('photo_path', $columns) && !in_array('image_path', $columns)) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN photo_path VARCHAR(255) NULL AFTER date_time");
            echo "<p>✅ Kolom photo_path ditambahkan</p>";
        }
        if (!in_array('status', $columns)) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming' AFTER photo_path");
            echo "<p>✅ Kolom status ditambahkan</p>";
        }
    }

    // 5. Membuat/Update akun admin
    echo "<h3>5. Memperbaiki akun admin</h3>";
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
            echo "<p>✅ Akun admin {$admin['email']} dibuat (password: admin123)</p>";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
            $stmt->execute([$adminPassword, $admin['role'], $admin['email']]);
            echo "<p>✅ Akun admin {$admin['email']} diperbarui</p>";
        }
    }

    // 6. Membuat direktori upload
    echo "<h3>6. Membuat direktori upload</h3>";
    $dirs = [
        'uploads',
        'uploads/reports',
        'uploads/education',
        'uploads/actions',
        'uploads/profiles',
        'uploads/completion'
    ];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p>✅ Direktori $dir dibuat</p>";
        } else {
            echo "<p>ℹ️ Direktori $dir sudah ada</p>";
        }
    }
    
    // Tambahkan file index.html kosong untuk keamanan
    foreach ($dirs as $dir) {
        $indexFile = $dir . '/index.html';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>Forbidden</title></head><body>Forbidden</body></html>');
        }
    }

    $pdo->commit();
    echo "<hr><h2 style='color: green;'>✅ SEMUA MIGRASI BERHASIL!</h2>";
    echo "<p>Database sekarang siap pakai. Silakan login dengan:</p>";
    echo "<ul>";
    echo "<li>Email: admin@ecocare.com</li>";
    echo "<li>Password: admin123</li>";
    echo "<li>Email: mugi@ecocare.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Kembali ke Halaman Utama</a></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color: red;'>❌ Migrasi Gagal!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
