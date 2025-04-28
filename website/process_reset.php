<?php
// process_reset.php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Define the function here instead of including checkin_page.php
function performReset($pdo) {
    try {
        $currentDateTime = new DateTime('now');
        
        $pdo->beginTransaction();
        
        // Reset existing attendance records for the new day
        $resetAttendanceRecordsSql = "
            UPDATE attendance
            SET 
                date = CURRENT_DATE,
                time_in = NULL,
                time_out = NULL,
                status = NULL,
                created_at = CURRENT_TIMESTAMP
            WHERE user_id IN (
                SELECT DISTINCT user_id 
                FROM attendance
            )
        ";
        $pdo->exec($resetAttendanceRecordsSql);
        
        // Update the last reset timestamp - use REPLACE instead of INSERT to ensure it's updated
        $updateResetTimeSql = "
            REPLACE INTO system_controls (key_name, value) 
            VALUES ('last_attendance_reset', :timestamp)
        ";
        $stmt = $pdo->prepare($updateResetTimeSql);
        $stmt->execute([
            ':timestamp' => $currentDateTime->format('Y-m-d H:i:s')
        ]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Manual reset failed: " . $e->getMessage());
        return false;
    }
}

try {
    $database = new Database();
    $pdo = $database->connect();
    
    if (performReset($pdo)) {
        // Set a session flag indicating reset just happened
        $_SESSION['attendance_reset_completed'] = true;
        
        // Log reset success and details
        error_log("Attendance reset successfully executed at " . date('Y-m-d H:i:s'));
        echo json_encode(['status' => 'success']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Reset failed']);
        exit;
    }
} catch (Exception $e) {
    error_log("Reset error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>