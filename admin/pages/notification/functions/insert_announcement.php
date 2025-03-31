<?php
require_once '../../../../config.php';

function insertAnnouncement($pdo, $message, $date, $time, $type) {
    // Enhanced validation
    $message = trim(strip_tags($message));
    if (empty($message)) {
        return ["status" => "error", "message" => "Announcement message is required."];
    }
    
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return ["status" => "error", "message" => "Invalid date format."];
    }
    
    // Validate time format (HH:MM)
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return ["status" => "error", "message" => "Invalid time format."];
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
        error_log("Database error in insertAnnouncement: " . $e->getMessage());
        return ["status" => "error", "message" => "Database error occurred."];
    }
}

// Verify request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

// Ensure proper content type for JSON response
header('Content-Type: application/json');

// Apply sanitization to all inputs - using filter_input for additional security
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$time = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

// Check if required fields are present
if (!$message || !$date || !$time || !$type) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit();
}

$response = insertAnnouncement($pdo, $message, $date, $time, $type);
echo json_encode($response);
exit();