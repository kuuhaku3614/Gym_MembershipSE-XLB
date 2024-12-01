<?php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $requiredFields = ['promoName', 'promoType', 'duration', 'durationType', 'activationDate', 'deactivationDate', 'price'];
    $missingFields = [];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        echo "Error: Missing required fields: " . implode(', ', $missingFields);
        exit;
    }

    $promoName = $_POST['promoName'];
    $promoType = $_POST['promoType'];
    $duration = $_POST['duration'];
    $durationType = $_POST['durationType'];
    $activationDate = $_POST['activationDate'];
    $deactivationDate = $_POST['deactivationDate'];
    $price = $_POST['price']; 
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    
    // Get duration_type_id from duration_types table with error handling
    $durationTypeQuery = "SELECT id FROM duration_types WHERE type_name = :type_name";
    $stmt = $pdo->prepare($durationTypeQuery);
    $stmt->execute([':type_name' => $durationType]);
    $durationTypeId = $stmt->fetchColumn();

    if ($durationTypeId === false) {
        echo "Error: Invalid duration type. Duration type not found in database.";
        exit;
    }
    
    // Set default status as active
    $status = 'active';

    $sql = "INSERT INTO membership_plans (
                plan_name, 
                plan_type, 
                duration, 
                duration_type_id,
                start_date, 
                end_date, 
                price, 
                description,
                status
            ) VALUES (
                :plan_name,
                :plan_type,
                :duration,
                :duration_type_id,
                :start_date,
                :end_date,
                :price,
                :description,
                :status
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':plan_name', $promoName);
        $stmt->bindParam(':plan_type', $promoType);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':duration_type_id', $durationTypeId);
        $stmt->bindParam(':start_date', $activationDate);
        $stmt->bindParam(':end_date', $deactivationDate);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Database error: Could not execute query.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Error: Invalid request method.";
}