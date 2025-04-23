<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include notification queries
require_once __DIR__ . '/notification_queries.php';

// In mark_notification_read.php
if (isset($_POST['type']) && isset($_POST['id'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    
    if ($type === 'cancelled_sessions' || $type === 'completed_sessions') {
        // Mark session notification as read
        // For now, we're just returning success since these are display-only
        $response = [
            'success' => true,
            'message' => 'Session notification marked as read'
        ];
        echo json_encode($response);
        exit;
    } elseif ($type === 'transaction_receipts') {
        // Mark transaction receipt notification as read
        if (!isset($_SESSION['read_notifications']['transaction_receipts'])) {
            $_SESSION['read_notifications']['transaction_receipts'] = [];
        }
        if (!in_array($id, $_SESSION['read_notifications']['transaction_receipts'])) {
            $_SESSION['read_notifications']['transaction_receipts'][] = $id;
        }
        
        echo json_encode(['success' => true]);
        exit;
    } else {
        // Mark other notification types as read
        markNotificationAsRead($type, $id);
        
        // Return success
        echo json_encode(['success' => true]);
        exit;
    }
}
?>