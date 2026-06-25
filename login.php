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
    // LOG DEBUG: Semua data POST yang diterima
    error_log("--- LOGIN SUBMITTED ---");
    error_log("POST RAW: " . print_r($_POST, true));
    
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
        error_log("ERROR: Invalid CSRF Token");
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        error_log("Login Email: '$email'");
        error_log("Login Password: '$password' (length: " . strlen($password) . ")");

        if (empty($email)) {
            $errors[] = "Email tidak boleh kosong";
            error_log("ERROR: Email kosong");
        }
        if (empty($password)) {
            $errors[] = "Password tidak boleh kosong";
            error_log("ERROR: Password kosong");
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
                
                error_log("User found from DB: " . print_r($user, true));

                if ($user) {
                    error_log("Password verify: " . (password_verify($password, $user['password']) ? "SUCCESS" : "FAILED"));
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
                    
                    error_log("SUCCESS: User $email berhasil login!");

                    // Redirect sesuai role
                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: dashboard_pengguna.php');
                    }
                    exit;
                } else {
                    $errors[] = "Email atau Password salah";
                    error_log("ERROR: Login gagal - email/password salah");
                }
            } catch(PDOException $e) {
                $errors[] = "Kesalahan Database: " . $e->getMessage();
                error_log("DB ERROR: " . $e->getMessage());
            }
        } else {
            error_log("ERRORS FOUND: " . print_r($errors, true));
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
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .login-gradient {
            background: linear-gradient(135deg, #6FAF8F 0%, #7DB7E8 100%);
        }
    </style>
</head>
<body class="login-gradient min-h-screen py-8">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-6xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <!-- Kiri: Ilustrasi & Deskripsi -->
                <div class="bg-gradient-to-br from-ecocare-primary to-ecocare-accent p-12 text-white flex flex-col justify-center relative overflow-hidden">
                    <!-- Decorative elements -->
                    <div class="absolute top-10 right-10 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-float"></div>
                    <div class="absolute bottom-10 left-10 w-48 h-48 bg-yellow-300/10 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
                    
                    <div class="flex items-center gap-3 mb-8 z-10">
                        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-3xl shadow-xl">
                            <i class="fas fa-leaf text-ecocare-primary"></i>
                        </div>
                        <div>
                            <span class="text-3xl font-bold">EcoCare+</span>
                            <p class="text-sm opacity-70">Peduli Lingkungan Kita</p>
                        </div>
                    </div>
                    
                    <h2 class="text-4xl font-bold mb-6 leading-tight z-10">
                        Laporkan, Pantau, dan Jaga Lingkungan Bersama
                    </h2>
                    <p class="text-lg opacity-90 mb-10 max-w-md z-10">
                        Bergabung dengan komunitas EcoCare+ untuk menjaga kebersihan dan kelestarian lingkungan sekitar kita.
                    </p>
                    
                    <!-- Benefits -->
                    <div class="space-y-4 z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="font-medium">Laporkan masalah lingkungan dengan mudah</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <span class="font-medium">Pantau status laporan secara real-time</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="font-medium">Bergabung dengan komunitas peduli lingkungan</span>
                        </div>
                    </div>
                    
                    <!-- SVG Ilustrasi -->
                    <svg class="w-full h-auto max-h-64 mt-8 z-10" viewBox="0 0 400 250" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Pohon Besar -->
                        <circle cx="100" cy="120" r="50" fill="#A8D5BA"/>
                        <circle cx="70" cy="150" r="35" fill="#6FAF8F"/>
                        <circle cx="130" cy="150" r="35" fill="#6FAF8F"/>
                        <rect x="90" y="160" width="20" height="70" fill="#F4EBD0" rx="5"/>
                        
                        <!-- Pohon Kecil -->
                        <circle cx="300" cy="180" r="35" fill="#A8D5BA"/>
                        <circle cx="275" cy="205" r="25" fill="#6FAF8F"/>
                        <circle cx="325" cy="205" r="25" fill="#6FAF8F"/>
                        <rect x="290" y="210" width="20" height="50" fill="#F4EBD0" rx="5"/>
                        
                        <!-- Bumi -->
                        <circle cx="350" cy="60" r="35" fill="#7DB7E8"/>
                        <ellipse cx="350" cy="60" rx="12" ry="30" fill="#6FAF8F" transform="rotate(15 350 60)"/>
                        <ellipse cx="350" cy="60" rx="8" ry="22" fill="#A8D5BA" transform="rotate(-10 350 60)"/>
                        
                        <!-- Orang -->
                        <circle cx="200" cy="190" r="18" fill="#FFB86C"/>
                        <rect x="187" y="208" width="26" height="35" fill="white" rx="5"/>
                    </svg>
                </div>
                
                <!-- Kanan: Form Login -->
                <div class="p-12 flex flex-col justify-center">
                    <div class="max-w-md w-full mx-auto">
                        <div class="flex items-center justify-between mb-8">
                            <h1 class="text-4xl font-extrabold text-ecocare-dark">Selamat Datang!</h1>
                            <a href="index.php" class="text-ecocare-dark/60 hover:text-ecocare-primary transition flex items-center gap-1">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <p class="text-ecocare-dark/70 mb-10">Masuk ke akun Anda untuk melanjutkan perjalanan bersama EcoCare+</p>
                        
                        <?php if ($errors): ?>
                            <div class="bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl mb-8 shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span class="font-semibold">Login Gagal</span>
                                </div>
                                <ul class="space-y-1 text-sm">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="space-y-2">
                                <label class="block text-ecocare-dark font-semibold" for="email">Email</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" name="email" id="email" required 
                                           class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                           placeholder="email@example.com">
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-ecocare-dark font-semibold" for="password">Password</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" name="password" id="password" required 
                                           class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                           placeholder="Masukkan password">
                                </div>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-4 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/40 transition-all transform hover:-translate-y-1">
                                <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                            </button>
                        </form>
                        
                        <div class="mt-10 pt-8 border-t border-ecocare-secondary/40 text-center">
                            <p class="text-ecocare-dark/70">
                                Belum punya akun? 
                                <a href="register.php" class="text-ecocare-primary font-bold hover:underline ml-1">Daftar sekarang</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
