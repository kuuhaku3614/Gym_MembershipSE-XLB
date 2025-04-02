<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/functions/expiry-notifications.class.php');

// Ensure this script only responds to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get POST data
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$notification_type = isset($_POST['notification_type']) ? $_POST['notification_type'] : '';

// Validate required fields
if (!$notification_id || empty($notification_type)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $expiryNotificationsObj = new ExpiryNotifications();
    $result = $expiryNotificationsObj->markNotificationAsRead($user_id, $notification_id, $notification_type);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
