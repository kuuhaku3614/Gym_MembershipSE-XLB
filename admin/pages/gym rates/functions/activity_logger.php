<?php
/**
 * Staff Activity Logger
 * 
 * This file provides functions to log staff activities in the database
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to log staff activity
function logStaffActivity($activity, $description = null) {
    global $pdo;
    
    // Get current user ID from session
    $staff_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    if (!$staff_id) {
        // No logged-in user found
        error_log("Activity logging failed: No staff_id found in session");
        return false;
    }
    
    try {
        $sql = "INSERT INTO staff_activity_log (activity, description, staff_id) 
                VALUES (:activity, :description, :staff_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':activity' => $activity,
            ':description' => $description,
            ':staff_id' => $staff_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log error (to error log instead of displaying to user)
        error_log("Staff activity logging error: " . $e->getMessage());
        return false;
    }
}