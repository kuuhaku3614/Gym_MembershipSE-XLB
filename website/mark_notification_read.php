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
    
    // Mark notification as read
    markNotificationAsRead($type, $id);
    
    // Return success
    echo json_encode(['success' => true]);
    exit;
}

// Return error if parameters missing
echo json_encode(['success' => false, 'error' => 'Missing parameters']);
?>