<?php
// Start session for message handling
session_start();

// Include database connection
require_once 'config.php';

// Define upload directory
$uploadDir = dirname(__DIR__, 3) . '/cms_img';

// Ensure upload directory exists
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception("Failed to create upload directory");
    }
}

// Function to handle file uploads
function uploadFile($file, $subDirectory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg']) {
    global $uploadDir;

    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error occurred.");
    }

    // Create subdirectory if it doesn't exist
    $fullUploadPath = $uploadDir . '/' . $subDirectory;
    if (!file_exists($fullUploadPath)) {
        if (!mkdir($fullUploadPath, 0755, true)) {
            throw new Exception("Failed to create upload subdirectory");
        }
    }

    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $targetFilePath = $fullUploadPath . '/' . uniqid() . '_' . $fileName;

    // Validate file type using mime type for more robust detection
    $mimeType = mime_content_type($file['tmp_name']);
    $validMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml'
    ];

    if (!in_array($fileType, $allowedTypes) || !array_key_exists($fileType, $validMimeTypes) || $mimeType !== $validMimeTypes[$fileType]) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
    }

    // Validate file size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Additional check to ensure it's a valid image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("Invalid image file.");
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        // Return relative path from project root
        return 'cms_img/' . $subDirectory . '/' . basename($targetFilePath);
    }

    throw new Exception("File upload failed due to an unknown error.");
}

// Function to delete an image file
function deleteImageFile($imagePath) {
    global $uploadDir;
    $fullPath = $uploadDir . '/' . $imagePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// Function to fetch dynamic content
function getDynamicContent($section) {
    global $pdo;
    $query = "SELECT * FROM website_content WHERE section = :section";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['section' => $section]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to fetch existing content from a table
function fetchExistingContent($table) {
    global $pdo;
    $query = "SELECT * FROM $table";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processing welcome section update
if (isset($_POST['update_welcome'])) {
    try {
        $stmt = $pdo->prepare("UPDATE website_content SET company_name = :name, description = :desc WHERE section = 'welcome'");
        $stmt->execute([
            ':name' => $_POST['company_name'],
            ':desc' => $_POST['welcome_description']
        ]);
        
        $_SESSION['success_message'] = "Welcome section updated successfully!";
        
        // If scroll_to is set, preserve it
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        
        // Store scroll position if available
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: /admin/content_management");
    exit();
}

// Processing contact information update
if (isset($_POST['update_contact'])) {
    try {
        $stmt = $pdo->prepare("UPDATE website_content SET 
            location = :location_name,
            phone = :phone, 
            email = :email,
            latitude = :lat,
            longitude = :lon 
            WHERE section = 'contact'");
        $stmt->execute([
            ':location_name' => $_POST['location_name'] ?? null,
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':lat' => $_POST['latitude'] ?? null,
            ':lon' => $_POST['longitude'] ?? null
        ]);
        
        $_SESSION['success_message'] = "Contact information updated successfully!";
        
        // If scroll_to is set, preserve it
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        
        // Store scroll position if available
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: /admin/content_management");

    exit;
    
}