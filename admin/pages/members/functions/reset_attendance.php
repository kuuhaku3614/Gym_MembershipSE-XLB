<?php
require_once '../../../../config.php';

try {
    $pdo->beginTransaction();
    
    // Get missed status ID
    $missedStatusSql = "SELECT id FROM attendance WHERE status = 'missed'";
    $missedStatusStmt = $pdo->prepare($missedStatusSql);
    $missedStatusStmt->execute();
    $missedStatusId = $missedStatusStmt->fetchColumn();
    
    // Get all user IDs
    $usersSql = "SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'member') AND is_active = 1";
    $usersStmt = $pdo->prepare($usersSql);
    $usersStmt->execute();
    $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get existing attendance records for today
    $existingAttendanceSql = "SELECT user_id FROM attendance WHERE date = CURRENT_DATE()";
    $existingAttendanceStmt = $pdo->prepare($existingAttendanceSql);
    $existingAttendanceStmt->execute();
    $attendedUserIds = $existingAttendanceStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Identify missed users
    $missedUserIds = array_diff($userIds, $attendedUserIds);
    
    // Insert missed records for those users
    foreach ($missedUserIds as $userId) {
        $missedSql = "INSERT INTO attendance (user_id, date, status_id) VALUES (:user_id, CURRENT_DATE(), :missed_status_id)";
        $missedStmt = $pdo->prepare($missedSql);
        $missedStmt->execute([
            ':user_id' => $userId,
            ':missed_status_id' => $missedStatusId
        ]);
    }
    
    // Reset logic - use missed status for all records
    $sql = "UPDATE attendance 
            SET time_in = NULL, 
                time_out = NULL, 
                status = :missed_status_id
            WHERE date = CURRENT_DATE()";
    $resetStmt = $pdo->prepare($sql);
    $resetStmt->execute([':missed_status_id' => $missedStatusId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance reset successful',
        'missed_users' => count($missedUserIds)
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}