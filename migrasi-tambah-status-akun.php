<?php
require 'config.php';

// Hanya untuk admin!
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$messages = [];

try {
    // Cek apakah kolom status sudah ada
    $checkStatus = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$checkStatus->fetch()) {
        // Tambahkan kolom status
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('Pending', 'Aktif', 'Ditolak', 'Dinonaktifkan') DEFAULT 'Pending' AFTER role");
        $messages[] = "<p class='text-green-700'>✓ Kolom 'status' berhasil ditambahkan!</p>";
    } else {
        $messages[] = "<p class='text-blue-700'>ℓ Kolom 'status' sudah ada</p>";
    }

    // Cek apakah kolom created_at sudah ada
    $checkCreatedAt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if (!$checkCreatedAt->fetch()) {
        // Tambahkan kolom created_at
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER resident_id");
        $messages[] = "<p class='text-green-700'>✓ Kolom 'created_at' berhasil ditambahkan!</p>";
    } else {
        $messages[] = "<p class='text-blue-700'>ℓ Kolom 'created_at' sudah ada</p>";
    }

    // Set semua pengguna yang sudah ada menjadi 'Aktif'
    $updateExisting = $pdo->prepare("UPDATE users SET status = 'Aktif' WHERE status IS NULL");
    $updateExisting->execute();
    $messages[] = "<p class='text-green-700'>✓ Semua akun yang sudah ada di-set status 'Aktif'!</p>";

    $messages[] = "<p class='text-green-800 font-bold mt-4 text-center'>✅ MIGRASI BERHASIL!</p>";

} catch (PDOException $e) {
    $messages[] = "<p class='text-red-700'>❌ Error database: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Tambah Status Akun - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-2xl w-full">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Migrasi Tambah Status Akun EcoCare+</h1>
        <div class="space-y-3">
            <?php foreach ($messages as $message): ?>
                <?php echo $message; ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="admin_dashboard.php" class="inline-block bg-gradient-to-r from-emerald-600 to-emerald-700 text-white font-semibold py-3 px-6 rounded-xl hover:opacity-90 transition">
                Kembali ke Dashboard Admin
            </a>
        </div>
    </div>
</body>
</html>
