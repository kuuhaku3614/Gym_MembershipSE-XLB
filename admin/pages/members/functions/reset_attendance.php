<?php
require_once '../config.php'; 

try {
    $sql = "UPDATE attendance SET time_in = NULL, time_out = NULL, status = 'Pending'";
    $pdo->exec($sql);
    echo "Attendance table reset successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>