<?php
require_once(__DIR__ . '/../../../../config.php');

function getPreviousAnnouncements($pdo) {
    try {
        // Get inactive announcements
        $stmt = $pdo->prepare("
            SELECT id, applied_date, applied_time, message, announcement_type 
            FROM announcements 
            WHERE is_active = 0 
            ORDER BY applied_date DESC, applied_time DESC
        ");
        $stmt->execute();
        
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and times for display
        foreach ($announcements as &$announcement) {
            $announcement['applied_date'] = date('F d, Y', strtotime($announcement['applied_date']));
            $announcement['applied_time'] = date('h:i A', strtotime($announcement['applied_time']));
        }
        
        return ["status" => "success", "data" => $announcements];
    } catch (PDOException $e) {
        error_log("Database error in getPreviousAnnouncements: " . $e->getMessage());
        return ["status" => "error", "message" => "Database error occurred"];
    }
}

// Ensure proper content type for JSON response
header('Content-Type: application/json');

// Process the request
$response = getPreviousAnnouncements($pdo);
echo json_encode($response);
exit();