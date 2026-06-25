<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Login Admin</h1>";

try {
    // 1. Show all users
    echo "<h2>1. Semua Pengguna di Database:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users");
    $users = $stmt->fetchAll();
    
    if (count($users) == 0) {
        echo "<p style='color:red'>Tidak ada pengguna di database!</p>";
    } else {
        echo "<table border='1' cellpadding='8'><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Check if tables exist
    echo "<h2>2. Tabel di Database:</h2>";
    $tables_stmt = $pdo->query("SHOW TABLES");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // 3. Create admin account if not exists - with test password
    echo "<h2>3. Membuat Akun Admin Test:</h2>";
    $test_email = "testadmin@ecocare.com";
    $test_password = "test123";
    
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$test_email]);
    if (!$check->fetch()) {
        $hashed = password_hash($test_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Test Admin', $test_email, $hashed]);
        echo "<p style='color:green'>✓ Akun test dibuat! Email: <strong>$test_email</strong>, Password: <strong>$test_password</strong></p>";
    } else {
        echo "<p style='color:blue'>- Akun test sudah ada! Email: <strong>$test_email</strong>, Password: <strong>$test_password</strong></p>";
        
        // Update password to test password in case it's wrong
        $hashed = password_hash($test_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $test_email]);
        echo "<p style='color:green'>✓ Password test di-reset!</p>";
    }
    
    // 4. Also reset password for existing admin accounts
    echo "<h2>4. Reset Password Akun Admin Lainnya:</h2>";
    $admin_password = "admin123";
    $admin_emails = ['nafa@ecocare.com', 'mugi@ecocare.com', 'nadia@ecocare.com', 'admin@ecocare.id'];
    foreach ($admin_emails as $email) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($user_data = $check->fetch()) {
            $hashed = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            echo "<p style='color:green'>✓ $email - Password di-reset menjadi <strong>$admin_password</strong> & role diset ke admin!</p>";
        } else {
            // Create if not exists
            $hashed = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $name = explode('@', $email)[0];
            $stmt->execute([ucfirst($name), $email, $hashed]);
            echo "<p style='color:green'>✓ $email - Dibuat baru! Password: <strong>$admin_password</strong></p>";
        }
    }
    
    // 5. Create missing tables
    echo "<h2>5. Membuat Tabel yang Hilang:</h2>";
    
    // educations table
    $sql_educations = "CREATE TABLE IF NOT EXISTS educations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        photo_path VARCHAR(255) NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_educations);
    echo "<p style='color:green'>✓ Tabel educations ready!</p>";
    
    // actions table
    $sql_actions = "CREATE TABLE IF NOT EXISTS actions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_actions);
    echo "<p style='color:green'>✓ Tabel actions ready!</p>";
    
    // reports table check
    $sql_reports = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        location_address VARCHAR(255) NOT NULL,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        photo_path VARCHAR(255) NULL,
        status ENUM('Baru','Diproses','Selesai') DEFAULT 'Baru',
        processed_by INT NULL,
        processed_at TIMESTAMP NULL,
        admin_notes TEXT NULL,
        completed_by INT NULL,
        completed_at TIMESTAMP NULL,
        completion_photo VARCHAR(255) NULL,
        completion_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_reports);
    echo "<p style='color:green'>✓ Tabel reports ready!</p>";
    
    // 6. Create upload directories
    echo "<h2>6. Membuat Direktori Upload:</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/profiles', 'uploads/educations', 'uploads/actions', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p style='color:green'>✓ Direktori $dir dibuat!</p>";
        } else {
            echo "<p style='color:blue'>- Direktori $dir sudah ada!</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2 style='color:green'>✅ SEMUA SUDAH SIAP!</h2>";
    echo "<h3>Login dengan akun berikut:</h3>";
    echo "<ul>";
    echo "<li><strong>testadmin@ecocare.com</strong> | password: <strong>test123</strong></li>";
    echo "<li><strong>nafa@ecocare.com</strong> | password: <strong>admin123</strong></li>";
    echo "<li><strong>mugi@ecocare.com</strong> | password: <strong>admin123</strong></li>";
    echo "<li><strong>nadia@ecocare.com</strong> | password: <strong>admin123</strong></li>";
    echo "</ul>";
    echo "<p><a href='admin_login.php'>→ Ke Halaman Login Admin</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
