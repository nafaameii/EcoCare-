<?php
require 'config.php';

// Hanya untuk admin!
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$messages = [];

try {
    // Daftar admin yang harus ada
    $requiredAdmins = [
        [
            'name' => 'Nafa Amelia',
            'email' => 'nafa@admin.com',
            'password' => 'admin123'
        ],
        [
            'name' => 'Nadia',
            'email' => 'nadia@admin.com',
            'password' => 'admin123'
        ],
        [
            'name' => 'Mugi',
            'email' => 'mugi@admin.com',
            'password' => 'admin123'
        ]
    ];

    // 1. Hapus semua admin yang tidak ada di daftar requiredAdmins
    $keepAdminEmails = array_column($requiredAdmins, 'email');
    $placeholders = implode(',', array_fill(0, count($keepAdminEmails), '?'));
    $deleteOtherAdmins = $pdo->prepare("DELETE FROM users WHERE role = 'admin' AND email NOT IN ($placeholders)");
    $deleteOtherAdmins->execute($keepAdminEmails);
    $messages[] = "<p class='text-green-700'>✓ Menghapus admin yang tidak diperlukan</p>";

    // 2. Tambahkan atau perbarui admin yang diperlukan
    foreach ($requiredAdmins as $admin) {
        $checkStmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $checkStmt->execute([$admin['email']]);
        $existingAdmin = $checkStmt->fetch();
        
        if ($existingAdmin) {
            // Perbarui nama dan password admin (jika ingin memastikan passwordnya admin123)
            $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE email = ?");
            $updateStmt->execute([$admin['name'], $hashedPassword, $admin['email']]);
            $messages[] = "<p class='text-blue-700'>ℓ Memperbarui akun admin: <strong>{$admin['email']}</strong></p>";
        } else {
            // Tambahkan admin baru
            $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $insertStmt->execute([$admin['name'], $admin['email'], $hashedPassword]);
            $messages[] = "<p class='text-green-700'>✓ Menambahkan akun admin baru: <strong>{$admin['email']}</strong></p>";
        }
    }

    // 3. Hapus pengguna dengan email yang tidak menggunakan @user.com
    $deleteNonUserDomains = $pdo->prepare("DELETE FROM users WHERE (role = 'pengguna' OR role = 'masyarakat') AND email NOT LIKE '%@user.com'");
    $deleteNonUserDomains->execute();
    $messages[] = "<p class='text-green-700'>✓ Menghapus pengguna dengan email tidak menggunakan @user.com</p>";

    // 4. Tambahkan contoh pengguna jika tidak ada
    $sampleUsers = [
        [
            'name' => 'Lia',
            'email' => 'lia@user.com',
            'password' => 'user123'
        ],
        [
            'name' => 'Andi',
            'email' => 'andi@user.com',
            'password' => 'user123'
        ],
        [
            'name' => 'Putri',
            'email' => 'putri@user.com',
            'password' => 'user123'
        ],
        [
            'name' => 'Budi',
            'email' => 'budi@user.com',
            'password' => 'user123'
        ]
    ];

    foreach ($sampleUsers as $user) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$user['email']]);
        if (!$checkStmt->fetch()) {
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'masyarakat')");
            $insertStmt->execute([$user['name'], $user['email'], $hashedPassword]);
            $messages[] = "<p class='text-green-700'>✓ Menambahkan contoh pengguna: <strong>{$user['email']}</strong></p>";
        }
    }

    $messages[] = "<p class='text-green-800 font-bold mt-4 text-center'>✅ SEMUA AKUN TELAH DIRAPIKAN!</p>";

} catch (PDOException $e) {
    $messages[] = "<p class='text-red-700'>❌ Error database: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Rapikan Akun - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-2xl w-full">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Migrasi Rapikan Akun EcoCare+</h1>
        <div class="space-y-3">
            <?php foreach ($messages as $message): ?>
                <?php echo $message; ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="admin_dashboard.php" class="inline-block bg-gradient-to-r from-emerald-600 to-emerald-700 text-white font-semibold py-3 px-6 rounded-xl hover:opacity-90 transition">
                Kembali ke Dashboard Admin
            </a>
        </div>
    </div>
</body>
</html>
