<?php
require 'config.php';
require_admin();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                // Handle file upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/education/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        $image_path = $target_file;
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO educations (title, content, image_path) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['title'], $_POST['content'], $image_path]);
                $message = "Artikel edukasi berhasil ditambahkan!";
                $message_type = 'success';
                
            } elseif ($_POST['action'] === 'edit') {
                // Handle file upload for edit
                $image_path = $_POST['current_image'] ?? null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/education/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        // Delete old image if exists
                        if ($image_path && file_exists($image_path)) {
                            unlink($image_path);
                        }
                        $image_path = $target_file;
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE educations SET title = ?, content = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$_POST['title'], $_POST['content'], $image_path, $_POST['id']]);
                $message = "Artikel edukasi berhasil diperbarui!";
                $message_type = 'success';
                
            } elseif ($_POST['action'] === 'delete') {
                // Delete image file first
                $stmt = $pdo->prepare("SELECT image_path FROM educations WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $edu = $stmt->fetch();
                if ($edu && $edu['image_path'] && file_exists($edu['image_path'])) {
                    unlink($edu['image_path']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM educations WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "Artikel edukasi berhasil dihapus!";
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all educations
try {
    $stmt = $pdo->query("SELECT * FROM educations ORDER BY created_at DESC");
    $educations = $stmt->fetchAll();
} catch(PDOException $e) {
    $educations = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Edukasi - EcoCare+ Admin</title>
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
                        <a href="admin_reports.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
                        <a href="admin_education.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
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
                        <h1 class="text-2xl font-bold text-ecocare-dark">Kelola Edukasi</h1>
                        <p class="text-gray-500 text-sm">Tambah, edit, dan hapus artikel edukasi</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button onclick="openAddModal()" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-6 py-2 rounded-xl font-semibold hover:shadow-lg transition flex items-center gap-2">
                            <i class="fas fa-plus"></i> Tambah Artikel
                        </button>
                        <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                            <i class="fas fa-home"></i>
                            <span>Ke Beranda</span>
                        </a>
                    </div>
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
                        <h3 class="text-lg font-bold text-ecocare-dark">Daftar Artikel Edukasi</h3>
                        <span class="text-sm text-gray-500"><?php echo count($educations); ?> artikel</span>
                    </div>
                    
                    <?php if (empty($educations)): ?>
                        <div class="px-6 py-16 text-center text-gray-500">
                            <i class="fas fa-book text-5xl mb-4 opacity-30"></i>
                            <h4 class="text-xl font-semibold mb-2">Belum ada artikel edukasi</h4>
                            <p>Silakan tambahkan artikel edukasi pertama Anda!</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Gambar</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Judul</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Konten</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($educations as $edu): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $edu['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($edu['image_path']): ?>
                                                    <img src="<?php echo htmlspecialchars($edu['image_path']); ?>" class="w-20 h-16 object-cover rounded-lg" alt="Gambar">
                                                <?php else: ?>
                                                    <div class="w-20 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($edu['title']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($edu['content']); ?>">
                                                <?php echo htmlspecialchars(substr($edu['content'], 0, 60)); ?>...
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y', strtotime($edu['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($edu)); ?>)" class="w-9 h-9 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-200 transition" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="confirmDelete(<?php echo $edu['id']; ?>)" class="w-9 h-9 bg-red-100 text-red-600 rounded-lg flex items-center justify-center hover:bg-red-200 transition" title="Hapus">
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
    
    <!-- Add/Edit Modal -->
    <div id="eduModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 id="modalTitle" class="text-xl font-bold text-ecocare-dark">Tambah Artikel Edukasi</h3>
                <button onclick="closeModal()" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="eduForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="actionInput" value="add">
                    <input type="hidden" name="id" id="idInput">
                    <input type="hidden" name="current_image" id="currentImageInput">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Artikel</label>
                        <input type="text" name="title" id="titleInput" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition" placeholder="Masukkan judul artikel">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Konten Artikel</label>
                        <textarea name="content" id="contentInput" required rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition" placeholder="Masukkan konten artikel"></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Gambar (opsional)</label>
                        <input type="file" name="image" id="imageInput" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-ecocare-primary file:text-white hover:file:bg-ecocare-green-dark transition">
                        <div id="currentImagePreview" class="mt-3 hidden">
                            <img id="currentImage" src="" class="w-32 h-24 object-cover rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Gambar saat ini</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition">Batal</button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white rounded-xl font-semibold hover:shadow-lg transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Artikel Edukasi';
            document.getElementById('actionInput').value = 'add';
            document.getElementById('idInput').value = '';
            document.getElementById('titleInput').value = '';
            document.getElementById('contentInput').value = '';
            document.getElementById('currentImageInput').value = '';
            document.getElementById('currentImagePreview').classList.add('hidden');
            document.getElementById('eduModal').classList.remove('hidden');
            document.getElementById('eduModal').classList.add('flex');
        }
        
        function openEditModal(edu) {
            document.getElementById('modalTitle').textContent = 'Edit Artikel Edukasi';
            document.getElementById('actionInput').value = 'edit';
            document.getElementById('idInput').value = edu.id;
            document.getElementById('titleInput').value = edu.title;
            document.getElementById('contentInput').value = edu.content;
            document.getElementById('currentImageInput').value = edu.image_path || '';
            
            if (edu.image_path) {
                document.getElementById('currentImage').src = edu.image_path;
                document.getElementById('currentImagePreview').classList.remove('hidden');
            } else {
                document.getElementById('currentImagePreview').classList.add('hidden');
            }
            
            document.getElementById('eduModal').classList.remove('hidden');
            document.getElementById('eduModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('eduModal').classList.add('hidden');
            document.getElementById('eduModal').classList.remove('flex');
        }
        
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus artikel ini?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('eduModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>