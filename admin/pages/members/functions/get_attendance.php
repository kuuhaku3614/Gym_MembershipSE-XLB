<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

try {
    $sql = "SELECT * FROM attendance WHERE date = CURRENT_DATE()";
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