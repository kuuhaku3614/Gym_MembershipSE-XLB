<?php
// reset_attendance.php
require_once '../../../../config.php';

try {
    $pdo->beginTransaction();
    
    // Get all records before reset for history
    $sql = "SELECT * FROM attendance 
            WHERE date = CURRENT_DATE 
            AND status_id != (SELECT id FROM attendance_status WHERE status_name = 'pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reset attendance table
    $sql = "UPDATE attendance 
            SET time_in = NULL, 
                time_out = NULL, 
                status_id = (SELECT id FROM attendance_status WHERE status_name = 'pending')
            WHERE date = CURRENT_DATE";
    $pdo->exec($sql);
    
    // Log final states to history
    foreach ($records as $record) {
        if ($record['time_in'] !== null) {
            $sql = "INSERT INTO attendance_history 
                    (attendance_id, time_in, status_id) 
                    VALUES (:attendance_id, :time_in, 
                            (SELECT id FROM attendance_status WHERE status_name = 'checked in'))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':attendance_id' => $record['id'],
                ':time_in' => $record['time_in']
            ]);
        }
        
        if ($record['time_out'] !== null) {
            $sql = "INSERT INTO attendance_history 
                    (attendance_id, time_in, time_out, status_id) 
                    VALUES (:attendance_id, :time_in, :time_out, 
                            (SELECT id FROM attendance_status WHERE status_name = 'checked out'))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':attendance_id' => $record['id'],
                ':time_in' => $record['time_in'],
                ':time_out' => $record['time_out']
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance reset successful'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}