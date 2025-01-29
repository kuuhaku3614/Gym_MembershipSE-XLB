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

// Get user's phone number from database
function getUserPhone($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.phone_number
        FROM users u 
        JOIN personal_details p ON u.id = p.user_id 
        WHERE u.username = ? AND u.is_active = 1 AND u.is_banned = 0
    ");
    $stmt->execute([$username]);
    return $stmt->fetchColumn();
}

// Verify username exists and is active
function verifyUsername($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id 
        FROM users 
        WHERE username = ? AND is_active = 1 AND is_banned = 0
    ");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Send verification SMS
function sendVerificationSMS($phoneNumber) {
    try {
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";
        
        // Format phone number to E.164 format if not already formatted
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . ltrim($phoneNumber, '0');
        }

        // Send verification
        $twilio = getTwilioClient();
        $verification = $twilio->verify->v2->services($verifyServiceId)
            ->verifications
            ->create($phoneNumber, "sms");
        
        return $verification->sid;
    } catch (Exception $e) {
        error_log("Twilio Error: " . $e->getMessage());
        throw new Exception("Failed to send verification code. Please try again.");
    }
}

// Verify the SMS code
function verifyTwilioCode($phoneNumber, $code) {
    try {
        $verifyServiceId = "VA65eaa4607fec1266ff04693d0dab7f4f";
        
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

        return $verification_check->status === "approved";
    } catch (Exception $e) {
        error_log("Twilio Verification Error: " . $e->getMessage());
        throw new Exception("Failed to verify code. Please try again.");
    }
}

// Update password in database
function updatePassword($username, $newPassword) {
    global $pdo;
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE username = ?
    ");
    
    return $stmt->execute([$hashedPassword, $username]);
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'verify_username':
                $username = $_POST['username'];
                $user = verifyUsername($username);
                
                if ($user) {
                    $phone = getUserPhone($username);
                    if ($phone) {
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception("No phone number found for this account.");
                    }
                } else {
                    throw new Exception("Username not found or account is inactive.");
                }
                break;
                
            case 'verify_code':
                $username = $_POST['username'];
                $code = $_POST['code'];
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
                
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                // Validate password
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                    throw new Exception("Password must be at least 8 characters and contain uppercase, lowercase, and numbers.");
                }
                
                if (updatePassword($username, $password)) {
                    // Clear session variables
                    unset($_SESSION['reset_verified']);
                    unset($_SESSION['reset_username']);
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Failed to update password.");
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle GET requests (for generating verification code)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        if (isset($_GET['action']) && $_GET['action'] === 'generate_code') {
            // Rate limiting check
            if (isset($_SESSION['last_code_sent']) && 
                time() - $_SESSION['last_code_sent'] < 60) {
                throw new Exception('Please wait 60 seconds before requesting another code');
            }
            
            $username = $_GET['username'];
            $phone = getUserPhone($username);
            
            if ($phone) {
                $verificationSid = sendVerificationSMS($phone);
                $_SESSION['last_code_sent'] = time();
                echo json_encode([
                    'success' => true,
                    'message' => 'Verification code sent successfully'
                ]);
            } else {
                throw new Exception("No phone number found for this account.");
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}