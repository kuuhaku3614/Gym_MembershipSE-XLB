<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $query = "SELECT membership_fee FROM registration LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $fee = $stmt->fetchColumn();

    if ($fee !== false) {
        echo json_encode([
            'success' => true, 
            'fee' => $fee
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Registration fee not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}