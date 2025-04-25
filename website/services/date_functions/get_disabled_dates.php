<?php
// get_disabled_dates.php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once 'membership_validation.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Create PDO connection
    $validator = new MembershipValidation($pdo);
    
    // Get all disabled dates for the current user including rental conflicts
    $disabledDates = $validator->getDisabledDates($user_id);
    
    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($disabledDates);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>