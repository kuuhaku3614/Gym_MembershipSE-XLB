<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once '../dashboard.class.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../login/login.php');
    exit();
}

// Check if we have a valid action and session ID
if (!isset($_POST['action']) || !isset($_POST['session_id'])) {
    $response = [
        'success' => false,
        'message' => 'Missing required parameters'
    ];
    echo json_encode($response);
    exit();
}

$action = $_POST['action'];
$sessionId = $_POST['session_id'];
$coachId = $_SESSION['user_id']; // Get the coach ID from the session

// Initialize database and coaching system
$database = new Database();
$coachingSystem = new CoachingSystem($database);

// Get session details first
$sessionDetails = $coachingSystem->getSessionById($sessionId, $coachId);

if (!$sessionDetails || isset($sessionDetails['error'])) {
    $response = [
        'success' => false,
        'message' => 'Error retrieving session details: ' . ($sessionDetails['error'] ?? 'Session not found')
    ];
    echo json_encode($response);
    exit();
}

// Process the action
switch ($action) {
    case 'cancel':
        // Get cancellation reason if provided
        $cancellationReason = isset($_POST['cancellation_reason']) ? $_POST['cancellation_reason'] : null;
        
        // Update status with cancellation reason
        $result = $coachingSystem->updateSessionStatus($sessionId, 'cancelled', $coachId, $cancellationReason);
        break;
    
    case 'complete':
        $result = $coachingSystem->updateSessionStatus($sessionId, 'completed', $coachId);
        break;
    
    default:
        $result = [
            'success' => false,
            'message' => 'Invalid action requested'
        ];
        break;
}

// Add a redirect URL to the response
if ($result['success']) {
    $result['redirect'] = '../dashboard.php';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
exit();