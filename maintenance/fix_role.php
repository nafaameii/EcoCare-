<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1 style='font-family:Arial; color:#2D3748;'>Fix Role Users</h1>";
echo "<div style='max-width:800px; margin:20px auto; font-family:Arial; font-size:16px;'>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 1. Tampilkan Semua Role Saat Ini ---
    echo "<h2 style='color:#4A7C59;'>1. Semua Users Sebelum Fix:</h2>";
    $get_all = $pdo->query("SELECT id, name, email, role FROM users");
    $users_before = $get_all->fetchAll(PDO::FETCH_ASSOC);
    if ($users_before) {
        echo "<table style='border-collapse: collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#4A7C59; color:white;'><th style='border:1px solid #ddd; padding:8px; text-align:left;'>ID</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Nama</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Email</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Role Lama</th></tr>";
        foreach ($users_before as $u) {
            echo "<tr style='background:white;'><td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['id']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['name']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['email']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['role']) . "</td></tr>";
        }
        echo "</table>";
    }

    // --- 2. Update Role 'masyarakat' Menjadi 'user' ---
    echo "<h2 style='color:#4A7C59;'>2. Fixing Role...</h2>";
    $update = $pdo->exec("UPDATE users SET role = 'user' WHERE role = 'masyarakat'");
    if ($update > 0) {
        echo "<p style='color:green;'>✅ Berhasil update $update user dari role 'masyarakat' menjadi 'user'</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Tidak ada role 'masyarakat' yang perlu diupdate</p>";
    }

    // --- 3. Tampilkan Hasil Setelah Fix ---
    echo "<h2 style='color:#4A7C59;'>3. Semua Users Setelah Fix:</h2>";
    $get_all_after = $pdo->query("SELECT id, name, email, role FROM users");
    $users_after = $get_all_after->fetchAll(PDO::FETCH_ASSOC);
    if ($users_after) {
        echo "<table style='border-collapse: collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#4A7C59; color:white;'><th style='border:1px solid #ddd; padding:8px; text-align:left;'>ID</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Nama</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Email</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Role Baru</th></tr>";
        foreach ($users_after as $u) {
            echo "<tr style='background:white;'><td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['id']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['name']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['email']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['role']) . "</td></tr>";
        }
        echo "</table>";
    }

    // --- 4. Hitung Jumlah User (role='user') ---
    $count = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    echo "<h2 style='color:green; margin-top:30px;'>🎉 Jumlah Warga Bergabung: $count</h2>";
    echo "<a href='index.php' style='display:inline-block; padding:15px 30px; background:#4A7C59; color:white; text-decoration:none; border-radius:10px; font-size:18px;'>Kembali ke Beranda</a>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
?>