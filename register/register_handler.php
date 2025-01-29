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

function generateAndStoreVerificationCode($userId, $pdo) {
    // Generate a random 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiration time (15 minutes from now)
    $created_at = new DateTime();
    $expires_at = (new DateTime())->modify('+15 minutes');
    
    // Store in database
    $stmt = $pdo->prepare("
        INSERT INTO verification_codes (user_id, code, created_at, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $code,
        $created_at->format('Y-m-d H:i:s'),
        $expires_at->format('Y-m-d H:i:s')
    ]); 
    
    return $code;
}

function sendVerificationSMS($phoneNumber) {
    try {
        // $sid = "AC80cae86174ab25c1728133facec97816";
        // $token = "1073ee807e57151f875aeef78576a100"; 
        // $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";

        $twilio = new Client($sid, $token);
        
        // Format phone number to E.164 format if not already formatted
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        // Send verification
        $verification = $twilio->verify->v2->services($verifyServiceId)
            ->verifications
            ->create($phoneNumber, "sms");
        
        return $verification->sid;
    } catch (Exception $e) {
        error_log("Twilio Error: " . $e->getMessage());
        throw new Exception("Failed to send verification code. Please try again.");
    }
}

function verifyCode($userId, $code, $pdo) {
    $stmt = $pdo->prepare("
        SELECT * FROM verification_codes 
        WHERE user_id = ? 
        AND code = ? 
        AND expires_at > NOW() 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$userId, $code]);
    $verification = $stmt->fetch();
    
    if (!$verification) {
        throw new Exception("Invalid or expired verification code.");
    }
    
    // Delete used verification code
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$verification['id']]);
    
    return true;
}

function verifyTwilioCode($phoneNumber, $code) {
    try {
        $sid = "AC80cae86174ab25c1728133facec97816";
        $token = "1073ee807e57151f875aeef78576a100"; // Replace with your actual Twilio auth token
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";

        $twilio = new Client($sid, $token);
        
        // Format phone number to E.164 format if not already formatted
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        // Verify the code
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

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle initial form validation
    if (isset($_POST['action']) && $_POST['action'] === 'validate') {
        try {
            $pdo->beginTransaction();
            
            // Validate username
            $username = trim($_POST['username']);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username already exists.");
            }
            
            // Validate password
            validatePassword($_POST['password']);
            
            // Validate full name
            validateFullName(
                $_POST['first_name'],
                $_POST['middle_name'],
                $_POST['last_name'],
                $pdo
            );
            
            // Validate age
            validateAge($_POST['birthday']);
            
            // Store validated data in session
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
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Handle verification code submission
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        try {
            $pdo->beginTransaction();
            
            $code = $_POST['code'];
            $phone = $_SESSION['registration_data']['phone'];
            
            if (!verifyTwilioCode($phone, $code)) {
                throw new Exception("Invalid verification code.");
            }
            
            // Complete the registration process
            // [Your registration code here]
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
// At the start of your generate_code handler:
if (isset($_SESSION['last_code_sent']) && 
    time() - $_SESSION['last_code_sent'] < 60) {
    echo json_encode([
        'success' => false,
        'message' => 'Please wait 60 seconds before requesting another code'
    ]);
    exit;
}
$_SESSION['last_code_sent'] = time();
// Handle verification code generation
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'generate_code') {
    try {
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