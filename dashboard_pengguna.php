<?php
require 'config.php';
require_login(); // Only logged-in users can access

// Get current user's reports
try {
    $stmt = $pdo->prepare("
        SELECT r.*,
               p.name as processed_by_name,
               c.name as completed_by_name
        FROM reports r 
        LEFT JOIN users p ON r.processed_by = p.id
        LEFT JOIN users c ON r.completed_by = c.id
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_reports = $stmt->fetchAll();

    // Get user's report statistics
    $total_user_reports = count($user_reports);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Baru'");
    $stmt->execute([$_SESSION['user_id']]);
    $user_reports_baru = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Diproses'");
    $stmt->execute([$_SESSION['user_id']]);
    $user_reports_diproses = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Selesai'");
    $stmt->execute([$_SESSION['user_id']]);
    $user_reports_selesai = $stmt->fetchColumn();

} catch(PDOException $e) {
    $user_reports = [];
    $total_user_reports = 0;
    $user_reports_baru = 0;
    $user_reports_diproses = 0;
    $user_reports_selesai = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - EcoCare+</title>
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
        .stat-card:hover { transform: translateY(-5px); }
        .timeline-line {
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #6FAF8F, #3D8B6A);
        }
    </style>
</head>
<body class="bg-ecocare-cream text-ecocare-dark min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-lg shadow-sm sticky top-0 z-50 border-b border-ecocare-secondary/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-ecocare-primary/30">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Peduli Lingkungan Kita</span>
                    </div>
                </a>

                <div class="flex items-center gap-8">
                    <a href="index.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Beranda</a>
                    <a href="map.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Peta</a>
                    <a href="my_community.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium transition">Komunitas Saya</a>
                    <div class="flex items-center gap-3">
                        <a href="user_profile.php" class="flex items-center gap-3 group">
                            <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary group-hover:border-ecocare-dark transition">
                                <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] && file_exists($_SESSION['profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                <?php else: ?>
                                    <div class="w-full h-full bg-ecocare-primary flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-ecocare-dark font-semibold hidden md:block"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        </a>
                        <a href="logout.php" class="text-ecocare-dark hover:text-red-500 transition flex items-center gap-1">
                            <i class="fas fa-sign-out-alt"></i> <span class="hidden md:block">Keluar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-extrabold text-ecocare-dark mb-3">Dashboard Pengguna</h1>
            <p class="text-lg text-ecocare-dark/70">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['name']); ?>! Lihat laporan Anda di sini.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Laporan</p>
                        <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_user_reports; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Laporan Baru</p>
                        <p class="text-3xl font-bold text-ecocare-dark"><?php echo $user_reports_baru; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-ecocare-orange to-orange-500 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Diproses</p>
                        <p class="text-3xl font-bold text-ecocare-dark"><?php echo $user_reports_diproses; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Selesai</p>
                        <p class="text-3xl font-bold text-ecocare-dark"><?php echo $user_reports_selesai; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-10 flex flex-wrap gap-4">
            <a href="submit_report.php" class="inline-block bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-4 px-8 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/40 transition flex items-center gap-3">
                <i class="fas fa-plus-circle text-xl"></i> Buat Laporan Baru
            </a>
            <a href="my_community.php" class="inline-block bg-white border-2 border-ecocare-primary text-ecocare-primary font-bold py-4 px-8 rounded-2xl hover:bg-ecocare-primary hover:text-white transition flex items-center gap-3">
                <i class="fas fa-users text-xl"></i> Komunitas Saya
            </a>
        </div>

        <!-- My Reports -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-ecocare-dark">Laporan Saya</h3>
                <span class="text-sm text-gray-500"><?php echo count($user_reports); ?> laporan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($user_reports)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4 opacity-30"></i>
                                    <p>Belum ada laporan</p>
                                    <a href="submit_report.php" class="text-ecocare-primary font-medium hover:underline mt-2 inline-block">Buat laporan pertama Anda</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_reports as $report): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $report['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                            <?php echo htmlspecialchars($report['category']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($report['location_address']); ?>">
                                        <?php echo htmlspecialchars(substr($report['location_address'], 0, 40)); ?>...
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = '';
                                        switch($report['status']) {
                                            case 'Baru': $status_class = 'bg-red-100 text-red-700'; break;
                                            case 'Diproses': $status_class = 'bg-yellow-100 text-yellow-700'; break;
                                            case 'Selesai': $status_class = 'bg-green-100 text-green-700'; break;
                                        }
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($report['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y H:i', strtotime($report['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="report_detail.php?id=<?php echo $report['id']; ?>" class="text-ecocare-primary hover:text-ecocare-green-dark font-medium transition">
                                            <i class="fas fa-eye mr-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Report Detail Modal -->
    <div id="reportDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Detail Laporan #<span id="reportIdDisplay"></span></h3>
                <button onclick="closeReportModal()" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="reportDetailContent" class="p-6">
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-ecocare-dark text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="opacity-70">&copy; 2026 EcoCare+. Made with <i class="fas fa-heart text-red-400"></i> for our planet.</p>
        </div>
    </footer>

    <script>
        function showReportDetail(report) {
            document.getElementById('reportIdDisplay').textContent = report.id;
            const content = document.getElementById('reportDetailContent');

            let statusClass = '';
            switch(report.status) {
                case 'Baru': statusClass = 'bg-red-100 text-red-700'; break;
                case 'Diproses': statusClass = 'bg-yellow-100 text-yellow-700'; break;
                case 'Selesai': statusClass = 'bg-green-100 text-green-700'; break;
            }

            let timelineSteps = `
                <div class="relative pl-12">
                    <div class="timeline-line"></div>
                    <!-- Step 1: Laporan Dikirim -->
                    <div class="relative mb-10">
                        <div class="absolute left-[-34px] w-8 h-8 rounded-full bg-ecocare-primary flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                            <h4 class="font-bold text-ecocare-dark mb-1">Laporan Dikirim</h4>
                            <p class="text-sm text-gray-500 mb-2">${new Date(report.created_at).toLocaleString('id-ID')}</p>
                            <p class="text-gray-700 text-sm">Anda telah berhasil mengirimkan laporan.</p>
                        </div>
                    </div>
            `;

            // Step 2: Diproses
            if (report.processed_at) {
                timelineSteps += `
                    <div class="relative mb-10">
                        <div class="absolute left-[-34px] w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
                            <h4 class="font-bold text-yellow-800 mb-1">Diverifikasi & Diproses</h4>
                            <p class="text-sm text-yellow-700 mb-2">
                                <i class="fas fa-user mr-1"></i> ${report.processed_by_name || 'Admin'} • ${new Date(report.processed_at).toLocaleString('id-ID')}
                            </p>
                            ${report.admin_notes ? `<p class="text-yellow-900 text-sm mt-2 bg-white/70 p-3 rounded-lg border border-yellow-200"><i class="fas fa-comment mr-2"></i>${report.admin_notes}</p>` : ''}
                        </div>
                    </div>
                `;
            }

            // Step 3: Selesai
            if (report.completed_at) {
                timelineSteps += `
                    <div class="relative mb-4">
                        <div class="absolute left-[-34px] w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                            <h4 class="font-bold text-green-800 mb-1">Laporan Selesai</h4>
                            <p class="text-sm text-green-700 mb-2">
                                <i class="fas fa-user mr-1"></i> ${report.completed_by_name || 'Admin'} • ${new Date(report.completed_at).toLocaleString('id-ID')}
                            </p>
                            ${report.completion_notes ? `<p class="text-green-900 text-sm mt-2 bg-white/70 p-3 rounded-lg border border-green-200"><i class="fas fa-comment mr-2"></i>${report.completion_notes}</p>` : ''}
                            ${report.completion_photo ? `
                                <div class="mt-3">
                                    <h5 class="text-sm font-semibold text-green-800 mb-2"><i class="fas fa-image mr-1"></i>Bukti Penyelesaian</h5>
                                    <img src="${report.completion_photo}" class="w-full max-h-64 object-cover rounded-xl border border-green-200" alt="Bukti Penyelesaian">
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            timelineSteps += `</div>`;

            content.innerHTML = `
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Status</h4>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${report.status}</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Kategori</h4>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">${report.category}</span>
                    </div>
                </div>

                <div class="mb-8">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Deskripsi Laporan</h4>
                    <p class="text-gray-900 bg-gray-50 p-4 rounded-xl">${report.description}</p>
                </div>

                ${report.photo_path ? `
                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Foto Laporan</h4>
                        <img src="${report.photo_path}" alt="Foto Laporan" class="w-full h-64 object-cover rounded-xl">
                    </div>
                ` : ''}

                <div class="mb-8">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Lokasi</h4>
                    <p class="text-gray-900 bg-gray-50 p-4 rounded-xl">
                        <i class="fas fa-map-marker-alt text-ecocare-primary mr-2"></i>
                        ${report.location_address}
                    </p>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-lg font-bold text-ecocare-dark mb-6 flex items-center gap-2">
                        <i class="fas fa-route text-ecocare-primary"></i> Timeline Laporan
                    </h4>
                    ${timelineSteps}
                </div>
            `;

            document.getElementById('reportDetailModal').classList.remove('hidden');
            document.getElementById('reportDetailModal').classList.add('flex');
        }

        function closeReportModal() {
            document.getElementById('reportDetailModal').classList.add('hidden');
            document.getElementById('reportDetailModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('reportDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeReportModal();
        });
    </script>
</body>
</html>
