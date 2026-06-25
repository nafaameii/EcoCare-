<?php
require 'config.php';

echo "<h2>Updating EcoCare+ Database...</h2>";

try {
    // Create educations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS educations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✅ Table 'educations' created successfully!</p>";
    
    // Create actions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255) NULL,
            date_time DATETIME NULL,
            image_path VARCHAR(255) NULL,
            status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✅ Table 'actions' created successfully!</p>";
    
    echo "<br><h3>Database updated successfully! 🎉</h3>";
    echo "<p><a href='admin_dashboard.php'>Go to Admin Dashboard</a></p>";
    
} catch(PDOException $e) {
    die("<p style='color:red;'>❌ Database error: " . $e->getMessage() . "</p>");
}
?>