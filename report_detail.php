<?php
require 'config.php';
require_login();

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as reporter_name
        FROM reports r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        header('Location: index.php');
        exit;
    }

    // Check if community exists and user is a member
    $memberStmt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE report_id = ? AND user_id = ?");
    $memberStmt->execute([$report_id, $_SESSION['user_id']]);
    $is_member = $memberStmt->fetchColumn() > 0;

    $totalMembersStmt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE report_id = ?");
    $totalMembersStmt->execute([$report_id]);
    $totalMembers = $totalMembersStmt->fetchColumn();

} catch (PDOException $e) {
    die("Error loading report: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - EcoCare+</title>
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
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Peduli Lingkungan Kita</span>
                    </div>
                </a>

                <div class="flex items-center gap-6">
                    <a href="dashboard_pengguna.php" class="text-gray-700 hover:text-ecocare-primary font-medium transition">Dashboard</a>
                    <a href="my_community.php" class="text-gray-700 hover:text-ecocare-primary font-medium transition">Komunitas Saya</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
        <a href="dashboard_pengguna.php" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-8 hover:underline">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start justify-between mb-6 flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-ecocare-dark mb-2">Laporan #<?= $report['id'] ?></h1>
                        <p class="text-gray-500">Dilaporkan oleh <?= htmlspecialchars($report['reporter_name']) ?> pada <?= date('d M Y H:i', strtotime($report['created_at'])) ?></p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-<?= 
                        $report['status'] == 'Baru' ? 'red' : 
                        ($report['status'] == 'Diproses' ? 'yellow' : 
                        ($report['status'] == 'Selesai' ? 'green' : 
                        ($report['status'] == 'Komunitas Terbentuk' ? 'blue' : 'purple'))) ?>-100 text-<?= 
                        $report['status'] == 'Baru' ? 'red' : 
                        ($report['status'] == 'Diproses' ? 'yellow' : 
                        ($report['status'] == 'Selesai' ? 'green' : 
                        ($report['status'] == 'Komunitas Terbentuk' ? 'blue' : 'purple'))) ?>-700">
                        <?= htmlspecialchars($report['status']) ?>
                    </span>
                </div>

                <div class="mb-6">
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                        <?= htmlspecialchars($report['category']) ?>
                    </span>
                </div>

                <p class="text-lg text-gray-700 mb-6 leading-relaxed"><?= htmlspecialchars($report['description']) ?></p>

                <?php if ($report['photo_path']): ?>
                    <img src="<?= htmlspecialchars($report['photo_path']) ?>" alt="Foto Laporan" class="w-full h-80 object-cover rounded-xl mb-6 shadow-md">
                <?php endif; ?>

                <div class="flex items-center gap-3 text-gray-600 mb-6">
                    <i class="fas fa-map-marker-alt text-ecocare-primary text-xl"></i>
                    <span class="text-lg"><?= htmlspecialchars($report['location_address']) ?></span>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-4">
                    <a href="community.php?id=<?= $report_id ?>" class="inline-flex items-center gap-2 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3 rounded-xl font-semibold hover:shadow-xl transition-all">
                        <i class="fas fa-hands-helping"></i>
                        Masuk ke Komunitas Aksi
                    </a>
                    <?php if (!$is_member): ?>
                        <button id="joinBtn" class="inline-flex items-center gap-2 bg-white border-2 border-ecocare-primary text-ecocare-primary px-8 py-3 rounded-xl font-semibold hover:bg-ecocare-primary hover:text-white transition-all">
                            <i class="fas fa-user-plus"></i>
                            Gabung Komunitas
                        </button>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-8 py-3 rounded-xl font-semibold">
                            <i class="fas fa-check-circle"></i>
                            Anda Sudah Bergabung
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Member Stats -->
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-ecocare-primary/20 rounded-full flex items-center justify-center text-ecocare-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-extrabold text-ecocare-dark"><?= $totalMembers ?></p>
                            <p class="text-sm text-gray-500">Relawan telah bergabung</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const reportId = <?= $report_id ?>;
        const isMember = <?= $is_member ? 'true' : 'false' ?>;

        document.getElementById('joinBtn')?.addEventListener('click', async () => {
            if (isMember) return;
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
    </script>
</body>
</html>
