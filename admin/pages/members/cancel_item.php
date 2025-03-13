<?php
require_once("../../../config.php");
require_once(__DIR__ . "/functions/members.class.php");

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['type']) || !isset($_POST['item_id'])) {
        throw new Exception("Missing required parameters");
    }

    $type = $_POST['type'];
    $itemId = (int)$_POST['item_id'];
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    $members = new Members();
    
    // Get counts of unpaid items for this user
    if ($userId) {
        $unpaidCounts = $members->getUnpaidItemCounts($userId);
    }
    
    // Process the cancellation
    $result = false;
    if ($type === 'membership') {
        $result = $members->cancelMembership($itemId);
        $message = "Membership cancelled successfully";
    } else if ($type === 'rental') {
        $result = $members->cancelRental($itemId);
        $message = "Rental cancelled successfully";
    } else {
        throw new Exception("Invalid item type");
    }

    // After successful cancellation, check if only registration is left
    if ($result && $userId) {
        $newCounts = $members->getUnpaidItemCounts($userId);
        
        // If only registration is left (no memberships or rentals)
        if ($newCounts['registrations'] > 0 && 
            $newCounts['memberships'] === 0 && 
            $newCounts['rentals'] === 0) {
            
            // Delete registration record and update user role
            $members->deleteRegistrationAndUpdateRole($userId);
            
            echo json_encode([
                'success' => true,
                'message' => $message . '. Registration cancelled as no other items remain.',
                'onlyRegistrationLeft' => true
            ]);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    error_log("Cancel item error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
