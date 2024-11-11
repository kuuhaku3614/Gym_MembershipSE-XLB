<?php
require_once 'config.php';
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
                
                // Insert into users table
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'member')");
                $stmt->execute([$data['username'], $hashedPassword]);
                
                // Get the last inserted user ID
                $userId = $pdo->lastInsertId();
                
                // Insert into personal_details table
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