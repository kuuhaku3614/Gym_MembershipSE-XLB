<?php
require_once '../../../../config.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $coachId = filter_input(INPUT_POST, 'coach_id', FILTER_SANITIZE_NUMBER_INT);
    $programId = filter_input(INPUT_POST, 'program_id', FILTER_SANITIZE_NUMBER_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validate required fields
    if (empty($coachId) || empty($programId) || empty($price)) {
        echo json_encode(['status' => 'error', 'message' => 'Coach, program, and price are required']);
        exit;
    }

    // Validate price
    if (!is_numeric($price) || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid price']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check if coach exists
    $checkCoachStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role_id = 4");
    $checkCoachStmt->execute([$coachId]);
    if (!$checkCoachStmt->fetch()) {
        throw new Exception("Invalid coach selected");
    }

    // Check if program exists
    $checkProgramStmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
    $checkProgramStmt->execute([$programId]);
    if (!$checkProgramStmt->fetch()) {
        throw new Exception("Invalid program selected");
    }

    // Check if coach is already assigned to this program
    $checkAssignmentStmt = $pdo->prepare("SELECT id FROM coach_program_types WHERE coach_id = ? AND program_id = ? AND status != 'inactive'");
    $checkAssignmentStmt->execute([$coachId, $programId]);
    if ($checkAssignmentStmt->fetch()) {
        throw new Exception("Coach is already assigned to this program");
    }

    // Insert into coach_program_types table
    $stmt = $pdo->prepare("INSERT INTO coach_program_types (coach_id, program_id, price, description, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$coachId, $programId, $price, $description]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Coach assigned to program successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}