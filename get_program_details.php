<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();

    $query = "
        SELECT 
            p.id, 
            p.program_name, 
            p.price, 
            p.duration, 
            dt.type_name AS duration_type,
            pt.type_name AS program_type,
            CONCAT(pd.first_name, ' ', pd.last_name) AS coach_name
        FROM programs p
        JOIN duration_types dt ON p.duration_type_id = dt.id
        JOIN program_types pt ON p.program_type_id = pt.id
        JOIN coaches c ON p.coach_id = c.id
        JOIN users u ON c.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE p.status_id = (SELECT id FROM status_types WHERE status_name = 'active')
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }

    echo json_encode($programs);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}