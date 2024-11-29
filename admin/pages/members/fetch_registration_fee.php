<?php
require_once 'config.php';

$query = "SELECT membership_fee FROM registration"; // Assuming there's only one fee to fetch
$stmt = $pdo->prepare($query);
$stmt->execute();
$fee = $stmt->fetchColumn();

echo $fee;
?>
