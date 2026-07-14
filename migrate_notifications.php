<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

try {
    // 1. Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        icon VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        type VARCHAR(50) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'text' => 'Tabel notifications berhasil dibuat!'];

    // 2. Insert sample notifications
    $users = $pdo->query("SELECT id FROM users LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($users)) {
        foreach ($users as $uid) {
            $sample_notifs = [
                ['icon' => 'fa-check-circle', 'title' => 'Laporan Terverifikasi', 'description' => 'Laporan kamu telah diverifikasi oleh admin', 'type' => 'report'],
                ['icon' => 'fa-sync-alt', 'title' => 'Status Laporan Diperbarui', 'description' => 'Status laporan berubah menjadi Diproses', 'type' => 'report_update'],
                ['icon' => 'fa-users', 'title' => 'Aksi Komunitas Baru', 'description' => 'Komunitas kamu mengadakan aksi baru!', 'type' => 'community'],
                ['icon' => 'fa-user-plus', 'title' => 'Relawan Baru Bergabung', 'description' => 'Seseorang bergabung dengan komunitas kamu', 'type' => 'volunteer'],
                ['icon' => 'fa-envelope', 'title' => 'Undangan Komunitas', 'description' => 'Kamu diundang untuk bergabung dengan komunitas!', 'type' => 'invitation']
            ];

            foreach ($sample_notifs as $notif) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO notifications (user_id, icon, title, description, type, is_read) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->execute([$uid, $notif['icon'], $notif['title'], $notif['description'], $notif['type']]);
            }
        }
        $messages[] = ['type' => 'success', 'text' => 'Notifikasi contoh berhasil ditambahkan!'];
    }

    $messages[] = ['type' => 'complete', 'text' => 'Migrasi notifikasi selesai!'];
} catch(PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Notifikasi - EcoCare+</title>
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
                <i class="fas fa-bell"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Migrasi Notifikasi</h1>
            <p class="text-lg text-gray-600">Setup sistem notifikasi EcoCare+</p>
        </div>
        <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8">
            <div class="space-y-4">
                <?php foreach ($messages as $msg): ?>
                    <div class="flex items-center gap-3 px-6 py-4 rounded-2xl <?php
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
                <a href="dashboard_pengguna.php" class="px-8 py-4 bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all">
                    <i class="fas fa-tachometer-alt mr-2"></i> Ke Dashboard
                </a>
                <a href="index.php" class="px-8 py-4 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-home mr-2"></i> Ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
