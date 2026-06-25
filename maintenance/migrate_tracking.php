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
    <title>Migrasi Tracking Laporan - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-white">
                <i class="fas fa-clipboard-check"></i>
            </div>
            Migrasi Tracking Laporan EcoCare+
        </h1>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 space-y-6">
            <?php
            $success_count = 0;
            $errors = [];

            try {
                // 1. Tambahkan kolom untuk tracking
                $columns_to_add = [
                    'processed_by' => 'INT NULL',
                    'processed_at' => 'TIMESTAMP NULL',
                    'admin_notes' => 'TEXT NULL',
                    'completed_by' => 'INT NULL',
                    'completed_at' => 'TIMESTAMP NULL',
                    'completion_photo' => 'VARCHAR(255) NULL',
                    'completion_notes' => 'TEXT NULL'
                ];

                foreach ($columns_to_add as $column_name => $column_def) {
                    $check_stmt = $pdo->query("SHOW COLUMNS FROM reports LIKE '$column_name'");
                    if (!$check_stmt->fetch()) {
                        $pdo->exec("ALTER TABLE reports ADD COLUMN $column_name $column_def");
                        echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                                <i class='fas fa-check-circle text-xl'></i>
                                Berhasil menambah kolom <strong>$column_name</strong>
                              </div>";
                        $success_count++;
                    } else {
                        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                                <i class='fas fa-info-circle text-xl'></i>
                                Kolom <strong>$column_name</strong> sudah ada, dilewati
                              </div>";
                    }
                }

                // 2. Buat direktori upload completion photo jika belum ada
                $upload_dir = 'uploads/completion';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3'>
                            <i class='fas fa-folder text-xl'></i>
                            Berhasil membuat direktori upload completion
                          </div>";
                    $success_count++;
                }

                echo "<div class='mt-6 pt-6 border-t border-gray-200'>
                        <h3 class='text-xl font-semibold text-gray-800 mb-4'>✅ Migrasi selesai! Total perubahan: $success_count</h3>
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
