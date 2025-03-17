<?php
require_once '../../../../config.php';
header('Content-Type: application/json');

// Handle toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    try {
        $programId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $newStatus = isset($_POST['new_status']) ? $_POST['new_status'] : '';
        
        // Validate input
        if (empty($programId)) {
            throw new Exception('Program ID is required');
        }
        
        if (!in_array($newStatus, ['active', 'inactive'])) {
            throw new Exception('Invalid status value');
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
            
            $file_extension = pathinfo($_FILES['programImage']['name'], PATHINFO_EXTENSION);
            $filename = 'program_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Check file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
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