<?php
require_once '../../../../config.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Handle toggle status action
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $newStatus = $_POST['new_status'] ?? '';

        if ($id <= 0) {
            throw new Exception('Invalid program ID');
        }

        if (!in_array($newStatus, ['active', 'inactive'])) {
            throw new Exception('Invalid status value');
        }

        $sql = "UPDATE programs SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':status' => $newStatus,
            ':id' => $id
        ]);

        if ($result) {
            $response['status'] = 'success';
            $response['message'] = 'Program status updated successfully';
        } else {
            throw new Exception('Failed to update program status');
        }
        
        echo json_encode($response);
        exit;
    }

    // Retrieve and sanitize input values
    $programName = trim($_POST['programName'] ?? '');
    $programType = filter_var($_POST['programType'] ?? 0, FILTER_VALIDATE_INT);
    $duration = filter_var($_POST['duration'] ?? 0, FILTER_VALIDATE_INT);
    $durationType = filter_var($_POST['durationType'] ?? 0, FILTER_VALIDATE_INT);
    $description = trim($_POST['description'] ?? '');
    $status = 'active';

    // Debug log
    error_log("Received data - Name: $programName, Type: $programType, Duration: $duration, DurationType: $durationType");

    // Validate required fields
    $errors = [];
    if (empty($programName)) {
        $errors['programName'] = 'Program name is required';
    }
    if ($programType <= 0) {
        $errors['programType'] = 'Valid program type must be selected';
    }
    if ($duration <= 0) {
        $errors['duration'] = 'Duration must be greater than 0';
    }
    if ($durationType <= 0) {
        $errors['durationType'] = 'Valid duration type must be selected';
    }

    if (!empty($errors)) {
        $response['message'] = 'Please correct the errors below';
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Validate program type exists
    $typeQuery = "SELECT id FROM program_types WHERE id = :id";
    $stmt = $pdo->prepare($typeQuery);
    $stmt->execute([':id' => $programType]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Invalid program type selected';
        echo json_encode($response);
        exit;
    }

    // Validate duration type exists
    $durationTypeQuery = "SELECT id FROM duration_types WHERE id = :id";
    $stmt = $pdo->prepare($durationTypeQuery);
    $stmt->execute([':id' => $durationType]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Invalid duration type selected';
        echo json_encode($response);
        exit;
    }

    // Insert the new program
    $sql = "INSERT INTO programs 
            (program_name, program_type_id, duration, duration_type_id, description, status)
            VALUES 
            (:program_name, :program_type_id, :duration, :duration_type_id, :description, :status)";
    
    $params = [
        ':program_name' => $programName,
        ':program_type_id' => $programType,
        ':duration' => $duration,
        ':duration_type_id' => $durationType,
        ':description' => $description,
        ':status' => $status
    ];
    
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        $error = $stmt->errorInfo();
        throw new PDOException("Database error: " . $error[2]);
    }

    $response['status'] = 'success';
    $response['message'] = 'Program added successfully';

} catch (PDOException $e) {
    error_log("PDO Exception in save_programs.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred: ' . $e->getMessage();
    $response['debug'] = ['sql_error' => $e->getMessage()];
} catch (Exception $e) {
    error_log("General Exception in save_programs.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);