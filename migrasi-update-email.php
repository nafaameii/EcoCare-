<?php
require 'config.php';

// Hanya untuk admin!
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$messages = [];

try {
    // Daftar email admin yang akan diubah
    $adminEmails = [
        'nafa@ecocare.com' => 'nafa@admin.com',
        'mugi@ecocare.com' => 'mugi@admin.com',
        'nadia@ecocare.com' => 'nadia@admin.com'
    ];

    // Daftar email pengguna yang akan diubah
    $userEmails = [
        'lia@example.com' => 'lia@user.com',
        'andi@example.com' => 'andi@user.com',
        'budi@example.com' => 'budi@user.com'
    ];

    // Update email admin
    foreach ($adminEmails as $oldEmail => $newEmail) {
        // Cek apakah email lama ada dan role admin
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
        $checkStmt->execute([$oldEmail]);
        if ($checkStmt->fetch()) {
            // Cek apakah email baru sudah ada
            $checkNewStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkNewStmt->execute([$newEmail]);
            if (!$checkNewStmt->fetch()) {
                // Update email
                $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ? AND role = 'admin'");
                $updateStmt->execute([$newEmail, $oldEmail]);
                $messages[] = "<p class='text-green-700'>✓ Email admin berhasil diubah: <strong>$oldEmail</strong> → <strong>$newEmail</strong></p>";
            } else {
                $messages[] = "<p class='text-yellow-700'>⚠ Email baru <strong>$newEmail</strong> sudah ada, dilewati</p>";
            }
        } else {
            $messages[] = "<p class='text-gray-500'>ℹ Email admin <strong>$oldEmail</strong> tidak ditemukan, dilewati</p>";
        }
    }

    // Update email pengguna
    foreach ($userEmails as $oldEmail => $newEmail) {
        // Cek apakah email lama ada dan role pengguna/masyarakat
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND (role = 'pengguna' OR role = 'masyarakat')");
        $checkStmt->execute([$oldEmail]);
        if ($checkStmt->fetch()) {
            // Cek apakah email baru sudah ada
            $checkNewStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkNewStmt->execute([$newEmail]);
            if (!$checkNewStmt->fetch()) {
                // Update email
                $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ? AND (role = 'pengguna' OR role = 'masyarakat')");
                $updateStmt->execute([$newEmail, $oldEmail]);
                $messages[] = "<p class='text-green-700'>✓ Email pengguna berhasil diubah: <strong>$oldEmail</strong> → <strong>$newEmail</strong></p>";
            } else {
                $messages[] = "<p class='text-yellow-700'>⚠ Email baru <strong>$newEmail</strong> sudah ada, dilewati</p>";
            }
        } else {
            $messages[] = "<p class='text-gray-500'>ℹ Email pengguna <strong>$oldEmail</strong> tidak ditemukan, dilewati</p>";
        }
    }

    // Pastikan akun admin default ada
    $adminEmail = 'admin@admin.com';
    $adminPassword = 'admin123';
    $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkAdmin->execute([$adminEmail]);
    if (!$checkAdmin->fetch()) {
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmtAdmin = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmtAdmin->execute(['Administrator', $adminEmail, $hashedPassword]);
        $messages[] = "<p class='text-green-700'>✓ Akun admin default berhasil dibuat: <strong>$adminEmail</strong></p>";
    } else {
        $messages[] = "<p class='text-blue-700'>ℹ Akun admin default <strong>$adminEmail</strong> sudah ada</p>";
    }

    // Pastikan akun user default ada
    $userEmail = 'user@user.com';
    $userPassword = 'user123';
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkUser->execute([$userEmail]);
    if (!$checkUser->fetch()) {
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'masyarakat')");
        $stmtUser->execute(['User Pengguna', $userEmail, $hashedPassword]);
        $messages[] = "<p class='text-green-700'>✓ Akun pengguna default berhasil dibuat: <strong>$userEmail</strong></p>";
    } else {
        $messages[] = "<p class='text-blue-700'>ℹ Akun pengguna default <strong>$userEmail</strong> sudah ada</p>";
    }

} catch (PDOException $e) {
    $messages[] = "<p class='text-red-700'>❌ Error database: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Email - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-2xl w-full">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Migrasi Email EcoCare+</h1>
        <div class="space-y-3">
            <?php foreach ($messages as $message): ?>
                <?php echo $message; ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="admin_dashboard.php" class="inline-block bg-gradient-to-r from-primary to-secondary text-white font-semibold py-3 px-6 rounded-xl hover:opacity-90 transition">
                Kembali ke Dashboard Admin
            </a>
        </div>
    </div>
</body>
</html>
