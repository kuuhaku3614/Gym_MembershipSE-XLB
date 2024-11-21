<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();

    $query = "
        SELECT 
            mp.id, 
            mp.plan_name, 
            mp.price, 
            mp.duration, 
            dt.type_name AS duration_type,
            dt.id AS duration_type_id
        FROM membership_plans mp
        JOIN duration_types dt ON mp.duration_type_id = dt.id
        WHERE mp.status_id = (SELECT id FROM status_types WHERE status_name = 'active')
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $plans = [];
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }

    echo json_encode($plans);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}