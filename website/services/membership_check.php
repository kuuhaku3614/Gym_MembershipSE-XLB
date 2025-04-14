<?php
// Prevent any output before JSON response
ob_start();

// Error handling
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to send JSON response and exit
function send_json_response($data, $status = true) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $status, 'data' => $data]);
    exit;
}

// Handle any errors
function handle_error($message) {
    send_json_response(['message' => $message], false);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    handle_error('Not logged in');
}

// Required files
require_once __DIR__ . '/../../config.php';

try {
    // Use the Database class from config.php
    $database = new Database();
    $pdo = $database->connect();
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Query to check for active memberships
    $query = "
        SELECT m.* 
        FROM memberships m
        JOIN transactions t ON m.transaction_id = t.id
        WHERE t.user_id = :user_id 
        AND m.status IN ('active', 'expiring')
        AND CURRENT_DATE BETWEEN m.start_date AND m.end_date
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $pdo->errorInfo()[2]);
    }
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($membership) {
        // User has an active membership
        send_json_response($membership);
    } else {
        // No active membership found
        send_json_response(null);
    }
    
} catch (Exception $e) {
    handle_error($e->getMessage());
}
?>