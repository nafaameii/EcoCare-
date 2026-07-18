<?php
require 'config.php';
require_login();

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : null;

// Get full user data from DB
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role, COALESCE(profile_pic, NULL) as profile_pic, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = [
        'id' => $user_id,
        'name' => $user_name,
        'email' => $user_email,
        'password' => '',
        'role' => 'masyarakat',
        'profile_pic' => $user_profile_pic,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Handle password change
        if (isset($_POST['change_password'])) {
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (!password_verify($old_password, $user['password'])) {
                throw new Exception("Password lama salah!");
            }
            if (strlen($new_password) < 8) {
                throw new Exception("Password baru harus minimal 8 karakter!");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok!");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
            $message = "Password berhasil diperbarui!";
            $message_type = 'success';
        }

        // Handle profile picture change
        if (isset($_POST['change_profile_pic'])) {
            $profile_pic_path = $user['profile_pic'];

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profiles';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png'];

                if (!in_array($file_ext, $allowed_types)) {
                    throw new Exception("Format file tidak diizinkan! Hanya JPG, JPEG, PNG yang diizinkan.");
                }
                if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                    throw new Exception("Ukuran file terlalu besar! Maksimal 2 MB.");
                }

                $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . '/' . $file_name;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    if ($profile_pic_path && file_exists($profile_pic_path)) {
                        unlink($profile_pic_path);
                    }
                    $profile_pic_path = $target_file;

                    // Update database
                    try {
                        $update_stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                        $update_stmt->execute([$profile_pic_path, $user_id]);
                    } catch (PDOException $e) {
                        // If profile_pic column doesn't exist, ignore
                    }

                    // Update session
                    $_SESSION['profile_pic'] = $profile_pic_path;
                    $user['profile_pic'] = $profile_pic_path;

                    $message = "Foto profil berhasil diperbarui!";
                    $message_type = 'success';
                }
            }
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
    <title>Pengaturan Akun - EcoCare+</title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #43A047;
            border-radius: 3px;
        }

        /* Sidebar Active Menu */
        .sidebar-active {
            background: linear-gradient(135deg, #2E7D32 0%, #43A047 100%);
            color: white !important;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        /* Card Hover Effect */
        .card-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2E7D32',
                        secondary: '#43A047',
                        lightgreen: '#E8F5E9',
                        success: '#4CAF50',
                        warning: '#FFB300',
                        danger: '#E53935',
                        bg: '#F8FAFC'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bg min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-72 bg-white shadow-xl border-r border-gray-100 min-h-screen fixed left-0 top-0 z-40 hidden lg:block">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center">
                        <i class="fas fa-leaf text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">EcoCare+</h1>
                        <p class="text-xs text-gray-500">Peduli Lingkungan</p>
                    </div>
                </div>
            </div>

            <nav class="p-4 space-y-2">
                <a href="dashboard_pengguna.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl text-gray-600 hover:bg-lightgreen hover:text-primary transition-all">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="map.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl text-gray-600 hover:bg-lightgreen hover:text-primary transition-all">
                    <i class="fas fa-map-marked-alt w-5 text-center"></i>
                    <span class="font-medium">Peta Interaktif</span>
                </a>
                <a href="laporan_saya.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl text-gray-600 hover:bg-lightgreen hover:text-primary transition-all">
                    <i class="fas fa-file-alt w-5 text-center"></i>
                    <span class="font-medium">Laporan Saya</span>
                </a>
                <a href="komunitas_saya.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl text-gray-600 hover:bg-lightgreen hover:text-primary transition-all">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span class="font-medium">Komunitas Saya</span>
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl sidebar-active transition-all">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span class="font-medium">Pengaturan</span>
                </a>
            </nav>

            <div class="absolute bottom-0 left-0 w-full p-4 border-t border-gray-100">
                <a href="logout.php" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl text-red-600 hover:bg-red-50 transition-all">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span class="font-medium">Keluar</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="lg:ml-72 flex-1">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-100 px-8 py-4 sticky top-0 z-30">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-4">
                            <button id="mobileMenuBtn" class="lg:hidden w-10 h-10 rounded-xl bg-lightgreen text-primary flex items-center justify-center">
                                <i class="fas fa-bars"></i>
                            </button>
                            <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-primary transition flex items-center gap-2">
                                <i class="fas fa-home"></i>
                                <span>Beranda</span>
                            </a>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mt-2">Pengaturan Akun</h1>
                        <p class="text-gray-500 text-sm">Kelola pengaturan dan preferensi akun Anda</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Profile -->
                        <div class="relative">
                            <button id="profileDropdownBtn" class="flex items-center gap-3 p-2 rounded-2xl hover:bg-gray-50 transition-all">
                                <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-primary flex-shrink-0">
                                    <?php if ($user_profile_pic && file_exists($user_profile_pic)): ?>
                                        <img src="<?= htmlspecialchars($user_profile_pic) ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user_name) ?></span>
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <div id="profileDropdown" class="hidden absolute right-0 top-14 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 w-56 z-50">
                                <a href="user_profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-lightgreen transition-all">
                                    <i class="fas fa-user"></i>
                                    Edit Profil
                                </a>
                                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-lightgreen transition-all">
                                    <i class="fas fa-cog"></i>
                                    Pengaturan
                                </a>
                                <hr class="my-2">
                                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Keluar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                <!-- Alert -->
                <?php if ($message): ?>
                    <div class="mb-8 p-4 rounded-2xl border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' ?>">
                        <div class="flex items-center gap-3">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600' ?> text-xl"></i>
                            <span class="font-medium <?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800' ?>"><?php echo htmlspecialchars($message) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-8">
                        <!-- 1. Informasi Akun -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                                    <i class="fas fa-user-circle text-primary"></i>
                                    Informasi Akun
                                </h2>
                            </div>

                            <div class="flex items-center gap-6 mb-6">
                                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-lightgreen flex-shrink-0">
                                    <?php if ($user['profile_pic'] && file_exists($user['profile_pic'])): ?>
                                        <img src="<?= htmlspecialchars($user['profile_pic']) ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-3xl">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nama</p>
                                    <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['name']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Email</p>
                                    <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Role</p>
                                    <span class="inline-block px-3 py-1 bg-lightgreen text-primary rounded-full text-sm font-semibold">
                                        <?php echo $user['role'] === 'admin' ? 'Admin' : 'Pengguna'; ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tanggal Bergabung</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('d M Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>

                            <div class="mt-8">
                                <a href="user_profile.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-secondary transition-all shadow-md">
                                    <i class="fas fa-edit"></i>
                                    Edit Profil
                                </a>
                            </div>
                        </div>

                        <!-- 2. Ubah Password -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-key"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-900">Ubah Password</h2>
                            </div>

                            <form method="POST">
                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password Lama</label>
                                        <input type="password" name="old_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all outline-none">
                                        <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all outline-none">
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <button type="submit" name="change_password" class="w-full bg-gradient-to-br from-primary to-secondary text-white px-6 py-3.5 rounded-xl font-semibold hover:opacity-90 transition-all shadow-md">
                                        <i class="fas fa-save mr-2"></i> Simpan Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-8">
                        <!-- 3. Ganti Foto Profil -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-900">Ganti Foto Profil</h2>
                            </div>

                            <div class="text-center mb-6">
                                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-lightgreen mx-auto mb-4">
                                    <?php if ($user['profile_pic'] && file_exists($user['profile_pic'])): ?>
                                        <img src="<?= htmlspecialchars($user['profile_pic']) ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-4xl">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form method="POST" enctype="multipart/form-data">
                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Foto Baru</label>
                                        <input type="file" name="profile_pic" accept="image/jpeg,image/jpg,image/png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary transition cursor-pointer">
                                        <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG • Maks 2 MB</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <button type="submit" name="change_profile_pic" class="w-full bg-primary text-white px-6 py-3.5 rounded-xl font-semibold hover:bg-secondary transition-all shadow-md">
                                        <i class="fas fa-upload mr-2"></i> Simpan Foto
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 4. Ganti Akun -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-900">Ganti Akun</h2>
                            </div>

                            <p class="text-gray-600 mb-6">Keluar dari akun saat ini dan masuk menggunakan akun lain.</p>

                            <button type="button" onclick="confirmSwitchAccount()" class="w-full border-2 border-orange-500 text-orange-600 px-6 py-3.5 rounded-xl font-semibold hover:bg-orange-50 transition-all">
                                <i class="fas fa-sign-out-alt mr-2"></i> Ganti Akun
                            </button>
                        </div>

                        <!-- 5. Tentang Aplikasi -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center text-white">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">EcoCare+</h2>
                                    <p class="text-sm text-gray-500">Versi 1.0</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-lightgreen rounded-xl">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Status Akun</p>
                                </div>
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                    Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile Menu Toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('fixed');
            sidebar.classList.toggle('lg:block');
        });

        // Profile Dropdown
        document.getElementById('profileDropdownBtn')?.addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });

        // Switch Account Confirmation
        function confirmSwitchAccount() {
            if (confirm('Apakah Anda yakin ingin berganti akun?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
