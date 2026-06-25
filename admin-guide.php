<?php
/*
EcoCare+ Admin System Panduan
=============================

1. Login Admin
--------------
Gunakan kredensial berikut untuk login sebagai admin:
- Email: admin@ecocare.id
- Password: admin123

2. Fitur Admin
--------------
- Dashboard: Statistik ringkas dan grafik
- Kelola Laporan: Lihat semua laporan, ubah status, hapus
- Kelola Pengguna: Lihat dan hapus pengguna
- Peta Monitoring: Peta sebaran laporan dengan warna marker berbeda
- Statistik: Grafik dan data statistik lengkap

3. Status Laporan
-----------------
- Baru (Merah): Laporan baru masuk
- Diproses (Kuning): Laporan sedang ditangani
- Selesai (Hijau): Laporan selesai

4. Keamanan
-----------
- Hanya pengguna dengan role 'admin' yang bisa mengakses halaman admin
- Pengguna biasa akan di-redirect ke halaman utama jika mencoba mengakses admin
*/
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Admin - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-ecocare-primary mb-8">EcoCare+ Admin System</h1>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Login Admin</h2>
                <div class="space-y-3">
                    <p class="text-gray-700">Gunakan kredensial berikut:</p>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-gray-800 mb-2">
                            <span class="font-semibold">Email:</span> admin@ecocare.id
                        </p>
                        <p class="text-gray-800">
                            <span class="font-semibold">Password:</span> admin123
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Fitur Admin</h2>
                <ul class="space-y-2 text-gray-700">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-chart-line text-ecocare-primary"></i>
                        Dashboard - Statistik & grafik
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-file-alt text-ecocare-primary"></i>
                        Kelola Laporan - Ubah status, hapus
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-users text-ecocare-primary"></i>
                        Kelola Pengguna - Lihat & hapus
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-map text-ecocare-primary"></i>
                        Peta Monitoring - Sebaran laporan
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-8">
            <a href="login.php" class="inline-block bg-ecocare-primary text-white px-8 py-4 rounded-xl font-bold hover:bg-opacity-90 transition shadow-lg">
                Login ke Admin Panel
            </a>
        </div>
    </div>
</body>
</html>