<?php
/**
 * Mark all notifications as read handler
 * Handles AJAX requests to mark all notifications as read
 */

// Include required files
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/functions/notifications.class.php');
require_once(__DIR__ . '/functions/expiry-notifications.class.php');

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug information
$debug = array();

// Check if we have necessary parameters
if (isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $debug['received_user_id'] = $userId;
    
    // If userId is 0 or not set properly, try to get it from session
    if ($userId <= 0 && isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $debug['using_session_user_id'] = $userId;
    }
    
    if ($userId > 0) {
        // Create objects
        $notificationsObj = new Notifications();
        $expiryNotificationsObj = new ExpiryNotifications();
        
        // Get all notifications for this user
        $expiryNotifications = $expiryNotificationsObj->getExpiryNotifications();
        $debug['notification_count'] = count($expiryNotifications);
        
        // Prepare notification data in the format expected by markAllAsRead
        $notificationData = [];
        
        foreach ($expiryNotifications as $notification) {
            $notificationData[] = [
                'id' => $notification['id'],
                'type' => $notification['type']
            ];
        }
        
        $debug['notification_data'] = $notificationData;
        
        // Mark all notifications as read using your existing method
        if ($expiryNotificationsObj->markAllAsRead($userId, $notificationData)) {
            // Also update the session storage
            if (!isset($_SESSION['read_notifications'])) {
                $_SESSION['read_notifications'] = [
                    'transactions' => [],
                    'memberships' => [],
                    'announcements' => [],
                    'expiring_membership' => [],
                    'expired_membership' => [],
                    'expiring_rental' => [],
                    'expired_rental' => []
                ];
            }
            
            // Update session with all notification IDs
            foreach ($notificationData as $notification) {
                $type = $notification['type'];
                $id = $notification['id'];
                
                // Map the expiry notification types to your session structure
                $sessionType = '';
                if (strpos($type, 'membership') !== false) {
                    $sessionType = 'memberships';
                } else if (strpos($type, 'rental') !== false) {
                    $sessionType = 'transactions';
                } else {
                    $sessionType = $type;
                }
                
                // Add to session if not already there
                if (!in_array($id, $_SESSION['read_notifications'][$sessionType])) {
                    $_SESSION['read_notifications'][$sessionType][] = $id;
                }
            }
            
            // Return success response
            echo json_encode(['success' => true]);
            exit;
        } else {    
            $debug['error'] = 'Marking notifications as read failed';
        }
    } else {
        $debug['error'] = 'Invalid user ID: ' . $userId;
    }
} else {
    $debug['error'] = 'No user_id provided in request';
}

// Return error if parameters missing or processing failed
echo json_encode(['success' => false, 'error' => 'Could not mark all notifications as read', 'debug' => $debug]);
?>