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

try {
    $expiryNotificationsObj = new ExpiryNotifications();
    
    // Add a new method to ExpiryNotifications class to mark all as read
    $result = $expiryNotificationsObj->markAllNotificationsAsRead($user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

exit; // Make sure to exit