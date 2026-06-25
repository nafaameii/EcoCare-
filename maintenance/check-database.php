<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

echo "<h1 style='color: #6FAF8F;'>Check EcoCare+ Database</h1>";

// 1. Cek tabel users
echo "<h3>1. Cek Tabel Users</h3>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>".$col['Field']."</td>";
        echo "<td>".$col['Type']."</td>";
        echo "<td>".$col['Null']."</td>";
        echo "<td>".$col['Key']."</td>";
        echo "<td>".$col['Default']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 2. Cek semua user di database
echo "<h3>2. Semua Pengguna di Database</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Dibuat</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>".$user['id']."</td>";
            echo "<td>".$user['name']."</td>";
            echo "<td>".$user['email']."</td>";
            echo "<td style='color: ".($user['role'] == 'admin' ? 'green' : 'blue')."'>".$user['role']."</td>";
            echo "<td>".$user['created_at']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Belum ada pengguna di database!</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 3. Buat akun admin jika belum ada
echo "<h3>3. Setup Akun Admin</h3>";
try {
    $emailAdmin = 'admin@ecocare.com';
    $passwordAdmin = 'admin123';
    $hashedPassword = password_hash($passwordAdmin, PASSWORD_DEFAULT);
    $adminName = 'Nafa'; // Nama admin realistis
    
    // Cek apakah admin sudah ada
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$emailAdmin]);
    
    if ($check->fetch()) {
        // Update password admin dan nama
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, role = 'admin' WHERE email = ?");
        $stmt->execute([$adminName, $hashedPassword, $emailAdmin]);
        echo "<p style='color: green; font-weight: bold;'>✅ Akun admin diperbarui!</p>";
    } else {
        // Insert admin baru dengan resident_id unik
        $residentIdAdmin = '111222333444555'; // ID unik untuk admin
        $attempts = 0;
        $success = false;
        
        while (!$success && $attempts < 5) {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, resident_id, role) VALUES (?, ?, ?, ?, ?, 'admin')");
                $stmt->execute([
                    $adminName,
                    $emailAdmin,
                    $hashedPassword,
                    '081234567890',
                    $residentIdAdmin
                ]);
                $success = true;
                echo "<p style='color: green; font-weight: bold;'>✅ Akun admin berhasil dibuat!</p>";
            } catch(PDOException $e) {
                // Jika duplikat resident_id, buat yang baru
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $residentIdAdmin = '111222333444' . rand(100, 999);
                    $attempts++;
                } else {
                    throw $e; // Throw error lain
                }
            }
        }
        
        if (!$success) {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Tidak bisa buat akun admin baru. Coba gunakan akun yang sudah ada dan ubah rolenya menjadi admin!</p>";
        }
    }
    
    echo "<div style='background: #f0fdf4; border: 1px solid #86efac; padding: 15px; margin-top: 20px; border-radius: 8px;'>";
    echo "<h4>Login Admin:</h4>";
    echo "<p><strong>Email:</strong> admin@ecocare.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<a href='admin_login.php' style='display: inline-block; margin-top: 10px; background: #6FAF8F; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Login Admin Sekarang</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>