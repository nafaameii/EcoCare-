<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips Kurangi Penggunaan Plastik - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'ecocare-primary': '#6FAF8F',
                        'ecocare-secondary': '#A8D5BA',
                        'ecocare-accent': '#7DB7E8',
                        'ecocare-cream': '#F4EBD0',
                        'ecocare-orange': '#FFB86C',
                        'ecocare-dark': '#2D3748',
                        'ecocare-green-dark': '#3D8B6A'
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-ecocare-cream text-ecocare-dark">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="text-2xl font-bold text-ecocare-dark">EcoCare+</span>
                </a>
                
                <div class="flex items-center gap-6">
                    <a href="index.php#fitur" class="text-ecocare-dark hover:text-ecocare-primary font-medium">Fitur</a>
                    <a href="index.php#edukasi" class="text-ecocare-dark hover:text-ecocare-primary font-medium">Edukasi</a>
                    <a href="map.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium flex items-center gap-2">
                        <i class="fas fa-map-marked-alt"></i> Peta
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-ecocare-dark font-medium">Halo, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        <a href="logout.php" class="text-red-500 hover:text-red-600 font-medium">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium">Masuk</a>
                        <a href="register.php" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-xl font-semibold hover:shadow-lg transition">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="bg-gradient-to-br from-orange-500 to-ecocare-orange py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="index.php#edukasi" class="inline-flex items-center gap-2 text-white/90 hover:text-white mb-8 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Edukasi
            </a>
            <div class="flex items-center gap-4 mb-6">
                <span class="inline-block px-4 py-1 bg-white/20 text-white rounded-full text-sm font-semibold">
                    <i class="fas fa-shopping-bag mr-2"></i> Hemat Plastik
                </span>
                <span class="text-white/80 text-sm font-medium">
                    <i class="fas fa-clock mr-1"></i> 7 menit baca
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">
                Tips Kurangi Penggunaan Plastik
            </h1>
            <p class="text-white/90 text-lg">
                Langkah mudah dan praktis untuk mengurangi ketergantungan pada plastik sekali pakai sehari-hari
            </p>
        </div>
    </section>

    <!-- Article Content -->
    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10 mb-10 border border-gray-100">
                <!-- Hero Image Placeholder -->
                <div class="w-full h-64 bg-gradient-to-br from-orange-400 to-ecocare-orange rounded-xl mb-8 flex items-center justify-center overflow-hidden">
                    <div class="text-white text-center">
                        <i class="fas fa-shopping-bag text-9xl mb-4"></i>
                        <p class="text-xl font-semibold">Ilustrasi Tanpa Plastik</p>
                    </div>
                </div>

                <!-- Introduction -->
                <h2 class="text-2xl font-bold text-ecocare-dark mb-4">Masalah Plastik Sekali Pakai</h2>
                <p class="text-gray-700 mb-6 leading-relaxed">
                    Plastik adalah bahan yang sangat praktis, tapi sayangnya kita sering menggunakannya dengan tidak bijak. 
                    Plastik sekali pakai seperti tas belanja, botol minum, dan kemasan makanan menjadi salah satu pencemar
                    terbesar di lingkungan kita, terutama di lautan.
                </p>

                <!-- Practical Tips -->
                <h3 class="text-xl font-bold text-ecocare-dark mb-4 mt-8">
                    <i class="fas fa-lightbulb text-ecocare-orange mr-2"></i>
                    Langkah yang Bisa Kamu Lakukan Hari Ini
                </h3>

                <div class="grid md:grid-cols-1 gap-4 mb-8">
                    <!-- Tip 1 -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6 flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-ecocare-dark mb-1">Bawa Tas Belanja Sendiri</h4>
                            <p class="text-gray-700 text-sm">Selalu siapkan tas kain atau totebag di tas atau mobilmu.</p>
                        </div>
                    </div>
                    
                    <!-- Tip 2 -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6 flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fas fa-bottle-water"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-ecocare-dark mb-1">Gunakan Botol Minum Isi Ulang</h4>
                            <p class="text-gray-700 text-sm">Pilih botol minum dari stainless steel atau kaca yang awet.</p>
                        </div>
                    </div>
                    
                    <!-- Tip 3 -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6 flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-ecocare-dark mb-1">Bawa Alat Makan Sendiri</h4>
                            <p class="text-gray-700 text-sm">Siapkan sendok, garpu, dan sedotan dari bahan yang dapat dipakai ulang.</p>
                        </div>
                    </div>
                    
                    <!-- Tip 4 -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6 flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-ecocare-dark mb-1">Belanja di Toko yang Ramah Lingkungan</h4>
                            <p class="text-gray-700 text-sm">Pilih toko yang menjual produk tanpa kemasan plastik.</p>
                        </div>
                    </div>
                </div>

                <!-- Fun Fact -->
                <h3 class="text-xl font-bold text-ecocare-dark mb-4 mt-8">
                    <i class="fas fa-star text-ecocare-primary mr-2"></i>
                    Tahukah Kamu?
                </h3>
                <div class="bg-gradient-to-br from-orange-50 to-yellow-50 border border-ecocare-orange/30 p-6 rounded-xl mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-ecocare-orange/20 rounded-xl flex items-center justify-center text-3xl flex-shrink-0">
                            🌍
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-800 font-medium mb-2">
                                Setiap tahun, lebih dari 8 juta ton plastik masuk ke lautan kita!
                            </p>
                            <p class="text-gray-600 text-sm">
                                Dengan mengurangi plastik sekali pakai, kita berkontribusi menyelamatkan ekosistem laut.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Articles -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                <h3 class="text-xl font-bold text-ecocare-dark mb-6">Artikel Terkait</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <a href="edukasi_sampah.php" class="group p-4 border border-gray-200 rounded-xl hover:border-ecocare-primary hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-ecocare-primary/20 rounded-lg flex items-center justify-center text-ecocare-primary">
                                <i class="fas fa-recycle"></i>
                            </div>
                            <div>
                                <p class="font-bold text-ecocare-dark">Memilah Sampah</p>
                                <p class="text-xs text-gray-500">Artikel Sebelumnya</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 group-hover:text-ecocare-primary transition">
                            Panduan lengkap memisahkan sampah organik dan anorganik
                        </p>
                    </a>
                    <a href="edukasi_sungai.php" class="group p-4 border border-gray-200 rounded-xl hover:border-ecocare-primary hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-ecocare-accent/20 rounded-lg flex items-center justify-center text-ecocare-accent">
                                <i class="fas fa-water"></i>
                            </div>
                            <div>
                                <p class="font-bold text-ecocare-dark">Menjaga Sungai</p>
                                <p class="text-xs text-gray-500">Artikel Lainnya</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 group-hover:text-ecocare-primary transition">
                            Bagaimana cara menjaga kebersihan dan kelestarian sungai kita
                        </p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-ecocare-dark text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="text-2xl font-bold">EcoCare+</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Platform untuk warga peduli lingkungan. Laporkan, pantau, dan bersama kita jaga bumi kita.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="index.php" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="index.php#fitur" class="hover:text-white transition">Fitur</a></li>
                        <li><a href="index.php#edukasi" class="hover:text-white transition">Edukasi</a></li>
                        <li><a href="map.php" class="hover:text-white transition">Peta</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Lainnya</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Kebijakan Privasi</a></li>
                        <li><a href="#" class="hover:text-white transition">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-10 pt-8 text-center text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> EcoCare+. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
