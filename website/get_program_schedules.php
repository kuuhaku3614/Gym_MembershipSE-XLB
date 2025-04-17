<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/coach_requests.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['subscription_id']) || empty($_POST['subscription_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing subscription ID']);
    exit;
}

try {
    error_log('Received subscription_id: ' . $_POST['subscription_id']);
    
    $database = new Database();
    $coachRequests = new CoachRequests($database);
    $schedules = $coachRequests->getProgramSchedules($_POST['subscription_id']);
    
    error_log('Found schedules: ' . print_r($schedules, true));

    // Format dates and times
    foreach ($schedules as &$schedule) {
        $date = new DateTime($schedule['date']);
        $start = new DateTime($schedule['start_time']);
        $end = new DateTime($schedule['end_time']);
        
        $schedule['formatted_date'] = $date->format('F j, Y');
        $schedule['formatted_time'] = $start->format('g:i A') . ' - ' . $end->format('g:i A');
        $schedule['formatted_amount'] = number_format($schedule['amount'], 2);
    }

    header('Content-Type: application/json');
    $response = ['success' => true, 'schedules' => $schedules];
    error_log('Sending JSON response: ' . print_r($response, true));
    echo json_encode($response);
} catch (Exception $e) {
    error_log('Error in get_program_schedules.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching schedules']);
}
