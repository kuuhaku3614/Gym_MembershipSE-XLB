<?php
require_once 'config.php';

function loginUser($username, $password) {
    global $pdo;
    
    try {
        $sql = "SELECT id, password, role, status FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            
            if ($user['status'] == 'inactive') {
                return ['success' => false, 'message' => 'Account is inactive. Please contact admin.'];
            }
            
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

function updateLastLogin($user_id) {
    global $pdo;
    
    try {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        // Log error
        error_log("Error updating last login: " . $e->getMessage());
    }
}

function redirectBasedOnRole($role) {
    switch($role) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'staff':
            header("Location: ../staff/dashboard.php");
            break;
        case 'coach':
            header("Location: ../coach/dashboard.php");
            break;
        case 'member':
            header("Location: ../members/dashboard.php");
            break;
        default:
            header("Location: index.php");
    }
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
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

function isEmailExists($email) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return true; // Assume exists in case of error
    }
}

function createUser($userData) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO users (username, password, email, first_name, last_name, role) 
                VALUES (:username, :password, :email, :first_name, :last_name, :role)";
        
        $stmt = $pdo->prepare($sql);
        
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':email', $userData['email'], PDO::PARAM_STR);
        $stmt->bindParam(':first_name', $userData['first_name'], PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $userData['last_name'], PDO::PARAM_STR);
        $stmt->bindParam(':role', $userData['role'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}
?>