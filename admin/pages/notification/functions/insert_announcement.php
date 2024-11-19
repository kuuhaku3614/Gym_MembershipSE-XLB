<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

function insertAnnouncement($pdo, $message, $date, $time, $type) {
    // Validate inputs
    if (empty($message) || empty($date) || empty($time) || empty($type)) {
        return ["status" => "error", "message" => "All fields are required."];
    }

    // Validate announcement type
    $validTypes = ['administrative', 'activity'];
    if (!in_array($type, $validTypes)) {
        return ["status" => "error", "message" => "Invalid announcement type."];
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (message, applied_date, applied_time, announcement_type, is_active) 
            VALUES (:message, :date, :time, :type, 1)
        ");
        
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':time', $time, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Announcement added successfully!"];
        } else {
            return ["status" => "error", "message" => "Failed to add announcement."];
        }
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Database error: " . $e->getMessage()];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $type = $_POST['type'] ?? '';

    $response = insertAnnouncement($pdo, $message, $date, $time, $type);
    echo json_encode($response);
    exit();
}
?>