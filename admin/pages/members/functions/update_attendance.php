<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'];
    $status = $_POST['status'];
    $date = $_POST['date'];
    $currentTime = date('H:i:s');
    
    try {
        $pdo->beginTransaction();
        
        // Check if attendance record exists for today
        $sql = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status === 'checked In') {
            // Handle check-in
            if ($existingRecord) {
                $sql = "UPDATE attendance 
                        SET time_in = :time, 
                            status = :status 
                        WHERE user_id = :user_id 
                        AND date = :date";
            } else {
                $sql = "INSERT INTO attendance (user_id, date, time_in, status) 
                        VALUES (:user_id, :date, :time, :status)";
            }
            
            // Execute attendance update/insert
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':date' => $date,
                ':time' => $currentTime,
                ':status' => $status
            ]);
            
            // Insert into attendance history for check-in only
            $historySQL = "INSERT INTO attendance_history 
                          (user_id, date, time_in, status) 
                          VALUES (:user_id, :date, :time_in, :status)";
            
            $stmt = $pdo->prepare($historySQL);
            $stmt->execute([
                ':user_id' => $userId,
                ':date' => $date,
                ':time_in' => $currentTime,
                ':status' => $status
            ]);
            
        } else if ($status === 'checked out') {
            // Handle check-out
            if ($existingRecord && $existingRecord['time_in']) {
                // Update attendance record with check-out time
                $sql = "UPDATE attendance 
                        SET time_out = :time, 
                            status = :status 
                        WHERE user_id = :user_id 
                        AND date = :date";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':date' => $date,
                    ':time' => $currentTime,
                    ':status' => $status
                ]);
                
                // Insert complete record into attendance history
                $historySQL = "INSERT INTO attendance_history 
                              (user_id, date, time_in, time_out, status) 
                              VALUES (:user_id, :date, :time_in, :time_out, :status)";
                
                $stmt = $pdo->prepare($historySQL);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':date' => $date,
                    ':time_in' => $existingRecord['time_in'], // Use existing check-in time
                    ':time_out' => $currentTime,
                    ':status' => $status
                ]);
            } else {
                throw new Exception("Cannot check out without a valid check-in record");
            }
        }
        
        $pdo->commit();
        
        // Fetch updated attendance record
        $sql = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        $updatedRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'attendance' => $updatedRecord
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>