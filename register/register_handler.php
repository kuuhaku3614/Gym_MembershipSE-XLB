<?php
require_once __DIR__ . '/../config.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle initial form submission
    if (isset($_POST['action']) && $_POST['action'] === 'validate') {
        try {
            $username = trim($_POST['username']);
            
            // Validate username uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
            
            // Store form data in session
            $_SESSION['registration_data'] = [
                'username' => $username,
                'password' => trim($_POST['password']),
                'first_name' => trim($_POST['first_name']),
                'middle_name' => trim($_POST['middle_name']),
                'last_name' => trim($_POST['last_name']),
                'sex' => $_POST['sex'],
                'birthday' => $_POST['birthday'],
                'phone' => trim($_POST['phone'])
            ];
            
            echo json_encode(['success' => true, 'message' => 'Validation successful']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    function uploadProfilePhoto($userId, $profilePhoto) {
        // Validate file upload
        if (!isset($profilePhoto) || $profilePhoto['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Invalid file upload. Error code: " . 
                ($profilePhoto['error'] ?? 'Unknown'));
        }
    
        // Allowed file types and max file size
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
    
        // Get file extension
        $fileExtension = strtolower(pathinfo($profilePhoto['name'], PATHINFO_EXTENSION));
    
        // Validate file type using extension
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Invalid file type. Only JPEG and PNG are allowed.");
        }
    
        if ($profilePhoto['size'] > $maxFileSize) {
            throw new Exception("File size exceeds 5MB limit.");
        }
    
        // Set upload directory relative to the project root
        $uploadDir = dirname(__DIR__, 1) . '/uploads'; // Navigate up three levels to reach the project root
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }
    
        // Generate unique filename
        $newFileName = 'profile_' . $userId . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . '/' . $newFileName; // Full file path for saving
        $relativePath = 'uploads/' . $newFileName; // Relative path for storing in the database
    
        // Log additional information for debugging
        error_log("Photo Upload Debug: " . json_encode([
            'userId' => $userId,
            'originalName' => $profilePhoto['name'],
            'newFileName' => $newFileName,
            'fullPath' => $uploadPath,
            'relativePath' => $relativePath
        ]));
        
        // Attempt to move uploaded file
        if (!move_uploaded_file($profilePhoto['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to move uploaded file");
        }
    
        return $relativePath;
    }

    // Handle verification code submission
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        try {
            $verificationCode = $_POST['code'];
            // In reality, you would validate against a real verification code
            // For this example, we're using the stored dummy code
            if ($verificationCode === $_SESSION['verification_code']) {
                
                $data = $_SESSION['registration_data'];
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Get the member role ID
                $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'member'");
                $stmt->execute();
                $roleId = $stmt->fetchColumn();
                
                if (!$roleId) {
                    throw new Exception('Member role not found in database');
                }
                
                // Insert into users table with role_id
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$data['username'], $hashedPassword, $roleId]);
                
                // Get the last inserted user ID
                $userId = $pdo->lastInsertId();
                
                // Handle profile photo upload if a file was submitted
                $profilePhotoPath = null;
                if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $profilePhotoPath = uploadProfilePhoto($userId, $_FILES['profile_photo']);
                }
                
                // Insert into personal_details table
                // Update the personal details insertion to remove profile_photo column
                $stmt = $pdo->prepare("INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $data['first_name'],
                    $data['middle_name'],
                    $data['last_name'],
                    $data['sex'],
                    $data['birthday'],
                    $data['phone']
                ]);

                // If a profile photo was uploaded, insert its path into the profile_photos table
                if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $profilePhotoPath = uploadProfilePhoto($userId, $_FILES['profile_photo']);
                    
                    $stmt = $pdo->prepare("INSERT INTO profile_photos (user_id, photo_path) VALUES (?, ?)");
                    $stmt->execute([$userId, $profilePhotoPath]);
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Clear session data
                unset($_SESSION['registration_data']);
                unset($_SESSION['verification_code']);
                
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
                
            } else {
                throw new Exception('Invalid verification code');
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle verification code generation
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'generate_code') {
    // In a real application:
    // 1. Generate a random verification code
    // 2. Send it via SMS to the provided phone number
    // 3. Store it securely (e.g., in a temporary table with expiration)
    
    // For this example, we'll use a dummy code
    $verificationCode = '123456';
    $_SESSION['verification_code'] = $verificationCode;
    
    echo json_encode(['success' => true, 'message' => 'Verification code generated']);
    exit;
}
?>