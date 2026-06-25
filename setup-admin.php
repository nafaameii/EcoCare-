<?php
require 'config.php';

// Script untuk membuat/mereset akun admin
echo "<h1>Setup Admin EcoCare+</h1>";

try {
    // Cek apakah admin sudah ada
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute(['admin@ecocare.id']);
    $admin_exists = $check->fetch();
    
    // Hash password "admin123" dengan password_hash
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    if ($admin_exists) {
        // Update password admin yang sudah ada
        $stmt = $pdo->prepare("UPDATE users SET password = ?, name = 'Admin EcoCare', role = 'admin' WHERE email = ?");
        $stmt->execute([$hashed_password, 'admin@ecocare.id']);
        echo "<p style='color: green; font-size: 18px;'>✅ Akun admin berhasil di-reset!</p>";
    } else {
        // Insert admin baru
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, phone, resident_id, role) 
            VALUES (?, ?, ?, ?, ?, 'admin')
        ");
        $stmt->execute([
            'Admin EcoCare',
            'admin@ecocare.id',
            $hashed_password,
            '081234567890',
            '9876543210987654'
        ]);
        echo "<p style='color: green; font-size: 18px;'>✅ Akun admin berhasil dibuat!</p>";
    }
    
    echo "<hr>";
    echo "<h3>Login Admin:</h3>";
    echo "<p><strong>Email:</strong> admin@ecocare.id</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<a href='login.php' style='display: inline-block; margin-top: 20px; background: #6FAF8F; color: white; padding: 10px 30px; border-radius: 10px; text-decoration: none; font-weight: bold;'>Login Sekarang</a>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>