<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Migrasi Database EcoCare+</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Cek dan tambah kolom profile_pic
    $check_profile = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if (!$check_profile->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER password");
        echo "<p style='color:green'>✅ Berhasil menambah kolom 'profile_pic'</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Kolom 'profile_pic' sudah ada</p>";
    }

    // 2. Cek dan tambah kolom updated_at
    $check_updated = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    if (!$check_updated->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "<p style='color:green'>✅ Berhasil menambah kolom 'updated_at'</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Kolom 'updated_at' sudah ada</p>";
    }

    // 3. Buat direktori uploads/profiles
    $upload_dir = 'uploads/profiles';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p style='color:green'>✅ Berhasil membuat direktori 'uploads/profiles'</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Direktori 'uploads/profiles' sudah ada</p>";
    }

    echo "<h2 style='color:green; margin-top:20px;'>🎉 Migrasi Selesai!</h2>";
    echo "<a href='index.php' style='display:inline-block;padding:10px 20px;background:#4A7C59;color:white;text-decoration:none;border-radius:8px;'>Kembali ke Beranda</a>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>