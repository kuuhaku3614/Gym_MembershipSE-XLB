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

$Services = new Services_Class();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch ALL schedules for this coach across ALL programs (for overlap checking)
    if (isset($_GET['all_coach_schedules']) && $_GET['all_coach_schedules'] == '1') {
        $conn = $Services->getDbConnection();
        $sql = "SELECT id, type FROM coach_program_types WHERE coach_id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $schedules = [ 'group' => [], 'personal' => [] ];
        foreach ($rows as $row) {
            $cpt_id = $row['id'];
            $cpt_type = $row['type'];
            // Get program_id and program_name for this coach_program_type
            $sqlProg = "SELECT p.id as program_id, p.program_name FROM coach_program_types cpt JOIN programs p ON cpt.program_id = p.id WHERE cpt.id = ? LIMIT 1";
            $stmtProg = $conn->prepare($sqlProg);
            $stmtProg->execute([$cpt_id]);
            $progRow = $stmtProg->fetch(PDO::FETCH_ASSOC);
            $program_id = $progRow ? $progRow['program_id'] : null;
            $program_name = $progRow ? $progRow['program_name'] : null;

            $scheds = $Services->getSchedulesByCoachProgramType($cpt_id, $cpt_type);
            // Attach program_id and program_name to each schedule
            foreach ($scheds as &$sched) {
                $sched['program_id'] = $program_id;
                $sched['program_name'] = $program_name;
                $sched['type'] = $cpt_type;
            }
            unset($sched); // break reference
            if ($cpt_type === 'group') {
                $schedules['group'] = array_merge($schedules['group'], $scheds);
            } else if ($cpt_type === 'personal') {
                $schedules['personal'] = array_merge($schedules['personal'], $scheds);
            }
        }
        echo json_encode(['success' => true, 'schedules' => $schedules]);
        exit;
    }
    // --- FETCH SCHEDULES LOGIC (was teach_program_schedules.php) ---
    if (!isset($_GET['program_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing program_id parameter.']);
        exit;
    }
    $program_id = intval($_GET['program_id']);
    $type = isset($_GET['type']) ? $_GET['type'] : null;

    if ($type) {
        $coach_program_type_id = $Services->getCoachProgramTypeId($user_id, $program_id, $type);
        if (!$coach_program_type_id) {
            echo json_encode(['success' => true, 'schedules' => []]);
            exit;
        }
        $schedules = $Services->getSchedulesByCoachProgramType($coach_program_type_id, $type);
        echo json_encode(['success' => true, 'schedules' => $schedules]);
        exit;
    }
    // No type provided: fetch all coach_program_type_id for this coach/program
    $conn = $Services->getDbConnection();
    $sql = "SELECT id, type FROM coach_program_types WHERE coach_id = ? AND program_id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $program_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $schedules = [ 'group' => [], 'personal' => [] ];
    foreach ($rows as $row) {
        $cpt_id = $row['id'];
        $cpt_type = $row['type'];
        $scheds = $Services->getSchedulesByCoachProgramType($cpt_id, $cpt_type);
        if ($cpt_type === 'group') {
            $schedules['group'] = array_merge($schedules['group'], $scheds);
        } else if ($cpt_type === 'personal') {
            $schedules['personal'] = array_merge($schedules['personal'], $scheds);
        }
    }
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- SAVE SCHEDULES LOGIC (was teach_program_backend.php) ---
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['program_id']) || !isset($input['schedules']) || !is_array($input['schedules'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }
    $program_id = intval($input['program_id']);
    $group_description = isset($input['group_description']) ? clean_input($input['group_description']) : '';
    $personal_description = isset($input['personal_description']) ? clean_input($input['personal_description']) : '';
    $schedules = $input['schedules'];
    $errors = [];
    $saved = 0;

    // Only update/create coach_program_types if there is at least one schedule of this type
    foreach (['group', 'personal'] as $type) {
        $desc = ($type === 'group') ? $group_description : $personal_description;
        // Check if there is at least one schedule of this type
        $hasSchedule = false;
        foreach ($schedules as $sch) {
            if (isset($sch['type']) && $sch['type'] === $type) {
                $hasSchedule = true;
                break;
            }
        }
        if ($desc !== '' && $hasSchedule) {
            $cpt_id = $Services->getCoachProgramTypeId($user_id, $program_id, $type);
            if ($cpt_id) {
                $conn = $Services->getDbConnection();
                $updateSql = "UPDATE coach_program_types SET description = ? WHERE id = ? AND type = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$desc, $cpt_id, $type]);
            } else {
                $Services->createCoachProgramType($user_id, $program_id, $type, $desc);
            }
        }
    }

    // Now add schedules as usual
    foreach ($schedules as $schedule) {
        $type = isset($schedule['type']) ? $schedule['type'] : '';
        $day = isset($schedule['day']) ? $schedule['day'] : '';
        $start_time = isset($schedule['start_time']) ? $schedule['start_time'] : '';
        $end_time = isset($schedule['end_time']) ? $schedule['end_time'] : '';
        $price = isset($schedule['price']) ? floatval($schedule['price']) : 0;

        // Validate that start_time < end_time
        if ($start_time !== '' && $end_time !== '') {
            $start = strtotime($start_time);
            $end = strtotime($end_time);
            if ($start === false || $end === false || $start >= $end) {
                $errors[] = array_merge($schedule, [
                    'error' => 'Start time must be less than end time.'
                ]);
                continue;
            }
        }

        // Find or create coach_program_type for this type
        $coach_program_type_id = $Services->getCoachProgramTypeId($user_id, $program_id, $type);
        if (!$coach_program_type_id) {
            // Fallback: create with blank description if not already created
            $coach_program_type_id = $Services->createCoachProgramType($user_id, $program_id, $type, '');
        }
        if (!$coach_program_type_id) {
            $errors[] = $schedule;
            continue;
        }
        if ($type === 'group') {
            $capacity = isset($schedule['capacity']) ? intval($schedule['capacity']) : 0;
            $result = $Services->addCoachGroupSchedule($coach_program_type_id, $day, $start_time, $end_time, $capacity, $price);
        } else if ($type === 'personal') {
            $duration_rate = isset($schedule['duration_rate']) ? intval($schedule['duration_rate']) : 0;
            $result = $Services->addCoachPersonalSchedule($coach_program_type_id, $day, $start_time, $end_time, $duration_rate, $price);
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
    exit;
}

// For any other method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
exit;
