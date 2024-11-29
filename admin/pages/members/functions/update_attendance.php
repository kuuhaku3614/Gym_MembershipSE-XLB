<?php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'];
    $status = $_POST['status'];
    $date = $_POST['date'];
    $currentTime = date('H:i:s');
    
    try {
        $pdo->beginTransaction();
        
        // Get status ID
        $statusId = null;
        $statusSql = "SELECT id FROM attendance_status WHERE status_name = :status";
        $statusStmt = $pdo->prepare($statusSql);
        $statusStmt->execute([':status' => strtolower($status)]);
        $statusId = $statusStmt->fetchColumn();
        
        if (!$statusId) {
            throw new Exception("Invalid status");
        }
        
        // Check if attendance record exists for today
        $sql = "
            SELECT * 
            FROM attendance 
            WHERE user_id = :user_id 
            AND date = :date
            ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status === 'checked In') {
            // Handle check-in
            if ($existingRecord) {
                $sql = "UPDATE attendance SET time_in = :time, status_id = :status_id WHERE user_id = :user_id AND date = :date";
            } else {
                $sql = "INSERT INTO attendance (user_id, date, time_in, status_id) VALUES (:user_id, :date, :time, :status_id)";
            }
            
            // Execute attendance update/insert
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':date' => $date,
                ':time' => $currentTime,
                ':status_id' => $statusId
            ]);
            
            // Get the attendance record ID
            $attendanceId = $existingRecord ? $existingRecord['id'] : $pdo->lastInsertId();
            
            // Insert into attendance history
            $historySQL = "INSERT INTO attendance_history 
                          (attendance_id, time_in, status_id) 
                          VALUES (:attendance_id, :time_in, :status_id)";
            
            $stmt = $pdo->prepare($historySQL);
            $stmt->execute([
                ':attendance_id' => $attendanceId,
                ':time_in' => $currentTime,
                ':status_id' => $statusId
            ]);
            
        } else if ($status === 'checked out') {
            // Handle check-out
            if ($existingRecord && $existingRecord['time_in']) {
                // Update attendance record with check-out time
                $sql = "UPDATE attendance SET time_out = :time, status_id = :status_id WHERE user_id = :user_id AND date = :date";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':date' => $date,
                    ':time' => $currentTime,
                    ':status_id' => $statusId
                ]);
                
                // Insert into attendance history
                $historySQL = "INSERT INTO attendance_history 
                              (attendance_id, time_in, time_out, status_id) 
                              VALUES (:attendance_id, :time_in, :time_out, :status_id)";
                
                $stmt = $pdo->prepare($historySQL);
                $stmt->execute([
                    ':attendance_id' => $existingRecord['id'],
                    ':time_in' => $existingRecord['time_in'],
                    ':time_out' => $currentTime,
                    ':status_id' => $statusId
                ]);
            } else {
                throw new Exception("Cannot check out without a valid check-in record");
            }
        }
        
        $pdo->commit();
        
        // Fetch updated attendance record
        $sql = "SELECT a.*, ast.status_name 
        FROM attendance a
        JOIN attendance_status ast ON a.status_id = ast.id
        WHERE a.user_id = :user_id AND a.date = :date";
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