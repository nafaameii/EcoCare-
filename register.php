<?php
require 'config.php';
require_login(false);

$errors = [];
$success = '';

// Redirect jika sudah login
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        // Sanitize & Validate Inputs
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Jangan di-trim/sanitize untuk password!
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $resident_id = sanitize_input($_POST['resident_id'] ?? '');

        // Validasi Nama
        if (empty($name)) {
            $errors[] = "Nama Lengkap tidak boleh kosong";
        }
        if (empty($email)) {
            $errors[] = "Email tidak boleh kosong";
        } elseif (!validate_email($email)) {
            $errors[] = "Format Email tidak valid";
        }
        if (empty($password)) {
            $errors[] = "Password tidak boleh kosong";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password minimal 8 karakter";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Password dan Konfirmasi Password tidak cocok";
        }
        if (empty($resident_id)) {
            $errors[] = "NIK tidak boleh kosong";
        }

        if (empty($errors)) {
            try {
                // Cek apakah email sudah terdaftar
                $check_email = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
                $check_email->execute([$email]);

                if ($check_email->fetch()) {
                    $errors[] = "Email sudah terdaftar";
                } else {
                    // Cek apakah NIK sudah terdaftar
                    $check_nik = $pdo->prepare("SELECT id, name FROM users WHERE resident_id = ?");
                    $check_nik->execute([$resident_id]);

                    if ($check_nik->fetch()) {
                        $errors[] = "NIK sudah terdaftar";
                    } else {
                        // Hash Password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Insert User
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, resident_id, role) VALUES (?, ?, ?, ?, ?, 'masyarakat')");
                        $stmt->execute([$name, $email, $hashed_password, $phone, $resident_id]);

                        // Regenerate Session ID untuk keamanan
                        session_regenerate_id(true);

                        // Auto Login setelah registrasi
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'masyarakat';
                        $_SESSION['profile_pic'] = null;

                        $success = "Registrasi berhasil! Anda akan dialihkan...";
                        header("Refresh: 2; URL=dashboard_pengguna.php");
                    }
                }
            } catch(PDOException $e) {
                $errors[] = "Kesalahan Database: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'ecocare-primary': '#2E7D32',
                        'ecocare-secondary': '#43A047',
                        'ecocare-light': '#C8E6C9',
                        'ecocare-cream': '#F4F4F4',
                        'ecocare-dark': '#1B5E20',
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        .login-bg {
            background-image: url('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
        .overlay {
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.85), rgba(46, 125, 50, 0.75));
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 py-12">
    <div class="absolute inset-0 overlay"></div>
    <div class="relative z-10 w-full max-w-lg">
        <!-- Back to Home -->
        <a href="index.php" class="mb-6 inline-flex items-center text-white/90 hover:text-white transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
        </a>

        <!-- Register Card -->
        <div class="glass-card rounded-3xl p-8 border border-white/20">
            <!-- Logo -->
            <div class="flex flex-col items-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-ecocare-primary to-ecocare-secondary rounded-2xl flex items-center justify-center text-white text-4xl shadow-xl mb-4">
                    <i class="fas fa-leaf"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">EcoCare+</h1>
                <p class="text-gray-500 mt-1">Peduli Lingkungan Kita</p>
            </div>

            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Buat Akun Baru</h2>
            <p class="text-gray-500 mb-8">Isi formulir berikut untuk mendaftar</p>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Registrasi Berhasil!</p>
                        <p class="text-sm mt-1"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Errors -->
            <?php if ($errors): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Registrasi Gagal</p>
                        <ul class="text-sm mt-1 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- Name -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="name" id="name" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="Masukkan nama lengkap Anda"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Email</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="email@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Phone -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Nomor Telepon (Opsional)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="text" name="phone" id="phone" 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="081234567890"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <!-- NIK -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">NIK</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input type="text" name="resident_id" id="resident_id" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="Masukkan NIK Anda"
                               value="<?php echo htmlspecialchars($_POST['resident_id'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="Minimal 8 karakter">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Konfirmasi Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-check-double"></i>
                        </span>
                        <input type="password" name="confirm_password" id="confirm_password" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="Masukkan password kembali">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-secondary text-white font-bold py-4 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/30 transition-all transform hover:-translate-y-0.5 mt-2">
                    <i class="fas fa-user-plus mr-2"></i> Daftar Sekarang
                </button>
            </form>

            <!-- Links -->
            <div class="mt-8 space-y-4">
                <!-- Login Link -->
                <div class="text-center text-gray-600">
                    Sudah punya akun? 
                    <a href="login.php" class="text-ecocare-primary font-bold hover:underline">Masuk sekarang</a>
                </div>

                <!-- Admin Login Link -->
                <div class="pt-4 border-t border-gray-200 text-center">
                    <p class="text-gray-500 text-sm mb-2">Anda admin?</p>
                    <a href="admin_login.php" class="inline-flex items-center gap-2 text-ecocare-primary font-semibold hover:underline">
                        <i class="fas fa-user-shield"></i> Login sebagai Admin
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>