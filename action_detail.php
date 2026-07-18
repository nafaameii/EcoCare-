<?php
require 'config.php';
require_login();

$action_id = intval($_GET['id'] ?? 0);
if (!$action_id) {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

// Handle joining/leaving action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participate_action'])) {
    $user_id = $_SESSION['user_id'];
    $participate = $_POST['participate_action'] === 'join';
    
    try {
        if ($participate) {
            $stmt = $pdo->prepare("INSERT INTO action_participants (action_id, user_id) VALUES (?, ?)");
            $stmt->execute([$action_id, $user_id]);
            $message = 'Berhasil bergabung ke aksi!';
            $message_type = 'success';
        } else {
            $stmt = $pdo->prepare("DELETE FROM action_participants WHERE action_id = ? AND user_id = ?");
            $stmt->execute([$action_id, $user_id]);
            $message = 'Berhasil keluar dari aksi!';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Terjadi kesalahan: ' . $e->getMessage();
        $message_type = 'error';
    }
}

try {
    // Get action details with report info
    $stmt = $pdo->prepare("
        SELECT ca.*, r.title as report_title, r.description as report_description, r.location as report_location
        FROM community_actions ca 
        LEFT JOIN reports r ON ca.report_id = r.id
        WHERE ca.id = ?
    ");
    $stmt->execute([$action_id]);
    $action = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$action) {
        header('Location: index.php');
        exit;
    }
    
    // Check if user is a participant
    $is_participant = false;
    $stmt = $pdo->prepare("SELECT id FROM action_participants WHERE action_id = ? AND user_id = ?");
    $stmt->execute([$action_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $is_participant = true;
    }
    
    // Get participants list
    $stmt = $pdo->prepare("
        SELECT ap.*, u.name, u.profile_pic 
        FROM action_participants ap
        JOIN users u ON ap.user_id = u.id
        WHERE ap.action_id = ?
        ORDER BY ap.joined_at DESC
    ");
    $stmt->execute([$action_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $participant_count = count($participants);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Determine display status and progress
$display_status = $action['status'];
$display_progress = $action['progress'];
if ($action['status'] === 'completed') {
    $display_progress = 100;
}

$status_map = [
    'planned' => 'Direncanakan',
    'active' => 'Berlangsung',
    'completed' => 'Selesai'
];

$status_class = '';
switch($action['status']) {
    case 'planned':
        $status_class = 'bg-yellow-100 text-yellow-700 border-yellow-200';
        break;
    case 'active':
        $status_class = 'bg-blue-100 text-blue-700 border-blue-200';
        break;
    case 'completed':
        $status_class = 'bg-green-100 text-green-700 border-green-200';
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($action['title']) ?> - Detail Aksi | EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="dashboard_pengguna.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Peduli Lingkungan</span>
                    </div>
                </a>
                <div class="flex items-center gap-6">
                    <a href="dashboard_pengguna.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Dashboard</a>
                    <a href="komunitas_saya.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Komunitas</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
        <!-- Back Button -->
        <a href="dashboard_pengguna.php" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-8 hover:underline">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <?php if ($message): ?>
            <div class="mb-8 px-6 py-4 rounded-xl border flex items-center gap-3 animate-fade-in <?php 
                echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'
            ?>">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Left Column: Action Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Action Hero Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden animate-fade-in">
                    <?php if ($action['photo_path']): ?>
                        <img src="<?= htmlspecialchars($action['photo_path']) ?>" alt="Aksi" class="w-full h-72 object-cover">
                    <?php else: ?>
                        <div class="w-full h-72 bg-gradient-to-br from-green-100 to-blue-100 flex items-center justify-center">
                            <i class="fas fa-hands-helping text-6xl text-gray-300"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-8">
                        <div class="flex items-start justify-between mb-4">
                            <h1 class="text-3xl font-extrabold text-ecocare-dark"><?= htmlspecialchars($action['title']) ?></h1>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold border <?= $status_class ?>">
                                <?= $status_map[$display_status] ?>
                            </span>
                        </div>
                        
                        <?php if ($action['report_title']): ?>
                            <div class="mb-4 flex items-center gap-2 text-gray-600">
                                <i class="fas fa-users text-ecocare-primary"></i>
                                <span>Komunitas: <?= htmlspecialchars($action['report_title']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-lg text-gray-700 mb-6 leading-relaxed">
                            <?= htmlspecialchars($action['description']) ?>
                        </p>
                        
                        <!-- Action Info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-map-marker-alt text-ecocare-primary"></i>
                                    <span class="text-sm text-gray-600 font-medium">Lokasi</span>
                                </div>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($action['location'] ?? 'Belum ditentukan') ?></p>
                            </div>
                            
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-calendar text-ecocare-primary"></i>
                                    <span class="text-sm text-gray-600 font-medium">Tanggal</span>
                                </div>
                                <p class="font-semibold text-gray-800"><?= $action['target_date'] ? date('d M Y', strtotime($action['target_date'])) : 'Belum ditentukan' ?></p>
                            </div>
                            
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-user-friends text-ecocare-primary"></i>
                                    <span class="text-sm text-gray-600 font-medium">Relawan</span>
                                </div>
                                <p class="font-semibold text-gray-800">
                                    <?= $participant_count ?> / <?= $action['target_volunteers'] ?? 'Tidak ada batas' ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Progress -->
                        <div class="mb-8">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700 font-semibold">Progress Aksi</span>
                                <span class="font-extrabold text-ecocare-primary text-xl"><?= $display_progress ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark h-4 rounded-full transition-all duration-500" style="width: <?= $display_progress ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Join/Leave Button -->
                        <?php if ($display_status !== 'completed'): ?>
                            <form method="POST">
                                <?php if ($is_participant): ?>
                                    <input type="hidden" name="participate_action" value="leave">
                                    <button type="submit" class="w-full px-8 py-4 bg-gray-200 text-gray-700 rounded-2xl font-semibold hover:bg-gray-300 transition flex items-center justify-center gap-2">
                                        <i class="fas fa-sign-out-alt"></i> Keluar dari Aksi
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="participate_action" value="join">
                                    <button type="submit" class="w-full px-8 py-4 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white rounded-2xl font-semibold hover:shadow-xl hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-hands-helping"></i> Gabung Aksi
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Participants -->
            <div class="space-y-6">
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 animate-fade-in" style="animation-delay: 0.1s">
                    <h3 class="text-xl font-bold text-ecocare-dark mb-6 flex items-center gap-2">
                        <i class="fas fa-users text-ecocare-primary"></i>
                        Daftar Relawan (<?= $participant_count ?>)
                    </h3>
                    
                    <?php if (empty($participants)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-user-plus text-4xl mb-4 opacity-30"></i>
                            <p>Belum ada relawan yang bergabung</p>
                            <p class="text-sm mt-2">Jadilah yang pertama!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach ($participants as $participant): ?>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-2xl hover:bg-green-50 transition">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center text-white font-bold overflow-hidden flex-shrink-0">
                                        <?php if (isset($participant['profile_pic']) && $participant['profile_pic']): ?>
                                            <img src="<?= htmlspecialchars($participant['profile_pic']) ?>" class="w-full h-full object-cover" alt="Profil">
                                        <?php else: ?>
                                            <?= strtoupper(substr($participant['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($participant['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('d M Y', strtotime($participant['joined_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>