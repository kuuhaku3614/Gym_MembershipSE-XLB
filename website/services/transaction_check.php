<?php
// Prevent direct access
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../../config.php';

// Function to send JSON response
function send_json_response($data, $status = true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $status, 'data' => $data]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json_response(['message' => 'Not logged in'], false);
}

$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Count pending transactions for this user
    $sql = "SELECT COUNT(*) as pending_count FROM transactions WHERE user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pendingCount = (int)$result['pending_count'];
    $hasPendingTransactions = $pendingCount > 0;
    $pendingLimitReached = $pendingCount >= 3;
    
    // Get specific details about pending transactions if needed
    $pendingTransactions = [];
    if ($hasPendingTransactions) {
        $sql = "SELECT t.id, t.created_at 
                FROM transactions t 
                WHERE t.user_id = ? AND t.status = 'pending'
                ORDER BY t.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $pendingTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Return results
    send_json_response([
        'hasPendingTransactions' => $hasPendingTransactions,
        'pendingLimitReached' => $pendingLimitReached,
        'pendingCount' => $pendingCount,
        'pendingTransactions' => $pendingTransactions
    ]);

} catch (Exception $e) {
    send_json_response(['message' => 'Error checking pending transactions: ' . $e->getMessage()], false);
}
?>