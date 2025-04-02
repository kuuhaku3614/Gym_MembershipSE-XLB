<?php
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/expiry-notifications.class.php');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

try {
    $expiryNotificationsObj = new ExpiryNotifications();
    
    // Get counts for notifications
    $counts = [
        'pending_transactions' => 0,
        'expiring' => 0,
        'expired' => 0,
        'total' => 0
    ];
    
    // Get counts from database for pending transactions
    $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
    if ($conn->connect_error) {
        echo json_encode($counts);
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
        $counts['pending_transactions'] = (int)$row['pending_transactions'];
    }
    
    $conn->close();
    
    // Use ExpiryNotifications class to get unread notifications counts
    if ($user_id > 0) {
        $unreadCounts = $expiryNotificationsObj->countUnreadNotifications($user_id);
        
        $counts['expiring'] = $unreadCounts['expiring'];
        $counts['expired'] = $unreadCounts['expired'];
        $counts['total'] = $counts['pending_transactions'] + $unreadCounts['total'];
    }
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($counts);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}