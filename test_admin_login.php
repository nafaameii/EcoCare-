<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Admin Login</h1>";

$email = 'nafa@ecocare.com';
$password = 'admin123';

echo "<p>Testing email: " . htmlspecialchars($email) . "</p>";
echo "<p>Testing password: " . htmlspecialchars($password) . "</p>";

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p>User found:</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";

        if (password_verify($password, $user['password'])) {
            echo "<p style='color:green; font-size: 20px;'><strong>✅ Password is correct!</strong></p>";

            if ($user['role'] === 'admin') {
                echo "<p style='color:green; font-size: 20px;'><strong>✅ User is admin!</strong></p>";

                $status = strtolower($user['status'] ?? 'aktif');
                if (in_array($status, ['aktif', 'active'])) {
                    echo "<p style='color:green; font-size: 20px;'><strong>✅ Status is active!</strong></p>";
                    echo "<h2 style='color: green;'>🎉 Login would be successful!</h2>";
                } else {
                    echo "<p style='color: red;'>❌ Status is not active: " . htmlspecialchars($user['status']) . "</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ User is not admin!</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Password is incorrect!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ User not found!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
