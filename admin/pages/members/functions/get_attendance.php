<?php
require_once '../../../../config.php';

try {
    $sql = "SELECT 
    a.*,
    ast.status_name as status
FROM attendance a
JOIN attendance_status ast ON a.status_id = ast.id
WHERE a.date = CURRENT_DATE();";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($attendanceRecords);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>