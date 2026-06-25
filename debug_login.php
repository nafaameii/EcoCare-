<?php
require 'config.php';
echo "<h1>Debug Login Admin</h1>";

try {
    // Get all users
    $stmt = $pdo->query("SELECT id, name, email, password, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h2>Semua User di Database:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Password Hash</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>".$user['id']."</td>";
        echo "<td>".htmlspecialchars($user['name'])."</td>";
        echo "<td>".htmlspecialchars($user['email'])."</td>";
        echo "<td>".$user['role']."</td>";
        echo "<td>".htmlspecialchars($user['password'])."</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification with example passwords
    echo "<br><hr><br>";
    echo "<h2>Test Password Verification:</h2>";
    
    $test_passwords = ['admin123', 'password123', 'nafa123', 'mugi123', 'nadia123'];
    
    foreach ($users as $user) {
        echo "<h3>Testing user: " . htmlspecialchars($user['email']) . "</h3>";
        foreach ($test_passwords as $test_pwd) {
            $result = password_verify($test_pwd, $user['password']) ? "✅ Cocok!" : "❌ Tidak cocok";
            echo "<p>Password '$test_pwd': $result</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
