<?php
require_once 'config.php';  // Ensure config file with PDO setup is included

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $promoName = $_POST['promoName'];
    $promoType = $_POST['promoType'];
    $duration = $_POST['duration'];
    $durationType = $_POST['durationType'];
    $activationDate = $_POST['activationDate'];
    $deactivationDate = $_POST['deactivationDate'];
    $price = $_POST['price'];
    $membershipFee = isset($_POST['membershipFee']) ? $_POST['membershipFee'] : null;

    $sql = "INSERT INTO membership_plans (plan_name, plan_type, duration, duration_type, start_date, end_date, price, description)
            VALUES (:plan_name, :plan_type, :duration, :duration_type, :start_date, :end_date, :price, :description)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':plan_name', $promoName);
        $stmt->bindParam(':plan_type', $promoType);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':duration_type', $durationType);
        $stmt->bindParam(':start_date', $activationDate);
        $stmt->bindParam(':end_date', $deactivationDate);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $membershipFee);
        
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
