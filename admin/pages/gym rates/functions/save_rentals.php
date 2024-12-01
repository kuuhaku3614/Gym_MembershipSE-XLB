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
            $sql = "UPDATE rental_services SET status = :status WHERE id = :id";
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
    $requiredFields = ['serviceName', 'duration', 'durationType', 'totalSlots', 'price'];
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

    // Get and sanitize input values
    $serviceName = trim($_POST['serviceName']);
    $price = (float)$_POST['price'];
    $totalSlots = (int)$_POST['totalSlots'];
    $duration = (int)$_POST['duration'];
    $durationType = $_POST['durationType'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validate numeric fields
    if ($price <= 0) {
        echo "Error: Price must be greater than 0";
        exit;
    }
    if ($totalSlots <= 0) {
        echo "Error: Total slots must be greater than 0";
        exit;
    }
    if ($duration <= 0) {
        echo "Error: Duration must be greater than 0";
        exit;
    }

    // Get duration_type_id from duration_types table
    $durationTypeQuery = "SELECT id FROM duration_types WHERE id = :id";
    $stmt = $pdo->prepare($durationTypeQuery);
    $stmt->execute([':id' => $durationType]);
    $durationTypeId = $stmt->fetchColumn();

    if ($durationTypeId === false) {
        echo "Error: Invalid duration type selected";
        exit;
    }

    try {
        // Set default status and available slots
        $status = 'active';
        $availableSlots = $totalSlots;

        // Insert the rental service
        $sql = "INSERT INTO rental_services (
                    service_name,
                    price,
                    total_slots,
                    available_slots,
                    duration,
                    duration_type_id,
                    description,
                    status
                ) VALUES (
                    :service_name,
                    :price,
                    :total_slots,
                    :available_slots,
                    :duration,
                    :duration_type_id,
                    :description,
                    :status
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':service_name' => $serviceName,
            ':price' => $price,
            ':total_slots' => $totalSlots,
            ':available_slots' => $availableSlots,
            ':duration' => $duration,
            ':duration_type_id' => $durationTypeId,
            ':description' => $description,
            ':status' => $status
        ]);

        echo "success";

    } catch (PDOException $e) {
        error_log("Error in save_rentals.php: " . $e->getMessage());
        echo "Error: Failed to save rental service. Please try again.";
    }
} else {
    echo "Error: Invalid request method";
}