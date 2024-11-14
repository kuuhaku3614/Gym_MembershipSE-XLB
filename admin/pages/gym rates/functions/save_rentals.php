<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values from the POST request
    $serviceName = $_POST['serviceName'];
    $price = $_POST['price'];
    $totalSlots = $_POST['slots'];  // Using 'slots' from form input for totalSlots
    $duration = $_POST['duration'];
    $durationType = $_POST['durationType'];
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    // Set available slots to equal total slots on creation
    $availableSlots = $totalSlots;

    // Prepare SQL query for inserting data into rental_services table
    $sql = "INSERT INTO rental_services (service_name, price, total_slots, available_slots, duration, duration_type, description, status)
            VALUES (:service_name, :price, :total_slots, :available_slots, :duration, :duration_type, :description, :status)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':service_name', $serviceName);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':total_slots', $totalSlots, PDO::PARAM_INT);
        $stmt->bindParam(':available_slots', $availableSlots, PDO::PARAM_INT);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':duration_type', $durationType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);

        // Execute query and provide feedback
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
