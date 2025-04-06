<?php
require_once '../../../../config.php';

header('Content-Type: application/json');

// Define upload folder
$uploadDir = __DIR__ . '/../../../../cms_img/programs/';

// Function to fetch program details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $programId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        if (empty($programId) || !is_numeric($programId)) {
            throw new Exception('Invalid program ID');
        }

        $sql = "SELECT * FROM programs WHERE id = :program_id AND is_removed = 0";

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
    try {
        // Get the POST data
        $programId = intval($_POST['programId']);
        $programName = trim($_POST['programName']);
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['programStatus'] ?? 'active');

        if (empty($programId) || empty($programName)) {
            throw new Exception('Program ID and name are required');
        }

        // Fetch existing image from database
        $stmt = $pdo->prepare("SELECT image FROM programs WHERE id = :program_id");
        $stmt->execute([':program_id' => $programId]);
        $existingImage = $stmt->fetchColumn();

        // Handle new image upload
        $newImage = $existingImage; // Default to existing image
        if (!empty($_FILES['editProgramImage']['name'])) {
            $fileName = basename($_FILES['editProgramImage']['name']);
            $newImage = time() . "_" . preg_replace('/[^a-zA-Z0-9\.]/', '_', $fileName); 
            $targetFilePath = $uploadDir . $newImage;
        
            // Validate file type
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['editProgramImage']['tmp_name']);
            finfo_close($fileInfo);
            
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new Exception('Only JPG, JPEG, PNG, and GIF files are allowed');
            }
            
            // Additional validation of file extension
            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                throw new Exception('Only JPG, JPEG, PNG, and GIF files are allowed');
            }
        
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
        
            // Upload new image
            if (move_uploaded_file($_FILES['editProgramImage']['tmp_name'], $targetFilePath)) {
                // Delete old image if a new one is uploaded
                if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }
            } else {
                throw new Exception('Failed to upload new image');
            }
        }

        // Update program details
        $sql = "UPDATE programs 
                SET program_name = :program_name,
                    description = :description,
                    status = :status,
                    image = :image,
                    updated_at = NOW()
                WHERE id = :program_id";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':program_name' => $programName,
            ':description' => $description,
            ':status' => $status,
            ':image' => $newImage,
            ':program_id' => $programId
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Program updated successfully']);
        } else {
            throw new Exception('No changes were made or program not found');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// If no valid action is provided
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action or method']);
exit;