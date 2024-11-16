<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';


$database = new Database();
$pdo = $database->connect();

function loginUser($username, $password) {
    global $pdo;
    
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $sql = "SELECT id, password, role FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password'])) {
                return [
                    'success' => true,
                    'user_id' => $user['id'],
                    'role' => $user['role']
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function redirectBasedOnRole($role) {
    switch($role) {
        case 'admin':
            header("Location: ../admin/index.php");
            break;
        case 'staff':
            header("Location: ../staff/dashboard.php");
            break;
        case 'coach':
            header("Location: ../coach/dashboard.php");
            break;
        case 'member':
            header("Location: ../website/website.php");
            break;
        default:
            header("Location: index.php");
    }
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isUsernameExists($username) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return true; // Assume exists in case of error
    }
}

function createUser($userData) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO users (username, password, role) 
                VALUES (:username, :password, :role)";
        
        $stmt = $pdo->prepare($sql);
        
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':role', $userData['role'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}