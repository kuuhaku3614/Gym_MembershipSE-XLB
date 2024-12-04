<?php
// Define upload directory
$uploadDir = dirname(__DIR__, 3) . '/cms_img';

// Ensure upload directory exists
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception("Failed to create upload directory");
    }
}

function uploadFile($file, $subDirectory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg']) {
    global $uploadDir;

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Create subdirectory if it doesn't exist
    $fullUploadPath = $uploadDir . '/' . $subDirectory;
    if (!file_exists($fullUploadPath)) {
        if (!mkdir($fullUploadPath, 0755, true)) {
            throw new Exception("Failed to create upload subdirectory");
        }
    }

    $fileName = basename($file['name']);
    $targetFilePath = $fullUploadPath . '/' . uniqid() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
    }

    // Validate file size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        // Return relative path from project root
        return 'cms_img/' . $subDirectory . '/' . basename($targetFilePath);
    }

    return null;
}

function deleteImageFile($imagePath) {
    global $uploadDir;
    $fullPath = $uploadDir . '/' . $imagePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}
?>