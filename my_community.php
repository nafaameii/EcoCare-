<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];

function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    $communityTablesExist = tableExists($pdo, 'community_members') && 
                            tableExists($pdo, 'community_actions');
    
    if ($communityTablesExist) {
        $stmt = $pdo->prepare("
            SELECT r.*, cm.joined_at, u.name as reporter_name
            FROM community_members cm
            JOIN reports r ON cm.report_id = r.id
            JOIN users u ON r.user_id = u.id
            WHERE cm.user_id = ?
            ORDER BY cm.joined_at DESC
        ");
        $stmt->execute([$user_id]);
        $myCommunities = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $totalCommunities = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM community_actions ca
            JOIN community_members cm ON ca.report_id = cm.report_id
            WHERE cm.user_id = ? AND ca.status = 'completed'
        ");
        $stmt->execute([$user_id]);
        $completedActions = $stmt->fetchColumn();
    } else {
        $myCommunities = [];
        $totalCommunities = 0;
        $completedActions = 0;
    }
} catch (PDOException $e) {
    $myCommunities = [];
    $totalCommunities = 0;
    $completedActions = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas Saya - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body class="bg-ecocare-cream text-ecocare-dark min-h-screen">
    <nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Peduli Lingkungan Kita</span>
                    </div>
                </a>

                <div class="flex items-center gap-6">
                    <a href="index.php" class="text-gray-700 hover:text-ecocare-primary font-medium transition">Beranda</a>
                    <a href="map.php" class="text-gray-700 hover:text-ecocare-primary font-medium transition">Peta</a>
                    <a href="dashboard_pengguna.php" class="text-gray-700 hover:text-ecocare-primary font-medium transition">Dashboard</a>
                    <div class="flex items-center gap-3">
                        <a href="user_profile.php" class="flex items-center gap-3 group">
                            <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary group-hover:border-ecocare-dark transition">
                                <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] && file_exists($_SESSION['profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-ecocare-dark font-semibold hidden md:block"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        </a>
                        <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="hidden md:block">Keluar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
        <div class="mb-12">
            <a href="dashboard_pengguna.php" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-4 hover:underline">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h1 class="text-4xl font-extrabold text-ecocare-dark mb-3 flex items-center gap-3">
                <i class="fas fa-users text-ecocare-primary"></i> Komunitas Saya
            </h1>
            <p class="text-lg text-ecocare-dark/70">Lihat semua laporan yang Anda bantu dan aksi yang Anda ikuti</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Komunitas yang Diikuti</p>
                        <p class="text-3xl font-extrabold text-ecocare-dark"><?php echo $totalCommunities; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Aksi Selesai</p>
                        <p class="text-3xl font-extrabold text-ecocare-dark"><?php echo $completedActions; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark flex items-center gap-2">
                    <i class="fas fa-list text-ecocare-primary"></i> Laporan yang Saya Bantu
                </h3>
                <span class="text-sm text-gray-500"><?php echo count($myCommunities); ?> laporan</span>
            </div>
            <div class="p-8">
                <?php if (empty($myCommunities)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-hand-holding-heart text-6xl text-gray-300 mb-6"></i>
                        <p class="text-lg text-gray-600 mb-4">Anda belum bergabung dengan komunitas manapun</p>
                        <a href="map.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-semibold px-8 py-3 rounded-xl hover:shadow-lg transition">
                            <i class="fas fa-map-marked-alt"></i> Jelajahi Laporan
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach ($myCommunities as $community): ?>
                            <a href="report_detail.php?id=<?php echo $community['id']; ?>" class="block group">
                                <div class="border border-gray-200 rounded-2xl p-6 hover:border-ecocare-primary hover:shadow-xl transition-all">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-lg">
                                                <?php 
                                                switch($community['category']) {
                                                    case 'Sampah': echo '<i class="fas fa-trash"></i>'; break;
                                                    case 'Saluran Air Tersumbat': echo '<i class="fas fa-water"></i>'; break;
                                                    case 'Genangan Air': echo '<i class="fas fa-tint"></i>'; break;
                                                    case 'Lingkungan Kurang Terawat': echo '<i class="fas fa-leaf"></i>'; break;
                                                    default: echo '<i class="fas fa-exclamation-circle"></i>'; break;
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-ecocare-dark text-lg"><?php echo htmlspecialchars($community['category']); ?></h4>
                                                <p class="text-sm text-gray-500">Dilaporkan oleh <?php echo htmlspecialchars($community['reporter_name']); ?></p>
                                            </div>
                                        </div>
                                        <?php
                                        $statusColors = [
                                            'Baru' => 'bg-red-100 text-red-700',
                                            'Diproses' => 'bg-yellow-100 text-yellow-700',
                                            'Komunitas Terbentuk' => 'bg-blue-100 text-blue-700',
                                            'Aksi Berjalan' => 'bg-purple-100 text-purple-700',
                                            'Selesai' => 'bg-green-100 text-green-700'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$community['status']]; ?>">
                                            <?php echo htmlspecialchars($community['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($community['description']); ?></p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <i class="fas fa-calendar text-ecocare-primary"></i>
                                            Bergabung: <?php echo date('d M Y', strtotime($community['joined_at'])); ?>
                                        </div>
                                        <span class="text-ecocare-primary font-semibold group-hover:underline flex items-center gap-1">
                                            Lihat Detail <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-ecocare-dark text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
            <p class="opacity-70">&copy; 2026 EcoCare+. Made with <i class="fas fa-heart text-red-400"></i> for our planet.</p>
        </div>
    </footer>
</body>
</html>
