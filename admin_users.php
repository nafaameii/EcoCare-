<?php
require 'config.php';
require_admin();

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'change_status' && isset($_POST['user_id']) && isset($_POST['new_status'])) {
            $user_id = (int)$_POST['user_id'];
            $new_status = $_POST['new_status'];

            // First, ensure status column exists
            $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
            if (!$checkColumn->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role");
            }

            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $message = "Status pengguna berhasil diperbarui!";
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Helper function to check if table exists
function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper function to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// Get all users with statistics
try {
    // Check and add missing columns on the fly
    if (!columnExists($pdo, 'users', 'status')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role");
    }
    if (!columnExists($pdo, 'users', 'latitude')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10,8) NULL AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude");
    }
    // Fix role enum just in case
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat'");
    } catch (PDOException $e) {
        // Ignore if already correct
    }
    $pdo->exec("UPDATE IGNORE users SET role = 'masyarakat' WHERE role NOT IN ('masyarakat', 'admin')");

    $communityTablesExist = tableExists($pdo, 'community_members') && 
                            tableExists($pdo, 'community_actions') && 
                            tableExists($pdo, 'community_contributions');

    if ($communityTablesExist) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COUNT(DISTINCT r.id) as report_count,
                   COUNT(DISTINCT cm.id) as community_count,
                   COUNT(DISTINCT ca.id) as action_count,
                   COUNT(DISTINCT cc.id) as contribution_count
            FROM users u 
            LEFT JOIN reports r ON u.id = r.user_id 
            LEFT JOIN community_members cm ON u.id = cm.user_id
            LEFT JOIN community_actions ca ON u.id = ca.created_by
            LEFT JOIN community_contributions cc ON u.id = cc.user_id
            GROUP BY u.id 
            ORDER BY u.role DESC, u.created_at DESC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COUNT(DISTINCT r.id) as report_count,
                   0 as community_count,
                   0 as action_count,
                   0 as contribution_count
            FROM users u 
            LEFT JOIN reports r ON u.id = r.user_id 
            GROUP BY u.id 
            ORDER BY u.role DESC, u.created_at DESC
        ");
    }
    $users = $stmt->fetchAll();

    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
    $total_admins = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'aktif'");
    $active_users = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $new_users_this_month = $stmt->fetch()['total'];
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - EcoCare+</title>
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
        .sidebar { transition: all 0.3s ease; }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%);
            color: white;
        }
        .user-card { transition: all 0.3s ease; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
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
                        <a href="admin_users.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
                        <h1 class="text-2xl font-bold text-ecocare-dark">Kelola Pengguna</h1>
                        <p class="text-gray-500 text-sm">Kelola semua pengguna dan administrator</p>
                    </div>
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        <span>Ke Beranda</span>
                    </a>
                </div>
            </header>

            <div class="p-8">
                <?php if ($message): ?>
                    <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border px-6 py-4 rounded-xl mb-8 flex items-center gap-3">
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                                <i class="fas fa-crown"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Admin</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_admins; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Pengguna Aktif</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $active_users; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Pengguna Baru (Bulan Ini)</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $new_users_this_month; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-gray-100">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchInput" 
                                       class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary focus:border-transparent"
                                       placeholder="Cari nama atau email pengguna...">
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <select id="roleFilter" class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                <option value="all">Semua Role</option>
                                <option value="admin">Admin</option>
                                <option value="masyarakat">Masyarakat</option>
                            </select>
                            <select id="statusFilter" class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                <option value="all">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="ditangguhkan">Ditangguhkan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-4 mb-8">
                    <button data-tab="all" class="tab-btn px-6 py-3 rounded-xl font-semibold bg-ecocare-primary text-white shadow-md">
                        <i class="fas fa-list mr-2"></i> Semua Pengguna
                    </button>
                    <button data-tab="admin" class="tab-btn px-6 py-3 rounded-xl font-semibold bg-white text-gray-700 border border-gray-200 hover:border-ecocare-primary">
                        <i class="fas fa-crown mr-2"></i> Administrator
                    </button>
                    <button data-tab="masyarakat" class="tab-btn px-6 py-3 rounded-xl font-semibold bg-white text-gray-700 border border-gray-200 hover:border-ecocare-primary">
                        <i class="fas fa-user mr-2"></i> Masyarakat
                    </button>
                </div>

                <!-- Administrator Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-crown text-yellow-500"></i> Administrator
                    </h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" id="adminCards">
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['role'] === 'admin'): ?>
                                <?php 
                                $userStatus = $user['status'] ?? 'aktif';
                                $status_class = [
                                    'aktif' => 'bg-green-100 text-green-700',
                                    'nonaktif' => 'bg-gray-100 text-gray-700',
                                    'ditangguhkan' => 'bg-orange-100 text-orange-700'
                                ][$userStatus];
                                ?>
                                <div class="user-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-name="<?php echo strtolower($user['name']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-status="<?php echo $userStatus; ?>">
                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-md">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <h3 class="text-lg font-bold text-ecocare-dark"><?php echo htmlspecialchars($user['name']); ?></h3>
                                                    <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                                                        Admin
                                                    </span>
                                                    <span class="px-3 py-1 <?php echo $status_class; ?> rounded-full text-xs font-semibold">
                                                        <?php echo ucfirst($userStatus); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                                                <span><i class="fas fa-calendar mr-1"></i> <?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                            </div>
                                            <div class="flex gap-3">
                                                <button onclick="showUserDetail(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition flex items-center gap-2">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <select onchange="changeStatus(<?php echo $user['id']; ?>, this.value)" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-ecocare-primary text-sm">
                                                    <option value="aktif" <?php echo $userStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="nonaktif" <?php echo $userStatus === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                                    <option value="ditangguhkan" <?php echo $userStatus === 'ditangguhkan' ? 'selected' : ''; ?>>Ditangguhkan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Masyarakat Section -->
                <div>
                    <h2 class="text-xl font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-users text-ecocare-primary"></i> Masyarakat
                    </h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" id="userCards">
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['role'] === 'masyarakat'): ?>
                                <?php 
                                $userStatus = $user['status'] ?? 'aktif';
                                $status_class = [
                                    'aktif' => 'bg-green-100 text-green-700',
                                    'nonaktif' => 'bg-gray-100 text-gray-700',
                                    'ditangguhkan' => 'bg-orange-100 text-orange-700'
                                ][$userStatus];
                                ?>
                                <div class="user-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-name="<?php echo strtolower($user['name']); ?>" data-email="<?php echo strtolower($user['email']); ?>" data-status="<?php echo $userStatus; ?>">
                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-md">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <h3 class="text-lg font-bold text-ecocare-dark"><?php echo htmlspecialchars($user['name']); ?></h3>
                                                    <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                                        Masyarakat
                                                    </span>
                                                    <span class="px-3 py-1 <?php echo $status_class; ?> rounded-full text-xs font-semibold">
                                                        <?php echo ucfirst($userStatus); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4 mb-4 text-sm text-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-file-alt text-ecocare-primary"></i>
                                                    <span><?php echo $user['report_count']; ?> Laporan</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-users text-blue-500"></i>
                                                    <span><?php echo $user['community_count']; ?> Komunitas</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-hands-helping text-purple-500"></i>
                                                    <span><?php echo $user['action_count']; ?> Aksi</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-gift text-orange-500"></i>
                                                    <span><?php echo $user['contribution_count']; ?> Kontribusi</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                                                <span><i class="fas fa-calendar mr-1"></i> <?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                                <?php if (isset($user['phone']) && $user['phone']): ?>
                                                    <span><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($user['phone']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex gap-3">
                                                <button onclick="showUserDetail(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition flex items-center gap-2">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <select onchange="changeStatus(<?php echo $user['id']; ?>, this.value)" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-ecocare-primary text-sm">
                                                    <option value="aktif" <?php echo $userStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="nonaktif" <?php echo $userStatus === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                                    <option value="ditangguhkan" <?php echo $userStatus === 'ditangguhkan' ? 'selected' : ''; ?>>Ditangguhkan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- User Detail Modal -->
    <div id="userDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Detail Pengguna</h3>
                <button onclick="closeUserDetailModal()" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6" id="userDetailContent">
            </div>
        </div>
    </div>

    <!-- Hidden form for status change -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="new_status" id="newStatus">
        <input type="hidden" name="action" value="change_status">
    </form>

    <script>
        // Filter tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('bg-ecocare-primary', 'text-white', 'shadow-md');
                    b.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200');
                });
                this.classList.add('bg-ecocare-primary', 'text-white', 'shadow-md');
                this.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200');

                filterCards();
            });
        });

        // Search and filter
        document.getElementById('searchInput').addEventListener('input', filterCards);
        document.getElementById('roleFilter').addEventListener('change', filterCards);
        document.getElementById('statusFilter').addEventListener('change', filterCards);

        function filterCards() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const selectedRole = document.querySelector('.tab-btn.bg-ecocare-primary').dataset.tab;
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            document.querySelectorAll('.user-card').forEach(card => {
                const name = card.dataset.name;
                const email = card.dataset.email;
                const status = card.dataset.status;
                const isAdmin = card.querySelector('.bg-purple-100') !== null;

                let show = true;

                if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                    show = false;
                }

                if (selectedRole === 'admin' && !isAdmin) {
                    show = false;
                }
                if (selectedRole === 'masyarakat' && isAdmin) {
                    show = false;
                }

                if (roleFilter !== 'all') {
                    if (roleFilter === 'admin' && !isAdmin) show = false;
                    if (roleFilter === 'masyarakat' && isAdmin) show = false;
                }

                if (statusFilter !== 'all' && status !== statusFilter) {
                    show = false;
                }

                card.style.display = show ? 'block' : 'none';
            });
        }

        // Show user detail
        function showUserDetail(user) {
            const content = document.getElementById('userDetailContent');
            const roleBadge = user.role === 'admin' 
                ? '<span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">Admin</span>' 
                : '<span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">Masyarakat</span>';

            const userStatus = user.status || 'aktif';
            const statusColors = {
                'aktif': 'bg-green-100 text-green-700',
                'nonaktif': 'bg-gray-100 text-gray-700',
                'ditangguhkan': 'bg-orange-100 text-orange-700'
            };

            content.innerHTML = `
                <div class="text-center mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br ${user.role === 'admin' ? 'from-purple-500 to-purple-700' : 'from-ecocare-primary to-ecocare-green-dark'} rounded-3xl flex items-center justify-center text-white text-4xl font-bold shadow-lg mx-auto mb-4">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <h3 class="text-2xl font-bold text-ecocare-dark mb-2">${user.name}</h3>
                    <div class="flex items-center justify-center gap-2">
                        ${roleBadge}
                        <span class="px-3 py-1 ${statusColors[userStatus]} rounded-full text-xs font-semibold">${userStatus.charAt(0).toUpperCase() + userStatus.slice(1)}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-xs text-gray-500 font-semibold mb-1">Email</p>
                        <p class="text-gray-800">${user.email}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-xs text-gray-500 font-semibold mb-1">Telepon</p>
                        <p class="text-gray-800">${user.phone || '-'}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-xs text-gray-500 font-semibold mb-1">NIK</p>
                        <p class="text-gray-800">${user.resident_id}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-xs text-gray-500 font-semibold mb-1">Tanggal Bergabung</p>
                        <p class="text-gray-800">${new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                    </div>
                </div>

                ${user.role === 'masyarakat' ? `
                    <h4 class="text-lg font-bold text-ecocare-dark mb-4">Statistik Aktivitas</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-xl text-center">
                            <p class="text-2xl font-bold text-blue-600">${user.report_count || 0}</p>
                            <p class="text-xs text-blue-700 font-semibold">Laporan</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-xl text-center">
                            <p class="text-2xl font-bold text-green-600">${user.community_count || 0}</p>
                            <p class="text-xs text-green-700 font-semibold">Komunitas</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-xl text-center">
                            <p class="text-2xl font-bold text-purple-600">${user.action_count || 0}</p>
                            <p class="text-xs text-purple-700 font-semibold">Aksi</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-xl text-center">
                            <p class="text-2xl font-bold text-orange-600">${user.contribution_count || 0}</p>
                            <p class="text-xs text-orange-700 font-semibold">Kontribusi</p>
                        </div>
                    </div>
                ` : ''}
            `;

            document.getElementById('userDetailModal').classList.remove('hidden');
            document.getElementById('userDetailModal').classList.add('flex');
        }

        function closeUserDetailModal() {
            document.getElementById('userDetailModal').classList.add('hidden');
            document.getElementById('userDetailModal').classList.remove('flex');
        }

        // Change user status
        function changeStatus(userId, newStatus) {
            if (confirm(`Apakah Anda yakin ingin mengubah status pengguna menjadi "${newStatus}"?`)) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('newStatus').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('userDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeUserDetailModal();
        });
    </script>
</body>
</html>
