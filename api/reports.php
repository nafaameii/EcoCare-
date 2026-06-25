<?php
header('Content-Type: application/json');
require '../config.php';

try {
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    
    $sql = "SELECT r.*, u.name FROM reports r JOIN users u ON r.user_id = u.id WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL";
    $params = [];
    
    if ($category) {
        $sql .= " AND r.category = ?";
        $params[] = $category;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $reports
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
