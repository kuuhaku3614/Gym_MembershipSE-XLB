<?php
// delete_pending_rental.php
session_start();
require_once __DIR__ . '/../../../config.php'; // Adjust path as needed
require_once 'rental_validation.php'; // Include the rental validation class

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if transaction ID is provided
if (!isset($_POST['transactionId']) || !is_numeric($_POST['transactionId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request: Missing or invalid transaction ID.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = (int)$_POST['transactionId'];

try {
    // Assuming $pdo is available from config.php
    $validator = new RentalValidation($pdo); // Use the RentalValidation class
    $deleted = $validator->deletePendingRentalTransaction($user_id, $transaction_id); // Call the function

    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Pending rental deleted successfully.']);
    } else {
        // Don't give too specific errors to the client for security
        http_response_code(500); // Or 403 if permission was likely the issue but we can't be sure
        echo json_encode(['success' => false, 'message' => 'Failed to delete pending rental. It might have been processed already or does not exist.']);
    }
} catch (Exception $e) {
    error_log("Server error deleting pending rental: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
?>