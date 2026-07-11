<?php
require 'config.php';
require_admin();

$message = '';
$message_type = '';

// Ambil data user dari database, handle kolom yang mungkin belum ada
try {
    // Coba query dengan kolom lengkap, jika gagal fallback
    $stmt = $pdo->prepare("SELECT id, name, email, password, role, COALESCE(profile_pic, NULL) as profile_pic, created_at, COALESCE(updated_at, created_at) as updated_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    // Fallback jika kolom profile_pic atau updated_at belum ada
    $stmt = $pdo->prepare("SELECT id, name, email, password, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $user['profile_pic'] = null;
    $user['updated_at'] = $user['created_at'];
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);

            // Validasi email unik (kecuali email sendiri)
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->execute([$email, $_SESSION['user_id']]);
            if ($check_email->fetch()) {
                throw new Exception("Email sudah digunakan oleh akun lain!");
            }

            // Handle upload foto profil
            $profile_pic_path = $user['profile_pic'];
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profiles';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png'];
                if (!in_array($file_ext, $allowed_types)) {
                    throw new Exception("Format file tidak diizinkan! Hanya JPG/PNG yang diizinkan.");
                }
                if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                    throw new Exception("Ukuran file terlalu besar! Max 2MB.");
                }

                $file_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . '/' . $file_name;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    // Hapus file lama jika ada
                    if ($profile_pic_path && file_exists($profile_pic_path)) {
                        unlink($profile_pic_path);
                    }
                    $profile_pic_path = $target_file;
                }
            }

            // Update database, handle kolom yang mungkin belum ada
            try {
                $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_pic = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$name, $email, $profile_pic_path, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                // Fallback jika kolom profile_pic/updated_at belum ada
                $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $update_stmt->execute([$name, $email, $_SESSION['user_id']]);
            }

            // Update session
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_pic'] = $profile_pic_path;

            $message = "Profil berhasil diperbarui!";
            $message_type = 'success';

            // Refresh data user
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, password, role, COALESCE(profile_pic, NULL) as profile_pic, created_at, COALESCE(updated_at, created_at) as updated_at FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("SELECT id, name, email, password, role, created_at FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                $user['profile_pic'] = $profile_pic_path;
                $user['updated_at'] = date('Y-m-d H:i:s');
            }

        } elseif (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verifikasi password lama
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Password lama salah!");
            }

            // Validasi password baru
            if (strlen($new_password) < 6) {
                throw new Exception("Password baru harus minimal 6 karakter!");
            }
            if ($new_password != $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok!");
            }

            // Hash dan update
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_new_password, $_SESSION['user_id']]);

            $message = "Password berhasil diperbarui!";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Profil Admin - EcoCare+</title>
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
        .sidebar-link { 
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: #f0fdf4;
        }
        .sidebar-link.active {
            background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%);
            color: white;
        }
        .sidebar-link:hover .sidebar-icon {
            transform: scale(1.1);
        }
        .sidebar-icon {
            transition: transform 0.2s ease;
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
                            <i class="sidebar-icon fas fa-tachometer-alt w-5 text-green-600"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_reports.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-file-alt w-5 text-blue-600"></i>
                            <span>Kelola Laporan</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-users w-5 text-purple-600"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_map.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-map-marked-alt w-5 text-red-600"></i>
                            <span>Peta Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_statistics.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-chart-bar w-5 text-orange-500"></i>
                            <span>Statistik</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_education.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-book w-5 text-teal-600"></i>
                            <span>Kelola Edukasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_actions.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-hands-helping w-5 text-amber-700"></i>
                            <span>Kelola Aksi Lingkungan</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="absolute bottom-0 left-0 w-64 p-4 border-t border-gray-200 bg-white">
                <a href="admin_profile.php" class="flex items-center gap-3 mb-4 hover:bg-gray-50 rounded-lg p-2 -mx-2 -my-2 transition">
                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary flex-shrink-0">
                        <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] && file_exists($_SESSION['profile_pic'])): ?>
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
                        <h1 class="text-2xl font-bold text-ecocare-dark">Pengaturan Profil Admin</h1>
                        <p class="text-gray-500 text-sm">Kelola informasi akun Anda</p>
                    </div>
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        <span>Ke Beranda</span>
                    </a>
                </div>
            </header>

            <div class="p-8">
                <?php if ($message): ?>
                    <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> border px-6 py-4 rounded-xl mb-8 flex items-center gap-3">
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Sidebar Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="text-center">
                                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-ecocare-primary mx-auto mb-6 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center text-white text-5xl font-bold">
                                    <?php if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-xl font-bold text-ecocare-dark mb-2"><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p class="text-gray-500 text-sm mb-4 flex items-center justify-center gap-2">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                                <span class="inline-block px-4 py-1.5 bg-ecocare-primary/10 text-ecocare-primary rounded-full text-sm font-semibold">
                                    <i class="fas fa-shield-alt mr-1"></i> Administrator
                                </span>
                            </div>

                            <div class="mt-8 pt-6 border-t border-gray-100">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Informasi Akun</h4>
                                <ul class="space-y-4 text-sm">
                                    <li class="flex items-center justify-between">
                                        <span class="text-gray-600">ID Akun</span>
                                        <span class="text-ecocare-dark font-semibold">#<?php echo htmlspecialchars($user['id']); ?></span>
                                    </li>
                                    <li class="flex items-center justify-between">
                                        <span class="text-gray-600">Dibuat</span>
                                        <span class="text-ecocare-dark font-medium text-xs"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                    </li>
                                    <li class="flex items-center justify-between">
                                        <span class="text-gray-600">Diperbarui</span>
                                        <span class="text-ecocare-dark font-medium text-xs"><?php echo date('d M Y', strtotime($user['updated_at'])); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Forms -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Ubah Profil Form -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <h2 class="text-xl font-bold text-ecocare-dark mb-6 flex items-center gap-3">
                                <i class="fas fa-user text-ecocare-primary text-lg"></i> Ubah Profil
                            </h2>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="grid md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                        <input type="text" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition outline-none">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Profil</label>
                                    <input type="file" name="profile_pic" accept="image/jpeg,image/png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-ecocare-primary file:text-white hover:file:bg-ecocare-green-dark transition cursor-pointer">
                                    <p class="text-xs text-gray-500 mt-2">File JPG/PNG max 2MB</p>
                                </div>
                                <button type="submit" name="update_profile" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3.5 rounded-xl font-semibold hover:shadow-lg transition">
                                    Simpan Perubahan Profil
                                </button>
                            </form>
                        </div>

                        <!-- Ubah Password Form -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <h2 class="text-xl font-bold text-ecocare-dark mb-6 flex items-center gap-3">
                                <i class="fas fa-lock text-ecocare-primary text-lg"></i> Ubah Password
                            </h2>
                            <form method="POST">
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password Lama</label>
                                    <input type="password" name="current_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition outline-none">
                                </div>
                                <div class="grid md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition outline-none">
                                    </div>
                                </div>
                                <button type="submit" name="update_password" class="bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white px-8 py-3.5 rounded-xl font-semibold hover:shadow-lg transition">
                                    Ubah Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
