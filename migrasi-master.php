<?php
/**
 * Migrasi Master Database EcoCare+
 * Menjalankan semua perbaikan sekaligus, aman dijalankan berkali-kali
 */
require 'config.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Migrasi Database EcoCare+</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 min-h-screen p-8'>
    <div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-lg p-8'>";

try {
    $pdo->beginTransaction();
    $changes = [];

    // --- 1. Perbaiki Tabel users ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4'>1. Memperbaiki Tabel users</h2>";
    
    // 1.1 Tambah kolom username
    if (!columnExists($pdo, 'users', 'username')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER name");
        $changes[] = "Tambah kolom username di users";
        echo "<p class='text-emerald-600'>✅ Kolom username ditambahkan</p>";
    } else {
        echo "<p class='text-gray-500'>ℹ️ Kolom username sudah ada</p>";
    }

    // 1.2 Tambah kolom address
    if (!columnExists($pdo, 'users', 'address')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone");
        $changes[] = "Tambah kolom address di users";
        echo "<p class='text-emerald-600'>✅ Kolom address ditambahkan</p>";
    } else {
        echo "<p class='text-gray-500'>ℹ️ Kolom address sudah ada</p>";
    }

    // 1.3 Tambah kolom status
    if (!columnExists($pdo, 'users', 'status')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role");
        $changes[] = "Tambah kolom status di users";
        echo "<p class='text-emerald-600'>✅ Kolom status ditambahkan</p>";
    } else {
        echo "<p class='text-gray-500'>ℹ️ Kolom status sudah ada</p>";
    }

    // --- 2. Perbaiki Tabel reports ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>2. Memperbaiki Tabel reports</h2>";
    $reportsColumns = [
        'title' => "ALTER TABLE reports ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Laporan' AFTER category",
        'processed_by' => "ALTER TABLE reports ADD COLUMN processed_by INT NULL AFTER status",
        'processed_at' => "ALTER TABLE reports ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by",
        'admin_notes' => "ALTER TABLE reports ADD COLUMN admin_notes TEXT NULL AFTER processed_at",
        'completed_by' => "ALTER TABLE reports ADD COLUMN completed_by INT NULL AFTER admin_notes",
        'completed_at' => "ALTER TABLE reports ADD COLUMN completed_at TIMESTAMP NULL AFTER completed_by",
        'completion_photo' => "ALTER TABLE reports ADD COLUMN completion_photo VARCHAR(255) NULL AFTER completed_at",
        'completion_notes' => "ALTER TABLE reports ADD COLUMN completion_notes TEXT NULL AFTER completion_photo",
        'updated_at' => "ALTER TABLE reports ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];
    foreach ($reportsColumns as $col => $sql) {
        if (!columnExists($pdo, 'reports', $col)) {
            $pdo->exec($sql);
            $changes[] = "Tambah kolom $col di reports";
            echo "<p class='text-emerald-600'>✅ Kolom $col ditambahkan</p>";
        } else {
            echo "<p class='text-gray-500'>ℹ️ Kolom $col sudah ada</p>";
        }
    }

    // --- 3. Buat Tabel educations jika tidak ada ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>3. Memperbaiki Tabel educations</h2>";
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
        $changes[] = "Tabel educations dibuat";
        echo "<p class='text-emerald-600'>✅ Tabel educations dibuat</p>";
    } else {
        // Fix kolom photo_path jika masih image_path
        if (columnExists($pdo, 'educations', 'image_path') && !columnExists($pdo, 'educations', 'photo_path')) {
            $pdo->exec("ALTER TABLE educations CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            $changes[] = "Ubah image_path jadi photo_path di educations";
            echo "<p class='text-emerald-600'>✅ Kolom image_path diubah jadi photo_path</p>";
        }
        echo "<p class='text-gray-500'>ℹ️ Tabel educations sudah ada</p>";
    }

    // --- 4. Buat Tabel actions jika tidak ada ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>4. Memperbaiki Tabel actions</h2>";
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
        $changes[] = "Tabel actions dibuat";
        echo "<p class='text-emerald-600'>✅ Tabel actions dibuat</p>";
    } else {
        // Fix kolom
        if (columnExists($pdo, 'actions', 'image_path') && !columnExists($pdo, 'actions', 'photo_path')) {
            $pdo->exec("ALTER TABLE actions CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            $changes[] = "Ubah image_path jadi photo_path di actions";
            echo "<p class='text-emerald-600'>✅ Kolom image_path diubah jadi photo_path</p>";
        }
        if (!columnExists($pdo, 'actions', 'status')) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming' AFTER photo_path");
            $changes[] = "Tambah kolom status di actions";
            echo "<p class='text-emerald-600'>✅ Kolom status ditambahkan</p>";
        }
        echo "<p class='text-gray-500'>ℹ️ Tabel actions sudah ada</p>";
    }

    // --- 5. Buat Tabel notifications ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>5. Membuat Tabel notifications (Opsional)</h2>";
    if (!tableExists($pdo, 'notifications')) {
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $changes[] = "Tabel notifications dibuat";
        echo "<p class='text-emerald-600'>✅ Tabel notifications dibuat</p>";
    } else {
        echo "<p class='text-gray-500'>ℹ️ Tabel notifications sudah ada</p>";
    }

    // --- 6. Pastikan direktori upload ada ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>6. Membuat Direktori Upload</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/education', 'uploads/actions', 'uploads/profiles', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            $changes[] = "Direktori $dir dibuat";
            echo "<p class='text-emerald-600'>✅ Direktori $dir dibuat</p>";
        } else {
            echo "<p class='text-gray-500'>ℹ️ Direktori $dir sudah ada</p>";
        }
        $indexFile = "$dir/index.html";
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>Forbidden</title></head><body>Forbidden</body></html>');
        }
    }

    // --- 7. Insert Admin Accounts ---
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 mt-8'>7. Memastikan Admin Accounts Tersedia</h2>";
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
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$admin['name'], $admin['email'], $adminPassword, $admin['role']]);
            $changes[] = "Akun admin {$admin['email']} dibuat";
            echo "<p class='text-emerald-600'>✅ Akun {$admin['email']} dibuat (password: admin123)</p>";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ?, status = 'active' WHERE email = ?");
            $stmt->execute([$adminPassword, $admin['role'], $admin['email']]);
            echo "<p class='text-gray-500'>ℹ️ Akun {$admin['email']} diperbarui</p>";
        }
    }

    $pdo->commit();

    echo "<div class='mt-8 p-6 bg-emerald-50 border border-emerald-200 rounded-xl'>
        <h3 class='text-xl font-bold text-emerald-800 mb-4'>🎉 Migrasi Berhasil!</h3>
        <p class='mb-4'>Total perubahan: " . count($changes) . "</p>
        <ul class='list-disc pl-6 space-y-1'>";
        foreach ($changes as $c) {
            echo "<li class='text-emerald-700'>$c</li>";
        }
        echo "</ul>
        <a href='index.php' class='mt-6 inline-block bg-emerald-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-emerald-700 transition'>
            Ke Halaman Utama
        </a>
    </div>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='mt-8 p-6 bg-red-50 border border-red-200 rounded-xl'>
        <h3 class='text-xl font-bold text-red-800 mb-4'>❌ Migrasi Gagal</h3>
        <p class='text-red-700'>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

echo "</div></body></html>";

// Helper Functions
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
?>