<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type header
header('Content-Type: application/json');

// Include database configuration
require_once 'config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;   
}

// Verify that we're receiving POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Debug logging
$logFile = __DIR__ . '/debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

try {
    // Check database connection
    if (!isset($conn)) {
        $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
    }

    $response = ['status' => 'error', 'message' => 'Unknown error occurred'];
    
    if (isset($_POST['action'])) {
        $userId = $_SESSION['user_id'];
        
        switch ($_POST['action']) {
            case 'check_username':
                $username = trim($_POST['username']);
                
                // Check if username exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("si", $username, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                echo json_encode(['exists' => $result->num_rows > 0]);
                exit;
                
            case 'update_username':
                $username = trim($_POST['username']);
                
                // Check if username exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("si", $username, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Username already exists'];
                } else {
                    // Update username
                    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("si", $username, $userId);
                    
                    if ($stmt->execute()) {
                        $_SESSION['username'] = $username;
                        $response = ['status' => 'success', 'message' => 'Username updated successfully'];
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                }
                break;
                
            case 'update_photo':
                if (!isset($_FILES['photo'])) {
                    throw new Exception("No photo file uploaded");
                }
                
                $file = $_FILES['photo'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $response = ['status' => 'error', 'message' => 'Invalid file type'];
                    break;
                }
                
                $uploadDir = '../../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = 'profile_' . $userId . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Deactivate old photo
                    $stmt = $conn->prepare("UPDATE profile_photos SET is_active = 0 WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Insert new photo
                    $relativeFilePath = '../../uploads/' . $fileName;
                    $stmt = $conn->prepare("INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (?, ?, 1)");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("is", $userId, $relativeFilePath);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_photo'] = $relativeFilePath;
                        $response = ['status' => 'success', 'message' => 'Photo updated successfully', 'path' => $relativeFilePath];
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                } else {
                    throw new Exception("Failed to move uploaded file");
                }
                break;
                
            default:
                $response = ['status' => 'error', 'message' => 'Invalid action'];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>