<?php
/**
 * Migrasi Master Database EcoCare+ (v2.0)
 * Menjalankan semua perbaikan sekaligus, aman dijalankan berkali-kali!
 */

require 'config.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migrasi Database EcoCare+ v2.0</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gradient-to-br from-emerald-50 to-green-50 min-h-screen py-12'>
    <div class='max-w-5xl mx-auto bg-white rounded-3xl shadow-2xl p-8 border border-emerald-100'>";

try {
    $pdo->beginTransaction();
    $changes = [];

    // ==================== TABLE: users ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-users text-emerald-600'></i> 1. Memperbaiki Tabel users
          </h2>";

    // Add missing columns
    $users_columns = [
        'username' => "ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER name",
        'address' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role"
    ];
    foreach ($users_columns as $col => $sql) {
        if (!columnExists($pdo, 'users', $col)) {
            $pdo->exec($sql);
            $changes[] = "Menambahkan kolom users.$col";
            echo "<p class='text-emerald-600'><i class='fas fa-plus-circle mr-2'></i> Kolom <span class='font-bold'>$col</span> ditambahkan!</p>";
        } else {
            echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Kolom $col sudah ada</p>";
        }
    }

    // Remove unique constraint from resident_id if exists
    if (indexExists($pdo, 'users', 'resident_id')) {
        $pdo->exec("ALTER TABLE users DROP INDEX resident_id");
        $changes[] = "Menghapus UNIQUE constraint dari resident_id";
        echo "<p class='text-yellow-600'><i class='fas fa-unlock mr-2'></i> Constraint UNIQUE resident_id dihapus!</p>";
    }

    // ==================== TABLE: reports ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-file-alt text-emerald-600'></i> 2. Memperbaiki Tabel reports
          </h2>";

    $reports_columns = [
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
    foreach ($reports_columns as $col => $sql) {
        if (!columnExists($pdo, 'reports', $col)) {
            $pdo->exec($sql);
            $changes[] = "Menambahkan kolom reports.$col";
            echo "<p class='text-emerald-600'><i class='fas fa-plus-circle mr-2'></i> Kolom <span class='font-bold'>$col</span> ditambahkan!</p>";
        } else {
            echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Kolom $col sudah ada</p>";
        }
    }

    // ==================== TABLE: educations ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-book text-emerald-600'></i> 3. Memperbaiki Tabel educations
          </h2>";
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
        echo "<p class='text-emerald-600'><i class='fas fa-check-circle mr-2'></i> Tabel educations berhasil dibuat!</p>";
    } else {
        // Fix column name
        if (columnExists($pdo, 'educations', 'image_path') && !columnExists($pdo, 'educations', 'photo_path')) {
            $pdo->exec("ALTER TABLE educations CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            $changes[] = "Mengubah image_path menjadi photo_path di educations";
            echo "<p class='text-yellow-600'><i class='fas fa-edit mr-2'></i> Kolom image_path diubah menjadi photo_path!</p>";
        }
        echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Tabel educations sudah ada</p>";
    }

    // ==================== TABLE: actions ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-hands-helping text-emerald-600'></i> 4. Memperbaiki Tabel actions
          </h2>";
    if (!tableExists($pdo, 'actions')) {
        $pdo->exec("CREATE TABLE actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255) NOT NULL,
            date_time DATETIME NULL,
            photo_path VARCHAR(255) NULL,
            created_by INT NOT NULL,
            status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $changes[] = "Tabel actions dibuat";
        echo "<p class='text-emerald-600'><i class='fas fa-check-circle mr-2'></i> Tabel actions berhasil dibuat!</p>";
    } else {
        // Fix column names
        if (columnExists($pdo, 'actions', 'image_path') && !columnExists($pdo, 'actions', 'photo_path')) {
            $pdo->exec("ALTER TABLE actions CHANGE COLUMN image_path photo_path VARCHAR(255) NULL");
            $changes[] = "Mengubah image_path menjadi photo_path di actions";
            echo "<p class='text-yellow-600'><i class='fas fa-edit mr-2'></i> Kolom image_path diubah menjadi photo_path!</p>";
        }
        if (!columnExists($pdo, 'actions', 'status')) {
            $pdo->exec("ALTER TABLE actions ADD COLUMN status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming' AFTER photo_path");
            $changes[] = "Menambahkan kolom status di actions";
            echo "<p class='text-emerald-600'><i class='fas fa-plus-circle mr-2'></i> Kolom status ditambahkan!</p>";
        }
        echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Tabel actions sudah ada</p>";
    }

    // ==================== TABLE: activity_log ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-history text-emerald-600'></i> 5. Membuat Tabel activity_log
          </h2>";
    if (!tableExists($pdo, 'activity_log')) {
        $pdo->exec("CREATE TABLE activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            description TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $changes[] = "Tabel activity_log dibuat";
        echo "<p class='text-emerald-600'><i class='fas fa-check-circle mr-2'></i> Tabel activity_log berhasil dibuat!</p>";
    } else {
        echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Tabel activity_log sudah ada</p>";
    }

    // ==================== TABLE: notifications ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-bell text-emerald-600'></i> 6. Membuat Tabel notifications
          </h2>";
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
        echo "<p class='text-emerald-600'><i class='fas fa-check-circle mr-2'></i> Tabel notifications berhasil dibuat!</p>";
    } else {
        echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Tabel notifications sudah ada</p>";
    }

    // ==================== DIRECTORIES ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-folder text-emerald-600'></i> 7. Memastikan Direktori Upload
          </h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/education', 'uploads/actions', 'uploads/profiles', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            $changes[] = "Direktori $dir dibuat";
            echo "<p class='text-emerald-600'><i class='fas fa-folder-plus mr-2'></i> Direktori <span class='font-mono'>$dir</span> dibuat!</p>";
        } else {
            echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Direktori $dir sudah ada</p>";
        }
        // Add index.html for security
        $index_file = "$dir/index.html";
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1></body></html>');
        }
    }

    // ==================== ADMIN ACCOUNTS ====================
    echo "<h2 class='text-2xl font-bold text-emerald-800 mt-10 mb-4 pb-3 border-b-2 border-emerald-100 flex items-center gap-2'>
            <i class='fas fa-user-shield text-emerald-600'></i> 8. Memastikan Akun Admin
          </h2>";
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
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
            $stmt->execute([$admin['name'], $admin['email'], $admin_password, $admin['role']]);
            $changes[] = "Akun admin {$admin['email']} dibuat";
            echo "<p class='text-emerald-600'><i class='fas fa-user-plus mr-2'></i> Akun <span class='font-bold'>{$admin['email']}</span> dibuat!</p>";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ?, status = 'active' WHERE email = ?");
            $stmt->execute([$admin_password, $admin['role'], $admin['email']]);
            echo "<p class='text-gray-400 text-sm'><i class='fas fa-check mr-2'></i> Akun {$admin['email']} diperbarui</p>";
        }
    }

    $pdo->commit();

    // ==================== FINAL REPORT ====================
    echo "<div class='mt-10 bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-200 rounded-2xl p-8'>
            <h3 class='text-3xl font-extrabold text-emerald-800 mb-6 flex items-center gap-3'>
                <i class='fas fa-trophy text-emerald-600'></i> 🎉 Migrasi Berhasil!
            </h3>
            <p class='text-lg text-gray-700 mb-6'>Total <span class='font-bold text-emerald-700 text-2xl'>" . count($changes) . "</span> perubahan berhasil dilakukan!</p>
            <div class='bg-white rounded-xl p-6 border border-emerald-100 shadow-inner'>
                <h4 class='font-bold text-lg text-emerald-800 mb-3'><i class='fas fa-list-check mr-2'></i> Daftar Perubahan:</h4>
                <ul class='space-y-2'>";
    foreach ($changes as $change) {
        echo "<li class='text-gray-700 flex items-center gap-2'><i class='fas fa-circle-check text-emerald-500'></i>$change</li>";
    }
    echo "      </ul>
            </div>
            <div class='mt-8 flex flex-wrap gap-4 justify-center'>
                <a href='index.php' class='bg-gradient-to-r from-emerald-600 to-green-700 text-white px-10 py-4 rounded-2xl font-bold text-lg hover:shadow-2xl transition'>
                    <i class='fas fa-home mr-2'></i> Ke Beranda
                </a>
                <a href='admin_dashboard.php' class='bg-gradient-to-r from-sky-600 to-blue-700 text-white px-10 py-4 rounded-2xl font-bold text-lg hover:shadow-2xl transition'>
                    <i class='fas fa-cog mr-2'></i> Dashboard Admin
                </a>
            </div>
            <div class='mt-8 bg-yellow-50 border border-yellow-200 rounded-xl p-6'>
                <h4 class='font-bold text-yellow-800 mb-2 flex items-center gap-2'><i class='fas fa-info-circle'></i> Akun Login:</h4>
                <p class='text-yellow-700'>Email: <span class='font-mono font-bold'>admin@ecocare.com</span> atau <span class='font-mono font-bold'>mugi@ecocare.com</span></p>
                <p class='text-yellow-700'>Password: <span class='font-mono font-bold'>admin123</span></p>
            </div>
        </div>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='mt-10 bg-red-50 border-2 border-red-300 rounded-2xl p-8'>
            <h3 class='text-2xl font-bold text-red-800 mb-4 flex items-center gap-2'>
                <i class='fas fa-circle-xmark'></i> ❌ Migrasi Gagal!
            </h3>
            <p class='text-red-700 text-lg mb-4'><i class='fas fa-circle-exclamation mr-2'></i>Error:</p>
            <div class='bg-white p-6 rounded-xl border border-red-200 font-mono text-sm text-red-800'>" . htmlspecialchars($e->getMessage()) . "</div>
            <p class='text-red-700 mt-4 text-sm'><i class='fas fa-code-branch mr-2'></i>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>
        </div>";
}

echo "</div></body></html>";

// ==================== HELPER FUNCTIONS ====================
function tableExists($pdo, $table_name) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
    return $stmt->rowCount() > 0;
}

function columnExists($pdo, $table_name, $column_name) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");
    return $stmt->rowCount() > 0;
}

function indexExists($pdo, $table_name, $index_name) {
    $stmt = $pdo->query("SHOW INDEX FROM `$table_name` WHERE Key_name = '$index_name'");
    return $stmt->rowCount() > 0;
}
