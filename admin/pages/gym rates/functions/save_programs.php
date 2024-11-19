<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $programName = $_POST['programName'] ?? null;
    $programType = $_POST['programType'] ?? null;
    $coachId = $_POST['coachId'] ?? null;
    $price = $_POST['price'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $durationType = $_POST['durationType'] ?? null;
    $description = $_POST['description'] ?? null;

    // Validate required fields
    if (empty($programName) || empty($programType) || empty($coachId) || empty($price) || empty($duration) || empty($durationType)) {
        echo "Error: All required fields must be filled.";
        exit;
    }

    // Insert program into the database
    $sql = "INSERT INTO programs 
            (program_name, program_type_id, coach_id, price, duration, duration_type_id, description, status_id)
            VALUES 
            (:program_name, :program_type, :coach_id, :price, :duration, :duration_type, :description, 
             (SELECT id FROM status_types WHERE status_name = 'active'))";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':program_name', $programName, PDO::PARAM_STR);
        $stmt->bindParam(':program_type', $programType, PDO::PARAM_INT);
        $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':duration_type', $durationType, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Database error: Failed to save the program.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
