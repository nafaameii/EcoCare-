<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pentingnya Menjaga Sungai - EcoCare+</title>
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
    <section class="bg-gradient-to-br from-blue-500 to-ecocare-accent py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="index.php#edukasi" class="inline-flex items-center gap-2 text-white/90 hover:text-white mb-8 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Edukasi
            </a>
            <div class="flex items-center gap-4 mb-6">
                <span class="inline-block px-4 py-1 bg-white/20 text-white rounded-full text-sm font-semibold">
                    <i class="fas fa-water mr-2"></i> Air Bersih
                </span>
                <span class="text-white/80 text-sm font-medium">
                    <i class="fas fa-clock mr-1"></i> 6 menit baca
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">
                Pentingnya Menjaga Sungai
            </h1>
            <p class="text-white/90 text-lg">
                Kenali dampak pencemaran sungai dan cara sederhana berkontribusi menjaga kebersihan dan kelestariannya
            </p>
        </div>
    </section>

    <!-- Article Content -->
    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10 mb-10 border border-gray-100">
                <!-- Hero Image Placeholder -->
                <div class="w-full h-64 bg-gradient-to-br from-blue-400 to-ecocare-accent rounded-xl mb-8 flex items-center justify-center overflow-hidden">
                    <div class="text-white text-center">
                        <i class="fas fa-water text-9xl mb-4"></i>
                        <p class="text-xl font-semibold">Ilustrasi Sungai Bersih</p>
                    </div>
                </div>

                <!-- Introduction -->
                <h2 class="text-2xl font-bold text-ecocare-dark mb-4">Sungai: Jantung Kehidupan Kita</h2>
                <p class="text-gray-700 mb-6 leading-relaxed">
                    Sungai bukan hanya sekadar aliran air, tapi sumber kehidupan bagi jutaan makhluk hidup, termasuk manusia. 
                    Air sungai kita gunakan untuk minum, pertanian, industri, dan masih banyak lagi. Sayangnya, banyak sungai
                    di Indonesia kini tercemar oleh limbah dan sampah.
                </p>

                <!-- River Issues -->
                <h3 class="text-xl font-bold text-ecocare-dark mb-4 mt-8">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    Dampak Pencemaran Sungai
                </h3>

                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <!-- Health -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6">
                        <div class="w-14 h-14 bg-red-500 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h4 class="font-bold text-ecocare-dark mb-2">Gangguan Kesehatan</h4>
                        <p class="text-sm text-gray-700">Air tercemar menyebabkan penyakit kulit, diare, dan gangguan pencernaan.</p>
                    </div>

                    <!-- Ecosystem -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-6">
                        <div class="w-14 h-14 bg-purple-500 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                            <i class="fas fa-fish"></i>
                        </div>
                        <h4 class="font-bold text-ecocare-dark mb-2">Kerusakan Ekosistem</h4>
                        <p class="text-sm text-gray-700">Ikan dan biota air mati, rantai makanan terputus.</p>
                    </div>
                </div>

                <!-- Practical Tips -->
                <h3 class="text-xl font-bold text-ecocare-dark mb-4 mt-8">
                    <i class="fas fa-lightbulb text-ecocare-orange mr-2"></i>
                    Langkah yang Bisa Kamu Lakukan Hari Ini
                </h3>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-xl mb-8">
                    <ol class="list-decimal list-inside space-y-3 text-gray-700">
                        <li class="font-medium">Jangan membuang sampah ke sungai atau saluran air</li>
                        <li>Buat lubang biopori di sekitar rumah untuk resapan air</li>
                        <li>Tanam pohon di area sekitar sungai atau bantaran</li>
                        <li>Laporkan jika ada aktivitas yang membuang limbah ke sungai</li>
                        <li>Gunakan produk pembersih yang ramah lingkungan</li>
                    </ol>
                </div>

                <!-- Fun Fact -->
                <h3 class="text-xl font-bold text-ecocare-dark mb-4 mt-8">
                    <i class="fas fa-star text-ecocare-primary mr-2"></i>
                    Tahukah Kamu?
                </h3>
                <div class="bg-gradient-to-br from-blue-50 to-ecocare-accent/10 border border-ecocare-accent/30 p-6 rounded-xl mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-ecocare-accent/20 rounded-xl flex items-center justify-center text-3xl flex-shrink-0">
                            🌊
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-800 font-medium mb-2">
                                Lebih dari 80% penyakit di negara berkembang disebabkan oleh air yang tidak bersih dan buruknya sanitasi!
                            </p>
                            <p class="text-gray-600 text-sm">
                                Menjaga sungai berarti menjaga kesehatan kita bersama.
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
                            Pelajari cara memisahkan sampah organik dan anorganik dengan benar
                        </p>
                    </a>
                    <a href="edukasi_plastik.php" class="group p-4 border border-gray-200 rounded-xl hover:border-ecocare-primary hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-ecocare-orange/20 rounded-lg flex items-center justify-center text-ecocare-orange">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div>
                                <p class="font-bold text-ecocare-dark">Tips Kurangi Plastik</p>
                                <p class="text-xs text-gray-500">Artikel Lainnya</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 group-hover:text-ecocare-primary transition">
                            Langkah mudah mengurangi ketergantungan pada plastik sekali pakai
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
