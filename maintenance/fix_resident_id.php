<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Resident ID Issue...</h1>";

try {
    // 1. First, remove UNIQUE constraint from resident_id and allow NULL
    echo "<h2>1. Fixing users table structure...</h2>";
    
    // Check if resident_id is UNIQUE
    $check_index = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'resident_id'");
    if ($check_index->fetch()) {
        // Drop UNIQUE index
        $pdo->exec("ALTER TABLE users DROP INDEX resident_id");
        echo "<p style='color:green'>✓ Dropped UNIQUE constraint from resident_id!</p>";
    }
    
    // Modify resident_id to allow NULL
    $pdo->exec("ALTER TABLE users MODIFY COLUMN resident_id VARCHAR(50) NULL");
    echo "<p style='color:green'>✓ Modified resident_id to allow NULL!</p>";
    
    // 2. Create admin accounts now
    echo "<h2>2. Creating admin accounts...</h2>";
    $admins = [
        ['name' => 'Test Admin', 'email' => 'testadmin@ecocare.com', 'password' => 'test123'],
        ['name' => 'Nafa', 'email' => 'nafa@ecocare.com', 'password' => 'admin123'],
        ['name' => 'Mugi', 'email' => 'mugi@ecocare.com', 'password' => 'admin123'],
        ['name' => 'Nadia', 'email' => 'nadia@ecocare.com', 'password' => 'admin123']
    ];
    
    foreach ($admins as $admin) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin['email']]);
        if (!$check->fetch()) {
            $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$admin['name'], $admin['email'], $hashed_password]);
            echo "<p style='color:green'>✓ Akun {$admin['name']} ({$admin['email']}) berhasil dibuat!</p>";
        } else {
            // Update password to ensure it works
            $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
            $stmt->execute([$hashed_password, $admin['email']]);
            echo "<p style='color:blue'>- Akun {$admin['name']} sudah ada! Password di-reset!</p>";
        }
    }
    
    // 3. Create missing tables
    echo "<h2>3. Ensuring all tables exist...</h2>";
    
    // educations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS educations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        photo_path VARCHAR(255) NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green'>✓ Tabel educations siap!</p>";
    
    // actions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(255) NOT NULL,
        date_time DATETIME NOT NULL,
        photo_path VARCHAR(255) NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green'>✓ Tabel actions siap!</p>";
    
    // 4. Create directories
    echo "<h2>4. Creating upload directories...</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/profiles', 'uploads/educations', 'uploads/actions', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p style='color:green'>✓ Direktori $dir dibuat!</p>";
        } else {
            echo "<p style='color:blue'>- Direktori $dir sudah ada!</p>";
        }
    }
    
    // 5. Show all users
    echo "<h2>5. Semua Pengguna di Database:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users");
    $users = $stmt->fetchAll();
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='8'><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>Tidak ada pengguna!</p>";
    }
    
    echo "<hr><h1 style='color:green'>✅ FIX BERHASIL!</h1>";
    echo "<h3>Login dengan:</h3>";
    echo "<ul>";
    echo "<li><strong>testadmin@ecocare.com</strong> | <strong>test123</strong></li>";
    echo "<li><strong>mugi@ecocare.com</strong> | <strong>admin123</strong></li>";
    echo "<li><strong>nafa@ecocare.com</strong> | <strong>admin123</strong></li>";
    echo "<li><strong>nadia@ecocare.com</strong> | <strong>admin123</strong></li>";
    echo "</ul>";
    echo "<p><a href='admin_login.php'>→ Ke Halaman Login Admin</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
