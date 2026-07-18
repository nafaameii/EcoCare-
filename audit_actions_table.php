
<?php
require 'config.php';
echo "<h1>Audit Tabel Actions & Community Actions</h1>";

// Check what tables exist
echo "<h2>Semua Tabel di Database:</h2>";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

foreach (['actions', 'community_actions'] as $table) {
    if (in_array($table, $tables)) {
        echo "<h2>DESCRIBE `$table`:</h2>";
        $stmt = $pdo->query("DESCRIBE `$table`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($cols);
        echo "</pre>";
        
        if ($stmt = $pdo->query("SELECT * FROM `$table` LIMIT 5")) {
            echo "<h2>Sample Data dari `$table`:</h2>";
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            print_r($data);
            echo "</pre>";
        }
    } else {
        echo "<h2>Tabel `$table` tidak ditemukan</h2>";
    }
}
?>
