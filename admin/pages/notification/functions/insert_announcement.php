<?php
require_once '../../../../config.php';

function insertAnnouncement($pdo, $message, $date) {
    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (message, applied_date) VALUES (:message, :date)");
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':date', $date);
        
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Announcement added successfully!"];
        } else {
            return ["status" => "error", "message" => "Database error occurred."];
        }
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Database error: " . $e->getMessage()];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message'] ?? '');
    $date = $_POST['date'] ?? '';

    if (!$message || !$date) {
        echo json_encode(["status" => "error", "message" => "Message and date are required."]);
        exit();
    }

    $response = insertAnnouncement($pdo, $message, $date);
    echo json_encode($response);
    exit();
}
?>