
<?php
require 'config.php';

echo "<h1>Checking Database Structure</h1>";

// List all tables
echo "<h2>Tables in database:</h2>";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

// Check educations table structure
if (in_array('educations', $tables)) {
    echo "<h2>educations table columns:</h2>";
    $stmt = $pdo->query("DESCRIBE educations");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
}

// Check community_actions table structure
if (in_array('community_actions', $tables)) {
    echo "<h2>community_actions table columns:</h2>";
    $stmt = $pdo->query("DESCRIBE community_actions");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
}

// Check actions table structure
if (in_array('actions', $tables)) {
    echo "<h2>actions table columns:</h2>";
    $stmt = $pdo->query("DESCRIBE actions");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
}
?>
