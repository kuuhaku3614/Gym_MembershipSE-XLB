<?php
require_once '../../../../config.php';
session_start(); // Ensure session is started

// Include the activity logger
require_once 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle toggle status action
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        if (empty($_POST['id']) || empty($_POST['status'])) {
            echo "Error: Missing required fields for status update";
            exit;
        }

        $id = $_POST['id'];
        $status = $_POST['status'];

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
                // Log the activity
                $activityType = $status === 'active' ? 'Gym Rate Activated' : 'Gym Rate Deactivated';
                
                // Get the gym rate name for better description
                $nameQuery = "SELECT plan_name FROM membership_plans WHERE id = :id";
                $nameStmt = $pdo->prepare($nameQuery);
                $nameStmt->execute([':id' => $id]);
                $planName = $nameStmt->fetchColumn();
                
                $description = "Changed status of gym rate '$planName' to $status";
                logStaffActivity($activityType, $description);
                
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

    // Define the correct uploads folder relative to project root
    $targetDir = __DIR__ . '/../../../../cms_img/gym_rates/';

    // Handle image upload
    $imageName = NULL;
    if (isset($_FILES['promoImage']) && $_FILES['promoImage']['error'] == 0) {
        $fileName = basename($_FILES['promoImage']['name']);
        $imageName = uniqid() . "_" . $fileName; // Prevent duplicate names
        $targetFilePath = $targetDir . $imageName; // Full path

        if (move_uploaded_file($_FILES['promoImage']['tmp_name'], $targetFilePath)) {
            // Store only the filename in the database (not full path)
        } else {
            echo "Error uploading image.";
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

    // Get duration_type_id from duration_types table
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

    // Insert into database with image
    $sql = "INSERT INTO membership_plans (
                plan_name, 
                plan_type, 
                duration, 
                duration_type_id,
                start_date, 
                end_date, 
                price, 
                description,
                status,
                image
            ) VALUES (
                :plan_name,
                :plan_type,
                :duration,
                :duration_type_id,
                :start_date,
                :end_date,
                :price,
                :description,
                :status,
                :image
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
        $stmt->bindParam(':image', $imageName);

        if ($stmt->execute()) {
            // Log the activity
            $description = "Added new gym rate: $promoName ($promoType) - ₱" . number_format($price, 2);
            logStaffActivity('Gym Rate Added', $description);
            
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
?>