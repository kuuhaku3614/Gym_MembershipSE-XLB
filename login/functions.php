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
        $sql = "SELECT u.id, u.password, r.role_name as role 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = :username AND u.is_active = 1";
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
        $sql = "SELECT id FROM users WHERE username = :username AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking username: " . $e->getMessage());
        return true; // Assume exists in case of error
    }
}

function createUser($userData) {
    global $pdo;
    
    try {
        // First get the role_id
        $roleQuery = "SELECT id FROM roles WHERE role_name = :role_name";
        $roleStmt = $pdo->prepare($roleQuery);
        $roleStmt->bindParam(':role_name', $userData['role'], PDO::PARAM_STR);
        $roleStmt->execute();
        $roleId = $roleStmt->fetchColumn();
        
        if (!$roleId) {
            throw new Exception("Invalid role specified");
        }

        $sql = "INSERT INTO users (username, password, role_id, is_active) 
                VALUES (:username, :password, :role_id, 1)";
        
        $stmt = $pdo->prepare($sql);
        
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $userId = $pdo->lastInsertId();
            // Create empty personal details record
            $personalDetailsSql = "INSERT INTO personal_details (user_id, first_name, last_name, sex, birthdate) 
                                 VALUES (:user_id, '', '', 'Male', CURRENT_DATE)";
            $pdStmt = $pdo->prepare($personalDetailsSql);
            $pdStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $pdStmt->execute();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}
