<?php
// teach_program_backend.php
session_start();
require_once '../../functions/sanitize.php';
require_once '../../login/functions.php';
require_once 'services.class.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['program_id']) || !isset($input['schedules']) || !is_array($input['schedules'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$program_id = intval($input['program_id']);
$description = isset($input['description']) ? clean_input($input['description']) : '';
$schedules = $input['schedules'];

$Services = new Services_Class();
$errors = [];
$saved = 0;

foreach ($schedules as $schedule) {
    $type = isset($schedule['type']) ? $schedule['type'] : '';
    $day = isset($schedule['day']) ? $schedule['day'] : '';
    $start_time = isset($schedule['start_time']) ? $schedule['start_time'] : '';
    $end_time = isset($schedule['end_time']) ? $schedule['end_time'] : '';
    $price = isset($schedule['price']) ? floatval($schedule['price']) : 0;

    // Find or create coach_program_type for this type
    $coach_program_type_id = $Services->getCoachProgramTypeId($user_id, $program_id, $type);
    if (!$coach_program_type_id) {
        $coach_program_type_id = $Services->createCoachProgramType($user_id, $program_id, $type, $description);
    }
    if (!$coach_program_type_id) {
        $errors[] = $schedule;
        continue;
    }

    if ($type === 'group') {
        $capacity = isset($schedule['capacity']) ? intval($schedule['capacity']) : 0;
        $result = $Services->addCoachGroupSchedule($coach_program_type_id, $day, $start_time, $end_time, $capacity, $price, $description);
    } else if ($type === 'personal') {
        $duration_rate = isset($schedule['duration_rate']) ? intval($schedule['duration_rate']) : 0;
        $result = $Services->addCoachPersonalSchedule($coach_program_type_id, $day, $start_time, $end_time, $duration_rate, $price, $description);
    } else {
        $result = false;
    }
    if ($result) {
        $saved++;
    } else {
        $errors[] = $schedule;
    }
}

if ($saved > 0) {
    echo json_encode(['success' => true, 'saved' => $saved, 'errors' => $errors]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save any schedules.', 'errors' => $errors]);
}
