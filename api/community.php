<?php
require '../config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

function tableExists($pdo, $tableName) {
    try {
        $result = $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    $dbReady = tableExists($pdo, 'community_members') && 
               tableExists($pdo, 'community_actions');
    
    if (!$dbReady) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database belum dimigrasi. <a href="migrate_community_system.php" target="_blank">Klik di sini untuk menjalankan migrasi</a>'
        ]);
        exit;
    }

    switch ($action) {
        case 'join':
            $report_id = intval($_POST['report_id'] ?? 0);
            if (!$report_id) {
                echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
                exit;
            }
            
            $check = $pdo->prepare("SELECT id FROM community_members WHERE report_id = ? AND user_id = ?");
            $check->execute([$report_id, $user_id]);
            
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Anda sudah menjadi anggota komunitas ini']);
                exit;
            }
            
            $pdo->prepare("INSERT INTO community_members (report_id, user_id, status) VALUES (?, ?, 'active')")
                ->execute([$report_id, $user_id]);
            
            $pdo->prepare("UPDATE reports SET status = 'Komunitas Terbentuk' WHERE id = ? AND status IN ('Baru', 'Diproses')")
                ->execute([$report_id]);
            
            echo json_encode(['success' => true, 'message' => 'Berhasil bergabung dengan komunitas!']);
            break;
        
        case 'leave':
            $report_id = intval($_POST['report_id'] ?? 0);
            if (!$report_id) {
                echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
                exit;
            }
            
            $pdo->prepare("DELETE FROM community_members WHERE report_id = ? AND user_id = ?")
                ->execute([$report_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Berhasil keluar dari komunitas']);
            break;
        
        case 'get_data':
            $report_id = intval($_GET['report_id'] ?? 0);
            
            $memberCount = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE report_id = ? AND status = 'active'");
            $memberCount->execute([$report_id]);
            $memberCount = $memberCount->fetchColumn();
            
            $actionCount = $pdo->prepare("SELECT COUNT(*) FROM community_actions WHERE report_id = ?");
            $actionCount->execute([$report_id]);
            $actionCount = $actionCount->fetchColumn();
            
            $contribCount = $pdo->prepare("SELECT COUNT(*) FROM community_contributions cc JOIN community_actions ca ON cc.action_id = ca.id WHERE ca.report_id = ?");
            $contribCount->execute([$report_id]);
            $contribCount = $contribCount->fetchColumn();
            
            $isMember = $pdo->prepare("SELECT id FROM community_members WHERE report_id = ? AND user_id = ?");
            $isMember->execute([$report_id, $user_id]);
            $isMember = $isMember->fetch() !== false;
            
            $members = $pdo->prepare("SELECT cm.*, u.name, u.profile_pic FROM community_members cm JOIN users u ON cm.user_id = u.id WHERE cm.report_id = ? ORDER BY cm.joined_at DESC LIMIT 20");
            $members->execute([$report_id]);
            $members = $members->fetchAll();
            
            $actions = $pdo->prepare("SELECT ca.*, u.name as creator_name FROM community_actions ca JOIN users u ON ca.created_by = u.id WHERE ca.report_id = ? ORDER BY ca.created_at DESC");
            $actions->execute([$report_id]);
            $actions = $actions->fetchAll();
            
            $comments = $pdo->prepare("SELECT cc.*, u.name FROM community_comments cc JOIN users u ON cc.user_id = u.id WHERE cc.report_id = ? ORDER BY cc.created_at DESC");
            $comments->execute([$report_id]);
            $comments = $comments->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'memberCount' => $memberCount,
                    'actionCount' => $actionCount,
                    'contribCount' => $contribCount,
                    'isMember' => $isMember,
                    'members' => $members,
                    'actions' => $actions,
                    'comments' => $comments
                ]
            ]);
            break;
        
        case 'add_comment':
            $report_id = intval($_POST['report_id'] ?? 0);
            $comment = sanitize_input($_POST['comment'] ?? '');
            
            if (!$report_id || empty($comment)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
                exit;
            }
            
            $pdo->prepare("INSERT INTO community_comments (report_id, user_id, comment) VALUES (?, ?, ?)")
                ->execute([$report_id, $user_id, $comment]);
            
            $newCommentId = $pdo->lastInsertId();
            $newComment = $pdo->prepare("SELECT cc.*, u.name FROM community_comments cc JOIN users u ON cc.user_id = u.id WHERE cc.id = ?")
                ->execute([$newCommentId])
                ->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Komentar berhasil dikirim',
                'comment' => $newComment
            ]);
            break;
        
        case 'create_action':
            $report_id = intval($_POST['report_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $targetVolunteers = !empty($_POST['target_volunteers']) ? intval($_POST['target_volunteers']) : null;
            $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            
            if (!$report_id || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Judul dan laporan harus diisi']);
                exit;
            }
            
            $pdo->prepare("
                INSERT INTO community_actions (report_id, created_by, title, description, target_volunteers, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'planned')
            ")->execute([$report_id, $user_id, $title, $description, $targetVolunteers, $startDate, $endDate]);
            
            $pdo->prepare("UPDATE reports SET status = 'Aksi Berjalan' WHERE id = ?")
                ->execute([$report_id]);
            
            echo json_encode(['success' => true, 'message' => 'Aksi komunitas berhasil dibuat']);
            break;
        
        case 'update_action':
            $actionId = intval($_POST['action_id'] ?? 0);
            $status = $_POST['status'] ?? null;
            $progress = isset($_POST['progress']) ? intval($_POST['progress']) : null;
            
            if (!$actionId) {
                echo json_encode(['success' => false, 'message' => 'ID aksi tidak valid']);
                exit;
            }
            
            $updates = [];
            $params = [];
            
            if ($status) {
                $updates[] = "status = ?";
                $params[] = $status;
            }
            if ($progress !== null) {
                $updates[] = "progress = ?";
                $params[] = $progress;
            }
            
            if (empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan']);
                exit;
            }
            
            $params[] = $actionId;
            $pdo->prepare("UPDATE community_actions SET " . implode(', ', $updates) . " WHERE id = ?")
                ->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Aksi berhasil diperbarui']);
            break;
        
        case 'add_contribution':
            $actionId = intval($_POST['action_id'] ?? 0);
            $category = $_POST['category'] ?? '';
            $description = sanitize_input($_POST['description'] ?? '');
            
            if (!$actionId || empty($category) || empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
                exit;
            }
            
            $pdo->prepare("INSERT INTO community_contributions (action_id, user_id, category, description) VALUES (?, ?, ?, ?)")
                ->execute([$actionId, $user_id, $category, $description]);
            
            echo json_encode(['success' => true, 'message' => 'Kontribusi berhasil ditambahkan']);
            break;
        
        case 'get_contributions':
            $actionId = intval($_GET['action_id'] ?? 0);
            $contribs = $pdo->prepare("SELECT cc.*, u.name FROM community_contributions cc JOIN users u ON cc.user_id = u.id WHERE cc.action_id = ? ORDER BY cc.created_at DESC")
                ->execute([$actionId])
                ->fetchAll();
            
            echo json_encode(['success' => true, 'contributions' => $contribs]);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>