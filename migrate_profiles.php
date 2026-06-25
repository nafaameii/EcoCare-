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
    <title>Migrasi Database Profil - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-white">
                <i class="fas fa-database"></i>
            </div>
            Migrasi Database Profil EcoCare+
        </h1>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 space-y-6">
            <?php
            $success_count = 0;
            $errors = [];

            try {
                // 1. Tambah kolom profile_pic jika belum ada
                $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
                if (!$check->fetch()) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER password");
                    echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-check-circle text-xl'></i>
                            Berhasil menambah kolom 'profile_pic'
                          </div>";
                    $success_count++;
                } else {
                    echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-info-circle text-xl'></i>
                            Kolom 'profile_pic' sudah ada, dilewati
                          </div>";
                }

                // 2. Tambah kolom updated_at jika belum ada
                $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
                if (!$check->fetch()) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
                    echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-check-circle text-xl'></i>
                            Berhasil menambah kolom 'updated_at'
                          </div>";
                    $success_count++;
                } else {
                    echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-info-circle text-xl'></i>
                            Kolom 'updated_at' sudah ada, dilewati
                          </div>";
                }

                // 3. Buat direktori uploads/profiles jika belum ada
                $upload_dir = 'uploads/profiles';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-folder text-xl'></i>
                            Berhasil membuat direktori 'uploads/profiles'
                          </div>";
                    $success_count++;
                } else {
                    echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-info-circle text-xl'></i>
                            Direktori 'uploads/profiles' sudah ada, dilewati
                          </div>";
                }

                echo "<div class='mt-6 pt-6 border-t border-gray-200'>
                        <h3 class='text-xl font-semibold text-gray-800 mb-4'>✅ Migrasi selesai!</h3>
                        <a href='index.php' class='inline-block bg-emerald-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-emerald-600 transition'>
                            Kembali ke Halaman Utama
                        </a>
                      </div>";

            } catch (PDOException $e) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl'>
                        <h4 class='font-semibold text-lg mb-2'>Error saat migrasi!</h4>
                        <p class='text-sm'>" . htmlspecialchars($e->getMessage()) . "</p>
                      </div>";
            }
            ?>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>