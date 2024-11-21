<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();

    $query = "
        SELECT 
            id, 
            service_name, 
            price, 
            total_slots, 
            available_slots,
            duration,
            (SELECT type_name FROM duration_types WHERE id = duration_type_id) AS duration_type
        FROM rental_services
        WHERE 
            status_id = (SELECT id FROM status_types WHERE status_name = 'active')
            AND available_slots > 0
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $rentals = [];
    while ($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }

    echo json_encode($rentals);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}