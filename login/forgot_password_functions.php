<?php
require_once '../config.php';
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

session_start();

// Twilio configuration
function getTwilioClient() {
    $sid = "AC80cae86174ab25c1728133facec97816";
    $token = "1073ee807e57151f875aeef78576a100";
    return new Client($sid, $token);
}

// Enhanced username verification with role check
function verifyUsername($username) {
    global $pdo;
    
    // Basic input validation
    if (empty(trim($username))) {
        throw new Exception("Username cannot be empty.");
    }
    
    // Check if username exists and get role
    $stmt = $pdo->prepare("
        SELECT u.id, r.role_name, u.is_active, u.is_banned
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = ?;
    ");
    
    if (!$stmt->execute([$username])) {
        throw new Exception("Database error occurred.");
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Username does not exist.");
    }
    
    // Check role restrictions - only allow regular users
    if (in_array($user['role_name'], ['staff', 'admin', 'coach'])) {
        throw new Exception("Password reset not allowed for staff, coach and admin accounts. Please contact system administrator.");
    }
    
    // Check account status
    if (!$user['is_active']) {
        throw new Exception("This account is not activated. Please contact support.");
    }
    
    if ($user['is_banned']) {
        throw new Exception("This account has been suspended. Please contact support.");
    }
    
    return true;
}

// Get user's phone number from database
function getUserPhone($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.phone_number
        FROM users u 
        JOIN personal_details p ON u.id = p.user_id 
        WHERE u.username = ? AND u.is_active = 1 AND u.is_banned = 0
    ");
    
    if (!$stmt->execute([$username])) {
        throw new Exception("Database error occurred.");
    }
    
    $phone = $stmt->fetchColumn();
    
    if (!$phone) {
        throw new Exception("No phone number found for this account. Please contact support.");
    }
    
    return $phone;
}

// Send verification SMS with improved phone formatting
function sendVerificationSMS($phoneNumber) {
    try {
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";
        
        // Sanitize and format phone number
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phoneNumber) < 10) {
            throw new Exception("Invalid phone number format.");
        }
        
        // Format to E.164
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        // Send verification
        $twilio = getTwilioClient();
        $verification = $twilio->verify->v2->services($verifyServiceId)
            ->verifications
            ->create($phoneNumber, "sms");
        
        if (!$verification->sid) {
            throw new Exception("Failed to generate verification code.");
        }
        
        return $verification->sid;
    } catch (Exception $e) {
        error_log("Twilio Error: " . $e->getMessage());
        throw new Exception("Failed to send verification code. Please try again later.");
    }
}

// Verify the SMS code with improved validation
function verifyTwilioCode($phoneNumber, $code) {
    try {
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";
        
        // Validate code format
        if (!preg_match('/^\d{6}$/', $code)) {
            throw new Exception("Invalid verification code format.");
        }
        
        // Sanitize and format phone number
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phoneNumber) < 10) {
            throw new Exception("Invalid phone number format.");
        }
        
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        $twilio = getTwilioClient();
        $verification_check = $twilio->verify->v2->services($verifyServiceId)
            ->verificationChecks
            ->create([
                'to' => $phoneNumber,
                'code' => $code
            ]);

        if (!$verification_check) {
            throw new Exception("Verification failed. Please try again.");
        }

        return $verification_check->status === "approved";
    } catch (Exception $e) {
        error_log("Twilio Verification Error: " . $e->getMessage());
        throw new Exception("Failed to verify code. Please try again.");
    }
}

// Update password in database with enhanced security
function updatePassword($username, $newPassword) {
    global $pdo;
    
    // Validate password requirements
    if (strlen($newPassword) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
        throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify user exists and is eligible for password reset
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE username = ? AND is_active = 1 AND is_banned = 0
        ");
        
        if (!$stmt->execute([$username])) {
            throw new Exception("Database error occurred.");
        }
        
        if (!$stmt->fetch()) {
            throw new Exception("Invalid user or account status.");
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, 
                updated_at = CURRENT_TIMESTAMP,
                password_reset_at = CURRENT_TIMESTAMP
            WHERE username = ?
        ");
        
        if (!$stmt->execute([$hashedPassword, $username])) {
            throw new Exception("Failed to update password.");
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Password Update Error: " . $e->getMessage());
        throw new Exception("Database error occurred. Please try again later.");
    }
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'verify_username':
                $username = trim($_POST['username'] ?? '');
                verifyUsername($username);
                $phone = getUserPhone($username);
                echo json_encode(['success' => true]);
                break;
                
            case 'verify_code':
                if (!isset($_POST['username']) || !isset($_POST['code'])) {
                    throw new Exception("Missing required parameters.");
                }
                
                $username = trim($_POST['username']);
                $code = trim($_POST['code']);
                $phone = getUserPhone($username);
                
                if (verifyTwilioCode($phone, $code)) {
                    $_SESSION['reset_verified'] = true;
                    $_SESSION['reset_username'] = $username;
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Invalid verification code.");
                }
                break;
                
            case 'reset_password':
                if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
                    throw new Exception("Verification required before resetting password.");
                }
                
                if (!isset($_POST['username']) || !isset($_POST['password'])) {
                    throw new Exception("Missing required parameters.");
                }
                
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                
                // Verify username matches session
                if ($username !== $_SESSION['reset_username']) {
                    throw new Exception("Invalid session. Please start over.");
                }
                
                if (updatePassword($username, $password)) {
                    // Clear session variables
                    unset($_SESSION['reset_verified']);
                    unset($_SESSION['reset_username']);
                    echo json_encode(['success' => true]);
                }
                break;
                
            default:
                throw new Exception("Invalid action specified.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle GET requests with rate limiting
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        if (isset($_GET['action']) && $_GET['action'] === 'generate_code') {
            // Rate limiting check
            if (isset($_SESSION['last_code_sent']) && 
                time() - $_SESSION['last_code_sent'] < 60) {
                throw new Exception('Please wait 60 seconds before requesting another code.');
            }
            
            $username = trim($_GET['username'] ?? '');
            if (empty($username)) {
                throw new Exception("Username is required.");
            }
            
            $phone = getUserPhone($username);
            $verificationSid = sendVerificationSMS($phone);
            $_SESSION['last_code_sent'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent successfully'
            ]);
        } else {
            throw new Exception("Invalid action specified.");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}