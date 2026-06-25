<?php
require 'config.php';

// If already logged in as admin, redirect to dashboard
if (is_logged_in() && is_admin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is admin
                if ($user['role'] === 'admin') {
                    // Regenerate session to prevent fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['profile_pic'] = $user['profile_pic'] ?? null;
                    $_SESSION['logged_in'] = true;
                    
                    error_log("SUCCESS: Admin " . $user['email'] . " logged in");
                    
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $errors[] = 'This account does not have admin privileges';
                    error_log("ERROR: Non-admin user " . $user['email'] . " tried to login to admin panel");
                }
            } else {
                $errors[] = 'Invalid email or password';
                error_log("ERROR: Admin login failed for email " . $email);
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log("ERROR: Admin login database error for email " . $email . ": " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - EcoCare+</title>
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
        body { background: linear-gradient(135deg, #f4ebd0 0%, #a8d5ba 100%); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-3xl flex items-center justify-center mx-auto text-white text-4xl shadow-xl shadow-ecocare-primary/30 mb-6">
                <i class="fas fa-leaf"></i>
            </div>
            <h2 class="text-4xl font-extrabold text-ecocare-dark mb-3">EcoCare+ Admin</h2>
            <p class="text-xl text-ecocare-dark/70">Masuk ke Panel Admin</p>
        </div>
        
        <div class="bg-white rounded-3xl shadow-2xl p-10 border border-ecocare-secondary/30">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-7">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div>
                    <label for="email" class="block text-sm font-semibold text-ecocare-dark mb-3">Email Admin</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-ecocare-primary">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                            class="block w-full pl-12 pr-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition text-lg" 
                            placeholder="admin@ecocare.com" required>
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-ecocare-dark mb-3">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-ecocare-primary">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" 
                            class="block w-full pl-12 pr-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-ecocare-primary focus:border-ecocare-primary transition text-lg" 
                            placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-4 px-6 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/40 transition transform hover:-translate-y-1 text-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Masuk Admin
                </button>
            </form>
            
            <div class="mt-8 text-center">
                <p class="text-gray-600">
                    Bukan Admin?
                    <a href="login.php" class="font-semibold text-ecocare-primary hover:text-ecocare-green-dark transition underline">
                        Masuk sebagai Pengguna
                    </a>
                </p>
            </div>
        </div>
        
        <div class="text-center mt-10">
            <a href="index.php" class="text-ecocare-dark/70 hover:text-ecocare-primary transition flex items-center justify-center gap-2 font-medium">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>