<?php
require 'config.php';

// Delete user@user.com
$email_to_delete = 'user@user.com';
$stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt_check->execute([$email_to_delete]);
$user = $stmt_check->fetch();

if ($user) {
    $user_id = $user['id'];
    
    // Delete related records
    $pdo->prepare("DELETE FROM actions WHERE created_by = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM educations WHERE created_by = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM reports WHERE user_id = ?")->execute([$user_id]);
    
    // Delete community tables if exist
    function tableExists($pdo, $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    $community_tables = [
        'community_members' => 'user_id',
        'community_actions' => 'created_by',
        'community_contributions' => 'user_id',
        'community_comments' => 'user_id'
    ];
    foreach ($community_tables as $table => $column) {
        if (tableExists($pdo, $table)) {
            $pdo->prepare("DELETE FROM $table WHERE $column = ?")->execute([$user_id]);
        }
    }
    
    // Delete user
    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$user_id]);
    $affected = $stmt_delete->rowCount();
    
    if ($affected > 0) {
        echo "<p style='color:green'>✅ user@user.com has been deleted from the database!</p>";
        echo "<p>Now go to admin_users.php, refresh and check that it's gone!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to delete user@user.com!</p>";
    }
} else {
    echo "<p style='color:blue'>ℹ️ user@user.com was already not in the database!</p>";
}
?>