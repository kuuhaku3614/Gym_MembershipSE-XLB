<?php
// Modified save_programs.php
require_once '../config.php';  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName = $_POST['programName'];
    $programType = $_POST['programType'];
    $coachId = $_POST['coachId'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $durationType = $_POST['durationType'];
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    
    $sql = "INSERT INTO programs (program_name, program_type, user_id, price, duration, duration_type, description, status)
            VALUES (:program_name, :program_type, :coach_id, :price, :duration, :duration_type, :description, 'active')";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':program_name', $programName);
        $stmt->bindParam(':program_type', $programType);
        $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':duration_type', $durationType);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Database error: Could not execute query.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}