<?php
require 'config.php';
require_admin();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['report_id'])) {
        $report_id = (int)$_POST['report_id'];
        $action = $_POST['action'];
        $admin_id = $_SESSION['user_id'];

        try {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
                $message = "Laporan berhasil dihapus!";
                $message_type = 'success';
            } else if ($action === 'Diproses') {
                $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
                $stmt = $pdo->prepare("UPDATE reports SET status = ?, processed_by = ?, processed_at = NOW(), admin_notes = ? WHERE id = ?");
                $stmt->execute([$action, $admin_id, $admin_notes, $report_id]);
                $message = "Status laporan berhasil diubah menjadi Diproses!";
                $message_type = 'success';
            } else if ($action === 'Selesai') {
                $completion_notes = isset($_POST['completion_notes']) ? trim($_POST['completion_notes']) : '';
                $completion_photo = null;

                // Handle photo upload
                if (isset($_FILES['completion_photo']) && $_FILES['completion_photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/completion';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                    $file_ext = strtolower(pathinfo($_FILES['completion_photo']['name'], PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_ext, $allowed_exts)) {
                        $file_name = 'completion_' . $report_id . '_' . time() . '.' . $file_ext;
                        $file_path = $upload_dir . '/' . $file_name;

                        if (move_uploaded_file($_FILES['completion_photo']['tmp_name'], $file_path)) {
                            $completion_photo = $file_path;
                        }
                    }
                }

                $stmt = $pdo->prepare("UPDATE reports SET status = ?, completed_by = ?, completed_at = NOW(), completion_notes = ?, completion_photo = ? WHERE id = ?");
                $stmt->execute([$action, $admin_id, $completion_notes, $completion_photo, $report_id]);
                $message = "Status laporan berhasil diubah menjadi Selesai!";
                $message_type = 'success';
            } else if (in_array($action, ['Baru'])) {
                $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
                $stmt->execute([$action, $report_id]);
                $message = "Status laporan berhasil diperbarui!";
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all reports with admin names
try {
    $stmt = $pdo->query("
        SELECT 
            r.*, 
            u.name as user_name, 
            u.email as user_email,
            p.name as processed_by_name,
            c.name as completed_by_name
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN users p ON r.processed_by = p.id
        LEFT JOIN users c ON r.completed_by = c.id
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - EcoCare+</title>
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
                        <a href="admin_reports.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="fas fa-file-alt w-5"></i>
                            <span>Kelola Laporan</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
                        <h1 class="text-2xl font-bold text-ecocare-dark">Kelola Laporan</h1>
                        <p class="text-gray-500 text-sm">Semua laporan dari pengguna</p>
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

                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-ecocare-dark">Daftar Laporan</h3>
                        <span class="text-sm text-gray-500"><?php echo count($reports); ?> laporan ditemukan</span>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="px-6 py-16 text-center text-gray-500">
                            <i class="fas fa-inbox text-5xl mb-4 opacity-30"></i>
                            <h4 class="text-xl font-semibold mb-2">Belum ada laporan</h4>
                            <p>Belum ada laporan dari pengguna</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelapor</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $report['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($report['user_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($report['user_email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                                    <?php echo htmlspecialchars($report['category']); ?>
                                                </span>
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
                                                <div class="flex items-center gap-2">
                                                    <!-- View Details -->
                                                    <button onclick="showDetail(<?php echo htmlspecialchars(json_encode($report)); ?>)" class="w-9 h-9 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-200 transition" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <!-- Status Modal -->
                                                    <button onclick="openStatusModal(<?php echo htmlspecialchars(json_encode($report)); ?>)" class="w-9 h-9 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center hover:bg-yellow-200 transition" title="Ubah Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <!-- Delete -->
                                                    <button onclick="confirmDelete(<?php echo $report['id']; ?>)" class="w-9 h-9 bg-red-100 text-red-600 rounded-lg flex items-center justify-center hover:bg-red-200 transition" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Detail Laporan</h3>
                <button onclick="closeModal('detailModal')" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6">
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-ecocare-dark">Ubah Status Laporan</h3>
                <button onclick="closeModal('statusModal')" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="statusModalContent" class="p-6">
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="report_id" id="deleteReportId">
        <input type="hidden" name="action" value="delete">
    </form>

    <script>
        function showDetail(report) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('modalContent');

            let photoHtml = '';
            if (report.photo_path) {
                photoHtml = `
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Foto Laporan</h4>
                        <img src="${report.photo_path}" alt="Foto Laporan" class="w-full h-64 object-cover rounded-xl">
                    </div>
                `;
            }

            let completionPhotoHtml = '';
            if (report.completion_photo) {
                completionPhotoHtml = `
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Bukti Penyelesaian</h4>
                        <img src="${report.completion_photo}" alt="Bukti Penyelesaian" class="w-full h-64 object-cover rounded-xl">
                    </div>
                `;
            }

            let statusClass = '';
            switch(report.status) {
                case 'Baru': statusClass = 'bg-red-100 text-red-700'; break;
                case 'Diproses': statusClass = 'bg-yellow-100 text-yellow-700'; break;
                case 'Selesai': statusClass = 'bg-green-100 text-green-700'; break;
            }

            content.innerHTML = `
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">ID Laporan</h4>
                        <p class="text-gray-900">#${report.id}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Status</h4>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${report.status}</span>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Pelapor</h4>
                        <p class="text-gray-900 font-medium">${report.user_name}</p>
                        <p class="text-gray-500 text-sm">${report.user_email}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Kategori</h4>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">${report.category}</span>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Deskripsi</h4>
                    <p class="text-gray-900 bg-gray-50 p-4 rounded-xl">${report.description}</p>
                </div>

                ${photoHtml}

                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Lokasi</h4>
                    <p class="text-gray-900 bg-gray-50 p-4 rounded-xl">
                        <i class="fas fa-map-marker-alt text-ecocare-primary mr-2"></i>
                        ${report.location_address}
                    </p>
                </div>

                ${report.processed_at ? `
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <h4 class="text-sm font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-clock mr-2"></i>Diproses oleh ${report.processed_by_name || 'Admin'} pada ${new Date(report.processed_at).toLocaleString('id-ID')}
                        </h4>
                        ${report.admin_notes ? `<p class="text-yellow-900 text-sm">Catatan: ${report.admin_notes}</p>` : ''}
                    </div>
                ` : ''}

                ${report.completed_at ? `
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                        <h4 class="text-sm font-semibold text-green-800 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>Selesai oleh ${report.completed_by_name || 'Admin'} pada ${new Date(report.completed_at).toLocaleString('id-ID')}
                        </h4>
                        ${report.completion_notes ? `<p class="text-green-900 text-sm">Catatan: ${report.completion_notes}</p>` : ''}
                    </div>
                ` : ''}

                ${completionPhotoHtml}

                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Tanggal Laporan</h4>
                    <p class="text-gray-900">${new Date(report.created_at).toLocaleString('id-ID')}</p>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openStatusModal(report) {
            const modal = document.getElementById('statusModal');
            const content = document.getElementById('statusModalContent');

            let formHtml = `
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="report_id" value="${report.id}">

                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-4">Pilih Status Baru</h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition">
                                <input type="radio" name="action" value="Baru" ${report.status === 'Baru' ? 'checked' : ''} class="w-5 h-5 text-ecocare-primary">
                                <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                                <span class="font-medium text-gray-900">Baru</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition">
                                <input type="radio" name="action" value="Diproses" ${report.status === 'Diproses' ? 'checked' : ''} class="w-5 h-5 text-ecocare-primary">
                                <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                                <span class="font-medium text-gray-900">Diproses</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition">
                                <input type="radio" name="action" value="Selesai" ${report.status === 'Selesai' ? 'checked' : ''} class="w-5 h-5 text-ecocare-primary">
                                <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                                <span class="font-medium text-gray-900">Selesai</span>
                            </label>
                        </div>
                    </div>

                    <div id="diprosesFields" class="mb-6 hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Admin (Opsional)</label>
                        <textarea name="admin_notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-transparent transition" placeholder="Tambahkan catatan untuk pengguna...">${report.admin_notes || ''}</textarea>
                    </div>

                    <div id="selesaiFields" class="mb-6 hidden">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Penyelesaian (Opsional)</label>
                            <textarea name="completion_notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-transparent transition" placeholder="Deskripsikan tindakan yang telah dilakukan...">${report.completion_notes || ''}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Bukti Penyelesaian (Opsional)</label>
                            <input type="file" name="completion_photo" accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-transparent transition">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeModal('statusModal')" class="flex-1 px-4 py-3 text-gray-700 bg-gray-100 rounded-xl font-semibold hover:bg-gray-200 transition">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-3 text-white bg-ecocare-primary rounded-xl font-semibold hover:bg-ecocare-green-dark transition">Simpan</button>
                    </div>
                </form>
            `;

            content.innerHTML = formHtml;

            // Show/hide fields based on radio selection
            const radios = content.querySelectorAll('input[name="action"]');
            const diprosesFields = document.getElementById('diprosesFields');
            const selesaiFields = document.getElementById('selesaiFields');

            function updateFields() {
                const selected = content.querySelector('input[name="action"]:checked');
                if (selected) {
                    diprosesFields.classList.toggle('hidden', selected.value !== 'Diproses');
                    selesaiFields.classList.toggle('hidden', selected.value !== 'Selesai');
                }
            }

            radios.forEach(radio => radio.addEventListener('change', updateFields));
            updateFields();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
                document.getElementById('deleteReportId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        document.querySelectorAll('#detailModal, #statusModal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });
    </script>
</body>
</html>
