<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';
// Set JSON content type header
header('Content-Type: application/json');

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

// Function to validate password
function validatePassword($password) {
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        throw new Exception("Password must be at least 8 characters and contain uppercase, lowercase, and numbers.");
    }
}

// Add this function near your other validation functions at the top of the file
function validatePhoneNumber($phone) {
    if (!preg_match('/^(?:\+63|0)9\d{9}$/', $phone)) {
        throw new Exception("Please enter a valid Philippine phone number (e.g., 09123456789 or +639123456789).");
    }
}

// Function to validate birthdate (must be at least 15 years old)
function validateBirthdate($birthdate) {
    $today = new DateTime();
    $birthdateObj = new DateTime($birthdate);
    $age = $birthdateObj->diff($today)->y;
    
    if ($age < 15) {
        throw new Exception("You must be at least 15 years old to register.");
    }
}

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
                        $response = [
                            'status' => 'success', 
                            'message' => 'Username updated successfully',
                            'user_data' => [
                                'username' => $username
                            ]
                        ];
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
                
                // Get existing photo path
                $stmt = $conn->prepare("SELECT photo_path FROM profile_photos WHERE user_id = ? AND is_active = 1");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $existingPhoto = $result->fetch_assoc();
                
                // Generate new filename
                $fileName = 'profile_' . $userId . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $fileName;
                
                // Delete existing photo file if it exists
                if ($existingPhoto && file_exists($existingPhoto['photo_path'])) {
                    unlink($existingPhoto['photo_path']);
                }
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $relativeFilePath = 'uploads/' . $fileName;
                    
                    if ($existingPhoto) {
                        // Update existing photo record
                        $stmt = $conn->prepare("UPDATE profile_photos SET photo_path = ? WHERE user_id = ? AND is_active = 1");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param("si", $relativeFilePath, $userId);
                    } else {
                        // Insert new photo record if none exists
                        $stmt = $conn->prepare("INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (?, ?, 1)");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param("is", $userId, $relativeFilePath);
                    }
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_photo'] = $relativeFilePath;
                        $response = [
                            'status' => 'success', 
                            'message' => 'Photo updated successfully', 
                            'path' => $relativeFilePath,
                            'user_data' => [
                                'photo_path' => $relativeFilePath
                            ]
                        ];
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                } else {
                    throw new Exception("Failed to move uploaded file");
                }
                break;
                
            case 'get_profile_data':
                // Corrected query to use proper table structure - fetch from both users and personal_details tables
                $stmt = $conn->prepare("
                    SELECT u.id, u.username, pd.first_name, pd.last_name, pd.sex, pd.birthdate, pd.phone_number,
                           IFNULL(p.photo_path, '../cms_img/user.png') as photo_path, u.last_password_change
                    FROM users u
                    LEFT JOIN personal_details pd ON u.id = pd.user_id
                    LEFT JOIN profile_photos p ON u.id = p.user_id AND p.is_active = 1
                    WHERE u.id = ?
                ");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
                
                if ($userData) {
                    $response = [
                        'status' => 'success',
                        'user_data' => $userData
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'User not found'
                    ];
                }
                break;

            case 'update_personal_details':
                $firstName = trim($_POST['first_name']);
                $lastName = trim($_POST['last_name']);
                $sex = $_POST['sex'];
                $birthdate = $_POST['birthdate'];
                $phoneNumber = trim($_POST['phone_number']);
                
                // Validate phone number (must be a valid Philippine number)
                validatePhoneNumber($phoneNumber);
                
                // Validate birthdate (must be at least 15 years old)
                validateBirthdate($birthdate);
                
                // Check if another user has the same full name
                $stmt = $conn->prepare("
                    SELECT pd.user_id 
                    FROM personal_details pd 
                    WHERE pd.first_name = ? AND pd.last_name = ? AND pd.user_id != ?
                ");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ssi", $firstName, $lastName, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception("A user with the same full name already exists. Please use a different name.");
                }
                
                // Check if another user has the same phone number
                $stmt = $conn->prepare("
                    SELECT pd.user_id 
                    FROM personal_details pd 
                    WHERE pd.phone_number = ? AND pd.user_id != ?
                ");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("si", $phoneNumber, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception("A user with the same phone number already exists. Please use a different phone number.");
                }
                
                // Check if personal details exist for the user
                $stmt = $conn->prepare("SELECT id FROM personal_details WHERE user_id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing personal details
                    $stmt = $conn->prepare("UPDATE personal_details SET first_name = ?, last_name = ?, sex = ?, birthdate = ?, phone_number = ? WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("sssssi", $firstName, $lastName, $sex, $birthdate, $phoneNumber, $userId);
                } else {
                    // Insert new personal details
                    $stmt = $conn->prepare("INSERT INTO personal_details (user_id, first_name, last_name, sex, birthdate, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("isssss", $userId, $firstName, $lastName, $sex, $birthdate, $phoneNumber);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name'] = $lastName;
                    $_SESSION['sex'] = $sex;
                    $_SESSION['birthdate'] = $birthdate;
                    $_SESSION['phone_number'] = $phoneNumber;
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Personal details updated successfully',
                        'user_data' => [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'sex' => $sex,
                            'birthdate' => $birthdate,
                            'phone_number' => $phoneNumber
                        ]
                    ];
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                break;

            case 'update_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                
                // First, validate the new password
                validatePassword($newPassword);
                
                // Check if user exists and get current password hash
                $stmt = $conn->prepare("SELECT password, last_password_change FROM users WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
                
                if (!$userData) {
                    throw new Exception("User not found");
                }
                
                // Verify current password
                if (!password_verify($currentPassword, $userData['password'])) {
                    throw new Exception("Current password is incorrect");
                }
                
                // Check if password was changed in the last month
                $lastPasswordChange = new DateTime($userData['last_password_change']);
                $now = new DateTime();
                $interval = $lastPasswordChange->diff($now);
                $daysSinceLastChange = $interval->days;
                
                if ($daysSinceLastChange < 30) {
                    throw new Exception("Password can only be changed once every 30 days. Please try again in " . (30 - $daysSinceLastChange) . " days.");
                }
                
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                // Update password and last_password_change
                $stmt = $conn->prepare("UPDATE users SET password = ?, last_password_change = CURRENT_TIMESTAMP WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("si", $hashedPassword, $userId);
                
                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Password updated successfully'
                    ];
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
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