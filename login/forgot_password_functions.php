<?php
require_once '../config.php';
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

session_start();

function getTwilioClient() {
    $sid = "ACc1f1f89f87b2b2e23e7c037aad8abae0";
    $token = "5f9d1a083156c3c38a641129fdf07be1";
    return new Client($sid, $token);
}

// Enhanced username verification with role check
function verifyUsername($username) {
    global $pdo;
    
    if (empty(trim($username))) {
        throw new Exception("Username cannot be empty.");
    }
    
    $stmt = $pdo->prepare("
        SELECT u.id, r.role_name, u.is_active, u.is_banned, u.last_password_change
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
    
    if (in_array($user['role_name'], ['staff', 'admin', 'coach'])) {
        throw new Exception("Password reset not allowed for staff, coach and admin accounts.");
    }
    
    if (!$user['is_active']) {
        throw new Exception("This account is not activated. Please contact support.");
    }
    
    if ($user['is_banned']) {
        throw new Exception("This account has been suspended. Please contact support.");
    }
    
    // Check if 30 days have passed since last password change
    if ($user['last_password_change']) {
        $lastChange = new DateTime($user['last_password_change']);
        $now = new DateTime();
        $daysSinceChange = $now->diff($lastChange)->days;
        
        if ($daysSinceChange < 30) {
            $daysRemaining = 30 - $daysSinceChange;
            throw new Exception("Password can only be changed every 30 days. Please wait {$daysRemaining} more days before attempting to change your password again.");
        }
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



// Updated sendVerificationSMS function with new credentials
function sendVerificationSMS($phoneNumber) {
    try {
        $sid = "ACc1f1f89f87b2b2e23e7c037aad8abae0";
        $token = "5f9d1a083156c3c38a641129fdf07be1";
        $verifyServiceId = "VA48723f597c526f0dcf1203976de0780f";

        $twilio = new Client($sid, $token);
        
        // Format phone number
        $formattedNumber = preg_replace('/^0/', '+63', $phoneNumber);
        if (!preg_match('/^\+63/', $formattedNumber)) {
            $formattedNumber = '+63' . $formattedNumber;
        }

        $verification = $twilio->verify->v2->services($verifyServiceId)
            ->verifications
            ->create($formattedNumber, "sms");
        
        $_SESSION['verification_phone'] = $formattedNumber;
        return $verification->sid;
    } catch (Exception $e) {
        error_log("Twilio Error (sendVerificationSMS): " . $e->getMessage());
        throw new Exception("Failed to send verification code. Please try again.");
    }
}

function verifyTwilioCode($phoneNumber, $code) {
    try {
        $sid = "ACc1f1f89f87b2b2e23e7c037aad8abae0";
        $token = "5f9d1a083156c3c38a641129fdf07be1";
        $verifyServiceId = "VA48723f597c526f0dcf1203976de0780f";

        $twilio = new Client($sid, $token);
        
        // Format phone number
        $formattedNumber = preg_replace('/^0/', '+63', $phoneNumber);
        if (!preg_match('/^\+63/', $formattedNumber)) {
            $formattedNumber = '+63' . $formattedNumber;
        }

        $verification_check = $twilio->verify->v2->services($verifyServiceId)
            ->verificationChecks
            ->create([
                'to' => $formattedNumber,
                'code' => $code
            ]);

        return $verification_check->status === "approved";
    } catch (Exception $e) {
        error_log("Twilio Verification Error: " . $e->getMessage());
        throw new Exception("Failed to verify code. Please try again.");
    }
}

// Update password in database with enhanced security
function updatePassword($username, $newPassword) {
    global $pdo;
    
    if (strlen($newPassword) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
        throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // Update password and last_password_change timestamp
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, last_password_change = CURRENT_TIMESTAMP 
            WHERE username = ?
        ");

        if (!$stmt->execute([$hashedPassword, $username])) {
            throw new Exception("Failed to update password. Please try again.");
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Failed to update password: " . $e->getMessage());
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    try {
        if (!isset($_POST['action'])) {
            throw new Exception("Invalid request.");
        }

        $action = $_POST['action'];

        switch ($action) {
            case 'verify_username':
                if (!isset($_POST['username'])) {
                    throw new Exception("Username is required.");
                }

                $username = trim($_POST['username']);
                verifyUsername($username);
                $_SESSION['reset_username'] = $username; // Store username in session
                $response['success'] = true;
                break;

            case 'verify_code':
                if (!isset($_POST['username']) || !isset($_POST['code'])) {
                    throw new Exception("Username and verification code are required.");
                }

                $username = trim($_POST['username']);
                $code = trim($_POST['code']);

                // Verify username is in session and matches
                if (!isset($_SESSION['reset_username']) || $_SESSION['reset_username'] !== $username) {
                    throw new Exception("Invalid session. Please start over.");
                }

                $phoneNumber = getUserPhone($username);
                if (!verifyTwilioCode($phoneNumber, $code)) {
                    throw new Exception("Invalid verification code.");
                }

                $_SESSION['code_verified'] = true;
                $response['success'] = true;
                break;

            case 'reset_password':
                if (!isset($_POST['username']) || !isset($_POST['password'])) {
                    throw new Exception("Username and password are required.");
                }

                // Verify the session is valid
                if (!isset($_SESSION['reset_username']) || !isset($_SESSION['code_verified'])) {
                    throw new Exception("Invalid session. Please start over.");
                }

                $username = trim($_POST['username']);
                if ($_SESSION['reset_username'] !== $username) {
                    throw new Exception("Invalid session. Please start over.");
                }

                $newPassword = trim($_POST['password']);
                updatePassword($username, $newPassword);
                
                // Clear session data after successful password reset
                unset($_SESSION['reset_username']);
                unset($_SESSION['code_verified']);
                
                $response['success'] = true;
                $response['message'] = "Password has been reset successfully.";
                break;

            default:
                throw new Exception("Invalid action.");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Handle GET requests for code generation with rate limiting
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = ['success' => false, 'message' => ''];

    try {
        if (!isset($_GET['action']) || !isset($_GET['username'])) {
            throw new Exception("Invalid request.");
        }

        $action = $_GET['action'];
        $username = trim($_GET['username']);

        // Verify username is in session
        if (!isset($_SESSION['reset_username']) || $_SESSION['reset_username'] !== $username) {
            throw new Exception("Invalid session. Please start over.");
        }

        if ($action === 'generate_code') {
            // Add rate limiting
            if (isset($_SESSION['last_code_sent']) && time() - $_SESSION['last_code_sent'] < 60) {
                throw new Exception("Please wait 60 seconds before requesting another code.");
            }

            $phoneNumber = getUserPhone($username);
            $verificationSid = sendVerificationSMS($phoneNumber);

            if (!$verificationSid) {
                throw new Exception("Failed to send verification code.");
            }

            $_SESSION['last_code_sent'] = time();
            $response['success'] = true;
            $response['message'] = "Verification code sent successfully.";
        } else {
            throw new Exception("Invalid action.");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>