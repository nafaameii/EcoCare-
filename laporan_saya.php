<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : null;

// Handle filter and search
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Semua';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get user reports
$reports = [];
try {
    $sql = "SELECT * FROM reports WHERE user_id = ?";
    $params = [$user_id];

    if ($status_filter !== 'Semua') {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }

    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR description LIKE ? OR location_address LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    $reports = [];
}

// Get statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_reports = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Baru'");
    $stmt->execute([$user_id]);
    $reports_baru = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Diproses'");
    $stmt->execute([$user_id]);
    $reports_diproses = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Selesai'");
    $stmt->execute([$user_id]);
    $reports_selesai = $stmt->fetchColumn();
} catch(PDOException $e) {
    $total_reports = 0;
    $reports_baru = 0;
    $reports_diproses = 0;
    $reports_selesai = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Saya - EcoCare+</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { font-family: 'Poppins', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #43A047; border-radius: 3px; }
        .sidebar-active {
            background: linear-gradient(135deg, #2E7D32 0%, #43A047 100%);
            color: white !important;
            box-shadow: 0 4px 12px rgba(46,125,50,0.3);
        }
        .card-lift { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .card-lift:hover { transform: translateY(-6px); box-shadow: 0 20px 25px -5px rgba(46,125,50,0.1), 0 10px 10px -5px rgba(46,125,50,0.04); }
        .animate-fadeInUp { animation: fadeInUp 0.6s ease-out forwards; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2E7D32',
                        secondary: '#43A047',
                        lightgreen: '#E8F5E9',
                        success: '#4CAF50',
                        warning: '#FFB300',
                        danger: '#E53935',
                        bg: '#F8FAFC'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bg min-h-screen overflow-x-hidden">
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleMobileSidebar()"></div>

    <!-- Wrapper -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-gray-100 fixed h-full z-40 shadow-sm flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-gray-900">EcoCare+</h1>
                        <p class="text-xs text-gray-500 font-medium">Purwokerto Green Hub</p>
                    </div>
                </div>
            </div>
            <nav class="p-4 flex-1 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard_pengguna.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                            <i class="fas fa-home w-5 text-center"></i>
                            Beranda
                        </a>
                    </li>
                    <li>
                        <a href="map.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                            <i class="fas fa-map-marked-alt w-5 text-center"></i>
                            Peta Interaktif
                        </a>
                    </li>
                    <li>
                        <a href="laporan_saya.php" class="sidebar-active flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all duration-200">
                            <i class="fas fa-file-alt w-5 text-center"></i>
                            Laporan Saya
                        </a>
                    </li>
                    <li>
                        <a href="komunitas_saya.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                            <i class="fas fa-users w-5 text-center"></i>
                            Komunitas Saya
                        </a>
                    </li>
                </ul>
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <ul class="space-y-2">
                        <li>
                            <a href="user_profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                                <i class="fas fa-user-cog w-5 text-center"></i>
                                Profil
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 font-medium hover:bg-red-50 transition-all duration-200">
                                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <a href="user_profile.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white transition-all">
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary flex-shrink-0">
                        <?php if ($user_profile_pic && file_exists($user_profile_pic)): ?>
                            <img src="<?= htmlspecialchars($user_profile_pic) ?>" class="w-full h-full object-cover" alt="Profile">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                                <?= strtoupper(substr($user_name, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($user_name) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($user_email) ?></p>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-72">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-100 px-8 py-4 sticky top-0 z-30">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-4">
                            <button id="mobileMenuBtn" class="lg:hidden w-10 h-10 rounded-xl bg-lightgreen text-primary flex items-center justify-center">
                                <i class="fas fa-bars"></i>
                            </button>
                            <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-primary transition flex items-center gap-2">
                                <i class="fas fa-home"></i>
                                <span>Beranda</span>
                            </a>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mt-2">Laporan Saya</h1>
                        <p class="text-gray-500 text-sm">Selamat datang, <?= htmlspecialchars($user_name) ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Profile -->
                        <div class="relative">
                            <button id="profileDropdownBtn" class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 transition-all">
                                <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-primary flex-shrink-0">
                                    <?php if ($user_profile_pic && file_exists($user_profile_pic)): ?>
                                        <img src="<?= htmlspecialchars($user_profile_pic) ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user_name) ?></span>
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <div id="profileDropdown" class="hidden absolute right-0 top-14 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 w-56 z-50">
                                <a href="user_profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-lightgreen transition-all">
                                    <i class="fas fa-user"></i>
                                    Edit Profil
                                </a>
                                <hr class="my-2">
                                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Keluar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8 space-y-8">
                <!-- Statistics Cards -->
                <section class="animate-fadeInUp" style="animation-delay: 0.1s;">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center text-blue-700 text-3xl">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Total</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $total_reports ?></p>
                            <p class="text-base font-semibold text-gray-600">Laporan Kamu</p>
                        </div>
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-red-200 rounded-2xl flex items-center justify-center text-red-700 text-3xl">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Baru</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $reports_baru ?></p>
                            <p class="text-base font-semibold text-gray-600">Menunggu Respon</p>
                        </div>
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center text-yellow-700 text-3xl">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Diproses</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $reports_diproses ?></p>
                            <p class="text-base font-semibold text-gray-600">Sedang Diperiksa</p>
                        </div>
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center text-green-700 text-3xl">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Selesai</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $reports_selesai ?></p>
                            <p class="text-base font-semibold text-gray-600">Selesai Ditangani</p>
                        </div>
                    </div>
                </section>

                <!-- Search and Filter -->
                <section class="animate-fadeInUp" style="animation-delay: 0.2s;">
                    <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100">
                        <form method="GET" action="laporan_saya.php" class="flex flex-wrap gap-4 items-center">
                            <div class="flex-1 min-w-[200px]">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" name="search" placeholder="Cari laporan..." 
                                           value="<?= htmlspecialchars($search) ?>"
                                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>
                            <div class="min-w-[150px]">
                                <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="Semua" <?= $status_filter === 'Semua' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="Baru" <?= $status_filter === 'Baru' ? 'selected' : '' ?>>Baru</option>
                                    <option value="Diproses" <?= $status_filter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="Selesai" <?= $status_filter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-6 py-3 bg-primary text-white font-semibold rounded-xl hover:bg-secondary transition-all">
                                    <i class="fas fa-filter mr-2"></i> Filter
                                </button>
                                <a href="laporan_saya.php" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </a>
                            </div>
                            <div class="ml-auto">
                                <a href="submit_report.php" class="px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center gap-2">
                                    <i class="fas fa-plus"></i> Buat Laporan
                                </a>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Reports List -->
                <section class="animate-fadeInUp" style="animation-delay: 0.3s;">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($reports)): ?>
                            <div class="col-span-full text-center py-16 bg-white rounded-3xl border-2 border-dashed border-gray-300">
                                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Laporan</h3>
                                <p class="text-gray-500 mb-6"><?= !empty($search) || $status_filter !== 'Semua' ? 'Tidak ada laporan yang cocok dengan filter.' : 'Yuk, buat laporan pertama kamu untuk lingkungan!' ?></p>
                                <?php if (empty($search) && $status_filter === 'Semua'): ?>
                                    <a href="submit_report.php" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-2xl hover:bg-secondary transition-all">
                                        <i class="fas fa-plus"></i> Buat Laporan
                                    </a>
                                <?php else: ?>
                                    <a href="laporan_saya.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-2xl hover:bg-gray-200 transition-all">
                                        <i class="fas fa-redo"></i> Reset Filter
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                                    <?php if ($report['photo_path'] && file_exists($report['photo_path'])): ?>
                                        <div class="h-48 bg-gray-100">
                                            <img src="<?= htmlspecialchars($report['photo_path']) ?>" alt="Report" class="w-full h-full object-cover">
                                        </div>
                                    <?php else: ?>
                                        <div class="h-48 bg-gradient-to-br from-lightgreen to-green-100 flex items-center justify-center">
                                            <i class="fas fa-image text-4xl text-primary/40"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="p-6">
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="text-xs font-bold text-primary bg-lightgreen px-3 py-1 rounded-full">
                                                <?= htmlspecialchars($report['category']) ?>
                                            </span>
                                            <?php
                                            $status_class = '';
                                            switch($report['status']) {
                                                case 'Baru': $status_class = 'bg-gradient-to-r from-red-500 to-red-600 text-white'; break;
                                                case 'Diproses': $status_class = 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white'; break;
                                                case 'Selesai': $status_class = 'bg-gradient-to-r from-green-500 to-green-600 text-white'; break;
                                                default: $status_class = 'bg-gradient-to-r from-gray-500 to-gray-600 text-white'; break;
                                            }
                                            ?>
                                            <span class="text-xs font-bold px-3 py-1 rounded-full <?= $status_class ?>">
                                                <?= htmlspecialchars($report['status']) ?>
                                            </span>
                                        </div>
                                        <h3 class="font-bold text-gray-900 mb-2 line-clamp-2"><?= htmlspecialchars($report['title']) ?></h3>
                                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?= htmlspecialchars($report['description']) ?></p>
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars(substr($report['location_address'], 0, 20)) ?>...
                                            </span>
                                            <span>
                                                <?= date('d M Y', strtotime($report['created_at'])) ?>
                                            </span>
                                        </div>
                                        <a href="report_detail.php?id=<?= $report['id'] ?>" class="mt-4 w-full py-2.5 bg-lightgreen text-primary font-semibold rounded-xl hover:bg-primary hover:text-white transition-all inline-flex items-center justify-center">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // Profile Dropdown
        document.getElementById('profileDropdownBtn')?.addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });

        // Attach mobile menu button listener
        document.getElementById('mobileMenuBtn')?.addEventListener('click', toggleMobileSidebar);
    </script>
</body>
</html>
