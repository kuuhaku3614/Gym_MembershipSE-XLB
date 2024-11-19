<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

function removeAnnouncement($pdo, $id) {
    // Validate input
    if ($id <= 0) {
        return ["status" => "error", "message" => "Invalid announcement ID."];
    }

    try {
        // Soft delete by updating is_active flag
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Announcement removed successfully!"];
        } else {
            return ["status" => "error", "message" => "Failed to remove announcement."];
        }
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Database error: " . $e->getMessage()];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)($_POST['id'] ?? 0);

    $response = removeAnnouncement($pdo, $id);
    echo json_encode($response);
    exit();
}
?>