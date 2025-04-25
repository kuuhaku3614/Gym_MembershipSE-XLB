<?php
// get_rental_disabled_dates.php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once 'rental_validation.php'; // Include the RentalValidation class

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Create PDO connection (assuming $pdo is defined in config.php)
    $rentalValidator = new RentalValidation($pdo);

    // Get disabled dates specifically for rentals
    $disabledDates = $rentalValidator->getRentalDisabledDates($user_id);

    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($disabledDates);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
