<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : null;

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'join_community') {
                $community_id = intval($_POST['community_id']);
                $stmt = $pdo->prepare("INSERT IGNORE INTO community_members (community_id, user_id, role) VALUES (?, ?, 'Anggota')");
                $stmt->execute([$community_id, $user_id]);
                $success_message = 'Berhasil bergabung dengan komunitas!';
            } elseif ($_POST['action'] == 'post_discussion') {
                $community_id = intval($_POST['community_id']);
                $message = trim($_POST['message']);
                if (!empty($message)) {
                    $stmt = $pdo->prepare("INSERT INTO community_discussions (community_id, user_id, message) VALUES (?, ?, ?)");
                    $stmt->execute([$community_id, $user_id, $message]);
                    $success_message = 'Pesan diskusi berhasil dikirim!';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get user's communities
$my_communities = [];
try {
    $stmt = $pdo->prepare("SELECT c.*, cm.joined_at, cm.role FROM communities c JOIN community_members cm ON c.id = cm.community_id WHERE cm.user_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$user_id]);
    $my_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    //
}

// Get available communities to join
$available_communities = [];
try {
    $stmt = $pdo->prepare("SELECT c.* FROM communities c WHERE c.id NOT IN (SELECT community_id FROM community_members WHERE user_id = ?) ORDER BY c.created_at DESC");
    $stmt->execute([$user_id]);
    $available_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    //
}

// Get selected community (first one if available)
$selected_community = null;
$selected_community_id = null;
$members = [];
$discussions = [];
$actions = [];
$is_member = false;

if (!empty($my_communities)) {
    $selected_community = $my_communities[0];
    $selected_community_id = $selected_community['id'];
    $is_member = true;
} elseif (!empty($available_communities)) {
    $selected_community = $available_communities[0];
    $selected_community_id = $selected_community['id'];
}

if ($selected_community_id) {
    // Get members
    try {
        $stmt = $pdo->prepare("SELECT cm.*, u.name, u.profile_pic FROM community_members cm JOIN users u ON cm.user_id = u.id WHERE cm.community_id = ? ORDER BY cm.joined_at DESC");
        $stmt->execute([$selected_community_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        //
    }

    // Get discussions
    try {
        $stmt = $pdo->prepare("SELECT cd.*, u.name, u.profile_pic FROM community_discussions cd JOIN users u ON cd.user_id = u.id WHERE cd.community_id = ? ORDER BY cd.created_at DESC");
        $stmt->execute([$selected_community_id]);
        $discussions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        //
    }

    // Get actions
    try {
        $stmt = $pdo->prepare("SELECT ca.*, u.name FROM community_actions ca JOIN users u ON ca.created_by = u.id WHERE ca.community_id = ? ORDER BY ca.action_date DESC");
        $stmt->execute([$selected_community_id]);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        //
    }

    // Check if current user is member
    if (!$is_member) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ?");
            $stmt->execute([$selected_community_id, $user_id]);
            $is_member = $stmt->fetch() !== false;
        } catch (PDOException $e) {
            //
        }
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
        .card-lift {
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .card-lift:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(46,125,50,0.1), 0 10px 10px -5px rgba(46,125,50,0.04);
        }
        .sidebar-active {
            background: linear-gradient(135deg, #2E7D32 0%, #43A047 100%);
            color: white !important;
            box-shadow: 0 4px 12px rgba(46,125,50,0.3);
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
                        success: '#4CAF50'
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
                        <a href="dashboard_pengguna.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all">
                            <i class="fas fa-home w-5 text-center"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="map.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all">
                            <i class="fas fa-map-marked-alt w-5 text-center"></i> Peta Interaktif
                        </a>
                    </li>
                    <li>
                        <a href="dashboard_pengguna.php#laporan" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all">
                            <i class="fas fa-file-alt w-5 text-center"></i> Laporan Saya
                        </a>
                    </li>
                    <li>
                        <a href="komunitas_saya.php" class="sidebar-active flex items-center gap-3 px-4 py-3 rounded-2xl font-medium transition-all">
                            <i class="fas fa-users w-5 text-center"></i> Komunitas Saya
                        </a>
                    </li>
                    <li>
                        <a href="index.php#edukasi" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-gray-700 font-medium hover:bg-lightgreen hover:text-primary transition-all">
                            <i class="fas fa-book w-5 text-center"></i> Edukasi
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Profile Bottom -->
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <a href="user_profile.php" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-white transition-all">
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
            <header class="glass sticky top-0 z-30 border-b border-gray-100 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button id="mobileMenuBtn" class="lg:hidden w-11 h-11 rounded-2xl bg-lightgreen text-primary flex items-center justify-center" onclick="toggleMobileSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" placeholder="Cari komunitas, diskusi, aksi..." class="pl-12 pr-6 py-3 bg-gray-50 border border-gray-200 rounded-2xl w-full focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="w-11 h-11 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-lightgreen hover:text-primary transition-all relative">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold">2</span>
                        </button>
                        <a href="logout.php" class="w-11 h-11 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-red-600 hover:bg-red-100 transition-all">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8 space-y-8">
                <?php if ($success_message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl flex items-center gap-3 animate-pulse">
                        <i class="fas fa-check-circle text-xl"></i>
                        <span class="font-semibold"><?= $success_message ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <span class="font-semibold"><?= $error_message ?></span>
                    </div>
                <?php endif; ?>

                <!-- Komunitas List -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Komunitas Saya -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6">
                            <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-star text-yellow-500"></i> Komunitas Saya
                            </h3>
                            <?php if (empty($my_communities)): ?>
                                <div class="text-center py-6 text-gray-500">
                                    <i class="fas fa-users text-4xl mb-2 text-gray-300"></i>
                                    <p>Belum bergabung dengan komunitas</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    <?php foreach ($my_communities as $com): ?>
                                        <div class="p-4 bg-lightgreen rounded-2xl border border-primary/20">
                                            <p class="font-semibold text-primary"><?= htmlspecialchars($com['title']) ?></p>
                                            <p class="text-xs text-gray-600 mt-1 flex items-center gap-2">
                                                <i class="fas fa-calendar"></i> Bergabung <?= date('d M Y', strtotime($com['joined_at'])) ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="text-xl font-extrabold text-gray-900 mt-8 mb-4 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-blue-500"></i> Gabung Komunitas
                            </h3>
                            <?php if (empty($available_communities)): ?>
                                <div class="text-center py-4 text-gray-400">
                                    <i class="fas fa-check-circle text-3xl mb-2"></i>
                                    <p class="text-sm">Sudah bergabung dengan semua komunitas</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($available_communities as $com): ?>
                                        <form method="POST" class="p-4 bg-gray-50 rounded-2xl border border-gray-200">
                                            <p class="font-semibold text-gray-900"><?= htmlspecialchars($com['title']) ?></p>
                                            <p class="text-xs text-gray-600 mb-3"><?= htmlspecialchars(substr($com['description'],0,50)) ?>...</p>
                                            <input type="hidden" name="action" value="join_community">
                                            <input type="hidden" name="community_id" value="<?= $com['id'] ?>">
                                            <button type="submit" class="w-full py-2 bg-gradient-to-r from-primary to-secondary text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-all">
                                                <i class="fas fa-user-plus mr-1"></i> Bergabung
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Komunitas Detail -->
                    <div class="lg:col-span-2">
                        <?php if (!$selected_community): ?>
                            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-16 text-center">
                                <i class="fas fa-users text-7xl text-gray-300 mb-4"></i>
                                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Belum Ada Komunitas</h2>
                                <p class="text-gray-600 mb-8">Silakan gabung dengan komunitas di sebelah kiri untuk mulai!</p>
                                <a href="index.php" class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-2xl">
                                    <i class="fas fa-home"></i> Kembali ke Beranda
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Hero Community -->
                            <div class="bg-gradient-to-r from-primary to-secondary rounded-3xl p-10 text-white shadow-2xl">
                                <div class="flex flex-col lg:flex-row items-start gap-8">
                                    <div class="flex-1">
                                        <span class="inline-block px-4 py-1 bg-white/20 rounded-full text-xs font-semibold mb-3">
                                            <i class="fas fa-calendar-check mr-1"></i> <?= htmlspecialchars($selected_community['action_status']) ?>
                                        </span>
                                        <h1 class="text-4xl font-extrabold mb-3"><?= htmlspecialchars($selected_community['title']) ?></h1>
                                        <p class="text-white/90 mb-6 max-w-2xl"><?= htmlspecialchars($selected_community['description']) ?></p>
                                        <div class="flex flex-wrap gap-4">
                                            <?php if ($is_member): ?>
                                                <span class="px-5 py-2 bg-white/30 border border-white/40 rounded-2xl flex items-center gap-2">
                                                    <i class="fas fa-check-circle"></i> Kamu adalah anggota
                                                </span>
                                            <?php else: ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="join_community">
                                                    <input type="hidden" name="community_id" value="<?= $selected_community['id'] ?>">
                                                    <button type="submit" class="px-6 py-3 bg-white text-primary font-bold rounded-2xl shadow-lg hover:bg-yellow-50 transition-all">
                                                        <i class="fas fa-user-plus mr-2"></i> Bergabung Sekarang
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-center lg:text-right">
                                        <div class="w-32 h-32 bg-white/20 rounded-3xl flex items-center justify-center text-6xl mb-4">
                                            <i class="fas fa-seedling"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
                                <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 p-6">
                                    <div class="text-4xl font-extrabold text-primary mb-1"><?= count($members) ?></div>
                                    <div class="text-sm text-gray-600 font-semibold">Anggota Aktif</div>
                                </div>
                                <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 p-6">
                                    <div class="text-4xl font-extrabold text-yellow-600 mb-1"><?= htmlspecialchars($selected_community['volunteer_target']) ?></div>
                                    <div class="text-sm text-gray-600 font-semibold">Target Relawan</div>
                                </div>
                                <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 p-6">
                                    <div class="text-4xl font-extrabold text-green-600 mb-1"><?= htmlspecialchars($selected_community['progress_percentage']) ?>%</div>
                                    <div class="text-sm text-gray-600 font-semibold">Progress Aksi</div>
                                </div>
                                <div class="card-lift bg-white rounded-3xl shadow-lg border border-gray-100 p-6">
                                    <div class="text-4xl font-extrabold text-blue-600 mb-1"><?= count($actions) ?></div>
                                    <div class="text-sm text-gray-600 font-semibold">Total Aksi</div>
                                </div>
                            </div>

                            <!-- Progress -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6">
                                    <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-chart-line text-primary"></i> Progress Aksi
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-3"><i class="fas fa-tag mr-2"></i> <?= htmlspecialchars($selected_community['current_action']) ?></p>
                                    <div class="w-full bg-gray-100 rounded-full h-5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all" style="width: <?= $selected_community['progress_percentage'] ?>%"></div>
                                    </div>
                                    <p class="text-right text-sm font-semibold text-gray-600 mt-2"><?= $selected_community['progress_percentage'] ?>% selesai</p>
                                </div>

                                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6">
                                    <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-location-dot text-red-500"></i> Lokasi
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($selected_community['location']) ?></p>
                                    <p class="text-xs text-gray-500"><i class="fas fa-calendar mr-1"></i> Dimulai: <?= date('d M Y', strtotime($selected_community['start_date'])) ?></p>
                                </div>
                            </div>

                            <!-- Anggota & Diskusi -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                                <!-- Anggota -->
                                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6">
                                    <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-users text-blue-500"></i> Daftar Anggota
                                    </h3>
                                    <div class="space-y-3 max-h-80 overflow-y-auto">
                                        <?php foreach ($members as $mem): ?>
                                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-2xl">
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
                                                    <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($mem['name']) ?></p>
                                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($mem['role']) ?> • Bergabung <?= date('d M Y', strtotime($mem['joined_at'])) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Diskusi -->
                                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6 flex flex-col h-full">
                                    <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-comments text-purple-500"></i> Forum Diskusi
                                    </h3>
                                    <div class="flex-1 overflow-y-auto mb-4 space-y-4 max-h-64">
                                        <?php foreach ($discussions as $msg): ?>
                                            <div class="p-4 bg-lightgreen rounded-2xl">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="w-8 h-8 rounded-full overflow-hidden border border-primary">
                                                        <?php if ($msg['profile_pic'] && file_exists($msg['profile_pic'])): ?>
                                                            <img src="<?= htmlspecialchars($msg['profile_pic']) ?>" class="w-full h-full object-cover" alt="Profile">
                                                        <?php else: ?>
                                                            <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-xs">
                                                                <?= strtoupper(substr($msg['name'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-xs font-semibold text-primary"><?= htmlspecialchars($msg['name']) ?></p>
                                                    <p class="text-xs text-gray-400"><?= date('d M H:i', strtotime($msg['created_at'])) ?></p>
                                                </div>
                                                <p class="text-sm text-gray-700"><?= htmlspecialchars($msg['message']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($discussions)): ?>
                                            <div class="text-center py-8 text-gray-400">
                                                <i class="fas fa-comment-slash text-4xl mb-2"></i>
                                                <p class="text-sm">Belum ada diskusi, mulailah berbicara!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($is_member): ?>
                                        <form method="POST" class="flex gap-3">
                                            <input type="hidden" name="action" value="post_discussion">
                                            <input type="hidden" name="community_id" value="<?= $selected_community['id'] ?>">
                                            <input type="text" name="message" required placeholder="Tulis pesan diskusi..." class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-2xl hover:opacity-90 transition-all">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="p-4 bg-gray-50 text-center rounded-2xl text-sm text-gray-500">
                                            Bergabung dengan komunitas untuk berdiskusi!
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Aksi History -->
                            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6 mt-8">
                                <h3 class="text-xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-history text-orange-500"></i> Riwayat Aksi Komunitas
                                </h3>
                                <?php if (empty($actions)): ?>
                                    <div class="text-center py-8 text-gray-400">
                                        <i class="fas fa-clock text-4xl mb-2"></i>
                                        <p>Belum ada aksi komunitas</p>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <?php foreach ($actions as $act): ?>
                                            <div class="card-lift p-5 bg-gray-50 rounded-2xl border border-gray-200">
                                                <h4 class="font-bold text-gray-900 mb-2"><?= htmlspecialchars($act['title']) ?></h4>
                                                <p class="text-xs text-gray-600 mb-3"><?= htmlspecialchars(substr($act['description'], 0, 80)) ?>...</p>
                                                <p class="text-xs text-gray-500 mb-2"><i class="fas fa-map-marker-alt mr-1 text-red-500"></i> <?= htmlspecialchars($act['location']) ?></p>
                                                <p class="text-xs text-gray-500"><i class="fas fa-calendar mr-1 text-blue-500"></i> <?= date('d M Y', strtotime($act['action_date'])) ?></p>
                                                <p class="text-xs text-gray-400 mt-2">Oleh: <?= htmlspecialchars($act['name']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    </script>
</body>
</html>
