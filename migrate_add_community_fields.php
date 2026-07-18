<?php
require 'config.php';
echo "<h1>🔧 Add Missing Community Fields Migration</h1>";
echo "<pre>";

try {
    echo "✅ Starting migration\n";
    
    // Add missing columns to community_actions
    $columnsToAdd = [
        'activity_date' => 'DATE',
        'registration_deadline' => 'DATE',
        'estimated_duration' => 'VARCHAR(100)',
        'priority' => "ENUM('rendah', 'sedang', 'tinggi') DEFAULT 'sedang'",
        'cover_photo' => 'VARCHAR(255)',
        'location' => 'VARCHAR(255)',
        'category' => 'VARCHAR(100)'
    ];
    
    foreach ($columnsToAdd as $column => $definition) {
        echo "\n📝 Adding column: $column\n";
        try {
            $pdo->exec("ALTER TABLE community_actions ADD COLUMN $column $definition");
            echo "  ✅ Column $column added successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "  ℹ️ Column $column already exists\n";
            } else {
                echo "  ❌ Error adding column $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "<p><a href='community.php?id=1'>Go to Community Page</a></p>";
} catch (PDOException $e) {
    echo "\n❌ MIGRATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
