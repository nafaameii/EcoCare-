<?php
require 'config.php';
require_login();

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as reporter_name FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        header('Location: index.php');
        exit;
    }
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
        .member-avatar { background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%); }
    </style>
</head>
<body class="bg-gradient-to-br from-ecocare-cream to-white text-ecocare-dark">
    <nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">EcoCare+</span>
                </a>
                
                <div class="flex items-center gap-6">
                    <a href="dashboard_pengguna.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Dashboard</a>
                    <a href="my_community.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Komunitas Saya</a>
                    <a href="map.php" class="text-gray-700 font-medium hover:text-ecocare-primary transition">Peta</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
        <a href="dashboard_pengguna.php" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-8 hover:underline">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Report Header -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
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

                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="fas fa-map-marker-alt text-ecocare-primary text-xl"></i>
                        <span class="text-lg"><?= htmlspecialchars($report['location_address']) ?></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-4 mt-8 pt-6 border-t border-gray-100">
                        <button id="joinBtn" class="inline-flex items-center gap-2 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3 rounded-xl font-semibold hover:shadow-xl transition-all">
                            <i class="fas fa-users"></i>
                            <span id="joinBtnText">Ikut Menindaklanjuti</span>
                        </button>
                        <button id="shareBtn" class="inline-flex items-center gap-2 bg-white border-2 border-ecocare-primary text-ecocare-primary px-6 py-3 rounded-xl font-semibold hover:bg-ecocare-primary hover:text-white transition-all">
                            <i class="fas fa-share-alt"></i> Ajak Teman
                        </button>
                    </div>
                </div>

                <!-- Community Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div id="statMembers" class="stat-card bg-white rounded-xl p-6 shadow-md border border-gray-100">
                        <div class="text-3xl font-extrabold text-ecocare-primary mb-1" id="memberCount">0</div>
                        <div class="text-gray-500 text-sm font-medium">Relawan</div>
                    </div>
                    <div id="statActions" class="stat-card bg-white rounded-xl p-6 shadow-md border border-gray-100">
                        <div class="text-3xl font-extrabold text-purple-600 mb-1" id="actionCount">0</div>
                        <div class="text-gray-500 text-sm font-medium">Aksi</div>
                    </div>
                    <div id="statContrib" class="stat-card bg-white rounded-xl p-6 shadow-md border border-gray-100">
                        <div class="text-3xl font-extrabold text-orange-500 mb-1" id="contribCount">0</div>
                        <div class="text-gray-500 text-sm font-medium">Kontribusi</div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h2 class="text-2xl font-bold text-ecocare-dark mb-6 flex items-center gap-2">
                        <i class="fas fa-route text-ecocare-primary"></i> Timeline Aksi
                    </h2>
                    <div class="relative pl-8 border-l-2 border-ecocare-secondary space-y-6">
                        <?php
                        $timeline = [
                            ['step' => 1, 'title' => 'Laporan Dibuat', 'date' => $report['created_at'], 'active' => true],
                            ['step' => 2, 'title' => 'Diverifikasi Admin', 'date' => null, 'active' => in_array($report['status'], ['Diproses', 'Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai'])],
                            ['step' => 3, 'title' => 'Komunitas Terbentuk', 'date' => null, 'active' => in_array($report['status'], ['Komunitas Terbentuk', 'Aksi Berjalan', 'Selesai'])],
                            ['step' => 4, 'title' => 'Aksi Berjalan', 'date' => null, 'active' => in_array($report['status'], ['Aksi Berjalan', 'Selesai'])],
                            ['step' => 5, 'title' => 'Selesai', 'date' => null, 'active' => $report['status'] == 'Selesai'],
                        ];
                        foreach ($timeline as $item):
                        ?>
                        <div class="relative">
                            <div class="absolute -left-10 top-0 w-6 h-6 rounded-full border-4 border-white <?= $item['active'] ? 'bg-ecocare-primary' : 'bg-gray-200' ?> shadow-md"></div>
                            <div>
                                <div class="font-semibold text-lg <?= $item['active'] ? 'text-ecocare-dark' : 'text-gray-400' ?>"><?= $item['title'] ?></div>
                                <div class="text-sm text-gray-500"><?= $item['date'] ? date('d M Y H:i', strtotime($item['date'])) : 'Menunggu...' ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Community Actions -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                        <h2 class="text-2xl font-bold text-ecocare-dark flex items-center gap-2">
                            <i class="fas fa-hands-helping text-ecocare-primary"></i> Aksi Komunitas
                        </h2>
                        <button id="createActionBtn" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition hidden">
                            <i class="fas fa-plus mr-2"></i> Buat Aksi
                        </button>
                    </div>

                    <div id="actionsList" class="space-y-4">
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-calendar-check text-5xl mb-4 opacity-30"></i>
                            <p class="text-lg">Loading aksi komunitas...</p>
                        </div>
                    </div>
                </div>

                <!-- Discussion -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h2 class="text-2xl font-bold text-ecocare-dark mb-6 flex items-center gap-2">
                        <i class="fas fa-comments text-ecocare-primary"></i> Diskusi Komunitas
                    </h2>

                    <div id="commentFormContainer" class="mb-8 hidden">
                        <form id="commentForm" class="space-y-4">
                            <textarea id="commentInput" rows="3" class="w-full border-2 border-gray-200 rounded-xl p-4 focus:outline-none focus:border-ecocare-primary focus:ring-4 focus:ring-ecocare-primary/20" placeholder="Tulis komentar atau koordinasi kegiatan..."></textarea>
                            <button type="submit" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3 rounded-xl font-semibold hover:shadow-lg transition">
                                Kirim Komentar
                            </button>
                        </form>
                    </div>

                    <div id="commentsList" class="space-y-4">
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-comment-dots text-5xl mb-4 opacity-30"></i>
                            <p class="text-lg">Loading diskusi...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Community Members -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-ecocare-dark mb-6 flex items-center gap-2">
                        <i class="fas fa-user-friends text-ecocare-primary"></i> Anggota Komunitas
                    </h3>
                    <div id="membersList" class="space-y-3">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-3xl mb-2 opacity-30"></i>
                            <p>Loading anggota...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Action Modal -->
    <div id="createActionModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Buat Aksi Komunitas</h3>
                <button onclick="closeActionModal()" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="createActionForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Aksi *</label>
                        <input type="text" id="actionTitle" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi *</label>
                        <textarea id="actionDescription" rows="4" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" required></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai</label>
                            <input type="date" id="actionStartDate" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai</label>
                            <input type="date" id="actionEndDate" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Target Relawan</label>
                        <input type="number" id="actionTargetVolunteers" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-ecocare-primary" min="1">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white py-3 rounded-xl font-semibold hover:shadow-xl transition">
                        <i class="fas fa-rocket mr-2"></i> Buat Aksi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Ajak Teman Bergabung!</h3>
                <button onclick="closeShareModal()" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Bagikan laporan ini agar lebih banyak orang dapat membantu!</p>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <button onclick="shareWhatsApp()" class="bg-green-500 text-white p-4 rounded-xl hover:bg-green-600 transition flex flex-col items-center gap-2">
                        <i class="fab fa-whatsapp text-2xl"></i>
                        <span class="text-sm font-medium">WhatsApp</span>
                    </button>
                    <button onclick="shareTelegram()" class="bg-blue-500 text-white p-4 rounded-xl hover:bg-blue-600 transition flex flex-col items-center gap-2">
                        <i class="fab fa-telegram text-2xl"></i>
                        <span class="text-sm font-medium">Telegram</span>
                    </button>
                    <button onclick="copyLink()" class="bg-gray-700 text-white p-4 rounded-xl hover:bg-gray-800 transition flex flex-col items-center gap-2">
                        <i class="fas fa-link text-2xl"></i>
                        <span class="text-sm font-medium">Salin Link</span>
                    </button>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl flex items-center gap-3">
                    <input type="text" id="shareLink" readonly class="bg-transparent flex-1 text-sm text-gray-700" value="">
                    <button onclick="copyLink()" class="text-ecocare-primary font-semibold">
                        <i class="fas fa-copy mr-1"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const reportId = <?= $report_id ?>;
        let isMember = false;
        let communityData = null;
        
        async function loadCommunityData() {
            try {
                const res = await fetch(`api/community.php?action=get_data&report_id=${reportId}`);
                const data = await res.json();
                
                if (!data.success) {
                    const alertMsg = document.createElement('div');
                    alertMsg.className = 'max-w-7xl mx-auto px-6 lg:px-8 py-4';
                    alertMsg.innerHTML = `
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-6 py-6 rounded-xl">
                            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                Database Belum Dimigrasi
                            </h3>
                            <p class="mb-6">Untuk menggunakan fitur komunitas, silakan jalankan migrasi terlebih dahulu!</p>
                            <a href="migrate_community_system.php" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3 rounded-xl font-semibold hover:shadow-lg transition-all">
                                <i class="fas fa-database"></i>
                                Jalankan Migrasi Sekarang
                            </a>
                        </div>
                    `;
                    document.querySelector('nav').after(alertMsg);
                    
                    document.getElementById('memberCount').textContent = '0';
                    document.getElementById('actionCount').textContent = '0';
                    document.getElementById('contribCount').textContent = '0';
                    
                    document.getElementById('membersList').innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-3xl mb-2 opacity-30"></i>
                            <p>Database belum dimigrasi</p>
                        </div>
                    `;
                    
                    document.getElementById('actionsList').innerHTML = `
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-calendar-check text-5xl mb-4 opacity-30"></i>
                            <p class="text-lg">Database belum dimigrasi</p>
                        </div>
                    `;
                    
                    document.getElementById('commentsList').innerHTML = `
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-comment-slash text-5xl mb-4 opacity-30"></i>
                            <p>Database belum dimigrasi</p>
                        </div>
                    `;
                    
                    return;
                }
                
                communityData = data.data;
                isMember = communityData.isMember;
                
                document.getElementById('memberCount').textContent = communityData.memberCount;
                document.getElementById('actionCount').textContent = communityData.actionCount;
                document.getElementById('contribCount').textContent = communityData.contribCount;
                
                updateJoinButton();
                renderMembers();
                renderActions();
                renderComments();
                
                document.getElementById('createActionBtn').classList.toggle('hidden', !isMember);
                document.getElementById('commentFormContainer').classList.toggle('hidden', !isMember);
            } catch (error) {
                console.error('Error loading community data:', error);
                alert('Terjadi kesalahan saat memuat data komunitas');
            }
        }
        
        function updateJoinButton() {
            const btn = document.getElementById('joinBtn');
            const btnText = document.getElementById('joinBtnText');
            if (isMember) {
                btn.classList.remove('from-ecocare-primary', 'to-ecocare-green-dark');
                btn.classList.add('from-gray-500', 'to-gray-600');
                btnText.textContent = 'Keluar dari Komunitas';
            } else {
                btn.classList.add('from-ecocare-primary', 'to-ecocare-green-dark');
                btn.classList.remove('from-gray-500', 'to-gray-600');
                btnText.textContent = 'Ikut Menindaklanjuti';
            }
        }
        
        function renderMembers() {
            const container = document.getElementById('membersList');
            if (!communityData.members.length) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-3xl mb-2 opacity-30"></i>
                        <p>Belum ada anggota</p>
                    </div>`;
                return;
            }
            
            container.innerHTML = communityData.members.map(member => `
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <div class="w-10 h-10 member-avatar rounded-full flex items-center justify-center text-white font-bold text-sm">
                        ${member.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-ecocare-dark truncate">${htmlspecialchars(member.name)}</p>
                        <p class="text-xs text-gray-500">${new Date(member.joined_at).toLocaleDateString('id-ID')}</p>
                    </div>
                </div>`).join('');
        }
        
        function renderActions() {
            const container = document.getElementById('actionsList');
            if (!communityData.actions.length) {
                container.innerHTML = `
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-calendar-check text-5xl mb-4 opacity-30"></i>
                        <p class="text-lg">Belum ada aksi komunitas untuk laporan ini</p>
                        <p class="text-sm mt-2">Jadilah yang pertama membuat aksi!</p>
                    </div>`;
                return;
            }
            
            const statusColors = {
                planned: 'bg-yellow-100 text-yellow-700',
                active: 'bg-green-100 text-green-700',
                completed: 'bg-gray-100 text-gray-700',
                cancelled: 'bg-red-100 text-red-700'
            };
            
            const statusNames = {
                planned: 'Direncanakan',
                active: 'Berlangsung',
                completed: 'Selesai',
                cancelled: 'Dibatalkan'
            };
            
            container.innerHTML = communityData.actions.map(action => `
                <div class="border border-gray-200 rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-ecocare-dark">${htmlspecialchars(action.title)}</h3>
                            <p class="text-gray-500 text-sm">Dibuat oleh ${htmlspecialchars(action.creator_name)}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColors[action.status]}">
                            ${statusNames[action.status]}
                        </span>
                    </div>
                    <p class="text-gray-700 mb-4">${htmlspecialchars(action.description)}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                        ${action.start_date ? `<span><i class="fas fa-calendar mr-1"></i> ${new Date(action.start_date).toLocaleDateString('id-ID')}</span>` : ''}
                        ${action.target_volunteers ? `<span><i class="fas fa-users mr-1"></i> Target: ${action.target_volunteers} relawan</span>` : ''}
                    </div>
                    <div class="mb-2">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Progress</span>
                            <span class="font-semibold">${action.progress}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark h-2 rounded-full" style="width: ${action.progress}%"></div>
                        </div>
                    </div>
                </div>`).join('');
        }
        
        function renderComments() {
            const container = document.getElementById('commentsList');
            if (!communityData.comments.length) {
                container.innerHTML = `
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-comment-slash text-5xl mb-4 opacity-30"></i>
                        <p>Belum ada diskusi</p>
                    </div>`;
                return;
            }
            
            container.innerHTML = communityData.comments.map(comment => `
                <div class="border-l-4 border-ecocare-primary pl-4 py-2">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-ecocare-dark">${htmlspecialchars(comment.name)}</span>
                        <span class="text-sm text-gray-500">${new Date(comment.created_at).toLocaleString('id-ID')}</span>
                    </div>
                    <p class="text-gray-700">${htmlspecialchars(comment.comment)}</p>
                </div>`).join('');
        }
        
        document.getElementById('joinBtn').addEventListener('click', async () => {
            const action = isMember ? 'leave' : 'join';
            const formData = new FormData();
            formData.append('report_id', reportId);
            
            const res = await fetch(`api/community.php?action=${action}`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                alert(data.message);
                loadCommunityData();
            } else {
                alert(data.message);
            }
        });
        
        document.getElementById('commentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const comment = document.getElementById('commentInput').value;
            if (!comment) return;
            
            const formData = new FormData();
            formData.append('report_id', reportId);
            formData.append('comment', comment);
            
            const res = await fetch('api/community.php?action=add_comment', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('commentInput').value = '';
                loadCommunityData();
            } else {
                alert(data.message);
            }
        });
        
        document.getElementById('createActionBtn').addEventListener('click', () => {
            document.getElementById('createActionModal').classList.remove('hidden');
            document.getElementById('createActionModal').classList.add('flex');
        });
        
        function closeActionModal() {
            document.getElementById('createActionModal').classList.add('hidden');
            document.getElementById('createActionModal').classList.remove('flex');
        }
        
        document.getElementById('createActionForm').addEventListener('submit', async (e) => {
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
                closeActionModal();
                document.getElementById('createActionForm').reset();
                loadCommunityData();
            } else {
                alert(data.message);
            }
        });
        
        document.getElementById('shareBtn').addEventListener('click', () => {
            document.getElementById('shareLink').value = window.location.href;
            document.getElementById('shareModal').classList.remove('hidden');
            document.getElementById('shareModal').classList.add('flex');
        });
        
        function closeShareModal() {
            document.getElementById('shareModal').classList.add('hidden');
            document.getElementById('shareModal').classList.remove('flex');
        }
        
        function copyLink() {
            const link = window.location.href;
            navigator.clipboard.writeText(link).then(() => {
                alert('Link berhasil disalin!');
            });
        }
        
        function shareWhatsApp() {
            const text = `Halo! Ada laporan lingkungan di EcoCare+ yang membutuhkan bantuan. Yuk bergabung: ${window.location.href}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }
        
        function shareTelegram() {
            const text = `Halo! Ada laporan lingkungan di EcoCare+ yang membutuhkan bantuan. Yuk bergabung: ${window.location.href}`;
            window.open(`https://t.me/share/url?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(text)}`, '_blank');
        }
        
        document.getElementById('createActionModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('createActionModal')) closeActionModal();
        });
        
        document.getElementById('shareModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('shareModal')) closeShareModal();
        });
        
        function htmlspecialchars(str) {
            return (str || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        
        loadCommunityData();
    </script>
</body>
</html>
