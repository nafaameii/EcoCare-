<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : null;

// Get user's communities
$my_communities = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, cm.joined_at,
               ca.title as action_title, ca.description as action_desc, 
               ca.status as action_status, ca.progress, ca.target_volunteers,
               ca.created_at as action_created_at
        FROM community_members cm
        JOIN reports r ON cm.report_id = r.id
        LEFT JOIN community_actions ca ON r.id = ca.report_id
        WHERE cm.user_id = ?
        ORDER BY cm.joined_at DESC
    ");
    $stmt->execute([$user_id]);
    $my_communities = $stmt->fetchAll();
} catch (PDOException $e) {
    $my_communities = [];
}

// Select first community if not specified
$selected_report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 
                     (!empty($my_communities) ? $my_communities[0]['id'] : null);

$selected_community = null;
$community_stats = [
    'member_count' => 0,
    'target_volunteers' => 0,
    'progress' => 0
];
$community_members = [];

if ($selected_report_id) {
    try {
        // Get selected community details
        $stmt = $pdo->prepare("
            SELECT r.*, cm.joined_at,
                   ca.title as action_title, ca.description as action_desc, 
                   ca.status as action_status, ca.progress, ca.target_volunteers,
                   ca.created_at as action_created_at
            FROM reports r
            LEFT JOIN community_members cm ON r.id = cm.report_id AND cm.user_id = ?
            LEFT JOIN community_actions ca ON r.id = ca.report_id
            WHERE r.id = ?
        ");
        $stmt->execute([$user_id, $selected_report_id]);
        $selected_community = $stmt->fetch();
        
        if ($selected_community) {
            // Get community members (only masyarakat)
            $stmt = $pdo->prepare("
                SELECT cm.*, u.name, u.profile_pic
                FROM community_members cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.report_id = ? AND u.role = 'masyarakat'
                ORDER BY cm.joined_at DESC
            ");
            $stmt->execute([$selected_report_id]);
            $community_members = $stmt->fetchAll();
            $community_stats['member_count'] = count($community_members);
            
            // Get target volunteers & progress
            $community_stats['target_volunteers'] = $selected_community['target_volunteers'] ?: 0;
            $community_stats['progress'] = $selected_community['progress'] ?: 0;
        }
    } catch (PDOException $e) {
        $selected_community = null;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas Saya - EcoCare+</title>

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
        .glass {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border:1px solid rgba(255,255,255,0.3);
        }
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
                        <a href="laporan_saya.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                            <i class="fas fa-file-alt w-5 text-center"></i>
                            Laporan Saya
                        </a>
                    </li>
                    <li>
                        <a href="komunitas_saya.php" class="sidebar-active flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all duration-200">
                            <i class="fas fa-users w-5 text-center"></i>
                            Komunitas Saya
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all duration-200">
                            <i class="fas fa-cog w-5 text-center"></i>
                            Pengaturan
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
                        <h1 class="text-2xl font-bold text-gray-900 mt-2">Komunitas Saya</h1>
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
                                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-lightgreen transition-all">
                                    <i class="fas fa-cog"></i>
                                    Pengaturan
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
                <?php if (!$selected_community): ?>
                    <!-- No Community -->
                    <section class="animate-fadeInUp bg-gradient-to-r from-primary via-secondary to-green-600 rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-2xl">
                        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>
                        <div class="relative z-10 text-center">
                            <i class="fas fa-users text-8xl mb-6 opacity-80"></i>
                            <h1 class="text-4xl lg:text-5xl font-extrabold mb-4">Anda Belum Bergabung Komunitas</h1>
                            <p class="text-lg text-white/90 mb-8 max-w-2xl mx-auto">
                                Klik tombol Cari Komunitas untuk melihat laporan yang memiliki komunitas, kemudian buka Detail Laporan dan pilih Gabung Komunitas.
                            </p>
                            <a href="map.php" class="inline-flex items-center gap-3 px-10 py-4 bg-white text-primary font-bold rounded-2xl shadow-xl hover:bg-yellow-50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                                <i class="fas fa-map-marked-alt"></i> Cari Komunitas
                            </a>
                        </div>
                    </section>
                <?php else: ?>
                    <!-- Hero Section -->
                    <section class="animate-fadeInUp bg-gradient-to-r from-primary via-secondary to-green-600 rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-2xl">
                        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>
                        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-10 items-center">
                            <div class="lg:col-span-2">
                                <p class="text-white/80 font-medium mb-3 flex items-center gap-2">
                                    <i class="fas fa-leaf"></i> Komunitas Saya
                                </p>
                                <h1 class="text-4xl lg:text-5xl font-extrabold mb-4 leading-tight">
                                    <?= $selected_community['action_title'] ? htmlspecialchars($selected_community['action_title']) : 'Aksi ' . htmlspecialchars($selected_community['category']) ?>
                                </h1>
                                <p class="text-white/90 mb-8 text-lg max-w-2xl">
                                    <?= $selected_community['action_desc'] ? htmlspecialchars($selected_community['action_desc']) : htmlspecialchars(substr($selected_community['description'], 0, 150)) ?>
                                </p>
                                <div class="flex flex-wrap gap-4">
                                    <a href="community.php?id=<?= $selected_report_id ?>" class="group px-10 py-4 bg-white text-primary font-bold rounded-2xl shadow-xl hover:bg-yellow-50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex items-center gap-3">
                                        <i class="fas fa-door-open text-xl"></i>
                                        Buka Komunitas
                                    </a>
                                    <a href="map.php" class="px-10 py-4 bg-white/20 border border-white/30 text-white font-bold rounded-2xl backdrop-blur hover:bg-white/30 transition-all duration-300 flex items-center gap-3">
                                        <i class="fas fa-search text-lg"></i>
                                        Cari Komunitas Lain
                                    </a>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-white/20 backdrop-blur rounded-2xl p-5 border border-white/20">
                                    <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"><i class="fas fa-fire"></i> Progress Aksi</h4>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-2xl font-extrabold"><?= $community_stats['progress'] ?>%</span>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-yellow-400 to-yellow-300 h-full rounded-full" style="width: <?= $community_stats['progress'] ?>%"></div>
                                    </div>
                                </div>
                                <div class="bg-white/20 backdrop-blur rounded-2xl p-5 border border-white/20">
                                    <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"><i class="fas fa-users"></i> Anggota</h4>
                                    <p class="text-2xl font-extrabold mb-1"><?= $community_stats['member_count'] ?></p>
                                    <p class="text-xs text-white/80 flex items-center gap-1">
                                        <i class="fas fa-user-check"></i> Aktif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Statistics Cards -->
                    <section class="animate-fadeInUp" style="animation-delay: 0.1s;">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center text-blue-700 text-3xl">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500">Anggota</span>
                                </div>
                                <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $community_stats['member_count'] ?></p>
                                <p class="text-base font-semibold text-gray-600">Jumlah Anggota</p>
                            </div>
                            <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center text-yellow-700 text-3xl">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500">Target</span>
                                </div>
                                <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $community_stats['target_volunteers'] ?: '-' ?></p>
                                <p class="text-base font-semibold text-gray-600">Target Relawan</p>
                            </div>
                            <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center text-green-700 text-3xl">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500">Progress</span>
                                </div>
                                <p class="text-4xl font-extrabold text-gray-900 mb-1"><?= $community_stats['progress'] ?>%</p>
                                <p class="text-base font-semibold text-gray-600">Progress Aksi</p>
                            </div>
                        </div>
                    </section>

                    <!-- Members List -->
                    <section class="animate-fadeInUp" style="animation-delay: 0.2s;">
                        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100">
                            <h2 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-user-friends text-blue-600"></i>
                                Daftar Relawan
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php if (empty($community_members)): ?>
                                    <div class="col-span-full text-center py-8 text-gray-500">
                                        <i class="fas fa-users text-3xl text-gray-300 mb-3"></i>
                                        <p class="text-sm">Belum ada relawan</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($community_members as $mem): ?>
                                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-lightgreen transition-all duration-200">
                                            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary flex-shrink-0">
                                                <?php if ($mem['profile_pic'] && file_exists($mem['profile_pic'])): ?>
                                                    <img src="<?= htmlspecialchars($mem['profile_pic']) ?>" class="w-full h-full object-cover" alt="Profile">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                                                        <?= strtoupper(substr($mem['name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($mem['name']) ?></p>
                                                <p class="text-xs text-gray-500">Bergabung <?= date('d M Y', strtotime($mem['joined_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
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
    </script>
</body>
</html>
