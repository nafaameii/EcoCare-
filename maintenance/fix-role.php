<?php
require 'config.php';

echo "<h1>Fixing Role Column</h1>";

try {
    // Update role column to use 'user' instead of 'masyarakat'
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin') DEFAULT 'user'");
    echo "<p style='color: green;'>✅ Role column updated!</p>";
    
    // Update existing users with role 'masyarakat' to 'user'
    $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE role = 'masyarakat'");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Existing users updated!</p>";
    
    echo "<p><a href='check-database.php'>Lanjut ke Setup Admin</a></p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>