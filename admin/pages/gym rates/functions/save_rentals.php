<?php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $serviceName = $_POST['serviceName'] ?? null;
    $price = $_POST['price'] ?? null;
    $totalSlots = $_POST['slots'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $durationType = $_POST['durationType'] ?? null;
    $description = $_POST['description'] ?? null;
    $status = $_POST['status'] ?? 1; // Default to "active" (status_id = 1)

    $availableSlots = $totalSlots;

    if (empty($serviceName) || empty($price) || empty($totalSlots) || empty($duration) || empty($durationType)) {
        echo "Error: All required fields must be filled.";
        exit;
    }

    $sql = "INSERT INTO rental_services 
            (service_name, price, total_slots, available_slots, duration, duration_type_id, description, status_id)
            VALUES 
            (:service_name, :price, :total_slots, :available_slots, :duration, :duration_type, :description, :status_id)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':service_name', $serviceName, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':total_slots', $totalSlots, PDO::PARAM_INT);
        $stmt->bindParam(':available_slots', $availableSlots, PDO::PARAM_INT);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':duration_type', $durationType, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':status_id', $status, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Database error: Could not execute query.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>