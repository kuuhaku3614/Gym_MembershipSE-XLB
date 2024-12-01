<?php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle toggle status action
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        if (empty($_POST['id']) || empty($_POST['status'])) {
            echo "Error: Missing required fields for status update";
            exit;
        }

        $id = $_POST['id'];
        $status = $_POST['status'];

        // Validate status value
        if (!in_array($status, ['active', 'inactive'])) {
            echo "Error: Invalid status value";
            exit;
        }

        try {
            $sql = "UPDATE membership_plans SET status = :status WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);

            if ($stmt->rowCount() > 0) {
                echo "success";
            } else {
                echo "Error: No changes were made. The record might not exist.";
            }
            exit;
        } catch (PDOException $e) {
            echo "Error: Database error - " . $e->getMessage();
            exit;
        }
    }

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