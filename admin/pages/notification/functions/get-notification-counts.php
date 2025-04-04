<?php
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/expiry-notifications.class.php'); // Include the expiry notifications class

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Initialize response array
$response = [
    'pending_transactions' => 0,
    'expiring' => 0,
    'expired' => 0,
    'total' => 0
];

// Get counts from database for pending transactions
$conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Pending Transactions Query
$pending_transactions_sql = "
    SELECT COALESCE(COUNT(*), 0) AS pending_transactions 
    FROM transactions 
    WHERE status = 'pending'
";

$result = $conn->query($pending_transactions_sql);
if ($result && $row = $result->fetch_assoc()) {
    $response['pending_transactions'] = (int)$row['pending_transactions'];
}

$conn->close();

// Use ExpiryNotifications class to get unread notifications counts
if ($current_user_id > 0) {
    try {
        $expiryNotificationsObj = new ExpiryNotifications();
        $unreadCounts = $expiryNotificationsObj->countUnreadNotifications($current_user_id);
        
        $response['expiring'] = $unreadCounts['expiring'];
        $response['expired'] = $unreadCounts['expired'];
        $response['total'] = $response['pending_transactions'] + $unreadCounts['total'];
        
    } catch (Exception $e) {
        // Log error but continue
        error_log('Error getting notification counts: ' . $e->getMessage());
    }
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;