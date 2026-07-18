<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Notification System Migration</h1>";

try {
    // 1. Table to track when user last read a community's comments
    echo "<h2>Step 1: Create user_community_read table</h2>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_community_read (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            report_id INT NOT NULL,
            last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_community (user_id, report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green'>✅ user_community_read created or already exists</p>";

    // 2. Table to track when user last read a report's status updates
    echo "<h2>Step 2: Create user_report_read table</h2>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_report_read (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            report_id INT NOT NULL,
            last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_report (user_id, report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green'>✅ user_report_read created or already exists</p>";

    echo "<h2>Migration Complete!</h2>";
    echo "<p><a href='dashboard_pengguna.php'>Go to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
}
?>
