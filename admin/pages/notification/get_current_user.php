<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Return the user ID as JSON
header('Content-Type: application/json');
echo json_encode([
    'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0
]);
?>