<?php
require_once '../../../config.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            mp.id,
            mp.plan_name,
            mp.price,
            mp.duration,
            mp.duration_type_id,
            dt.type_name as duration_type
        FROM membership_plans mp
        JOIN duration_types dt ON mp.duration_type_id = dt.id
        WHERE mp.status_id = 1
        ORDER BY mp.plan_name
    ");
    
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($plans);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 