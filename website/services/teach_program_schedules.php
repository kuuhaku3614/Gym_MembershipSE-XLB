<?php
// teach_program_schedules.php
// Returns all schedules for the current coach, program, and type
session_start();
require_once '../../functions/sanitize.php';
require_once '../../login/functions.php';
require_once 'services.class.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}
if (!isset($_GET['program_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing program_id parameter.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$program_id = intval($_GET['program_id']);
$type = isset($_GET['type']) ? $_GET['type'] : null;

$Services = new Services_Class();

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
