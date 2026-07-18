<?php
require 'config.php';
require_login();

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : null;

// Simple notifications: always empty
$unread_count = 0;
$notifications = [];

// Get user's community info
$my_community = null;
$community_member_count = 0;
try {
    $stmt = $pdo->prepare("SELECT r.*, cm.joined_at FROM reports r JOIN community_members cm ON r.id = cm.report_id WHERE cm.user_id = ? ORDER BY cm.joined_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $my_community = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($my_community) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE report_id = ?");
        $stmt->execute([$my_community['id']]);
        $community_member_count = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $my_community = null;
    $community_member_count = 0;
}

// Get user's upcoming action
$upcoming_action = null;
try {
    if ($my_community) {
        $stmt = $pdo->prepare("SELECT * FROM community_actions WHERE report_id = ? AND status IN ('planned', 'active') ORDER BY target_date ASC, created_at ASC LIMIT 1");
        $stmt->execute([$my_community['id']]);
        $upcoming_action = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $upcoming_action = null;
}

// Get user's contribution count this month
$monthly_contributions = 0;
try {
    $current_month = date('Y-m');
    // Count reports created this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$user_id, $current_month]);
    $reports_this_month = $stmt->fetchColumn();
    
    // Count community contributions this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_contributions cc JOIN community_actions ca ON cc.action_id = ca.id WHERE cc.user_id = ? AND DATE_FORMAT(cc.created_at, '%Y-%m') = ?");
    $stmt->execute([$user_id, $current_month]);
    $contributions_this_month = $stmt->fetchColumn();
    
    $monthly_contributions = ($reports_this_month * 25) + ($contributions_this_month * 15);
} catch (PDOException $e) {
    $monthly_contributions = 0;
}

// Get upcoming actions
$upcoming_actions = [];
try {
    $stmt = $pdo->prepare("
        SELECT ca.*, r.title as report_title
        FROM community_actions ca
        LEFT JOIN reports r ON ca.report_id = r.id
        WHERE ca.status IN ('planned', 'active')
        ORDER BY ca.target_date ASC, ca.created_at ASC
        LIMIT 5
    ");
    $stmt->execute();
    $upcoming_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $upcoming_actions = [];
}

// Get activity feed (user's reports + notifications, unified)
$activity_feed = [];
try {
    // Get user reports for activity feed
    $stmt = $pdo->prepare("SELECT 
        id, 
        title, 
        status, 
        created_at, 
        'report' as type 
        FROM reports 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5");
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get notifications for activity feed
    $activity_feed = array_merge($reports, $notifications);
    
    // Sort activity feed by created_at
    usort($activity_feed, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 8 items total
    $activity_feed = array_slice($activity_feed, 0, 8);
} catch (PDOException $e) {
    $activity_feed = [];
}

// Get statistics
try {
    // Total reports
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_reports = $stmt->fetchColumn();

    // Reports by status
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Baru'");
    $stmt->execute([$user_id]);
    $reports_baru = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Diproses'");
    $stmt->execute([$user_id]);
    $reports_diproses = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Selesai'");
    $stmt->execute([$user_id]);
    $reports_selesai = $stmt->fetchColumn();

    // Get user reports
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$user_id]);
    $recent_reports = $stmt->fetchAll();

    // Get all reports for map
    $stmt = $pdo->prepare("SELECT * FROM reports");
    $map_reports = $stmt->fetchAll();

} catch(PDOException $e) {
    $total_reports = 0;
    $reports_baru = 0;
    $reports_diproses = 0;
    $reports_selesai = 0;
    $recent_reports = [];
    $map_reports = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - EcoCare+</title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #43A047;
            border-radius: 3px;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-slideInLeft {
            animation: slideInLeft 0.4s ease-out forwards;
        }

        /* Gradient Text */
        .text-gradient {
            background: linear-gradient(135deg, #2E7D32 0%, #43A047 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Card Hover Effect */
        .card-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-lift:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(46, 125, 50, 0.1), 0 10px 10px -5px rgba(46, 125, 50, 0.04);
        }

        /* Sidebar Active Menu */
        .sidebar-active {
            background: linear-gradient(135deg, #2E7D32 0%, #43A047 100%);
            color: white !important;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        /* Status Badges */
        .status-baru { background: linear-gradient(135deg, #EF5350, #E53935); }
        .status-diproses { background: linear-gradient(135deg, #FFB74D, #FFB300); }
        .status-selesai { background: linear-gradient(135deg, #66BB6A, #4CAF50); }

        /* Floating Action Button */
        .fab-menu {
            transition: all 0.3s ease;
        }

        .fab-menu.open .fab-options {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .fab-options {
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        /* Map Container */
        #dashboard-map {
            height: 400px;
            border-radius: 16px;
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
            <!-- Logo -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-gray-900">EcoCare+</h1>
                        <p class="text-xs text-gray-500 font-medium">Purwokerto Green Hub</p>
                    </div>
                </div>
            </div>

            <!-- Menu -->
            <nav class="p-4 flex-1 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard_pengguna.php" class="sidebar-active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 font-medium transition-all duration-200">
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

            <!-- Profile Bottom -->
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
                    <!-- Left Header -->
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
                        <h1 class="text-2xl font-bold text-gray-900 mt-2">Dashboard Pengguna</h1>
                        <p class="text-gray-500 text-sm">Selamat datang, <?= htmlspecialchars($user_name) ?></p>
                    </div>

                    <!-- Right Header -->
                    <div class="flex items-center gap-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button id="notifDropdownBtn" class="w-11 h-11 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-lightgreen hover:text-primary transition-all relative">
                                <i class="fas fa-bell text-lg"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notifDropdown" class="hidden absolute right-0 top-14 bg-white rounded-2xl shadow-2xl border border-gray-100 w-96 z-50 max-h-[500px] overflow-y-auto">
                                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                                    <h4 class="text-lg font-extrabold text-gray-900"><i class="fas fa-bell mr-2 text-primary"></i> Notifikasi</h4>
                                    <span class="text-xs text-primary font-semibold"><?= $unread_count ?> belum dibaca</span>
                                </div>
                                <div class="p-3">
                                    <?php if (empty($notifications)): ?>
                                        <div class="text-center py-10 text-gray-500">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p>Tidak ada notifikasi</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notif): ?>
                                        <div class="p-4 mb-3 rounded-xl <?php echo $notif['is_read'] ? 'bg-gray-50' : 'bg-lightgreen' ?> border border-gray-100">
                                            <div class="flex gap-3">
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $notif['is_read'] ? 'bg-gray-200 text-gray-500' : 'bg-primary/20 text-primary' ?>">
                                                    <i class="fas <?php echo htmlspecialchars($notif['icon']) ?>"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($notif['title']) ?></p>
                                                    <?php if ($notif['description']): ?>
                                                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($notif['description']) ?></p>
                                                    <?php endif; ?>
                                                    <p class="text-xs text-gray-400 mt-1"><i class="far fa-clock mr-1"></i><?php echo date('d M H:i', strtotime($notif['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4 border-t border-gray-100 text-center">
                                    <a href="?mark_read=1" class="text-sm text-primary font-semibold hover:underline">Tandai semua sebagai dibaca</a>
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <button class="w-11 h-11 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-lightgreen hover:text-primary transition-all">
                            <i class="fas fa-cog text-lg"></i>
                        </button>

                        <!-- Profile -->
                        <div class="relative">
                            <button id="profileDropdownBtn" class="flex items-center gap-3 p-2 rounded-2xl hover:bg-gray-50 transition-all">
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
                                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-lightgreen transition-all">
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
                <!-- Hero Section with Dynamic Info -->
                <section class="animate-fadeInUp bg-gradient-to-r from-primary via-secondary to-green-600 rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-2xl">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

                    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-10 items-center">
                        <!-- Left: Welcome & CTA -->
                        <div class="lg:col-span-2">
                            <p class="text-white/80 font-medium mb-3 flex items-center gap-2">
                                <i class="fas fa-leaf"></i> Selamat Datang Kembali, 
                                <span class="font-bold text-yellow-200"><?= htmlspecialchars($user_name) ?></span>!
                            </p>
                            <h1 class="text-4xl lg:text-5xl font-extrabold mb-4 leading-tight">
                                Mari Jaga Lingkungan Bersama 🌿
                            </h1>
                            <p class="text-white/90 mb-8 text-lg max-w-2xl">
                                Setiap laporan kamu adalah langkah kecil untuk perubahan besar. Ayo mulai hari ini!
                            </p>
                            <div class="flex flex-wrap gap-4">
                                <a href="submit_report.php" class="group px-8 py-4 bg-white text-primary font-bold rounded-2xl shadow-xl hover:bg-yellow-50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex items-center gap-3">
                                    <i class="fas fa-plus-circle text-xl group-hover:rotate-90 transition-transform duration-300"></i>
                                    Buat Laporan Baru
                                </a>
                                <a href="map.php" class="px-8 py-4 bg-white/20 border border-white/30 text-white font-bold rounded-2xl backdrop-blur hover:bg-white/30 transition-all duration-300 flex items-center gap-3">
                                    <i class="fas fa-map-marked-alt text-lg"></i>
                                    Lihat Peta
                                </a>
                            </div>
                        </div>

                        <!-- Right: Dynamic Stats Cards -->
                        <div class="space-y-4">
                            <!-- Contribution Progress -->
                            <div class="bg-white/20 backdrop-blur rounded-2xl p-5 border border-white/20">
                                <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"><i class="fas fa-fire"></i> Kontribusi Bulan Ini</h4>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-2xl font-extrabold"><?= $monthly_contributions ?></span>
                                    <span class="text-xs text-white/80">pts</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-300 h-full rounded-full" style="width: min(<?= min($monthly_contributions / 2, 100) ?>,100)%"></div>
                                </div>
                            </div>

                            <!-- Community Info -->
                            <div class="bg-white/20 backdrop-blur rounded-2xl p-5 border border-white/20">
                                <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"><i class="fas fa-users"></i> Komunitas Saya</h4>
                                <p class="text-2xl font-extrabold mb-1">
                                    <?= $my_community ? htmlspecialchars(substr($my_community['title'],0,18)) : 'Belum Gabung' ?>
                                </p>
                                <p class="text-xs text-white/80 flex items-center gap-1">
                                    <i class="fas fa-user-friends"></i> <?= $community_member_count ?> Anggota
                                </p>
                            </div>

                            <!-- Upcoming Action -->
                            <div class="bg-white/20 backdrop-blur rounded-2xl p-5 border border-white/20">
                                <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"><i class="fas fa-calendar-star"></i> Aksi Mendatang</h4>
                                <?php if (!empty($upcoming_actions)): ?>
                                    <?php $first_action = $upcoming_actions[0]; ?>
                                    <a href="action_detail.php?id=<?= $first_action['id'] ?>" class="block">
                                        <p class="font-semibold mb-1 hover:underline"><?= htmlspecialchars($first_action['title']) ?></p>
                                        <p class="text-xs text-white/80 flex items-center gap-1">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= $first_action['target_date'] ? date('d M Y', strtotime($first_action['target_date'])) : 'Segera' ?>
                                        </p>
                                    </a>
                                <?php else: ?>
                                    <p class="font-semibold mb-1">Belum ada aksi mendatang</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Statistics Cards -->
                <section class="animate-fadeInUp" style="animation-delay: 0.1s;">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Total Reports -->
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center text-blue-700 text-3xl">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Total</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1 counter" data-target="<?= $total_reports ?>">0</p>
                            <p class="text-base font-semibold text-gray-600">Laporan Kamu</p>
                        </div>

                        <!-- Baru -->
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-red-200 rounded-2xl flex items-center justify-center text-red-700 text-3xl">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Baru</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1 counter" data-target="<?= $reports_baru ?>">0</p>
                            <p class="text-base font-semibold text-gray-600">Menunggu Respon</p>
                        </div>

                        <!-- Diproses -->
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center text-yellow-700 text-3xl">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Diproses</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1 counter" data-target="<?= $reports_diproses ?>">0</p>
                            <p class="text-base font-semibold text-gray-600">Sedang Diperiksa</p>
                        </div>

                        <!-- Selesai -->
                        <div class="card-lift bg-white border border-gray-100 rounded-3xl p-7 shadow-xl">
                            <div class="flex items-center justify-between mb-5">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center text-green-700 text-3xl">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-500">Selesai</span>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-900 mb-1 counter" data-target="<?= $reports_selesai ?>">0</p>
                            <p class="text-base font-semibold text-gray-600">Selesai Ditangani</p>
                        </div>
                    </div>
                </section>

                <!-- Map & Timeline Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Map -->
                    <section class="lg:col-span-2 animate-fadeInUp" style="animation-delay: 0.2s;">
                        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h2 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                                        <i class="fas fa-map-marked-alt text-primary"></i>
                                        Peta Interaktif Purwokerto
                                    </h2>
                                    <p class="text-gray-500 text-sm mt-1">Real-time environmental monitoring</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1 text-xs font-semibold bg-green-100 text-green-700 px-3 py-1 rounded-full">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        Live Updates
                                    </span>
                                </div>
                            </div>
                            <div id="dashboard-map" class="shadow-lg"></div>
                        </div>
                    </section>

                    <!-- Timeline -->
                    <section class="animate-fadeInUp" style="animation-delay: 0.3s;">
                        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100 h-full">
                            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-clock-rotate-left text-primary"></i>
                                Aktivitas Terbaru
                            </h2>

                            <div class="space-y-4">
                                <?php if (empty($activity_feed)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox text-3xl text-gray-300 mb-3"></i>
                                        <p class="text-sm">Belum ada aktivitas</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($activity_feed as $activity): ?>
                                        <div class="flex gap-4 p-4 rounded-2xl border border-gray-100 bg-gray-50 hover:bg-lightgreen transition-all duration-200">
                                            <!-- Icon -->
                                            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white flex-shrink-0 <?php 
                                                if (isset($activity['type']) && $activity['type'] == 'report') {
                                                    echo 'bg-gradient-to-br from-blue-500 to-blue-600';
                                                } else if (isset($activity['type']) && in_array($activity['type'], ['report_update','community'])) {
                                                    echo 'bg-gradient-to-br from-yellow-500 to-orange-500';
                                                } else {
                                                    echo 'bg-gradient-to-br from-primary to-secondary';
                                                }
                                            ?>">
                                                <i class="fas <?php 
                                                    if (isset($activity['type']) && $activity['type'] == 'report') {
                                                        echo 'fa-file-lines';
                                                    } else if (isset($activity['type']) && $activity['type'] == 'report_update') {
                                                        echo 'fa-sync';
                                                    } else if (isset($activity['icon'])) {
                                                        echo $activity['icon'];
                                                    } else {
                                                        echo 'fa-bell';
                                                    }
                                                ?>"></i>
                                            </div>
                                            
                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900">
                                                    <?php 
                                                        if (isset($activity['type']) && $activity['type'] == 'report') {
                                                            echo htmlspecialchars('Kamu mengirim laporan: ' . $activity['title']);
                                                        } else if (isset($activity['title'])) {
                                                            echo htmlspecialchars($activity['title']);
                                                        } else {
                                                            echo 'Notifikasi baru';
                                                        }
                                                    ?>
                                                </p>
                                                <?php if (isset($activity['description'])): ?>
                                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2"><?= htmlspecialchars($activity['description']) ?></p>
                                                <?php endif; ?>
                                                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                                                    <i class="far fa-clock"></i> 
                                                    <?= date('d M Y H:i', strtotime($activity['created_at'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="mt-6 p-4 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-2xl border border-primary/20">
                                <h4 class="font-bold text-primary mb-3">Capaian Minggu Ini</h4>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-700">Laporan Selesai</span>
                                    <span class="text-xs font-extrabold text-primary">
                                        <?= $total_reports > 0 ? round(($reports_selesai / $total_reports) * 100) : 0 ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: <?= $total_reports > 0 ? round(($reports_selesai / $total_reports) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Laporan Saya -->
                <section id="laporan" class="animate-fadeInUp" style="animation-delay: 0.4s;">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-file-alt text-primary"></i>
                                Laporan Saya
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Laporan terbaru yang kamu buat</p>
                        </div>
                        <a href="laporan_saya.php" class="text-primary font-semibold hover:text-secondary transition-all flex items-center gap-1">
                            Lihat Semua <i class="fas fa-arrow-right text-sm"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($recent_reports)): ?>
                            <div class="col-span-full text-center py-16 bg-white rounded-3xl border-2 border-dashed border-gray-300">
                                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Laporan</h3>
                                <p class="text-gray-500 mb-6">Yuk, buat laporan pertama kamu untuk lingkungan!</p>
                                <a href="submit_report.php" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-2xl hover:bg-secondary transition-all">
                                    <i class="fas fa-plus"></i>
                                    Buat Laporan
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_reports as $report): ?>
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
                                                case 'Baru': $status_class = 'status-baru text-white'; break;
                                                case 'Diproses': $status_class = 'status-diproses text-white'; break;
                                                case 'Selesai': $status_class = 'status-selesai text-white'; break;
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
                                        <button onclick="showReportDetail(<?= htmlspecialchars(json_encode($report)) ?>)" class="mt-4 w-full py-2.5 bg-lightgreen text-primary font-semibold rounded-xl hover:bg-primary hover:text-white transition-all">
                                            Lihat Detail
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Edukasi Section -->
                <section class="animate-fadeInUp" style="animation-delay: 0.5s;">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-book text-primary"></i>
                                Edukasi Terbaru
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Pelajari tips & trik menjaga lingkungan</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="h-40 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                <i class="fas fa-recycle text-6xl text-white"></i>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-gray-900 mb-2">Panduan Daur Ulang</h3>
                                <p class="text-sm text-gray-500 mb-4">Pelajari cara memilah dan mendaur ulang sampah dengan benar</p>
                                <a href="edukasi_sampah.php" class="text-primary font-semibold text-sm hover:underline">Baca Selengkapnya →</a>
                            </div>
                        </div>

                        <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="h-40 bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-6xl text-white"></i>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-gray-900 mb-2">Kurangi Penggunaan Plastik</h3>
                                <p class="text-sm text-gray-500 mb-4">Tips mengurangi penggunaan plastik sekali pakai</p>
                                <a href="edukasi_plastik.php" class="text-primary font-semibold text-sm hover:underline">Baca Selengkapnya →</a>
                            </div>
                        </div>

                        <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="h-40 bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center">
                                <i class="fas fa-water text-6xl text-white"></i>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-gray-900 mb-2">Jaga Kebersihan Sungai</h3>
                                <p class="text-sm text-gray-500 mb-4">Tips menjaga sungai tetap bersih dan sehat</p>
                                <a href="edukasi_sungai.php" class="text-primary font-semibold text-sm hover:underline">Baca Selengkapnya →</a>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>



    <!-- Report Detail Modal -->
    <div id="reportDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-3xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-xl font-bold text-gray-900">Detail Laporan</h3>
                <button onclick="closeReportModal()" class="w-11 h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all">
                    <i class="fas fa-times text-lg text-gray-600"></i>
                </button>
            </div>
            <div id="reportDetailContent" class="p-6"></div>
        </div>
    </div>

    <script>
        // Toggle Mobile Sidebar
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // Counter Animation
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 30);
        }

        // Initialize counters when page loads
        window.addEventListener('load', () => {
            document.querySelectorAll('.counter').forEach(el => {
                const target = parseInt(el.getAttribute('data-target'));
                animateCounter(el, target);
            });

            // Attach mobile menu button listener
            document.getElementById('mobileMenuBtn')?.addEventListener('click', toggleMobileSidebar);
        });



        // Profile Dropdown
        document.getElementById('profileDropdownBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Notifications Dropdown
        document.getElementById('notifDropdownBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notifDropdown').classList.toggle('hidden');
            // When opening, mark as read via AJAX or direct, but for simplicity, mark on open:
            // Auto-mark as read when opening dropdown is opened
            if (!document.getElementById('notifDropdown').classList.contains('hidden')) {
                // We'll just rely on the link "Tandai semua", or auto via URL
                // For now, we just toggle and close both dropdowns
                document.getElementById('profileDropdown').classList.add('hidden');
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#notifDropdown') && !e.target.closest('#notifDropdownBtn')) {
                document.getElementById('notifDropdown').classList.add('hidden');
            }
            if (!e.target.closest('#profileDropdown') && !e.target.closest('#profileDropdownBtn')) {
                document.getElementById('profileDropdown').classList.add('hidden');
            }
        });

        // Initialize Map
        let map;
        document.addEventListener('DOMContentLoaded', function() {
            map = L.map('dashboard-map').setView([-7.4245, 109.2401], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Add markers
            const reports = <?= json_encode($map_reports) ?>;
            reports.forEach(function(report) {
                if (report.latitude && report.longitude) {
                    let color = '#EF5350';
                    if (report.status === 'Diproses') color = '#FFB300';
                    if (report.status === 'Selesai') color = '#4CAF50';

                    const customIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background:${color};width:24px;height:24px;border-radius:50%;border:3px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.2);"></div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });

                    L.marker([report.latitude, report.longitude], { icon: customIcon })
                        .addTo(map)
                        .bindPopup(`<div style="font-family:Poppins;padding:8px;"><strong style="color:#2E7D32;">${report.title}</strong><br><small style="color:#666;">${report.category}</small></div>`);
                }
            });
        });

        // Report Detail
        function showReportDetail(report) {
            const modal = document.getElementById('reportDetailModal');
            const content = document.getElementById('reportDetailContent');

            let statusClass = '';
            switch(report.status) {
                case 'Baru': statusClass = 'status-baru text-white'; break;
                case 'Diproses': statusClass = 'status-diproses text-white'; break;
                case 'Selesai': statusClass = 'status-selesai text-white'; break;
            }

            content.innerHTML = `
                <div class="space-y-6">
                    ${report.photo_path ? `<img src="${report.photo_path}" class="w-full h-56 object-cover rounded-2xl">` : ''}
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold px-4 py-1 bg-lightgreen text-primary rounded-full">${report.category}</span>
                        <span class="text-sm font-bold px-4 py-1 rounded-full ${statusClass}">${report.status}</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-extrabold text-gray-900 mb-2">${report.title}</h3>
                        <p class="text-gray-600">${report.description}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <span class="text-xs text-gray-500 block mb-1">Lokasi</span>
                            <p class="font-semibold text-gray-900">${report.location_address}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <span class="text-xs text-gray-500 block mb-1">Tanggal</span>
                            <p class="font-semibold text-gray-900">${new Date(report.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})}</p>
                        </div>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeReportModal() {
            document.getElementById('reportDetailModal').classList.add('hidden');
            document.getElementById('reportDetailModal').classList.remove('flex');
        }
        document.getElementById('reportDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeReportModal();
        });
    </script>
</body>
</html>
