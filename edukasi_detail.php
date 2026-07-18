<?php
require 'config.php';
require_login();

$edu_id = intval($_GET['id'] ?? 0);
if (!$edu_id) {
    header('Location: edukasi_pengguna.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM educations WHERE id = ?");
    $stmt->execute([$edu_id]);
    $education = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$education) {
        header('Location: edukasi_pengguna.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($education['title']) ?> | EcoCare+ Edukasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'inter': ['Inter', 'sans-serif'] },
                    colors: {
                        'ecocare-primary': '#6FAF8F',
                        'ecocare-green-dark': '#3D8B6A',
                        'ecocare-dark': '#2D3748'
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .prose p { margin-bottom: 1.5rem; line-height: 1.8; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="dashboard_pengguna.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-ecocare-dark">EcoCare+</span>
                        <span class="block text-xs text-ecocare-dark/60 font-medium">Edukasi</span>
                    </div>
                </a>
                <div class="flex items-center gap-6">
                    <a href="dashboard_pengguna.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Dashboard</a>
                    <a href="edukasi_pengguna.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Daftar Edukasi</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
        <!-- Back Button -->
        <a href="edukasi_pengguna.php" class="inline-flex items-center gap-2 text-ecocare-primary font-medium mb-8 hover:underline">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Edukasi
        </a>
        
        <!-- Article Card -->
        <article class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            <?php if ($education['photo_path']): ?>
                <img src="<?= htmlspecialchars($education['photo_path']) ?>" alt="<?= htmlspecialchars($education['title']) ?>" class="w-full h-96 object-cover">
            <?php else: ?>
                <div class="w-full h-96 bg-gradient-to-br from-green-100 to-blue-100 flex items-center justify-center">
                    <i class="fas fa-book-open text-8xl text-gray-300"></i>
                </div>
            <?php endif; ?>
            
            <div class="p-8 lg:p-12">
                <h1 class="text-4xl lg:text-5xl font-extrabold text-ecocare-dark mb-6 leading-tight">
                    <?= htmlspecialchars($education['title']) ?>
                </h1>
                
                <div class="flex items-center gap-4 mb-8 text-gray-500 text-sm pb-8 border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-calendar text-ecocare-primary"></i>
                        <?= date('d M Y', strtotime($education['created_at'])) ?>
                    </span>
                </div>
                
                <div class="prose max-w-none text-gray-700 text-lg leading-relaxed whitespace-pre-line">
                    <?= htmlspecialchars($education['content']) ?>
                </div>
            </div>
        </article>
    </main>
</body>
</html>