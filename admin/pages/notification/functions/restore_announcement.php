<?php
require_once(__DIR__ . '/../../../../config.php');

function restoreAnnouncement($pdo, $id) {
    try {
        // Input validation
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return ["status" => "error", "message" => "Invalid announcement ID."];
        }
        
        // Check if the announcement exists
        $checkStmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() === 0) {
            return ["status" => "error", "message" => "Announcement not found."];
        }
        
        // Update the announcement status to active
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return ["status" => "success", "message" => "Announcement restored successfully."];
        } else {
            return ["status" => "error", "message" => "Failed to restore announcement."];
        }
    } catch (PDOException $e) {
        error_log("Database error in restoreAnnouncement: " . $e->getMessage());
        return ["status" => "error", "message" => "Database error occurred."];
    }
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

// Ensure proper content type for JSON response
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "Announcement ID is required"]);
    exit();
}

// Process the request
$response = restoreAnnouncement($pdo, $_POST['id']);
echo json_encode($response);
exit();