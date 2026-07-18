
<?php
require 'config.php';

echo "<h1>Fixing community_actions table</h1>";
echo "<pre>";

try {
    $pdo->beginTransaction();
    
    $required_columns = [
        'location' => 'VARCHAR(255) NULL AFTER description',
        'target_date' => 'DATE NULL AFTER location',
        'start_date' => 'DATE NULL AFTER target_date',
        'end_date' => 'DATE NULL AFTER start_date',
        'target_volunteers' => 'INT NULL AFTER end_date',
        'photo_path' => 'VARCHAR(255) NULL AFTER target_volunteers',
        'progress' => 'INT DEFAULT 0 AFTER status'
    ];
    
    $existing_columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM community_actions");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    echo "Existing columns in community_actions: " . implode(', ', $existing_columns) . "\n\n";
    
    foreach ($required_columns as $column_name => $column_def) {
        if (!in_array($column_name, $existing_columns)) {
            echo "Adding column: $column_name\n";
            $pdo->exec("ALTER TABLE community_actions ADD COLUMN $column_name $column_def");
        } else {
            echo "Column $column_name already exists\n";
        }
    }
    
    // Also make sure action_participants exists
    echo "\nChecking action_participants table...";
    $stmt = $pdo->query("SHOW TABLES LIKE 'action_participants'");
    if (!$stmt->fetch()) {
        echo "creating...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS action_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action_id INT NOT NULL,
                user_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (action_id) REFERENCES community_actions(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_participant (action_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        echo " exists.\n";
    }
    
    // Also make sure community_members has status
    echo "\nChecking community_members status column...";
    $stmt = $pdo->query("SHOW COLUMNS FROM community_members LIKE 'status'");
    if (!$stmt->fetch()) {
        echo "adding...\n";
        $pdo->exec("ALTER TABLE community_members ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER user_id");
    } else {
        echo " exists.\n";
    }
    
    $pdo->commit();
    echo "\n✅ All fixes applied successfully!";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
echo "</pre>";
?>
