<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Updating Database (ecocareproject)...</h1>";

try {
    // 1. Create educations table
    echo "<h2>1. Creating educations table...</h2>";
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
    echo "<p style='color:green'>✓ educations table created!</p>";

    // 2. Create actions table
    echo "<h2>2. Creating actions table...</h2>";
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
    echo "<p style='color:green'>✓ actions table created!</p>";

    // 3. Alter users table to add missing columns and fix role enum
    echo "<h2>3. Updating users table...</h2>";
    
    // Check if profile_pic column exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER role");
        echo "<p style='color:green'>✓ Added profile_pic column!</p>";
    }
    
    // Check if updated_at column exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "<p style='color:green'>✓ Added updated_at column!</p>";
    }
    
    // Fix role enum if needed - but let's just add admin accounts
    echo "<h2>4. Adding admin accounts...</h2>";
    $admins = [
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
            echo "<p style='color:green'>✓ Admin {$admin['name']} ({$admin['email']}) created!</p>";
        } else {
            echo "<p style='color:blue'>- Admin {$admin['name']} already exists!</p>";
        }
    }
    
    // 5. Fix existing admin account if any
    echo "<h2>5. Checking existing admin accounts...</h2>";
    $check_old_admin = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
    $check_old_admin->execute(['admin@ecocare.id']);
    if ($old_admin = $check_old_admin->fetch()) {
        if ($old_admin['role'] !== 'admin') {
            $fix_stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $fix_stmt->execute([$old_admin['id']]);
            echo "<p style='color:green'>✓ Fixed admin@ecocare.id role to admin!</p>";
        } else {
            echo "<p style='color:blue'>- admin@ecocare.id is already admin!</p>";
        }
    }
    
    // 6. Create directories
    echo "<h2>6. Creating upload directories...</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/profiles', 'uploads/educations', 'uploads/actions', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p style='color:green'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color:blue'>- Directory $dir already exists!</p>";
        }
    }

    echo "<hr><h1 style='color:green'>✓ Database Update Complete!</h1>";
    echo "<p><strong>You can now login with:</strong></p>";
    echo "<ul>";
    echo "<li>nafa@ecocare.com / admin123</li>";
    echo "<li>mugi@ecocare.com / admin123</li>";
    echo "<li>nadia@ecocare.com / admin123</li>";
    echo "</ul>";
    echo "<p><a href='admin_login.php'>Go to Admin Login →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
