<?php
require 'config.php';

// Fetch statistics for Hero Section
try {
    // Total Reports
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports");
    $total_reports = $stmt->fetchColumn();

    // Finished Reports
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Selesai'");
    $reports_selesai = $stmt->fetchColumn();

    // Processing Reports
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Diproses'");
    $reports_diproses = $stmt->fetchColumn();

    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Fetch reports for map preview
    $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 10");
    $map_reports = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback to 0 if error occurs
    $total_reports = 0;
    $reports_selesai = 0;
    $reports_diproses = 0;
    $total_users = 0;
    $map_reports = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCare+ - Bersama Warga, Lindungi Lingkungan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'jakarta': ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        'ecocare-primary': '#4A7C59',
                        'ecocare-secondary': '#8FBC8F',
                        'ecocare-dark': '#1A3A2A',
                        'ecocare-cream': '#F8F6F0',
                        'ecocare-accent': '#C8D6AF',
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        html { scroll-behavior: smooth; }
        #preview-map { height: 320px; border-radius: 1.5rem; z-index: 10; }
    </style>
</head>
<body class="bg-ecocare-cream text-ecocare-dark">
    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="#hero" class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-ecocare-primary rounded-xl flex items-center justify-center text-white text-xl">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">EcoCare+</span>
                </a>
                
                <div class="hidden md:flex items-center gap-10">
                    <a href="#hero" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Beranda</a>
                    <a href="#kenapa" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Mengapa</a>
                    <a href="#cara-kerja" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Cara Kerja</a>
                    <a href="#edukasi" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Edukasi</a>
                    <a href="map.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Peta</a>
                </div>
                
                <div class="flex items-center gap-4">
                    <?php if (is_logged_in()): ?>
                        <div class="flex items-center gap-4">
                            <?php $profile_page = is_admin() ? 'admin_profile.php' : 'user_profile.php'; ?>
                            <a href="<?php echo $profile_page; ?>" class="flex items-center gap-3 group">
                                <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary group-hover:border-ecocare-dark transition">
                                    <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] && file_exists($_SESSION['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-ecocare-primary flex items-center justify-center text-white font-bold">
                                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="text-gray-700 font-medium hidden md:block"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            </a>
                            <?php if (is_admin()): ?>
                                <a href="admin_dashboard.php" class="bg-ecocare-primary text-white px-5 py-2 rounded-lg font-semibold hover:bg-ecocare-dark transition">Dashboard Admin</a>
                            <?php else: ?>
                                <a href="dashboard_pengguna.php" class="bg-ecocare-primary text-white px-5 py-2 rounded-lg font-semibold hover:bg-ecocare-dark transition">Dashboard Saya</a>
                            <?php endif; ?>
                            <a href="logout.php" class="text-gray-600 hover:text-red-600 transition flex items-center gap-2">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden md:block">Keluar</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Masuk Pengguna</a>
                        <a href="admin_login.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Masuk Admin</a>
                        <a href="register.php" class="bg-ecocare-dark text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-ecocare-primary transition">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- 1. Hero Section -->
    <section id="hero" class="relative min-h-[80vh] flex items-center py-20">
        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <img 
                src="images/hero1.jpg" 
                alt="Latar Belakang Lingkungan" 
                class="w-full h-full object-cover"
                onerror="this.src='https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920&auto=format&fit=crop'"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-ecocare-dark/90 via-ecocare-dark/70 to-ecocare-dark/50"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 w-full">
            <div class="max-w-3xl text-white">
                <span class="inline-block px-4 py-2 bg-ecocare-primary/20 border border-ecocare-secondary/30 rounded-full text-sm font-semibold mb-6 backdrop-blur-sm">
                    <i class="fas fa-heart mr-2"></i> Gerakan Masyarakat
                </span>
                <h1 class="text-5xl lg:text-7xl font-extrabold leading-tight mb-6">
                    Bersama Warga, <br>Mewujudkan Lingkungan yang Lebih Bersih
                </h1>
                <p class="text-lg text-gray-200 mb-10 max-w-xl leading-relaxed">
                    Setiap laporan kamu adalah langkah nyata. Laporkan masalah lingkungan, lihat perkembangan, dan menjadi bagian dari solusi bersama komunitas.
                </p>
                <div class="flex flex-wrap gap-4">
                    <?php if (!is_logged_in()): ?>
                        <a href="register.php" class="bg-white text-ecocare-dark px-8 py-4 rounded-xl font-bold text-lg hover:bg-ecocare-cream transition shadow-lg">
                            Bergabung Sekarang
                        </a>
                    <?php endif; ?>
                    <a href="map.php" class="border-2 border-white text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-white/10 transition backdrop-blur-sm">
                        Lihat Peta
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Statistik EcoCare+ -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-ecocare-dark mb-4">Statistik EcoCare+</h2>
                <p class="text-gray-600">Perkembangan gerakan kita sampai saat ini</p>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-ecocare-cream border border-ecocare-secondary/20 rounded-2xl p-8 text-center shadow-sm">
                    <div class="text-5xl font-extrabold text-ecocare-primary mb-2"><?php echo number_format($total_reports); ?></div>
                    <div class="text-gray-600 font-medium">Laporan Masuk</div>
                </div>
                <div class="bg-ecocare-cream border border-ecocare-secondary/20 rounded-2xl p-8 text-center shadow-sm">
                    <div class="text-5xl font-extrabold text-ecocare-dark mb-2"><?php echo number_format($reports_selesai); ?></div>
                    <div class="text-gray-600 font-medium">Terselesaikan</div>
                </div>
                <div class="bg-ecocare-cream border border-ecocare-secondary/20 rounded-2xl p-8 text-center shadow-sm">
                    <div class="text-5xl font-extrabold text-yellow-600 mb-2"><?php echo number_format($reports_diproses); ?></div>
                    <div class="text-gray-600 font-medium">Diproses</div>
                </div>
                <div class="bg-ecocare-cream border border-ecocare-secondary/20 rounded-2xl p-8 text-center shadow-sm">
                    <div class="text-5xl font-extrabold text-ecocare-secondary mb-2"><?php echo number_format($total_users); ?></div>
                    <div class="text-gray-600 font-medium">Warga Bergabung</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Mengapa EcoCare+ -->
    <section id="kenapa" class="py-24 bg-ecocare-cream">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative">
                    <img 
                        src="images/hero2.jpg" 
                        alt="Tentang EcoCare+" 
                        class="w-full rounded-3xl shadow-2xl"
                        onerror="this.src='https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=900&auto=format&fit=crop'"
                    >
                    <div class="absolute -bottom-8 -left-8 bg-ecocare-primary text-white p-8 rounded-2xl shadow-xl max-w-xs">
                        <div class="text-5xl font-bold mb-1">100%</div>
                        <div class="font-medium opacity-90">Laporan Dari Warga</div>
                    </div>
                </div>
                
                <div>
                    <span class="inline-block px-4 py-1 bg-ecocare-primary/10 text-ecocare-primary rounded-full text-sm font-semibold mb-6">
                        Mengapa EcoCare+
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-extrabold mb-8 leading-tight">
                        Gerakan Kolaboratif untuk Bumi Kita
                    </h2>
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-ecocare-primary flex-shrink-0 shadow-sm">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Berbasis Komunitas</h3>
                                <p class="text-gray-600">Setiap orang berperan aktif dalam menjaga lingkungan sekitarnya.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-ecocare-primary flex-shrink-0 shadow-sm">
                                <i class="fas fa-eye text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Transparan & Terukur</h3>
                                <p class="text-gray-600">Lihat progress laporan secara real-time tanpa ada yang tertutup.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-ecocare-primary flex-shrink-0 shadow-sm">
                                <i class="fas fa-bolt text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Tindakan Cepat</h3>
                                <p class="text-gray-600">Laporan kamu langsung diterima dan ditindaklanjuti oleh tim terkait.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Cara Kerja -->
    <section id="cara-kerja" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 bg-ecocare-cream text-ecocare-primary rounded-full text-sm font-semibold mb-6 shadow-sm">
                    Cara Kerja
                </span>
                <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">
                    3 Langkah Mudah untuk Beraksi
                </h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-10">
                <div class="text-center">
                    <div class="w-20 h-20 bg-ecocare-primary rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-8">
                        <i class="fas fa-camera"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Laporkan</h3>
                    <p class="text-gray-600">Ambil foto, tuliskan detail, dan kirim laporanmu dalam hitungan detik.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-ecocare-primary rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-8">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Proses</h3>
                    <p class="text-gray-600">Tim kami meninjau dan menindaklanjuti laporanmu secepat mungkin.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-ecocare-primary rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-8">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Selesai</h3>
                    <p class="text-gray-600">Kamu akan mendapatkan notifikasi ketika laporanmu selesai ditangani.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Peta Kondisi Lingkungan -->
    <section class="py-24 bg-ecocare-dark">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <span class="inline-block px-4 py-1 bg-ecocare-primary/20 text-ecocare-secondary rounded-full text-sm font-semibold mb-6">
                        Peta Interaktif
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-extrabold mb-6 leading-tight">
                        Lihat Kondisi Lingkungan di Sekitarmu
                    </h2>
                    <p class="text-gray-300 mb-10 text-lg">
                        Peta interaktif yang menampilkan semua laporan masuk. Kamu bisa melihat area mana yang membutuhkan perhatian lebih.
                    </p>
                    <a href="map.php" class="inline-block bg-white text-ecocare-dark px-8 py-4 rounded-xl font-bold text-lg hover:bg-ecocare-cream transition shadow-lg">
                        Buka Peta <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                <div class="rounded-3xl overflow-hidden shadow-2xl border-4 border-white/10">
                    <div id="preview-map"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Edukasi Lingkungan -->
    <section id="edukasi" class="py-24 bg-ecocare-cream">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center mb-20">
                <div class="order-2 lg:order-1">
                    <span class="inline-block px-4 py-1 bg-white text-ecocare-primary rounded-full text-sm font-semibold mb-6 shadow-sm">
                        Edukasi & Pelestarian
                    </span>
                    <h2 class="text-4xl lg:text-5xl font-extrabold mb-8 leading-tight">
                        Belajar Menjaga Lingkungan, Bersama-sama
                    </h2>
                    <p class="text-gray-600 text-lg mb-10">
                        Berbagai panduan dan tips untuk hidup lebih ramah lingkungan. Dari memilah sampah hingga menghemat energi.
                    </p>
                    <div class="space-y-4">
                        <a href="edukasi_sampah.php" class="flex items-center gap-4 p-4 bg-white rounded-xl hover:bg-ecocare-secondary/10 transition shadow-sm">
                            <i class="fas fa-recycle text-2xl text-ecocare-primary"></i>
                            <div class="font-semibold">Memilah Sampah dengan Benar</div>
                            <i class="fas fa-arrow-right ml-auto text-gray-400"></i>
                        </a>
                        <a href="edukasi_sungai.php" class="flex items-center gap-4 p-4 bg-white rounded-xl hover:bg-ecocare-secondary/10 transition shadow-sm">
                            <i class="fas fa-water text-2xl text-ecocare-primary"></i>
                            <div class="font-semibold">Menjaga Kebersihan Sungai</div>
                            <i class="fas fa-arrow-right ml-auto text-gray-400"></i>
                        </a>
                        <a href="edukasi_plastik.php" class="flex items-center gap-4 p-4 bg-white rounded-xl hover:bg-ecocare-secondary/10 transition shadow-sm">
                            <i class="fas fa-shopping-bag text-2xl text-ecocare-primary"></i>
                            <div class="font-semibold">Kurangi Penggunaan Plastik</div>
                            <i class="fas fa-arrow-right ml-auto text-gray-400"></i>
                        </a>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <img 
                        src="images/hero3.jpg" 
                        alt="Edukasi Lingkungan" 
                        class="w-full rounded-3xl shadow-2xl"
                        onerror="this.src='https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=900&auto=format&fit=crop'"
                    >
                </div>
            </div>
        </div>
    </section>

    <!-- 7. CTA -->
    <section class="py-20 bg-gradient-to-r from-ecocare-primary to-ecocare-dark">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-white mb-6">
                Tunggu Apa Lagi? Bergabunglah Hari Ini!
            </h2>
            <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                Jutaan warga sudah bergabung. Saatnya kamu berkontribusi untuk lingkungan yang lebih baik.
            </p>
            <?php if (!is_logged_in()): ?>
                <a href="register.php" class="inline-block bg-white text-ecocare-dark px-10 py-4 rounded-xl font-bold text-xl hover:bg-ecocare-cream transition shadow-xl">
                    Daftar Gratis Sekarang
                </a>
            <?php else: ?>
                <a href="dashboard_pengguna.php" class="inline-block bg-white text-ecocare-dark px-10 py-4 rounded-xl font-bold text-xl hover:bg-ecocare-cream transition shadow-xl">
                    Ke Dashboard Saya
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-ecocare-dark text-white py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-ecocare-primary rounded-xl flex items-center justify-center text-2xl">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="text-2xl font-bold">EcoCare+</span>
                    </div>
                    <p class="text-gray-400 max-w-md mb-8">
                        Platform masyarakat untuk pelaporan dan pemantauan lingkungan yang transparan dan mudah diakses oleh semua.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-11 h-11 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-11 h-11 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="w-11 h-11 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-6">Navigasi</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#hero" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="#kenapa" class="hover:text-white transition">Mengapa EcoCare+</a></li>
                        <li><a href="#cara-kerja" class="hover:text-white transition">Cara Kerja</a></li>
                        <li><a href="map.php" class="hover:text-white transition">Peta</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-6">Kontak</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-center gap-3"><i class="fas fa-envelope text-ecocare-primary"></i> info@ecocare.id</li>
                        <li class="flex items-center gap-3"><i class="fas fa-phone text-ecocare-primary"></i> (0281) 123-456</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 mt-16 pt-8 text-center text-gray-500">
                &copy; <?php echo date('Y'); ?> EcoCare+. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Script Peta Interaktif Preview -->
    <script>
        // Inisialisasi peta
        var previewMap = L.map('preview-map').setView([-6.2088, 106.8456], 10); // Default Jakarta

        // Tile layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(previewMap);

        // Data laporan dari PHP
        var reports = <?php echo json_encode($map_reports); ?>;

        // Custom icons
        var statusIcons = {
            'Baru': L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color:#ef4444; width:20px; height:20px; border-radius:50%; border:2px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            }),
            'Diproses': L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color:#f59e0b; width:20px; height:20px; border-radius:50%; border:2px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            }),
            'Selesai': L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color:#10b981; width:20px; height:20px; border-radius:50%; border:2px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        };

        // Tambahkan marker untuk setiap laporan
        reports.forEach(function(report) {
            if (report.latitude && report.longitude) {
                var icon = statusIcons[report.status] || statusIcons['Baru'];
                var marker = L.marker([report.latitude, report.longitude], {icon: icon}).addTo(previewMap);
                marker.bindPopup(
                    '<div style="padding:10px; font-family:\'Plus Jakarta Sans\', sans-serif;">' +
                        '<h4 style="margin:0 0 8px 0; color:#1a3a2a; font-weight:700;">' + (report.title || 'Laporan Lingkungan') + '</h4>' +
                        '<p style="margin:0 0 5px 0; color:#4b5563; font-size:14px;">Status: <strong style="color:' + (report.status === 'Selesai' ? '#10b981' : report.status === 'Diproses' ? '#f59e0b' : '#ef4444') + '">' + report.status + '</strong></p>' +
                        '<p style="margin:0; color:#4b5563; font-size:13px;">' + (report.description || 'Tidak ada deskripsi') + '</p>' +
                    '</div>'
                );
            }
        });

        // Klik pada peta untuk membuka halaman map.php
        previewMap.on('click', function() {
            window.location.href = 'map.php';
        });
    </script>
</body>
</html>