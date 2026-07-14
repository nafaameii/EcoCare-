<?php
require 'config.php';
require_login();

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header('Location: index.php');
    exit;
}

$isAdmin = $_SESSION['role'] === 'admin';

try {
    // Get report and community action details
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as reporter_name, 
               ca.id as action_id, ca.title as action_title, ca.description as action_desc, 
               ca.status as action_status, ca.progress, ca.target_volunteers,
               ca.created_at as action_created_at
        FROM reports r
        LEFT JOIN community_actions ca ON r.id = ca.report_id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        header('Location: index.php');
        exit;
    }

    // Check if current user is a member (only for non-admins)
    $is_member = false;
    $stmt = $pdo->prepare("
        SELECT cm.*, u.name, u.profile_pic
        FROM community_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.report_id = ?
        ORDER BY cm.joined_at DESC
    ");
    $stmt->execute([$report_id]);
    $members = $stmt->fetchAll();

    if (!$isAdmin) {
        foreach ($members as $member) {
            if ($member['user_id'] == $_SESSION['user_id']) {
                $is_member = true;
                break;
            }
        }
    }

    // Get community discussions
    $stmt = $pdo->prepare("
        SELECT cc.*, u.name, u.profile_pic
        FROM community_comments cc
        JOIN users u ON cc.user_id = u.id
        WHERE cc.report_id = ?
        ORDER BY cc.created_at DESC
    ");
    $stmt->execute([$report_id]);
    $comments = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($report['category']) ?> - Komunitas Aksi | EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'inter': ['Inter', 'sans-serif'] },
                    colors: {
                        'ecocare-primary': '#6FAF8F',
                        'ecocare-green-dark': '#3D8B6A',
                        'ecocare-dark': '#2D3748'
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .status-planned { background: #FEF3C7; color: #92400E; }
        .status-active { background: #DBEAFE; color: #1E40AF; }
        .status-completed { background: #D1FAE5; color: #065F46; }
        
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50 text-ecocare-dark min-h-screen">
    <nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Komunitas Aksi</span>
                    </div>
                </a>
                <div class="flex items-center gap-6">
                    <a href="dashboard_pengguna.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Dashboard</a>
                    <?php if (!$isAdmin): ?>
                        <a href="my_community.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Komunitas Saya</a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-8 py-10">
        <!-- Back button -->
        <a href="report_detail.php?id=<?= $report_id ?>" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-8 hover:underline">
            <i class="fas fa-arrow-left"></i> Kembali ke Laporan
        </a>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Left Column: Community Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Community Action Header -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-8">
                    <h1 class="text-3xl font-extrabold text-ecocare-dark mb-2">
                        <?= $report['action_title'] ? htmlspecialchars($report['action_title']) : 'Aksi ' . htmlspecialchars($report['category']) ?>
                    </h1>
                    <p class="text-lg text-gray-600 mb-6">
                        <?= $report['action_desc'] ? htmlspecialchars($report['action_desc']) : 'Mari bersama-sama menyelesaikan masalah lingkungan ini!' ?>
                    </p>

                    <!-- Action Status & Progress -->
                    <div class="grid sm:grid-cols-2 gap-6 mb-8">
                        <div>
                            <span class="text-sm text-gray-500 font-medium mb-1 block">Status Aksi</span>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold status-<?= $report['action_status'] ?? 'planned' ?>">
                                <?php 
                                $status_map = [
                                    'planned' => 'Direncanakan',
                                    'active' => 'Berjalan',
                                    'completed' => 'Selesai'
                                ];
                                echo $status_map[$report['action_status'] ?? 'planned'] ?? 'Direncanakan';
                                ?>
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-500 font-medium">Progress Aksi</span>
                                <span class="font-bold text-ecocare-primary"><?= $report['progress'] ?? 0 ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark h-3 rounded-full" style="width: <?= $report['progress'] ?? 0 ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Volunteers -->
                    <div class="flex items-center gap-6 pt-6 border-t border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-ecocare-primary/20 rounded-full flex items-center justify-center text-ecocare-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-extrabold text-ecocare-dark"><?= count($members) ?></p>
                                <p class="text-sm text-gray-500">Relawan</p>
                            </div>
                        </div>
                        <?php if ($report['target_volunteers']): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                                    <i class="fas fa-bullseye"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-extrabold text-ecocare-dark"><?= $report['target_volunteers'] ?></p>
                                    <p class="text-sm text-gray-500">Target</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Admin Controls -->
                    <?php if ($isAdmin && $report['action_id']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <h3 class="text-lg font-semibold text-ecocare-dark mb-4 flex items-center gap-2">
                                <i class="fas fa-cog text-ecocare-primary"></i>
                                Kelola Aksi Komunitas
                            </h3>
                            <form id="updateActionForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status Aksi</label>
                                        <select id="newActionStatus" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            <option value="planned" <?= $report['action_status'] === 'planned' ? 'selected' : '' ?>>Direncanakan</option>
                                            <option value="active" <?= $report['action_status'] === 'active' ? 'selected' : '' ?>>Berjalan</option>
                                            <option value="completed" <?= $report['action_status'] === 'completed' ? 'selected' : '' ?>>Selesai</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Progress (%)</label>
                                        <input type="number" id="newActionProgress" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-orange-500" value="<?= $report['progress'] ?? 0 ?>" min="0" max="100">
                                    </div>
                                </div>
                                <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3 rounded-xl font-semibold hover:shadow-lg transition">
                                    <i class="fas fa-save mr-2"></i>
                                    Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Create/Edit Action Form -->
                    <?php if (!$isAdmin && $is_member && !$report['action_title']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <h3 class="text-lg font-semibold text-ecocare-dark mb-4 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-ecocare-primary"></i>
                                Buat Aksi Komunitas
                            </h3>
                            <form id="createActionForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Aksi *</label>
                                        <input type="text" id="actionTitle" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" placeholder="Contoh: Bersih Sungai Serayu" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Target Relawan</label>
                                        <input type="number" id="actionTargetVolunteers" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" min="1">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Aksi *</label>
                                    <textarea id="actionDescription" rows="3" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" placeholder="Deskripsi detail aksi..." required></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai</label>
                                        <input type="date" id="actionStartDate" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai</label>
                                        <input type="date" id="actionEndDate" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                    </div>
                                </div>
                                <button type="submit" class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white py-3 rounded-xl font-semibold hover:shadow-lg transition">
                                    <i class="fas fa-rocket mr-2"></i>
                                    Buat Aksi Komunitas
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Community Discussion - Chat Style -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden flex flex-col" style="height: 600px;">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-ecocare-primary/5 to-ecocare-green-dark/5">
                        <h2 class="text-2xl font-bold text-ecocare-dark flex items-center gap-2">
                            <i class="fas fa-comments text-ecocare-primary"></i>
                            Forum Diskusi
                        </h2>
                    </div>

                    <!-- Chat Messages Area -->
                    <div id="commentsList" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/50">
                        <?php if (empty($comments)): ?>
                            <div class="text-center py-16 text-gray-500">
                                <i class="fas fa-comment-slash text-5xl mb-4 opacity-30"></i>
                                <p class="text-lg">Belum ada diskusi</p>
                                <?php if (!$isAdmin && !$is_member): ?>
                                    <p class="text-sm mt-2">Gabung komunitas untuk berdiskusi!</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($comments) as $comment): ?>
                                <?php $isCurrentUser = $comment['user_id'] == $_SESSION['user_id']; ?>
                                <div class="flex <?php echo $isCurrentUser ? 'justify-end' : 'justify-start'; ?>">
                                    <div class="flex gap-3 <?php echo $isCurrentUser ? 'flex-row-reverse' : 'flex-row'; ?> max-w-[80%]">
                                        <?php if (!$isCurrentUser): ?>
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold overflow-hidden flex-shrink-0">
                                                <?php if (isset($comment['profile_pic']) && $comment['profile_pic'] && file_exists($comment['profile_pic'])): ?>
                                                    <img src="<?php echo htmlspecialchars($comment['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center">
                                                        <?php echo strtoupper(substr($comment['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex flex-col gap-1">
                                            <?php if (!$isCurrentUser): ?>
                                                <span class="text-sm font-semibold text-ecocare-dark ml-1"><?= htmlspecialchars($comment['name']) ?></span>
                                            <?php endif; ?>
                                            
                                            <div class="p-4 rounded-2xl shadow-sm <?php echo $isCurrentUser ? 'bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white rounded-br-md' : 'bg-white border border-gray-100 rounded-bl-md'; ?>">
                                                <p class="text-sm leading-relaxed"><?= htmlspecialchars($comment['comment']) ?></p>
                                            </div>
                                            
                                            <span class="text-xs text-gray-400 <?php echo $isCurrentUser ? 'text-right' : 'text-left'; ?>">
                                                <?= date('H:i, d M Y', strtotime($comment['created_at'])) ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($isCurrentUser): ?>
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold overflow-hidden flex-shrink-0">
                                                <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] && file_exists($_SESSION['profile_pic'])): ?>
                                                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center">
                                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Input Area -->
                    <?php if (!$isAdmin && $is_member): ?>
                        <div class="p-4 border-t border-gray-100 bg-white">
                            <form id="commentForm" class="flex items-end gap-3">
                                <div class="flex-1">
                                    <textarea 
                                        id="commentInput" 
                                        rows="1" 
                                        class="w-full border border-gray-200 rounded-2xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary/50 focus:border-ecocare-primary resize-none text-sm" 
                                        placeholder="Tulis pesan untuk anggota komunitas..."
                                    ></textarea>
                                </div>
                                <button 
                                    type="submit" 
                                    id="sendBtn"
                                    class="w-12 h-12 rounded-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white flex items-center justify-center hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Members & Actions -->
            <div class="space-y-6">
                <!-- Action Buttons -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <?php if (!$isAdmin): ?>
                        <?php if ($is_member): ?>
                            <button id="leaveBtn" class="w-full bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-300 transition flex items-center justify-center gap-2">
                                <i class="fas fa-sign-out-alt"></i> Keluar dari Komunitas
                            </button>
                        <?php else: ?>
                            <button id="joinBtn" class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white py-3 rounded-xl font-semibold hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-hands-helping"></i> Gabung Aksi
                            </button>
                        <?php endif; ?>
                        <hr class="my-4 border-gray-100">
                        <button id="inviteBtn" class="w-full bg-white border-2 border-ecocare-primary text-ecocare-primary py-3 rounded-xl font-semibold hover:bg-ecocare-primary hover:text-white transition flex items-center justify-center gap-2">
                            <i class="fas fa-share-alt"></i> Ajak Teman
                        </button>
                    <?php else: ?>
                        <div class="space-y-3">
                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl">
                                <h4 class="font-semibold text-blue-800 flex items-center gap-2 mb-1">
                                    <i class="fas fa-shield-alt"></i> Panel Admin
                                </h4>
                                <p class="text-sm text-blue-700">Anda login sebagai Admin. Anda dapat melihat dan mengelola komunitas ini.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Members List -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <h3 class="text-xl font-bold text-ecocare-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-user-friends text-ecocare-primary"></i>
                        Daftar Relawan (<?= count($members) ?>)
                    </h3>
                    <div class="space-y-3">
                        <?php if (empty($members)): ?>
                            <div class="text-center py-6 text-gray-500">
                                <i class="fas fa-users text-2xl mb-2 opacity-30"></i>
                                <p class="text-sm">Belum ada relawan</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold overflow-hidden">
                                        <?php if (isset($member['profile_pic']) && $member['profile_pic'] && file_exists($member['profile_pic'])): ?>
                                            <img src="<?php echo htmlspecialchars($member['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center">
                                                <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-ecocare-dark"><?php echo htmlspecialchars($member['name']); ?></p>
                                        <p class="text-xs text-gray-500">Bergabung <?php echo date('d M Y', strtotime($member['joined_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Share Modal -->
    <div id="shareModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Ajak Teman Bergabung</h3>
                <button onclick="document.getElementById('shareModal').classList.add('hidden'); document.getElementById('shareModal').classList.remove('flex');" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Bagikan link komunitas ini untuk mengajak teman bergabung!</p>
                <div class="bg-gray-50 p-4 rounded-xl flex items-center gap-3 mb-6">
                    <input type="text" id="shareLink" readonly class="bg-transparent flex-1 text-sm text-gray-700 focus:outline-none" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
                    <button onclick="copyLink()" class="text-ecocare-primary font-semibold text-sm">
                        <i class="fas fa-copy mr-1"></i> Salin
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <a href="https://wa.me/?text=<?= urlencode('Halo! Ada aksi lingkungan di EcoCare+ yang membutuhkan bantuanmu! Yuk gabung: ' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")) ?>" target="_blank" class="bg-green-500 text-white p-3 rounded-xl text-center hover:bg-green-600 transition flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-xl"></i> WhatsApp
                    </a>
                    <a href="https://t.me/share/url?url=<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>&text=<?= urlencode('Halo! Ada aksi lingkungan di EcoCare+ yang membutuhkan bantuanmu! Yuk gabung!') ?>" target="_blank" class="bg-blue-500 text-white p-3 rounded-xl text-center hover:bg-blue-600 transition flex items-center justify-center gap-2">
                        <i class="fab fa-telegram text-xl"></i> Telegram
                    </a>
                    <a href="#" id="instagramShareBtn" class="bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500 text-white p-3 rounded-xl text-center hover:brightness-110 transition flex items-center justify-center gap-2">
                        <i class="fab fa-instagram text-xl"></i> Instagram
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const reportId = <?= $report_id ?>;
        let isMember = <?= $is_member ? 'true' : 'false' ?>;
        const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        const actionId = <?= $report['action_id'] ?? 'null' ?>;
        
        // Auto-resize textarea and enable/disable send button
        const commentInput = document.getElementById('commentInput');
        const sendBtn = document.getElementById('sendBtn');
        
        if (commentInput && sendBtn) {
            commentInput.addEventListener('input', () => {
                // Auto-resize
                commentInput.style.height = 'auto';
                commentInput.style.height = Math.min(commentInput.scrollHeight, 150) + 'px';
                
                // Enable/disable button
                if (commentInput.value.trim()) {
                    sendBtn.disabled = false;
                } else {
                    sendBtn.disabled = true;
                }
            });
        }

        // Join button
        document.getElementById('joinBtn')?.addEventListener('click', async () => {
            if (isMember || isAdmin) return;
            const formData = new FormData();
            formData.append('report_id', reportId);
            const res = await fetch('api/community.php?action=join', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        });

        // Leave button
        document.getElementById('leaveBtn')?.addEventListener('click', async () => {
            if (isAdmin) return;
            if (confirm('Yakin ingin keluar dari komunitas?')) {
                const formData = new FormData();
                formData.append('report_id', reportId);
                const res = await fetch('api/community.php?action=leave', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            }
        });

        // Comment form
        document.getElementById('commentForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const comment = document.getElementById('commentInput').value;
            if (!comment.trim() || isAdmin) return;

            const formData = new FormData();
            formData.append('report_id', reportId);
            formData.append('comment', comment);
            const res = await fetch('api/community.php?action=add_comment', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        });

        // Create action form
        document.getElementById('createActionForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('report_id', reportId);
            formData.append('title', document.getElementById('actionTitle').value);
            formData.append('description', document.getElementById('actionDescription').value);
            formData.append('start_date', document.getElementById('actionStartDate').value);
            formData.append('end_date', document.getElementById('actionEndDate').value);
            formData.append('target_volunteers', document.getElementById('actionTargetVolunteers').value);

            const res = await fetch('api/community.php?action=create_action', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        });

        // Update action form (admin)
        document.getElementById('updateActionForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Update action status
            const formDataStatus = new FormData();
            formDataStatus.append('action_id', actionId);
            formDataStatus.append('status', document.getElementById('newActionStatus').value);

            const resStatus = await fetch('api/community.php?action=update_action_status', { method: 'POST', body: formDataStatus });
            const dataStatus = await resStatus.json();

            if (!dataStatus.success) {
                alert(dataStatus.message);
                return;
            }

            // Update action progress
            const formDataProgress = new FormData();
            formDataProgress.append('action_id', actionId);
            formDataProgress.append('progress', document.getElementById('newActionProgress').value);

            const resProgress = await fetch('api/community.php?action=update_action_progress', { method: 'POST', body: formDataProgress });
            const dataProgress = await resProgress.json();

            if (dataProgress.success) {
                alert("Perubahan berhasil disimpan!");
                location.reload();
            } else {
                alert(dataProgress.message);
            }
        });

        // Instagram share
        document.getElementById('instagramShareBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            const shareUrl = "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>";
            const shareText = "Halo! Ada aksi lingkungan di EcoCare+ yang membutuhkan bantuanmu! Yuk gabung!";
            
            // Try navigator.share first
            if (navigator.share) {
                navigator.share({
                    title: 'EcoCare+ Aksi Lingkungan',
                    text: shareText,
                    url: shareUrl,
                }).catch(() => {
                    // Fallback if share fails
                    fallbackInstagramShare(shareText, shareUrl);
                });
            } else {
                // Fallback
                fallbackInstagramShare(shareText, shareUrl);
            }
        });
        
        function fallbackInstagramShare(text, url) {
            // Copy link to clipboard
            copyLink();
            // Open Instagram in new tab
            window.open('https://www.instagram.com', '_blank');
        }
        
        // Invite friend
        document.getElementById('inviteBtn')?.addEventListener('click', () => {
            if (isAdmin) return;
            const modal = document.getElementById('shareModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function copyLink() {
            const link = document.getElementById('shareLink');
            link.select();
            link.setSelectionRange(0, 99999);
            document.execCommand('copy');
            alert('Link berhasil disalin!');
        }

        // Close modal on outside click
        document.getElementById('shareModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('shareModal')) {
                document.getElementById('shareModal').classList.add('hidden');
                document.getElementById('shareModal').classList.remove('flex');
            }
        });
    </script>
</body>
</html>
