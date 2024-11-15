<?php
require_once '../config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'];
    $date = $_POST['date'];
    $status = $_POST['status'];

    try {
        // Check if a record exists in the attendance table
        $sql = "SELECT * FROM attendance WHERE user_id = :user_id AND date = :date";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($status === 'Checked In') {
            if ($existingRecord) {
                // If the record exists, update the time_in and status
                $sql = "UPDATE attendance SET time_in = NOW(), status = :status WHERE user_id = :user_id AND date = :date";
            } else {
                // If no record exists, insert a new one
                $sql = "INSERT INTO attendance (user_id, date, time_in, status) VALUES (:user_id, :date, NOW(), :status)";
            }
        } elseif ($status === 'Checked Out') {
            if ($existingRecord) {
                // Update the time_out and status for check-out
                $sql = "UPDATE attendance SET time_out = NOW(), status = :status WHERE user_id = :user_id AND date = :date";
            } else {
                // If no record exists, insert one with time_out
                $sql = "INSERT INTO attendance (user_id, date, time_out, status) VALUES (:user_id, :date, NOW(), :status)";
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        echo "success";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
