<?php
require 'config.php';
require_login();

// Get all educations
try {
    $stmt = $pdo->query("SELECT * FROM educations ORDER BY created_at DESC");
    $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $educations = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edukasi Lingkungan | EcoCare+</title>
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
        .card-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(46, 125, 50, 0.1), 0 10px 10px -5px rgba(46, 125, 50, 0.04);
        }
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
                    <a href="komunitas_saya.php" class="text-gray-600 hover:text-ecocare-primary font-medium transition">Komunitas</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 transition flex items-center gap-1">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
        <!-- Page Header -->
        <div class="mb-12 text-center">
            <h1 class="text-4xl lg:text-5xl font-extrabold text-ecocare-dark mb-4">
                <i class="fas fa-book-open text-ecocare-primary mr-3"></i>Edukasi Lingkungan
            </h1>
            <p class="text-gray-600 text-lg">Pelajari cara menjaga lingkungan kita bersama!</p>
        </div>

        <!-- Educations Grid -->
        <?php if (empty($educations)): ?>
            <div class="text-center py-20 bg-white rounded-3xl shadow-xl border border-gray-100">
                <i class="fas fa-book text-8xl text-gray-300 mb-6"></i>
                <h3 class="text-2xl font-semibold text-gray-700 mb-2">Belum ada artikel edukasi</h3>
                <p class="text-gray-500">Nantikan artikel menarik dari kami!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($educations as $edu): ?>
                        <a href="edukasi_detail.php?id=<?= $edu['id'] ?>" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden card-lift">
                            <?php if ($edu['photo_path']): ?>
                                <img src="<?= htmlspecialchars($edu['photo_path']) ?>" alt="<?= htmlspecialchars($edu['title']) ?>" class="w-full h-56 object-cover">
                            <?php else: ?>
                                <div class="w-full h-56 bg-gradient-to-br from-green-100 to-blue-100 flex items-center justify-center">
                                    <i class="fas fa-book-open text-6xl text-gray-300"></i>
                                </div>
                            <?php endif; ?>
                        
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-ecocare-dark mb-3 line-clamp-2"><?= htmlspecialchars($edu['title']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?= htmlspecialchars(substr($edu['content'], 0, 200)) ?>...</p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span><i class="fas fa-calendar mr-1"></i> <?= date('d M Y', strtotime($edu['created_at'])) ?></span>
                                <span class="text-ecocare-primary font-semibold hover:underline">
                                    Baca Selengkapnya <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>