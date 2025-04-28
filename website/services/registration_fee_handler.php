<?php
// services/registration_fee_handler.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection from config.php
require_once __DIR__ . '/../../config.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get database connection
    $conn = $database->connect();
    
    // Query to get the registration fee details
    $query = "SELECT r.*, dt.type_name as duration_type_name 
              FROM registration r 
              LEFT JOIN duration_types dt ON r.duration_type_id = dt.id 
              WHERE r.id = 1"; // Assuming there's only one record
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $registrationDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'registration' => $registrationDetails
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'data' => [
                'message' => 'Registration fee details not found'
            ]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'data' => [
            'message' => 'Database error: ' . $e->getMessage()
        ]
    ]);
}
?>