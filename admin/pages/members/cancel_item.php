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

    // After successful cancellation, check if only unpaid registration remains
    if ($result && $userId) {
        $newCounts = $members->getUnpaidItemCounts($userId);
        
        // If only unpaid registration is left (no memberships or rentals)
        $unpaidMemberships = isset($newCounts['unpaid_memberships']) ? $newCounts['unpaid_memberships'] : 0;
        $unpaidRentals = isset($newCounts['unpaid_rentals']) ? $newCounts['unpaid_rentals'] : 0;
        
        // Check for unpaid registration
        $pdo = $members->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) as unpaid_registrations FROM registration_records rr JOIN transactions t ON rr.transaction_id = t.id WHERE t.user_id = :user_id AND rr.is_paid = 0");
        $stmt->execute([':user_id' => $userId]);
        $regResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $unpaidRegistrations = isset($regResult['unpaid_registrations']) ? (int)$regResult['unpaid_registrations'] : 0;
        
        if ($unpaidRegistrations > 0 && $unpaidMemberships === 0) {
            // Delete registration record and update user role
            $members->deleteRegistrationAndUpdateRole($userId);
            echo json_encode([
                'success' => true,
                'message' => $message,
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
