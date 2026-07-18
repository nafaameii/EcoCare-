<?php
require 'config.php';

$errors = [];

// Redirect jika sudah login
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard_pengguna.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = "Email tidak boleh kosong";
        }
        if (empty($password)) {
            $errors[] = "Password tidak boleh kosong";
        }

        if (empty($errors)) {
            try {
                // Coba query dengan profile_pic, jika gagal fallback tanpa
                try {
                    $stmt = $pdo->prepare("SELECT id, name, email, password, role, profile_pic FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                } catch(PDOException $e) {
                    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    $user['profile_pic'] = null;
                }

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate Session ID untuk keamanan
                    session_regenerate_id(true);

                    // Set Session Data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['profile_pic'] = $user['profile_pic'] ?? null;

                    // Redirect sesuai role
                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: dashboard_pengguna.php');
                    }
                    exit;
                } else {
                    $errors[] = "Email atau Password salah";
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
    <title>Masuk - EcoCare+</title>
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
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <div class="absolute inset-0 overlay"></div>
    <div class="relative z-10 w-full max-w-md">
        <!-- Back to Home -->
        <a href="index.php" class="mb-6 inline-flex items-center text-white/90 hover:text-white transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
        </a>

        <!-- Login Card -->
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
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang Kembali!</h2>
            <p class="text-gray-500 mb-8">Masuk ke akun Anda untuk melanjutkan</p>

            <!-- Errors -->
            <?php if ($errors): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Login Gagal</p>
                        <ul class="text-sm mt-1 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- Email -->
                <div class="space-y-2">
                    <label class="block text-gray-700 font-semibold">Email</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                               class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary/30 focus:border-ecocare-primary transition-all text-gray-800"
                               placeholder="email@example.com">
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
                               placeholder="Masukkan password Anda">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-secondary text-white font-bold py-4 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/30 transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                </button>
            </form>

            <!-- Links -->
            <div class="mt-8 space-y-4">
                <!-- Register Link -->
                <div class="text-center text-gray-600">
                    Belum punya akun? 
                    <a href="register.php" class="text-ecocare-primary font-bold hover:underline">Daftar sekarang</a>
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