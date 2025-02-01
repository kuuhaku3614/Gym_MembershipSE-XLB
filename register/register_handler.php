<?php
require_once '../config.php';
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

session_start();

// Validation functions
function validateFullName($firstName, $middleName, $lastName, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM personal_details 
        WHERE LOWER(first_name) = LOWER(?) 
        AND LOWER(middle_name) = LOWER(?) 
        AND LOWER(last_name) = LOWER(?)
    ");
    $stmt->execute([
        trim($firstName),
        trim($middleName),
        trim($lastName)
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("This name is already registered in our system.");
    }
}

function validatePhoneExists($phone, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM personal_details 
        WHERE phone_number = ?
    ");
    $stmt->execute([trim($phone)]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("This phone number is already registered.");
    }
}

function validateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < 15) {
        throw new Exception("You must be at least 15 years old to register.");
    }
}

function validatePassword($password) {
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        throw new Exception("Password must be at least 8 characters and contain uppercase, lowercase, and numbers.");
    }
}

function validatePhoneNumber($phone) {
    if (!preg_match('/^(?:\+63|0)9\d{9}$/', $phone)) {
        throw new Exception("Please enter a valid Philippine phone number (e.g., 09123456789 or +639123456789).");
    }
}

function validateProfilePhoto($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Profile photo is required.");
    }

    $allowedTypes = ['image/jpeg', 'image/png'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($detectedType, $allowedTypes)) {
        throw new Exception("Only JPG and PNG files are allowed.");
    }

    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception("File size must not exceed 5MB.");
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("Invalid image file.");
    }

    if ($imageInfo[0] < 200 || $imageInfo[1] < 200) {
        throw new Exception("Image dimensions must be at least 200x200 pixels.");
    }
}

function saveProfilePhoto($file, $userId) {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to save profile photo.");
    }

    return 'uploads/' . $filename;
}

function sendVerificationSMS($phoneNumber) {
    try {
        $sid = "AC80cae86174ab25c1728133facec97816";
        $token = "6ea56e4f9eb311a8c85158e835f5ba38"; 
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";

        $twilio = new Client($sid, $token);
        
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        $verification = $twilio->verify->v2->services($verifyServiceId)
            ->verifications
            ->create($phoneNumber, "sms");
        
        return $verification->sid;
    } catch (Exception $e) {
        error_log("Twilio Error: " . $e->getMessage());
        throw new Exception("Failed to send verification code. Please try again.");
    }
}

function verifyTwilioCode($phoneNumber, $code) {
    try {
        $sid = "AC80cae86174ab25c1728133facec97816";
        $token = "6ea56e4f9eb311a8c85158e835f5ba38";
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";

        $twilio = new Client($sid, $token);
        
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        $verification_check = $twilio->verify->v2->services($verifyServiceId)
            ->verificationChecks
            ->create([
                'to' => $phoneNumber,
                'code' => $code
            ]);

        return $verification_check->status === "approved";
    } catch (Exception $e) {
        error_log("Twilio Verification Error: " . $e->getMessage());
        throw new Exception("Failed to verify code. Please try again.");
    }
}

function determineErrorField($errorMessage) {
    $patterns = [
        'username' => '/username already exists/i',
        'password' => '/password must be/i',
        'name' => '/name is already registered/i',
        'age' => '/must be at least 15 years/i',
        'phone' => '/phone number/i'
    ];
    
    foreach ($patterns as $field => $pattern) {
        if (preg_match($pattern, $errorMessage)) {
            return $field;
        }
    }
    return 'general';
}

// Rate limiting for code generation
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "GET" && 
    isset($_GET['action']) && $_GET['action'] === 'generate_code') {
    
    if (isset($_SESSION['last_code_sent']) && time() - $_SESSION['last_code_sent'] < 60) {
        echo json_encode([
            'success' => false,
            'message' => 'Please wait 60 seconds before requesting another code'
        ]);
        exit;
    }
    
    try {
        $_SESSION['last_code_sent'] = time();
        $phone = $_SESSION['registration_data']['phone'];
        $verificationSid = sendVerificationSMS($phone);
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Validation handler
if (isset($_POST['action']) && $_POST['action'] === 'validate') {
    try {
        $pdo->beginTransaction();
        
        $username = trim($_POST['username']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists.");
        }
        
        validatePassword($_POST['password']);
        validateFullName(
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $pdo
        );
        validateAge($_POST['birthday']);
        validatePhoneNumber($_POST['phone']);
        validatePhoneExists($_POST['phone'], $pdo);
        
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
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'field' => determineErrorField($e->getMessage())
        ]);
    }
    exit;
}

// Verification handler
if (isset($_POST['action']) && $_POST['action'] === 'verify') {
    try {
        if (!isset($_SESSION['registration_data'])) {
            throw new Exception("Registration data not found. Please start over.");
        }

        $pdo->beginTransaction();
        
        $code = $_POST['code'];
        $registrationData = $_SESSION['registration_data'];
        $phone = $registrationData['phone'];
        
        // First validate the profile photo
        validateProfilePhoto($_FILES['profile_photo']);
        
        // Then verify the code
        if (!verifyTwilioCode($phone, $code)) {
            throw new Exception("Invalid verification code.");
        }

        // Insert into users table first to get user_id
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role_id, is_active)
            VALUES (?, ?, ?, 1)
        ");
        
        $hashedPassword = password_hash($registrationData['password'], PASSWORD_DEFAULT);
        $roleId = 3; // Regular member role
        $stmt->execute([$registrationData['username'], $hashedPassword, $roleId]);
        $userId = $pdo->lastInsertId();

        // Save the profile photo
        $photoPath = saveProfilePhoto($_FILES['profile_photo'], $userId);

        // Insert into personal_details
        $stmt = $pdo->prepare("
            INSERT INTO personal_details 
            (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $registrationData['first_name'],
            $registrationData['middle_name'],
            $registrationData['last_name'],
            $registrationData['sex'],
            $registrationData['birthday'],
            $registrationData['phone']
        ]);

        // Insert into profile_photos
        $stmt = $pdo->prepare("
            INSERT INTO profile_photos (user_id, photo_path, is_active)
            VALUES (?, ?, 1)
        ");
        
        $stmt->execute([$userId, $photoPath]);

        // Clear the session registration data
        unset($_SESSION['registration_data']);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>