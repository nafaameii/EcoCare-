
<?php
require 'config.php';

echo "<h1>Fixing Admin Tables</h1>";
echo "<pre>";

try {
    $pdo->beginTransaction();

    // 1. Add photo_path and location to community_actions if not exists
    echo "\n--- Checking community_actions table ---\n";

    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM community_actions");
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $col['Field'];
    }

    if (!in_array('photo_path', $columns)) {
        $pdo->exec("ALTER TABLE community_actions ADD COLUMN photo_path VARCHAR(255) NULL AFTER target_volunteers");
        echo "✓ Added photo_path to community_actions\n";
    } else {
        echo "- photo_path already exists in community_actions\n";
    }

    if (!in_array('location', $columns)) {
        $pdo->exec("ALTER TABLE community_actions ADD COLUMN location VARCHAR(255) NULL AFTER description");
        echo "✓ Added location to community_actions\n";
    } else {
        echo "- location already exists in community_actions\n";
    }

    // 2. Add action_participants table if not exists
    echo "\n--- Checking action_participants table ---\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'action_participants'");
    if (!$stmt->fetch()) {
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
        echo "✓ Created action_participants table\n";
    } else {
        echo "- action_participants table already exists\n";
    }

    $pdo->commit();
    echo "\n✅ All fixes applied successfully!\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
