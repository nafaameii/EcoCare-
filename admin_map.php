<?php
require 'config.php';
require_admin();

// Get all reports
try {
    $stmt = $pdo->query("
        SELECT r.*, u.name as user_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
} catch(PDOException $e) {
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Monitoring - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        #map { height: calc(100vh - 280px); width: 100%; border-radius: 1rem; }
        .sidebar { transition: all 0.3s ease; }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%);
            color: white;
        }
        .leaflet-popup-content-wrapper { border-radius: 1rem; }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .legend-color { width: 1rem; height: 1rem; border-radius: 9999px; border: 2px solid white; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white shadow-lg border-r border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-ecocare-dark">EcoCare+</h2>
                        <p class="text-xs text-gray-500 font-semibold">Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="admin_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_reports.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-file-alt w-5"></i>
                            <span>Kelola Laporan</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-users w-5"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_map.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-map-marked-alt w-5"></i>
                            <span>Peta Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_statistics.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Statistik</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_education.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-book w-5"></i>
                            <span>Kelola Edukasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_actions.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-hands-helping w-5"></i>
                            <span>Kelola Aksi Lingkungan</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="absolute bottom-0 left-0 w-64 p-4 border-t border-gray-200 bg-white">
                <a href="admin_profile.php" class="flex items-center gap-3 mb-4 hover:bg-gray-50 rounded-lg p-2 -mx-2 -my-2 transition">
                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary flex-shrink-0">
                        <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic']): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </a>
                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1">
            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-ecocare-dark">Peta Monitoring</h1>
                        <p class="text-gray-500 text-sm">Peta sebaran semua laporan</p>
                    </div>
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        <span>Ke Beranda</span>
                    </a>
                </div>
            </header>
            
            <div class="p-8">
                <div class="grid lg:grid-cols-4 gap-8">
                    <!-- Map Container -->
                    <div class="lg:col-span-3">
                        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                            <div id="map"></div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Info -->
                    <div class="space-y-6">
                        <!-- Legend -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-ecocare-dark mb-4">Legenda</h3>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #ef4444;"></div>
                                <span class="text-sm text-gray-700">Laporan Baru</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #FFB86C;"></div>
                                <span class="text-sm text-gray-700">Diproses</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #6FAF8F;"></div>
                                <span class="text-sm text-gray-700">Selesai</span>
                            </div>
                        </div>
                        
                        <!-- Statistics -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-ecocare-dark mb-4">Ringkasan</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-xl">
                                    <span class="text-sm text-gray-700">Laporan Baru</span>
                                    <span class="font-bold text-red-600">
                                        <?php 
                                        $count = 0;
                                        foreach($reports as $r) if($r['status'] === 'Baru') $count++;
                                        echo $count;
                                        ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-xl">
                                    <span class="text-sm text-gray-700">Diproses</span>
                                    <span class="font-bold text-yellow-600">
                                        <?php 
                                        $count = 0;
                                        foreach($reports as $r) if($r['status'] === 'Diproses') $count++;
                                        echo $count;
                                        ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                                    <span class="text-sm text-gray-700">Selesai</span>
                                    <span class="font-bold text-green-600">
                                        <?php 
                                        $count = 0;
                                        foreach($reports as $r) if($r['status'] === 'Selesai') $count++;
                                        echo $count;
                                        ?>
                                    </span>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-700">Total Laporan</span>
                                        <span class="font-bold text-ecocare-primary"><?php echo count($reports); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize map
        const map = L.map('map');
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Reports data from PHP
        const reports = <?php echo json_encode($reports); ?>;
        
        // Add markers
        let markers = [];
        reports.forEach(report => {
            if (report.latitude && report.longitude) {
                let color;
                switch(report.status) {
                    case 'Baru': color = '#ef4444'; break;
                    case 'Diproses': color = '#FFB86C'; break;
                    case 'Selesai': color = '#6FAF8F'; break;
                    default: color = '#6b7280';
                }
                
                const marker = L.circleMarker(
                    [parseFloat(report.latitude), parseFloat(report.longitude)],
                    {
                        radius: 12,
                        fillColor: color,
                        color: '#ffffff',
                        weight: 4,
                        opacity: 1,
                        fillOpacity: 0.9
                    }
                );
                
                let photoHtml = '';
                if (report.photo_path) {
                    photoHtml = `<img src="${report.photo_path}" class="w-full h-32 object-cover rounded-lg mb-3" alt="Foto">`;
                }
                
                const popupContent = `
                    <div class="p-1 min-w-[250px]">
                        <h4 class="font-bold text-ecocare-dark mb-2">${report.category}</h4>
                        <p class="text-sm text-gray-600 mb-3">${report.description}</p>
                        ${photoHtml}
                        <div class="text-xs text-gray-500 space-y-1">
                            <p><i class="fas fa-user mr-1 text-ecocare-primary"></i> ${report.user_name}</p>
                            <p><i class="fas fa-map-marker-alt mr-1 text-ecocare-primary"></i> ${report.location_address}</p>
                            <p><i class="fas fa-clock mr-1 text-ecocare-primary"></i> ${new Date(report.created_at).toLocaleDateString('id-ID')}</p>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                marker.addTo(map);
                markers.push(marker);
            }
        });
        
        // Set map center and bounds
        function setAdminMapCenter() {
            if (markers.length > 0) {
                if (markers.length > 1) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    map.setView(markers[0].getLatLng(), 15);
                }
            } else {
                // Fallback ke lokasi umum jika tidak ada laporan
                map.setView([-7.4244, 109.2300], 12);
            }
        }
        
        setAdminMapCenter();
    </script>
</body>
</html>