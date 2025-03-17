<?php
/**
 * Single notification marking handler
 * Handles AJAX requests to mark notifications as read
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
if (isset($_POST['type']) && isset($_POST['id'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    
    // Get current user ID from session
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $debug['session_user_id'] = $userId;
    
    // Also check if user_id was directly provided in the request
    if (isset($_POST['user_id']) && $_POST['user_id'] > 0) {
        $userId = (int)$_POST['user_id'];
        $debug['post_user_id'] = $userId;
    }
    
    if ($userId) {
        // Create ExpiryNotifications object
        $expiryNotificationsObj = new ExpiryNotifications();
        
        // Mark notification as read using your existing method
        if ($expiryNotificationsObj->markAsRead($userId, $type, $id)) {
            // Also update the session storage for read notifications
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
            
            // Return success response
            echo json_encode(['success' => true]);
            exit;
        } else {
            $debug['error'] = 'markAsRead returned false';
        }
    } else {
        $debug['error'] = 'No valid user ID found';
    }
} else {
    $debug['error'] = 'Missing required POST parameters';
    $debug['post_data'] = $_POST;
}

// Return error if parameters missing or processing failed
echo json_encode(['success' => false, 'error' => 'Could not mark notification as read', 'debug' => $debug]);
?>