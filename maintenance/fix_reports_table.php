<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Reports Table...</h1>";

try {
    // 1. Add missing columns to reports table
    echo "<h2>1. Adding missing columns to reports table...</h2>";
    
    // Check and add columns one by one
    $columns_to_add = [
        'title VARCHAR(255) NOT NULL AFTER category',
        'processed_by INT NULL AFTER status',
        'processed_at TIMESTAMP NULL AFTER processed_by',
        'admin_notes TEXT NULL AFTER processed_at',
        'completed_by INT NULL AFTER admin_notes',
        'completed_at TIMESTAMP NULL AFTER completed_by',
        'completion_photo VARCHAR(255) NULL AFTER completed_at',
        'completion_notes TEXT NULL AFTER completion_photo',
        'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
    ];
    
    foreach ($columns_to_add as $column_def) {
        $col_name = explode(' ', $column_def)[0];
        $check = $pdo->query("SHOW COLUMNS FROM reports LIKE '$col_name'");
        if (!$check->fetch()) {
            try {
                $pdo->exec("ALTER TABLE reports ADD COLUMN $column_def");
                echo "<p style='color:green'>✓ Added column '$col_name'</p>";
            } catch (PDOException $e) {
                echo "<p style='color:orange'>⚠️ Could not add $col_name: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p style='color:blue'>- Column '$col_name' already exists</p>";
        }
    }
    
    // 2. Add foreign keys
    echo "<h2>2. Checking foreign keys...</h2>";
    $fk_check = $pdo->query("SHOW CREATE TABLE reports")->fetch()['Create Table'];
    
    if (strpos($fk_check, 'processed_by') === false || strpos($fk_check, 'processed_by_ibfk') === false) {
        try {
            $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "<p style='color:green'>✓ Added foreign key for processed_by</p>";
        } catch (PDOException $e) {
            echo "<p style='color:orange'>⚠️ Could not add FK processed_by: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:blue'>- FK processed_by already exists</p>";
    }
    
    if (strpos($fk_check, 'completed_by') === false || strpos($fk_check, 'completed_by_ibfk') === false) {
        try {
            $pdo->exec("ALTER TABLE reports ADD CONSTRAINT fk_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "<p style='color:green'>✓ Added foreign key for completed_by</p>";
        } catch (PDOException $e) {
            echo "<p style='color:orange'>⚠️ Could not add FK completed_by: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:blue'>- FK completed_by already exists</p>";
    }
    
    // 3. Fix any existing reports - give them a default title
    echo "<h2>3. Fixing existing reports...</h2>";
    $stmt = $pdo->query("SELECT id, category FROM reports WHERE title IS NULL OR title = ''");
    $reports_to_fix = $stmt->fetchAll();
    foreach ($reports_to_fix as $r) {
        $default_title = "Laporan " . $r['category'];
        $update = $pdo->prepare("UPDATE reports SET title = ? WHERE id = ?");
        $update->execute([$default_title, $r['id']]);
        echo "<p style='color:blue'>- Updated report #{$r['id']} with title: $default_title</p>";
    }
    if (count($reports_to_fix) == 0) {
        echo "<p style='color:blue'>- No reports need fixing</p>";
    }
    
    // 4. Show all columns in reports now
    echo "<h2>4. Final columns in reports table:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM reports")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col) . "</li>";
    }
    echo "</ul>";
    
    // 5. Create upload directories
    echo "<h2>5. Ensuring upload directories exist...</h2>";
    $dirs = ['uploads', 'uploads/reports', 'uploads/completion'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "<p style='color:green'>✓ Created $dir</p>";
        } else {
            echo "<p style='color:blue'>- $dir exists</p>";
        }
    }
    
    echo "<hr><h1 style='color:green'>✅ TABLE REPORTS BERHASIL DIPERBAIKI!</h1>";
    echo "<p><a href='admin_reports.php'>→ Ke Halaman Kelola Laporan</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Debug: " . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
