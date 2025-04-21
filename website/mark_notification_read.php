<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include notification queries
require_once __DIR__ . '/notification_queries.php';

// Check if we have necessary parameters
if (isset($_POST['type']) && isset($_POST['id'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    
    if ($type === 'cancelled_sessions' || $type === 'completed_sessions') {
        // Mark session notification as read
        // You might need to create a table to track read status for these notifications
        // For now, we're just returning success since these are display-only
        $response = [
            'success' => true,
            'message' => 'Session notification marked as read'
        ];
        echo json_encode($response);
        exit;
    } else {
        // Mark other notification types as read
        markNotificationAsRead($type, $id);
        
        // Return success
        echo json_encode(['success' => true]);
        exit;
    }
}

// Return error if parameters missing
echo json_encode(['success' => false, 'error' => 'Missing parameters']);
?>