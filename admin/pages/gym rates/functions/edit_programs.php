<?php
require_once '../../../../config.php';

header('Content-Type: application/json');

// Function to fetch program details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $programId = intval($_GET['id']);
        
        $sql = "SELECT 
                p.*,
                pt.type_name AS program_type,
                dt.type_name AS duration_type
                FROM programs p
                JOIN program_types pt ON p.program_type_id = pt.id
                JOIN duration_types dt ON p.duration_type_id = dt.id
                WHERE p.id = :program_id AND p.is_removed = 0";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':program_id' => $programId]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$program) {
            throw new Exception('Program not found');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $program
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle remove (soft delete) program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    try {
        $programId = isset($_POST['programId']) ? intval($_POST['programId']) : 0;

        if (empty($programId)) {
            throw new Exception('Program ID is required');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Soft delete the program and set status to inactive
        $sql = "UPDATE programs 
                SET is_removed = 1,
                    status = 'inactive',
                    updated_at = NOW()
                WHERE id = :program_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':program_id' => $programId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Program not found or already removed');
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Program removed successfully'
        ]);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle update program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'remove')) {
    try {
        // Get the POST data
        $programId = isset($_POST['programId']) ? intval($_POST['programId']) : 0;
        $programName = isset($_POST['programName']) ? trim($_POST['programName']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $programType = isset($_POST['programType']) ? intval($_POST['programType']) : 0;
        $durationType = isset($_POST['durationType']) ? intval($_POST['durationType']) : 0;

        // Validate inputs
        if (empty($programId) || empty($programName) || empty($duration) || empty($programType) || empty($durationType)) {
            throw new Exception('All fields are required');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Update program
        $sql = "UPDATE programs 
                SET program_name = :program_name,
                    duration = :duration,
                    program_type_id = :program_type_id,
                    duration_type_id = :duration_type_id,
                    updated_at = NOW()
                WHERE id = :program_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':program_name' => $programName,
            ':duration' => $duration,
            ':program_type_id' => $programType,
            ':duration_type_id' => $durationType,
            ':program_id' => $programId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No changes were made or program not found');
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Program updated successfully'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}