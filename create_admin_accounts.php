<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Buat Akun Admin EcoCare+</h1>";

$admins = [
    ['name' => 'Nafa', 'email' => 'nafa@ecocare.com', 'password' => 'admin123'],
    ['name' => 'Mugi', 'email' => 'mugi@ecocare.com', 'password' => 'admin123'],
    ['name' => 'Nadia', 'email' => 'nadia@ecocare.com', 'password' => 'admin123']
];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($admins as $admin) {
        // Cek apakah email sudah ada
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin['email']]);
        if ($check->fetch()) {
            // Update jika sudah ada
            $hashed = password_hash($admin['password'], PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET name = ?, password = ?, role = 'admin' WHERE email = ?");
            $update->execute([$admin['name'], $hashed, $admin['email']]);
            echo "<p style='color:blue'>ℹ️ Akun " . htmlspecialchars($admin['name']) . " (" . htmlspecialchars($admin['email']) . ") sudah ada dan diupdate</p>";
        } else {
            // Buat baru
            $hashed = password_hash($admin['password'], PASSWORD_DEFAULT);
            // Generate random resident_id (16 digit)
            $resident_id = strval(rand(1000000000000000, 9999999999999999));
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, phone, resident_id, role) VALUES (?, ?, ?, '081234567890', ?, 'admin')");
            $insert->execute([$admin['name'], $admin['email'], $hashed, $resident_id]);
            echo "<p style='color:green'>✅ Akun " . htmlspecialchars($admin['name']) . " (" . htmlspecialchars($admin['email']) . ") berhasil dibuat!</p>";
        }
    }

    echo "<h2 style='color:green; margin-top:20px;'>🎉 Semua akun admin selesai!</h2>";
    echo "<p><strong>Daftar Akun Admin:</strong></p>";
    echo "<ul>";
    foreach ($admins as $admin) {
        echo "<li>Nama: <strong>" . htmlspecialchars($admin['name']) . "</strong>, Email: <strong>" . htmlspecialchars($admin['email']) . "</strong>, Password: <strong>admin123</strong></li>";
    }
    echo "</ul>";
    echo "<a href='admin_login.php' style='display:inline-block;padding:10px 20px;background:#4A7C59;color:white;text-decoration:none;border-radius:8px;margin-top:10px;'>Login Admin</a>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>