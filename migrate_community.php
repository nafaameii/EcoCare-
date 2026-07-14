<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

try {
    // 1. Create communities table
    $sql = "CREATE TABLE IF NOT EXISTS communities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        cover_image VARCHAR(255) NULL,
        current_action VARCHAR(255) NOT NULL,
        action_status ENUM('Berlangsung', 'Selesai', 'Akan Datang') DEFAULT 'Berlangsung',
        volunteer_target INT NOT NULL DEFAULT 100,
        progress_percentage INT NOT NULL DEFAULT 0,
        location VARCHAR(255) NOT NULL,
        start_date DATETIME NOT NULL,
        end_date DATETIME NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'text' => 'Tabel communities berhasil dibuat!'];

    // 2. Create community_members table
    $sql = "CREATE TABLE IF NOT EXISTS community_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        community_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        role ENUM('Admin', 'Anggota') DEFAULT 'Anggota',
        FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_member (community_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'text' => 'Tabel community_members berhasil dibuat!'];

    // 3. Create community_discussions table
    $sql = "CREATE TABLE IF NOT EXISTS community_discussions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        community_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'text' => 'Tabel community_discussions berhasil dibuat!'];

    // 4. Create community_actions table
    $sql = "CREATE TABLE IF NOT EXISTS community_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        community_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        action_date DATETIME NOT NULL,
        location VARCHAR(255) NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'text' => 'Tabel community_actions berhasil dibuat!'];

    // 5. Insert sample community
    $check = $pdo->query("SELECT COUNT(*) FROM communities")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("INSERT INTO communities (title, description, cover_image, current_action, action_status, volunteer_target, progress_percentage, location, start_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            "Purwokerto Green Spirit 2026",
            "Komunitas peduli lingkungan untuk menjadikan Purwokerto lebih hijau dan bersih. Bersama kita bergerak!",
            null,
            "Tanam Pohon di Taman Kota",
            "Berlangsung",
            200,
            65,
            "Taman Kota Purwokerto",
            date('Y-m-d H:i:s', strtotime('-1 month')),
            1
        ]);
        $community_id = $pdo->lastInsertId();
        $messages[] = ['type' => 'success', 'text' => 'Komunitas contoh berhasil ditambahkan!'];

        // Add sample members
        $users = $pdo->query("SELECT id FROM users LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $user_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO community_members (community_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$community_id, $user_id, $user_id == 1 ? 'Admin' : 'Anggota']);
        }
        $messages[] = ['type' => 'success', 'text' => 'Anggota komunitas contoh berhasil ditambahkan!'];

        // Add sample discussions
        $stmt = $pdo->prepare("INSERT INTO community_discussions (community_id, user_id, message) VALUES (?, ?, ?)");
        if (!empty($users)) {
            $stmt->execute([$community_id, $users[0], "Halo semua! Yuk kita rapat untuk persiapan tanam pohon besok!"]);
            if (isset($users[1])) $stmt->execute([$community_id, $users[1], "Siap! Saya akan bawa 10 bibit pohon trembesi."]);
        }

        // Add sample actions
        $stmt = $pdo->prepare("INSERT INTO community_actions (community_id, title, description, action_date, location, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $community_id,
            "Kebersihan Sungai",
            "Aksi membersihkan sungai bersama warga setempat",
            date('Y-m-d H:i:s', strtotime('-2 weeks')),
            "Sungai Purwokerto",
            $users[0] ?? 1
        ]);
        $messages[] = ['type' => 'success', 'text' => 'Data diskusi dan aksi contoh berhasil ditambahkan!'];
    }

    $messages[] = ['type' => 'complete', 'text' => 'Semua migrasi berhasil! Silakan buka halaman Komunitas Saya.'];

} catch(PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Komunitas - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Poppins', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2E7D32',
                        secondary: '#43A047',
                        lightgreen: '#E8F5E9'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-lightgreen min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-10">
            <div class="w-24 h-24 bg-gradient-to-br from-primary to-secondary rounded-3xl flex items-center justify-center text-white text-4xl mx-auto shadow-2xl mb-6">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Migrasi Komunitas EcoCare+</h1>
            <p class="text-lg text-gray-600">Membuat struktur database untuk modul komunitas</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8">
            <div class="space-y-4">
                <?php foreach ($messages as $msg): ?>
                    <div class="flex items-center gap-3 px-6 py-4 rounded-2xl
                        <?php
                        if ($msg['type'] == 'success') echo 'bg-green-50 border border-green-200 text-green-700';
                        elseif ($msg['type'] == 'complete') echo 'bg-gradient-to-r from-primary to-secondary text-white shadow-lg';
                        else echo 'bg-red-50 border border-red-200 text-red-700';
                        ?>">
                        <?php if ($msg['type'] == 'success'): ?>
                            <i class="fas fa-check-circle text-2xl"></i>
                        <?php elseif ($msg['type'] == 'complete'): ?>
                            <i class="fas fa-trophy text-2xl"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle text-2xl"></i>
                        <?php endif; ?>
                        <span class="font-semibold"><?= $msg['text'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 pt-8 border-t border-gray-100 flex flex-wrap gap-4 justify-center">
                <a href="index.php" class="px-8 py-4 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-home mr-2"></i> Ke Beranda
                </a>
                <a href="dashboard_pengguna.php" class="px-8 py-4 bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard Pengguna
                </a>
            </div>
        </div>
    </div>
</body>
</html>
