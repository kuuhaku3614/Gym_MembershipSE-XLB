<?php
require_once '../../../../config.php';
require_once '../../../../functions/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    switch ($action) {
        case 'toggle_status':
            toggleProgramStatus();
            break;
        default:
            saveProgram();
            break;
    }
}

function toggleProgramStatus() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$id || !$status) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        return;
    }

    try {
        // Get status_id based on action
        $newStatusId = ($status === 'activate') ? 1 : 2; // 1 for Active, 2 for Inactive

        // First, check current status
        $checkSql = "SELECT status_id FROM programs WHERE id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id]);
        $currentStatus = $checkStmt->fetchColumn();

        // Only update if status is different
        if ($currentStatus != $newStatusId) {
            $sql = "UPDATE programs SET status_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newStatusId, $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update program status']);
            }
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Program is already in desired status']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function saveProgram() {
    global $pdo;
    
    try {
        // Sanitize input data
        $programName = clean_input($_POST['programName'] ?? '');
        $programType = clean_input($_POST['programType'] ?? '');
        $duration = clean_input($_POST['duration'] ?? '');
        $durationType = clean_input($_POST['durationType'] ?? '');
        $description = clean_input($_POST['description'] ?? '');

        // Validate required fields
        if (empty($programName) || empty($programType) || empty($duration) || empty($durationType)) {
            echo "Error: All required fields must be filled.";
            return;
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert program into the database
        $sql = "INSERT INTO programs (program_name, program_type_id, duration, duration_type_id, description, status_id, created_at) 
                VALUES (:program_name, :program_type, :duration, :duration_type, :description, 1, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':program_name' => $programName,
            ':program_type' => $programType,
            ':duration' => $duration,
            ':duration_type' => $durationType,
            ':description' => $description
        ]);

        // Commit transaction
        $pdo->commit();
        echo "success";

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error adding program: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }
}
?>