<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors to user

require 'config.php';
require_admin();

// Get statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];
    
    // Total reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
    $total_reports = $stmt->fetch()['total'];
    
    // Reports by status
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE status = ?");
    $stmt->execute(['Baru']);
    $reports_baru = $stmt->fetch()['total'];
    
    $stmt->execute(['Diproses']);
    $reports_diproses = $stmt->fetch()['total'];
    
    $stmt->execute(['Selesai']);
    $reports_selesai = $stmt->fetch()['total'];
    
    // Get recent reports for the table
    $stmt = $pdo->query("
        SELECT r.*, u.name as user_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $recent_reports = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Admin dashboard DB error: " . $e->getMessage());
    $total_users = 0;
    $total_reports = 0;
    $reports_baru = 0;
    $reports_diproses = 0;
    $reports_selesai = 0;
    $recent_reports = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .sidebar { transition: all 0.3s ease; }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%);
            color: white;
        }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white shadow-lg border-r border-gray-200">
            <!-- Logo -->
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
            
            <!-- Menu -->
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="admin_dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
                        <a href="admin_map.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
            
            <!-- User Info -->
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
            <!-- Navbar -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-ecocare-dark">Dashboard Admin</h1>
                        <p class="text-gray-500 text-sm">Selamat datang, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                            <i class="fas fa-home"></i>
                            <span>Ke Beranda</span>
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="p-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Pengguna</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_users; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Laporan</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_reports; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Laporan Baru</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_baru; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-ecocare-orange to-orange-500 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Diproses</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_diproses; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Selesai</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_selesai; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts and Recent Reports -->
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Chart -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-ecocare-dark mb-6">Statistik Laporan</h3>
                        <canvas id="reportsChart" height="100"></canvas>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-ecocare-dark mb-6">Aksi Cepat</h3>
                        <div class="space-y-3">
                            <a href="admin_reports.php" class="block w-full px-4 py-3 bg-gradient-to-r from-red-400 to-red-600 text-white rounded-xl font-medium hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-eye"></i>
                                Lihat Semua Laporan
                            </a>
                            <a href="admin_users.php" class="block w-full px-4 py-3 bg-gradient-to-r from-blue-400 to-blue-600 text-white rounded-xl font-medium hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-users"></i>
                                Kelola Pengguna
                            </a>
                            <a href="admin_map.php" class="block w-full px-4 py-3 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white rounded-xl font-medium hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-map-marked-alt"></i>
                                Peta Monitoring
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Reports Table -->
                <div class="mt-8 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-ecocare-dark">Laporan Terbaru</h3>
                        <a href="admin_reports.php" class="text-ecocare-primary font-medium hover:underline text-sm flex items-center gap-1">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelapor</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($recent_reports)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4 opacity-30"></i>
                                            <p>Belum ada laporan</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_reports as $report): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $report['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($report['user_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                                    <?php echo htmlspecialchars($report['category']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($report['location_address']); ?>">
                                                <?php echo htmlspecialchars(substr($report['location_address'], 0, 40)); ?>...
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                $status_class = '';
                                                switch($report['status']) {
                                                    case 'Baru': $status_class = 'bg-red-100 text-red-700'; break;
                                                    case 'Diproses': $status_class = 'bg-yellow-100 text-yellow-700'; break;
                                                    case 'Selesai': $status_class = 'bg-green-100 text-green-700'; break;
                                                }
                                                ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($report['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y', strtotime($report['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Baru', 'Diproses', 'Selesai'],
                    datasets: [{
                        label: 'Jumlah Laporan',
                        data: [<?php echo $reports_baru; ?>, <?php echo $reports_diproses; ?>, <?php echo $reports_selesai; ?>],
                        backgroundColor: [
                            '#ef4444',
                            '#FFB86C',
                            '#6FAF8F'
                        ],
                        borderWidth: 0,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f0'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>