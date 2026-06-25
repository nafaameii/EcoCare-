<?php
require 'config.php';
require_login();

$message = '';
$message_type = '';

// Ambil data user dari database, handle kolom yang mungkin belum ada
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role, COALESCE(profile_pic, NULL) as profile_pic, created_at, COALESCE(updated_at, created_at) as updated_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
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

            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->execute([$email, $_SESSION['user_id']]);
            if ($check_email->fetch()) {
                throw new Exception("Email sudah digunakan oleh akun lain!");
            }

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
                    if ($profile_pic_path && file_exists($profile_pic_path)) {
                        unlink($profile_pic_path);
                    }
                    $profile_pic_path = $target_file;
                }
            }

            try {
                $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_pic = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$name, $email, $profile_pic_path, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $update_stmt->execute([$name, $email, $_SESSION['user_id']]);
            }

            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_pic'] = $profile_pic_path;

            $message = "Profil berhasil diperbarui!";
            $message_type = 'success';

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

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Password lama salah!");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Password baru harus minimal 6 karakter!");
            }
            if ($new_password != $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok!");
            }

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
    <title>Pengaturan Profil - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ecocare-primary': '#4A7C59',
                        'ecocare-secondary': '#8FBC8F',
                        'ecocare-dark': '#1A3A2A',
                        'ecocare-cream': '#F8F6F0',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-ecocare-cream">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-ecocare-primary rounded-xl flex items-center justify-center text-white text-xl">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="text-xl font-bold text-ecocare-dark">EcoCare+</span>
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="index.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Beranda</a>
                    <a href="dashboard_pengguna.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Dashboard Saya</a>
                    <a href="map.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Peta</a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary bg-ecocare-primary flex items-center justify-center text-white font-bold">
                            <?php if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                            <?php else: ?>
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-gray-700 font-medium hidden md:block"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <a href="logout.php" class="text-gray-600 hover:text-red-600 transition flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden md:block">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
        <div class="mb-8">
            <a href="dashboard_pengguna.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-ecocare-primary transition mb-4">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h1 class="text-3xl font-extrabold text-ecocare-dark">Pengaturan Profil</h1>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
            <div class="mb-8 <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?> px-6 py-4 rounded-xl flex items-center gap-3">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Sidebar Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <div class="text-center">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-ecocare-primary mx-auto mb-6 bg-ecocare-primary flex items-center justify-center text-white text-5xl font-bold">
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
                            <i class="fas fa-user mr-1"></i> Pengguna
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
                            <input type="file" name="profile_pic" accept="image/jpeg,image/png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-ecocare-primary file:text-white hover:file:bg-ecocare-dark transition cursor-pointer">
                            <p class="text-xs text-gray-500 mt-2">File JPG/PNG max 2MB</p>
                        </div>
                        <button type="submit" name="update_profile" class="bg-ecocare-primary text-white px-8 py-3.5 rounded-xl font-semibold hover:bg-ecocare-dark transition shadow-md">
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
                        <button type="submit" name="update_password" class="bg-ecocare-dark text-white px-8 py-3.5 rounded-xl font-semibold hover:bg-ecocare-primary transition shadow-md">
                            Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-ecocare-dark text-white py-10 mt-12">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-10 h-10 bg-ecocare-primary rounded-xl flex items-center justify-center text-xl">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="text-xl font-bold">EcoCare+</span>
            </div>
            <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> EcoCare+. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
