<?php
require 'config.php';

$errors = [];
$success = '';
$debug_info = '';

// Redirect jika sudah login
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOG DEBUG: Semua data POST yang diterima
    $debug_info .= "<h3 class=\"font-bold mb-2\">Debug Info:</h3>";
    $debug_info .= "<pre class=\"text-xs bg-white p-2 rounded\">POST RAW: " . print_r($_POST, true) . "</pre>";
    
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
        $debug_info .= "<p class=\"text-red-500 mt-2\">ERROR: Invalid CSRF Token</p>";
    } else {
        // Sanitize & Validate Inputs
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Jangan di-trim/sanitize untuk password!
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $resident_id = sanitize_input($_POST['resident_id'] ?? '');
        
        $debug_info .= "<p class=\"mt-2\">Name: '$name'</p>";
        $debug_info .= "<p>Email: '$email'</p>";
        $debug_info .= "<p>Password: '$password' (length: " . strlen($password) . ")</p>";
        $debug_info .= "<p>Confirm Password: '$confirm_password' (length: " . strlen($confirm_password) . ")</p>";
        $debug_info .= "<p>Phone: '$phone'</p>";
        $debug_info .= "<p>Resident ID (NIK): '$resident_id'</p>";

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
            $errors[] = "Password minimal 8 karakter (panjang saat ini: " . strlen($password) . ")";
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
                    $debug_info .= "<p class=\"text-red-500 mt-2\">ERROR: Email $email sudah terdaftar</p>";
                } else {
                    // Cek apakah NIK sudah terdaftar
                    $check_nik = $pdo->prepare("SELECT id, name FROM users WHERE resident_id = ?");
                    $check_nik->execute([$resident_id]);
                    $nik_check_result = $check_nik->fetch();
                    $debug_info .= "<p class=\"mt-2\">NIK Check Result: " . print_r($nik_check_result, true) . "</p>";
                    
                    if ($nik_check_result) {
                        $errors[] = "NIK sudah terdaftar (digunakan oleh: " . htmlspecialchars($nik_check_result['name']) . ")";
                        $debug_info .= "<p class=\"text-red-500\">ERROR: NIK $resident_id sudah terdaftar</p>";
                    } else {
                        // Hash Password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert User
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, resident_id, role) VALUES (?, ?, ?, ?, ?, 'masyarakat')");
                        $stmt->execute([$name, $email, $hashed_password, $phone, $resident_id]);
                        
                        $debug_info .= "<p class=\"text-green-500 mt-2 font-bold\">SUCCESS: User $email berhasil didaftarkan!</p>";
                        
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
                // Tampilkan error asli untuk debugging
                $errors[] = "Kesalahan Database: " . $e->getMessage();
                $debug_info .= "<p class=\"text-red-500 mt-2\">DB ERROR: " . $e->getMessage() . "</p>";
            }
        } else {
            $debug_info .= "<p class=\"text-red-500 mt-2\">ERRORS FOUND: " . print_r($errors, true) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - EcoCare+</title>
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
        .register-gradient {
            background: linear-gradient(135deg, #6FAF8F 0%, #7DB7E8 100%);
        }
    </style>
</head>
<body class="register-gradient min-h-screen py-8">
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
                        Bergabung Bersama Kami untuk Lingkungan yang Lebih Baik!
                    </h2>
                    <p class="text-lg opacity-90 mb-10 max-w-md z-10">
                        Daftarkan akun Anda sekarang dan mulai berkontribusi dalam menjaga kebersihan lingkungan.
                    </p>
                    
                    <!-- Benefits -->
                    <div class="space-y-4 z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="font-medium">Laporkan masalah dengan cepat & mudah</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="font-medium">Pantau progress laporan secara real-time</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-book"></i>
                            </div>
                            <span class="font-medium">Dapatkan tips edukasi lingkungan</span>
                        </div>
                    </div>
                    
                    <!-- SVG Ilustrasi -->
                    <svg class="w-full h-auto max-h-64 mt-8 z-10" viewBox="0 0 400 250" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Pohon -->
                        <circle cx="100" cy="120" r="50" fill="#A8D5BA"/>
                        <circle cx="70" cy="150" r="35" fill="#6FAF8F"/>
                        <circle cx="130" cy="150" r="35" fill="#6FAF8F"/>
                        <rect x="90" y="160" width="20" height="70" fill="#F4EBD0" rx="5"/>
                        
                        <!-- Pohon 2 -->
                        <circle cx="300" cy="180" r="35" fill="#A8D5BA"/>
                        <circle cx="275" cy="205" r="25" fill="#6FAF8F"/>
                        <circle cx="325" cy="205" r="25" fill="#6FAF8F"/>
                        <rect x="290" y="210" width="20" height="50" fill="#F4EBD0" rx="5"/>
                        
                        <!-- Orang -->
                        <circle cx="200" cy="190" r="18" fill="#FFB86C"/>
                        <rect x="187" y="208" width="26" height="35" fill="white" rx="5"/>
                        
                        <!-- Bumi -->
                        <circle cx="350" cy="60" r="35" fill="#7DB7E8"/>
                        <ellipse cx="350" cy="60" rx="12" ry="30" fill="#6FAF8F" transform="rotate(15 350 60)"/>
                        <ellipse cx="350" cy="60" rx="8" ry="22" fill="#A8D5BA" transform="rotate(-10 350 60)"/>
                        
                        <!-- Recycle -->
                        <circle cx="150" cy="60" r="30" fill="#FFB86C"/>
                        <text x="150" y="70" text-anchor="middle" fill="white" font-size="24" font-weight="bold">♻️</text>
                    </svg>
                </div>
                
                <!-- Kanan: Form Register -->
                <div class="p-12 flex flex-col justify-center">
                    <div class="max-w-md w-full mx-auto">
                        <div class="flex items-center justify-between mb-8">
                            <h1 class="text-4xl font-extrabold text-ecocare-dark">Daftar Akun!</h1>
                            <a href="index.php" class="text-ecocare-dark/60 hover:text-ecocare-primary transition flex items-center gap-1">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <p class="text-ecocare-dark/70 mb-10">Isi formulir berikut untuk menjadi bagian dari komunitas EcoCare+</p>
                        
                        <?php if ($success): ?>
                            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="font-semibold">Registrasi Berhasil!</span>
                                </div>
                                <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($errors): ?>
                            <div class="bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl mb-8 shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span class="font-semibold">Registrasi Gagal</span>
                                </div>
                                <ul class="space-y-1 text-sm">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Debug Info (Hanya untuk testing) -->
                        <?php if ($debug_info): ?>
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-4 rounded-2xl mb-8 text-xs overflow-auto max-h-60 shadow-sm">
                                <div class="font-semibold mb-2 flex items-center gap-2">
                                    <i class="fas fa-bug"></i> Debug Info
                                </div>
                                <?php echo $debug_info; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="grid md:grid-cols-2 gap-5">
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="name">Nama Lengkap</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" name="name" id="name" required 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="Nama Lengkap"
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="email">Email</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" name="email" id="email" required 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="email@example.com"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-5">
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="password">Password</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" name="password" id="password" required 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="Minimal 8 karakter">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="confirm_password">Konfirmasi Password</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                        <input type="password" name="confirm_password" id="confirm_password" required 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="Ketik ulang password">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-5">
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="phone">Nomor Telepon</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" name="phone" id="phone" 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="081234567890"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-ecocare-dark font-semibold" for="resident_id">NIK</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-ecocare-primary">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                        <input type="text" name="resident_id" id="resident_id" required 
                                               class="w-full pl-14 pr-4 py-4 bg-ecocare-cream border border-ecocare-secondary/60 rounded-2xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary transition-all text-ecocare-dark shadow-sm"
                                               placeholder="1234567890123456"
                                               value="<?php echo htmlspecialchars($_POST['resident_id'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-4 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/40 transition-all transform hover:-translate-y-1 mt-2">
                                <i class="fas fa-user-plus mr-2"></i> Daftar Sekarang
                            </button>
                        </form>
                        
                        <div class="mt-10 pt-8 border-t border-ecocare-secondary/40 text-center">
                            <p class="text-ecocare-dark/70">
                                Sudah punya akun? 
                                <a href="login.php" class="text-ecocare-primary font-bold hover:underline ml-1">Masuk sekarang</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>