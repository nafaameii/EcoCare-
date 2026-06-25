<?php
require 'config.php';
echo "<h1>Debug Database EcoCare</h1>";

try {
    echo "<h2>Isi Tabel users:</h2>";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Resident ID (NIK)</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>".$user['id']."</td>";
            echo "<td>".htmlspecialchars($user['name'])."</td>";
            echo "<td>".htmlspecialchars($user['email'])."</td>";
            echo "<td>".htmlspecialchars($user['resident_id'])."</td>";
            echo "<td>".$user['role']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tabel users kosong!</p>";
    }
    
    // Coba hapus semua user (opsional, hanya untuk testing)
    echo "<br><hr><br>";
    echo "<h2>Opsi Debug:</h2>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='clear_users' style='background-color: #ff4444; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Kosongkan Tabel users (Hanya Testing!)</button>";
    echo "</form>";
    
    if (isset($_POST['clear_users'])) {
        $pdo->exec("DELETE FROM users");
        echo "<p>Tabel users telah dikosongkan!</p>";
        echo "<meta http-equiv='refresh' content='1'>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
