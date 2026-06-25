<?php
require 'config.php';

// Ambil semua data laporan beserta nama user
try {
    $stmt = $pdo->query("
        SELECT r.*, u.name as user_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
    
    // 1. Laporan Bulan Ini (Realtime dari Database)
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $total_reports_month = $stmt->fetchColumn();

    // 2. Statistik Area Berdasarkan Status Laporan (Realtime dari Database)
    // Area Aman = Selesai
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Selesai'");
    $area_safe = $stmt->fetchColumn();

    // Area Siaga = Diproses
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Diproses'");
    $area_alert = $stmt->fetchColumn();

    // Area Rawan = Baru
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Baru'");
    $area_danger = $stmt->fetchColumn();
} catch(PDOException $e) {
    $reports = [];
    $error = "Gagal memuat data laporan: " . $e->getMessage();
    $total_reports_month = 0;
    $area_safe = 0;
    $area_alert = 0;
    $area_danger = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Interaktif - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                        'ecocare-beige': '#E8DCCF',
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
        #map { height: 75vh; width: 100%; border-radius: 1.5rem; }
        .leaflet-popup-content-wrapper { border-radius: 1rem; padding: 0; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.2); }
        .leaflet-popup-content { margin: 0; min-width: 300px; }
        .leaflet-popup-tip { background: #ffffff; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gradient-to-br from-ecocare-cream to-ecocare-secondary/30 text-ecocare-dark min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-sm sticky top-0 z-50 border-b border-ecocare-secondary/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-ecocare-primary/30">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Peduli Lingkungan Kita</span>
                    </div>
                </a>
                
                <div class="flex items-center gap-8">
                    <a href="index.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Beranda</a>
                    <a href="index.php#edukasi" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Edukasi</a>
                    <?php if (is_logged_in()): ?>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-ecocare-primary/10 rounded-full">
                                <i class="fas fa-user text-ecocare-primary"></i>
                                <span class="text-ecocare-dark font-semibold"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            </div>
                            <?php if (is_admin()): ?>
                                <a href="admin_dashboard.php" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-xl font-semibold hover:shadow-lg hover:shadow-ecocare-primary/30 transition">Dashboard Admin</a>
                            <?php else: ?>
                                <a href="dashboard_pengguna.php" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-xl font-semibold hover:shadow-lg hover:shadow-ecocare-primary/30 transition">Dashboard Saya</a>
                            <?php endif; ?>
                            <a href="logout.php" class="text-ecocare-dark hover:text-red-500 transition flex items-center gap-1">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Masuk</a>
                        <a href="register.php" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-xl font-semibold hover:shadow-lg hover:shadow-ecocare-primary/30 transition">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="mb-12">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6">
                <div>
                    <div class="inline-block px-4 py-1 bg-ecocare-accent/10 text-ecocare-accent rounded-full text-sm font-semibold mb-4">
                        <i class="fas fa-map-marked-alt mr-2"></i> Peta Interaktif
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-extrabold text-ecocare-dark mb-4">Pantau Lingkungan secara Real-Time</h1>
                    <p class="text-lg text-ecocare-dark/70 max-w-2xl">
                        Lihat semua laporan dan status lingkungan di wilayah Anda melalui peta interaktif yang mudah digunakan
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-extrabold text-ecocare-primary"><?php echo count($reports); ?></div>
                    <div class="text-ecocare-dark/60 font-medium">Laporan terdaftar</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl mb-10">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistik Cards -->
        <div class="grid md:grid-cols-4 gap-6 mb-10">
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-ecocare-secondary/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-purple-400/30">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-ecocare-dark"><?php echo $total_reports_month; ?></div>
                        <div class="text-sm text-ecocare-dark/60 font-medium">Laporan Bulan Ini</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-ecocare-secondary/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-ecocare-primary/30">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-ecocare-dark"><?php echo $area_safe; ?></div>
                        <div class="text-sm text-ecocare-dark/60 font-medium">Area Aman</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-ecocare-secondary/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-ecocare-orange to-orange-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-orange-400/30">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-ecocare-dark"><?php echo $area_alert; ?></div>
                        <div class="text-sm text-ecocare-dark/60 font-medium">Area Siaga</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-ecocare-secondary/30">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-400 to-red-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-red-400/30">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-ecocare-dark"><?php echo $area_danger; ?></div>
                        <div class="text-sm text-ecocare-dark/60 font-medium">Area Rawan</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Map Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-xl p-6">
                    <div id="map"></div>
                </div>
                
                <!-- Legend -->
                <div class="mt-6 bg-white rounded-2xl shadow-lg p-6 border border-ecocare-secondary/30">
                    <h3 class="text-xl font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-ecocare-primary"></i> Legenda Status
                    </h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-ecocare-primary border-4 border-white shadow-sm"></div>
                            <div>
                                <div class="font-bold text-ecocare-dark">Selesai</div>
                                <div class="text-xs text-ecocare-dark/60">Laporan sudah ditangani</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-ecocare-orange border-4 border-white shadow-sm"></div>
                            <div>
                                <div class="font-bold text-ecocare-dark">Diproses</div>
                                <div class="text-xs text-ecocare-dark/60">Sedang dalam penanganan</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-500 border-4 border-white shadow-sm"></div>
                            <div>
                                <div class="font-bold text-ecocare-dark">Baru</div>
                                <div class="text-xs text-ecocare-dark/60">Laporan baru masuk</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Info -->
            <div class="space-y-6">
                <!-- Tips Card -->
                <div class="bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-3xl shadow-xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="text-xl font-bold">Tips Menjaga Lingkungan</h3>
                    </div>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-seedling mt-1"></i>
                            <span class="text-sm leading-relaxed">Buang sampah pada tempatnya dan pisahkan sesuai jenisnya</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-recycle mt-1"></i>
                            <span class="text-sm leading-relaxed">Gunakan barang yang dapat didaur ulang</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-tint mt-1"></i>
                            <span class="text-sm leading-relaxed">Hemat penggunaan air dan listrik sehari-hari</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Tahukah Kamu? -->
                <div class="bg-white rounded-3xl shadow-lg p-6 border border-ecocare-secondary/30">
                    <h3 class="text-lg font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-star text-yellow-400"></i> Tahukah Kamu?
                    </h3>
                    <p class="text-sm text-ecocare-dark/80 leading-relaxed">
                        Satu pohon dapat menyerap sekitar 22 kg karbon dioksida per tahun dan menghasilkan cukup oksigen untuk 2 orang dewasa!
                    </p>
                </div>
                
                <!-- Recent Reports -->
                <div class="bg-white rounded-3xl shadow-lg p-6 border border-ecocare-secondary/30">
                    <h3 class="text-lg font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-clock text-ecocare-accent"></i> Laporan Terbaru
                    </h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php 
                        $recent_reports = array_slice($reports, 0, 5);
                        if (empty($recent_reports)): 
                        ?>
                            <p class="text-sm text-ecocare-dark/60 italic text-center py-4">Belum ada laporan</p>
                        <?php else: 
                            foreach($recent_reports as $r):
                        ?>
                            <a href="report_detail.php?id=<?php echo $r['id']; ?>" class="block p-3 bg-ecocare-cream/50 rounded-xl border border-ecocare-secondary/20 hover:bg-ecocare-cream/80 transition">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm">
                                        <?php 
                                        switch($r['category']) {
                                            case 'Sampah': echo '🗑️'; break;
                                            case 'Saluran Air Tersumbat': echo '🌊'; break;
                                            case 'Genangan Air': echo '💧'; break;
                                            case 'Lingkungan Kurang Terawat': echo '🏚️'; break;
                                            default: echo '📍';
                                        }
                                        ?>
                                    </span>
                                    <span class="font-semibold text-sm text-ecocare-dark"><?php echo htmlspecialchars($r['category']); ?></span>
                                </div>
                                <p class="text-xs text-ecocare-dark/70 mb-1 line-clamp-2"><?php echo htmlspecialchars(mb_substr($r['description'], 0, 50)); ?>...</p>
                                <div class="text-xs text-ecocare-dark/50">
                                    <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($r['location_address']); ?>
                                </div>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-ecocare-dark text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="opacity-70">&copy; 2026 EcoCare+. Made with <i class="fas fa-heart text-red-400"></i> for our planet.</p>
        </div>
    </footer>

    <script>
        // Data laporan dari PHP (diubah ke JSON)
        var reports = <?php echo json_encode($reports); ?>;
        
        // Inisialisasi peta
        var map = L.map('map');
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Fungsi untuk warna marker berdasarkan status
        function getStatusColor(status) {
            switch(status) {
                case 'Baru': return '#ef4444'; // Merah
                case 'Diproses': return '#FFB86C'; // Oranye
                case 'Komunitas Terbentuk': return '#3b82f6'; // Biru
                case 'Aksi Berjalan': return '#8b5cf6'; // Ungu
                case 'Selesai': return '#6FAF8F'; // Hijau
                default: return '#6b7280';
            }
        }

        // Fungsi untuk emoji kategori
        function getCategoryEmoji(category) {
            switch(category) {
                case 'Sampah': return '🗑️';
                case 'Saluran Air Tersumbat': return '🌊';
                case 'Genangan Air': return '💧';
                case 'Lingkungan Kurang Terawat': return '🏚️';
                default: return '📍';
            }
        }

        // Render semua marker dan kumpulkan koordinat
        var markers = [];
        reports.forEach(function(report) {
            if (report.latitude && report.longitude) {
                var color = getStatusColor(report.status);
                
                var marker = L.circleMarker(
                    [parseFloat(report.latitude), parseFloat(report.longitude)],
                    {
                        radius: 14,
                        fillColor: color,
                        color: '#ffffff',
                        weight: 5,
                        opacity: 1,
                        fillOpacity: 0.9
                    }
                );

                // Konten popup
                var popupContent = '<div class="p-6 min-w-[300px]">';
                popupContent += '<h3 class="text-xl font-bold text-ecocare-dark mb-3 flex items-center gap-2">' +
                    getCategoryEmoji(report.category) + ' ' + report.category + '</h3>';
                popupContent += '<div class="mb-4"><span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold text-white shadow-sm" style="background-color: ' + color + '">' +
                    report.status + '</span></div>';
                // Tampilkan foto laporan
                if (report.photo_path) {
                    popupContent += '<img src="' + report.photo_path + 
                        '" class="w-full h-44 object-cover rounded-xl mb-4 shadow-sm" alt="Foto laporan">';
                }
                // Deskripsi singkat (max 150 karakter)
                var shortDesc = (report.description || 'Tidak ada deskripsi').substring(0, 150);
                if (report.description && report.description.length > 150) shortDesc += '...';
                popupContent += '<p class="text-ecocare-dark/80 mb-4 leading-relaxed">' + shortDesc + '</p>';
                popupContent += '<div class="text-sm text-ecocare-dark/70 border-t pt-4 space-y-2 mb-4">';
                popupContent += '<div><span class="font-semibold opacity-100 text-ecocare-dark"><i class="fas fa-map-marker-alt mr-2 text-ecocare-primary"></i>Lokasi:</span><br>' + 
                    report.location_address + '</div>';
                popupContent += '<div><span class="font-semibold opacity-100 text-ecocare-dark"><i class="fas fa-user mr-2 text-ecocare-accent"></i>Pelapor:</span><br>' + 
                    report.user_name + '</div>';
                popupContent += '</div>';
                popupContent += '<a href="report_detail.php?id=' + report.id + '" class="block w-full bg-ecocare-primary text-white text-center py-3 rounded-xl font-semibold hover:bg-ecocare-green-dark transition">';
                popupContent += '<i class="fas fa-eye mr-2"></i>Lihat Detail';
                popupContent += '</a></div>';

                marker.bindPopup(popupContent);
                marker.addTo(map);
                markers.push(marker);
            }
        });

        // Atur center map
        function setMapCenter() {
            // Case 1: Ada laporan
            if (markers.length > 0) {
                // Jika ada banyak laporan, gunakan fitBounds
                if (markers.length > 1) {
                    var group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1)); // pad untuk jarak
                } 
                // Jika hanya 1 laporan, fokus ke laporan tersebut
                else {
                    var latlng = markers[0].getLatLng();
                    map.setView(latlng, 15);
                }
            } 
            // Case 2: Tidak ada laporan, coba dapatkan lokasi pengguna
            else {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            map.setView([position.coords.latitude, position.coords.longitude], 15);
                        },
                        function() {
                            // Fallback: jika tidak bisa dapat lokasi pengguna, gunakan area umum
                            map.setView([-7.4244, 109.2300], 12);
                        }
                    );
                } else {
                    // Fallback: browser tidak mendukung geolocation
                    map.setView([-7.4244, 109.2300], 12);
                }
            }
        }

        // Panggil fungsi setMapCenter
        setMapCenter();
    </script>
</body>
</html>
