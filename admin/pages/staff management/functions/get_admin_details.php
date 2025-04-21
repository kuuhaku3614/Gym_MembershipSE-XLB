<?php
// Database connection
require_once '../../../../config.php';  // Adjust the path based on the actual location
$database = new Database();
$pdo = $database->connect();

header('Content-Type: application/json');

// Get current admin ID from session
session_start();
$admin_id = $_SESSION['user_id'] ?? 0;

if (!$admin_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in or session expired'
    ]);
    exit;
}

try {
    // Fetch admin details
    $query = "SELECT username FROM users WHERE id = :id AND role_id = (SELECT id FROM roles WHERE role_name = 'admin')";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo json_encode([
            'success' => true,
            'data' => $admin
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Admin not found or not authorized'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>