<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Database Penuh - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-ecocare-cream min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-4xl mx-auto shadow-lg">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-ecocare-dark mt-6 mb-2">Migrasi Database Penuh</h1>
            <p class="text-lg text-gray-600">Membuat semua tabel untuk proyek EcoCare+</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 space-y-6">
            <?php
            $success_count = 0;
            $errors = [];

            try {
                // 1. Tabel Users
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2'>1. Membuat Tabel <code>users</code></h3>";
                $sql_users = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NULL,
                    resident_id VARCHAR(50) NULL UNIQUE,
                    role ENUM('user','admin') DEFAULT 'user',
                    profile_pic VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sql_users);
                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-check-circle'></i> Tabel users berhasil dibuat!</div>";
                $success_count++;

                // 2. Tabel Reports
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2 mt-6'>2. Membuat Tabel <code>reports</code></h3>";
                $sql_reports = "CREATE TABLE IF NOT EXISTS reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    location_address VARCHAR(255) NOT NULL,
                    latitude DECIMAL(10, 8) NULL,
                    longitude DECIMAL(11, 8) NULL,
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sql_reports);
                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-check-circle'></i> Tabel reports berhasil dibuat!</div>";
                $success_count++;

                // 3. Tabel Educations
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2 mt-6'>3. Membuat Tabel <code>educations</code></h3>";
                $sql_educations = "CREATE TABLE IF NOT EXISTS educations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    photo_path VARCHAR(255) NULL,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sql_educations);
                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-check-circle'></i> Tabel educations berhasil dibuat!</div>";
                $success_count++;

                // 4. Tabel Actions
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2 mt-6'>4. Membuat Tabel <code>actions</code></h3>";
                $sql_actions = "CREATE TABLE IF NOT EXISTS actions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    location VARCHAR(255) NOT NULL,
                    date_time DATETIME NOT NULL,
                    photo_path VARCHAR(255) NULL,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sql_actions);
                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-check-circle'></i> Tabel actions berhasil dibuat!</div>";
                $success_count++;

                // 5. Membuat Direktori Uploads
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2 mt-6'>5. Membuat Direktori Uploads</h3>";
                $dirs = ['uploads', 'uploads/reports', 'uploads/profiles', 'uploads/educations', 'uploads/actions', 'uploads/completion'];
                foreach ($dirs as $dir) {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-folder'></i> Direktori <code>$dir</code> berhasil dibuat!</div>";
                    } else {
                        echo "<div class='bg-gray-50 border border-gray-200 text-gray-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-folder-open'></i> Direktori <code>$dir</code> sudah ada!</div>";
                    }
                }
                $success_count++;

                // 6. Membuat Akun Admin Default
                echo "<h3 class='text-xl font-bold text-ecocare-dark border-b pb-2 mt-6'>6. Membuat Akun Admin</h3>";
                $admins = [
                    ['name' => 'Nafa', 'email' => 'nafa@ecocare.com', 'password' => 'admin123'],
                    ['name' => 'Mugi', 'email' => 'mugi@ecocare.com', 'password' => 'admin123'],
                    ['name' => 'Nadia', 'email' => 'nadia@ecocare.com', 'password' => 'admin123']
                ];
                foreach ($admins as $admin) {
                    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check->execute([$admin['email']]);
                    if (!$check->fetch()) {
                        $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                        $stmt->execute([$admin['name'], $admin['email'], $hashed_password]);
                        echo "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-user-shield'></i> Akun admin <strong>{$admin['name']}</strong> berhasil dibuat!</div>";
                    } else {
                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-xl flex items-center gap-2'><i class='fas fa-user-check'></i> Akun admin <strong>{$admin['name']}</strong> sudah ada!</div>";
                    }
                }
                $success_count++;

                echo "<div class='mt-8 pt-6 border-t border-gray-200 text-center'>
                    <div class='w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-white text-4xl mx-auto mb-4 shadow-lg'>
                        <i class='fas fa-check-double'></i>
                    </div>
                    <h3 class='text-2xl font-bold text-ecocare-dark mb-2'>Migrasi Berhasil!</h3>
                    <p class='text-gray-600 mb-6'>Total: $success_count langkah berhasil!</p>
                    <div class='flex flex-wrap gap-4 justify-center'>
                        <a href='index.php' class='px-8 py-3 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition'>
                            <i class='fas fa-home mr-2'></i> Ke Beranda
                        </a>
                        <a href='admin_login.php' class='px-8 py-3 bg-white text-ecocare-dark font-bold rounded-xl border-2 border-ecocare-primary hover:bg-ecocare-primary hover:text-white transition'>
                            <i class='fas fa-sign-in-alt mr-2'></i> Login Admin
                        </a>
                    </div>
                </div>";

            } catch(PDOException $e) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
