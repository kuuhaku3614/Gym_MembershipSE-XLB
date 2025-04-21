<?php
require_once '../../../../config.php';
require_once 'activity_logger.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Handle toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    try {
        $programId = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Validate input
        if (empty($programId) || !is_numeric($programId)) {
            throw new Exception('Program ID is required and must be numeric');
        }

        if (!in_array($newStatus, ['active', 'inactive'])) {
            throw new Exception('Invalid status value');
        }
        
        // Get program name for logging
        $stmtName = $pdo->prepare("SELECT program_name FROM programs WHERE id = :id");
        $stmtName->execute([':id' => $programId]);
        $programName = $stmtName->fetchColumn();
        
        if (!$programName) {
            throw new Exception('Program not found');
        }
        
        $sql = "UPDATE programs SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $programId
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Program not found or status already set');
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Program status updated successfully']);
        
        // Log activity with program name
        logStaffActivity('Update Program Status', 'Changed status of program: ' . $programName . ' (ID: ' . $programId . ') to ' . $newStatus);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle adding new program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        // Get the POST data
        $programName = trim($_POST['programName'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['programStatus'] ?? 'active');
        
        // Validate required fields
        if (empty($programName)) {
            throw new Exception('Program name is required');
        }
        
        // Handle image upload
        $imagePath = '';
        if (!empty($_FILES['programImage']['name'])) {
            $upload_dir = __DIR__ . '/../../../../cms_img/programs/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Sanitize filename
            $original_filename = basename($_FILES['programImage']['name']);
            $filename = 'program_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $original_filename);
            $target_file = $upload_dir . $filename;
            
            // Check file MIME type
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['programImage']['tmp_name']);
            finfo_close($fileInfo);
            
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
            }
            
            // Also check file extension
            $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['programImage']['tmp_name'], $target_file)) {
                $imagePath = $filename;
            } else {
                throw new Exception('Failed to upload image');
            }
        }
        
        // Insert new program into database
        $sql = "INSERT INTO programs (program_name, description, status, image, created_at, updated_at) 
                VALUES (:program_name, :description, :status, :image, NOW(), NOW())";
                
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':program_name' => $programName,
            ':description' => $description,
            ':status' => $status,
            ':image' => $imagePath
        ]);
        
        if ($result) {
            $programId = $pdo->lastInsertId();
            echo json_encode([
                'status' => 'success',
                'message' => 'Program added successfully',
                'program_id' => $programId
            ]);
            
            // Log activity with program name
            logStaffActivity('Add Program', 'Added new program: ' . $programName . ' (ID: ' . $programId . ')');
        } else {
            throw new Exception('Failed to add program');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// If no valid action is provided
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing parameters']);
exit;