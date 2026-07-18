<?php
require 'config.php';
echo "<h1>Debug Delete User</h1>";

// Show all tables and foreign keys
echo "<h3>Database Tables & Foreign Keys to users:</h3>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "<h4>$table</h4>";
    try {
        $fks = $pdo->query("
            SELECT 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '$table' 
                AND REFERENCED_TABLE_NAME = 'users'
        ")->fetchAll();
        if ($fks) {
            echo "<ul>";
            foreach ($fks as $fk) {
                echo "<li>{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No foreign keys to users</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error checking FKs: " . $e->getMessage() . "</p>";
    }
}

// Get all users to pick an ID
echo "<h3>All Users:</h3>";
$stmt = $pdo->query("SELECT id, name, email, role FROM users");
$users = $stmt->fetchAll();
echo "<ul>";
foreach ($users as $u) {
    echo "<li>ID: {$u['id']} - {$u['name']} ({$u['email']}) - {$u['role']} <a href='debug_delete.php?test_id={$u['id']}'>Test Delete</a></li>";
}
echo "</ul>";

// Test delete if ID provided
if (isset($_GET['test_id'])) {
    $test_id = $_GET['test_id'];
    echo "<h3>Testing Delete for ID: $test_id</h3>";
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$test_id]);
        $user = $stmt->fetch();
        if (!$user) {
            echo "<p style='color:red'>User not found!</p>";
            exit;
        }
        echo "<p>Found user: {$user['name']} ({$user['email']})</p>";
        
        // Check related records
        $check_reports = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
        $check_reports->execute([$test_id]);
        $count_reports = $check_reports->fetchColumn();
        echo "<p>Reports: $count_reports</p>";
        
        $check_educations = $pdo->prepare("SELECT COUNT(*) FROM educations WHERE created_by = ?");
        $check_educations->execute([$test_id]);
        $count_educations = $check_educations->fetchColumn();
        echo "<p>Educations: $count_educations</p>";
        
        $check_actions = $pdo->prepare("SELECT COUNT(*) FROM actions WHERE created_by = ?");
        $check_actions->execute([$test_id]);
        $count_actions = $check_actions->fetchColumn();
        echo "<p>Actions: $count_actions</p>";
        
        // Check community tables if exist
        $community_tables = ['community_members', 'community_actions', 'community_contributions', 'community_comments'];
        foreach ($community_tables as $table) {
            try {
                $check = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($check->fetch()) {
                    if ($table == 'community_actions') {
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE created_by = ?");
                    } else {
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
                    }
                    $stmt_check->execute([$test_id]);
                    $count = $stmt_check->fetchColumn();
                    echo "<p>$table: $count</p>";
                }
            } catch (Exception $e) {
                echo "<p>$table: Not found or error: " . $e->getMessage() . "</p>";
            }
        }
        
        // Now do delete
        echo "<h4>Executing Delete...</h4>";
        
        // Delete related records
        $pdo->prepare("DELETE FROM actions WHERE created_by = ?")->execute([$test_id]);
        $pdo->prepare("DELETE FROM educations WHERE created_by = ?")->execute([$test_id]);
        $pdo->prepare("DELETE FROM reports WHERE user_id = ?")->execute([$test_id]);
        
        // Delete user
        $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete->execute([$test_id]);
        $affected = $stmt_delete->rowCount();
        echo "<p>Rows affected: $affected</p>";
        
        if ($affected > 0) {
            echo "<p style='color:green'>✅ Delete successful!</p>";
        } else {
            echo "<p style='color:red'>❌ Delete failed!</p>";
        }
        
        // Verify deletion
        $check_after = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $check_after->execute([$test_id]);
        if ($check_after->fetch()) {
            echo "<p style='color:red'>❌ User STILL exists!</p>";
        } else {
            echo "<p style='color:green'>✅ User is gone!</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}
?>