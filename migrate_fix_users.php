<?php
require 'config.php';

echo "<h1>Migrasi Database - Fix Users Table</h1>";
echo "<pre>";

try {
    // Start transaction
    $pdo->beginTransaction();
    echo "✓ Transaction started\n";

    // 1. Fix role enum
    echo "\n--- Fixing users table ---\n";
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('masyarakat', 'admin') NOT NULL DEFAULT 'masyarakat'");
        echo "✓ Updated users role enum\n";
    } catch (PDOException $e) {
        echo "- Role enum already correct: " . $e->getMessage() . "\n";
    }

    // 2. Add status column if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif', 'ditangguhkan') NOT NULL DEFAULT 'aktif' AFTER role");
        echo "✓ Added status column to users table\n";
    } else {
        echo "- status column already exists\n";
    }

    // 3. Add latitude/longitude columns if not exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'latitude'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
        echo "✓ Added latitude/longitude to users table\n";
    } else {
        echo "- latitude/longitude already exist\n";
    }

    // 4. Fix any existing users with wrong role
    $pdo->exec("UPDATE users SET role = 'masyarakat' WHERE role NOT IN ('masyarakat', 'admin')");
    echo "✓ Fixed any incorrect user roles\n";

    // Commit transaction
    $pdo->commit();
    echo "\n✅ MIGRASI FIX USER BERHASIL!\n";

} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    echo "\n❌ Migrasi gagal: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='admin_users.php'>Ke Halaman Kelola Pengguna</a> | <a href='migrate_all.php'>Migrasi Semua Fitur</a></p>";
?>
