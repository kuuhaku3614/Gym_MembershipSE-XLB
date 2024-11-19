<?php
// delete_staff.php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    // Due to ON DELETE CASCADE, related records in personal_details and coaches
    // tables will be automatically deleted

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>