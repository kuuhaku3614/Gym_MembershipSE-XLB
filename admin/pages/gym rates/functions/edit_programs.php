<?php
require_once '../../../../config.php';

header('Content-Type: application/json');

// Define upload folder
$uploadDir = __DIR__ . '/../../../../cms_img/programs/';

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

        echo json_encode(['success' => true, 'data' => $program]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle remove (soft delete) program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    try {
        $programId = isset($_POST['programId']) ? intval($_POST['programId']) : 0;

        if (empty($programId)) {
            throw new Exception('Program ID is required');
        }

        // Soft delete the program
        $sql = "UPDATE programs 
                SET is_removed = 1, status = 'inactive', updated_at = NOW()
                WHERE id = :program_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':program_id' => $programId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Program not found or already removed');
        }

        echo json_encode(['success' => true, 'message' => 'Program removed successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle update program (including image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Check for required fields
    $requiredFields = ['programId', 'programName', 'duration', 'programType', 'durationType'];
    $missingFields = [];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
        exit;
    }

    try {
        // Get the POST data
        $programId = intval($_POST['programId']);
        $programName = trim($_POST['programName']);
        $duration = intval($_POST['duration']);
        $programType = intval($_POST['programType']);
        $durationType = intval($_POST['durationType']);

        // Fetch existing image from database
        $stmt = $pdo->prepare("SELECT image FROM programs WHERE id = :program_id");
        $stmt->execute([':program_id' => $programId]);
        $existingImage = $stmt->fetchColumn();

        // Handle new image upload
        $newImage = $existingImage; // Default to existing image
        if (!empty($_FILES['editProgramImage']['name'])) {
            $fileName = basename($_FILES['editProgramImage']['name']);
            $newImage = time() . "_" . $fileName; 
            $targetFilePath = $uploadDir . $newImage;

            // Validate file type
            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed']);
                exit;
            }

            // Upload new image
            if (move_uploaded_file($_FILES['editProgramImage']['tmp_name'], $targetFilePath)) {
                // Delete old image if a new one is uploaded
                if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload new image']);
                exit;
            }
        }

        // Update program details
        $sql = "UPDATE programs 
                SET program_name = :program_name,
                    duration = :duration,
                    program_type_id = :program_type_id,
                    duration_type_id = :duration_type_id,
                    image = :image,
                    updated_at = NOW()
                WHERE id = :program_id";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':program_name' => $programName,
            ':duration' => $duration,
            ':program_type_id' => $programType,
            ':duration_type_id' => $durationType,
            ':image' => $newImage,
            ':program_id' => $programId
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Program updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made or program not found']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// If no valid action is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
?>