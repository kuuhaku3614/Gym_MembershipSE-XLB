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

// Required files
require_once __DIR__ . '/../../config.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        send_json_response(['hasPendingTransactions' => false], true);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Database connection
    $db = new Database();
    $conn = $db->connect();
    
    // Query to check for pending transactions
    $sql = "SELECT u.username, t.id AS transaction_id, t.status, t.created_at 
            FROM users u 
            JOIN transactions t ON u.id = t.user_id 
            WHERE t.status = 'pending' AND u.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    
    // Check if there are any pending transactions
    $pendingTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPending = !empty($pendingTransactions);
    
    echo json_encode([
        'success' => true,
        'hasPendingTransactions' => $hasPending,
        'pendingTransactions' => $hasPending ? $pendingTransactions : []
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'hasPendingTransactions' => false,
        'message' => 'Error checking pending transactions: ' . $e->getMessage()
    ]);
}
?>