<?php
require_once '../../../../config.php';

function removeAnnouncement($pdo, $id) {
    // Enhanced input validation with detailed error message
    if (!isset($id) || empty($id)) {
        return ["status" => "error", "message" => "Announcement ID is required"];
    }

    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        return ["status" => "error", "message" => "Invalid announcement ID: must be a positive integer"];
    }

    try {
        // First check if announcement exists
        $checkStmt = $pdo->prepare("SELECT id FROM announcements WHERE id = :id AND is_active = 1");
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            return ["status" => "error", "message" => "Announcement not found or already removed"];
        }

        // Proceed with soft delete
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Announcement removed successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to remove announcement"];
        }
    } catch (PDOException $e) {
        error_log("Database error in removeAnnouncement: " . $e->getMessage());
        return ["status" => "error", "message" => "Database error occurred"];
    }
}

// Ensure proper content type for JSON response
header('Content-Type: application/json');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// Get and validate the ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing announcement ID"]);
    exit();
}

// Process the removal
$response = removeAnnouncement($pdo, $id);
echo json_encode($response);
exit();