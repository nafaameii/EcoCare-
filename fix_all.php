<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1 style='font-family:Arial, sans-serif; color:#2D3748;'>Fix Semua Masalah EcoCare+</h1>";
echo "<div style='max-width:800px; margin:20px auto; font-family:Arial, sans-serif; font-size:16px;'>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 1. Cek dan Tambah Kolom yang Dibutuhkan ---
    echo "<h2 style='color:#4A7C59;'>1. Cek dan Tambah Kolom Database</h2>";
    $required_columns = ['profile_pic' => 'VARCHAR(255) NULL', 'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'];
    foreach ($required_columns as $col_name => $col_def) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$col_name'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col_name $col_def");
            echo "<p style='color:green;'>✅ Berhasil menambah kolom '$col_name'</p>";
        } else {
            echo "<p style='color:blue;'>ℹ️ Kolom '$col_name' sudah ada</p>";
        }
    }

    // --- 2. Buat/Update 3 Akun Admin ---
    echo "<h2 style='color:#4A7C59;'>2. Buat/Update Akun Admin</h2>";
    $admins = [
        ['name' => 'Nafa', 'email' => 'nafa@ecocare.com', 'password' => 'admin123'],
        ['name' => 'Mugi', 'email' => 'mugi@ecocare.com', 'password' => 'admin123'],
        ['name' => 'Nadia', 'email' => 'nadia@ecocare.com', 'password' => 'admin123']
    ];
    foreach ($admins as $admin) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin['email']]);
        $hashed_pw = password_hash($admin['password'], PASSWORD_DEFAULT);
        if ($check->fetch()) {
            $update = $pdo->prepare("UPDATE users SET name=?, password=?, role='admin' WHERE email=?");
            $update->execute([$admin['name'], $hashed_pw, $admin['email']]);
            echo "<p style='color:blue;'>ℹ️ Akun " . htmlspecialchars($admin['name']) . " (" . htmlspecialchars($admin['email']) . ") diupdate</p>";
        } else {
            $resident_id = strval(rand(1000000000000000, 9999999999999999));
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, phone, resident_id, role) VALUES (?, ?, ?, '081234567890', ?, 'admin')");
            $insert->execute([$admin['name'], $admin['email'], $hashed_pw, $resident_id]);
            echo "<p style='color:green;'>✅ Akun " . htmlspecialchars($admin['name']) . " (" . htmlspecialchars($admin['email']) . ") dibuat</p>";
        }
    }

    // --- 3. Buat Folder Upload ---
    echo "<h2 style='color:#4A7C59;'>3. Cek Folder Upload</h2>";
    $upload_dir = 'uploads/profiles';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p style='color:green;'>✅ Folder 'uploads/profiles' dibuat</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Folder 'uploads/profiles' sudah ada</p>";
    }

    // --- 4. Tampilkan Semua Akun di Database ---
    echo "<h2 style='color:#4A7C59;'>4. Semua Akun di Database:</h2>";
    $get_users = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id");
    $users = $get_users->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        echo "<table style='border-collapse: collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background-color:#4A7C59; color:white;'><th style='border:1px solid #ddd; padding:8px; text-align:left;'>ID</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Nama</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Email</th><th style='border:1px solid #ddd; padding:8px; text-align:left;'>Role</th></tr>";
        foreach ($users as $u) {
            echo "<tr style='background-color:white;'><td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['id']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['name']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['email']) . "</td>";
            echo "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($u['role']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ Belum ada akun di database</p>";
    }

    echo "<h2 style='color:green; margin-top:30px;'>🎉 FIX SELESAI!</h2>";
    echo "<h3 style='color:#2D3748;'>Daftar Akun Admin:</h3>";
    echo "<ul style='background-color:#f0fff4; border:1px solid #4A7C59; padding:20px; border-radius:10px;'>";
    foreach ($admins as $a) {
        echo "<li style='margin:10px 0;'><strong>Nama:</strong> " . htmlspecialchars($a['name']) . " | <strong>Email:</strong> " . htmlspecialchars($a['email']) . " | <strong>Password:</strong> admin123</li>";
    }
    echo "</ul>";
    echo "<a href='admin_login.php' style='display:inline-block; padding:15px 30px; background-color:#4A7C59; color:white; text-decoration:none; border-radius:10px; font-size:18px; margin:20px 0;'>Login Admin Sekarang</a>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
?>