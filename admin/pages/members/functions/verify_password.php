<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

if (!isset($_POST['userId']) || !isset($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = $_POST['userId'];
$password = $_POST['password'];

try {
    // Modified to use personal_details id as requested
    $sql = "SELECT u.password 
            FROM users u 
            JOIN personal_details pd ON u.id = pd.user_id 
            WHERE pd.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}