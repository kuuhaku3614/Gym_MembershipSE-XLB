<?php
require_once 'services.class.php';

header('Content-Type: application/json');

if (!isset($_GET['coach_program_type_id']) || !isset($_GET['type'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$coachProgramTypeId = intval($_GET['coach_program_type_id']);
$type = $_GET['type'];

$Services = new Services_Class();

try {
    // First get the program and coach info
    $programInfo = $Services->getCoachProgramType($coachProgramTypeId);
    if (!$programInfo) {
        echo json_encode(['error' => 'Program information not found']);
        exit;
    }

    // Get schedules based on type
    if ($type === 'personal') {
        $schedules = $Services->getCoachPersonalSchedule($coachProgramTypeId);
    } else if ($type === 'group') {
        $schedules = $Services->getCoachGroupSchedule($coachProgramTypeId);
    } else {
        echo json_encode(['error' => 'Invalid training type']);
        exit;
    }

    // Add program and coach info to each schedule
    if (is_array($schedules) && !isset($schedules['error']) && !isset($schedules['message'])) {
        foreach ($schedules as &$schedule) {
            $schedule['program_name'] = $programInfo['program_name'];
            $schedule['coach_name'] = $programInfo['coach_name'];
        }
    }

    if (isset($schedules['error'])) {
        echo json_encode(['error' => $schedules['error']]);
        exit;
    }

    if (isset($schedules['message'])) {
        echo json_encode(['message' => $schedules['message']]);
        exit;
    }

    // Format time for display
    foreach ($schedules as &$schedule) {
        if (isset($schedule['start_time'])) {
            $schedule['start_time'] = date('h:i A', strtotime($schedule['start_time']));
        }
        if (isset($schedule['end_time'])) {
            $schedule['end_time'] = date('h:i A', strtotime($schedule['end_time']));
        }
    }

    echo json_encode($schedules);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred while fetching schedules']);
    exit;
}
?>
