<?php
require_once '../config.php';

try {
    $pdo->beginTransaction();
    
    // Get all records before reset for history
    $sql = "SELECT * FROM attendance WHERE date = CURRENT_DATE AND status != 'pending'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reset attendance table
    $sql = "UPDATE attendance 
            SET time_in = NULL, 
                time_out = NULL, 
                status = 'pending' 
            WHERE date = CURRENT_DATE";
    $pdo->exec($sql);
    
    // Log final states to history
    foreach ($records as $record) {
        if ($record['time_in'] !== null) {
            $sql = "INSERT INTO attendance_history 
                    (user_id, date, time_in, status) 
                    VALUES (:user_id, :date, :time_in, 'checked in')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $record['user_id'],
                ':date' => $record['date'],
                ':time_in' => $record['time_in']
            ]);
        }
        
        if ($record['time_out'] !== null) {
            $sql = "INSERT INTO attendance_history 
                    (user_id, date, time_out, status) 
                    VALUES (:user_id, :date, :time_out, 'checked out')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $record['user_id'],
                ':date' => $record['date'],
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
?>