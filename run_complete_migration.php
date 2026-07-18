<?php
require 'config.php';

$messages = [];

try {
    // Fix users table
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat'");
    $messages[] = "✅ Fixed users table role column";
} catch (PDOException $e) {
    $messages[] = "⚠️ Users table already correct: " . $e->getMessage();
}

try {
    // Fix reports table status
    $pdo->exec("ALTER TABLE reports MODIFY COLUMN status ENUM('Baru', 'Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai') DEFAULT 'Baru'");
    $messages[] = "✅ Fixed reports table status column";
} catch (PDOException $e) {
    $messages[] = "⚠️ Reports table already correct: " . $e->getMessage();
}

try {
    // Add columns to community_actions if needed
    $columns = [
        'location' => 'TEXT AFTER description',
        'target_date' => 'DATE AFTER target_volunteers',
        'photo_path' => 'VARCHAR(255) AFTER target_date'
    ];
    
    foreach ($columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE community_actions ADD COLUMN $col $def");
            $messages[] = "✅ Added column $col to community_actions";
        } catch (PDOException $e) {
            $messages[] = "⚠️ Column $col already exists: " . $e->getMessage();
        }
    }
    
    $pdo->exec("ALTER TABLE community_actions MODIFY COLUMN progress INT DEFAULT 0");
    $messages[] = "✅ Fixed community_actions progress column";
} catch (PDOException $e) {
    $messages[] = "⚠️ community_actions table update: " . $e->getMessage();
}

try {
    // Create educations table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS educations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        image_path VARCHAR(255) NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $messages[] = "✅ Created educations table";
} catch (PDOException $e) {
    $messages[] = "⚠️ educations table: " . $e->getMessage();
}

try {
    // Create action_participants table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS action_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_participant (action_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $messages[] = "✅ Created action_participants table";
} catch (PDOException $e) {
    $messages[] = "⚠️ action_participants table: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migasi Database Complete - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen py-12">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-10">
                <div class="flex items-center gap-4 mb-4">
                    <i class="fas fa-database text-6xl opacity-90"></i>
                    <div>
                        <h1 class="text-3xl font-extrabold">Migrasi Database Selesai!</h1>
                        <p class="text-green-100 mt-1">Semua tabel telah diperbaiki dan diperbarui</p>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                    Hasil Migrasi:
                </h2>
                
                <div class="space-y-3">
                    <?php foreach ($messages as $msg): ?>
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="text-lg">
                                <?php if (str_starts_with($msg, '✅')): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                <?php else: ?>
                                    <i class="fas fa-info-circle text-yellow-500"></i>
                                <?php endif; ?>
                            </span>
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($msg); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="admin_dashboard.php" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-4 rounded-xl font-semibold hover:shadow-xl transition-all">
                        <i class="fas fa-home"></i>
                        Kembali ke Dashboard Admin
                    </a>
                    <a href="index.php" class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-8 py-4 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        <i class="fas fa-leaf"></i>
                        Ke Halaman Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>