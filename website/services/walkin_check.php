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
    
    // Get the date to check (either provided date or current date)
    $date_to_check = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    
    // Query to check for walk-in records for the specified date
    $query = "
        SELECT wir.* 
        FROM walk_in_records wir
        JOIN transactions t ON wir.transaction_id = t.id
        WHERE t.user_id = :user_id 
        AND wir.date = :date
        AND wir.status IN ('walked-in', 'pending')
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $pdo->errorInfo()[2]);
    }
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':date', $date_to_check, PDO::PARAM_STR);
    $stmt->execute();
    
    $walkin_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($walkin_record) {
        // User already has a walk-in record for this date
        send_json_response([
            'hasWalkinForDate' => true,
            'walkInRecord' => $walkin_record
        ]);
    } else {
        // No walk-in record found for this date
        send_json_response([
            'hasWalkinForDate' => false
        ]);
    }
    
} catch (Exception $e) {
    handle_error($e->getMessage());
}
?>