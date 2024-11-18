<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

function removeAnnouncement($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Announcement removed successfully!"];
        } else {
            return ["status" => "error", "message" => "Database error occurred."];
        }
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Database error: " . $e->getMessage()];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid announcement ID."]);
        exit();
    }

    $response = removeAnnouncement($pdo, $id);
    echo json_encode($response);
    exit();
}
?>