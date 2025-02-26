<?php
require_once __DIR__ . '/../config.php';

$database = new Database();
$pdo = $database->connect();

function redirectBasedOnRole($role) {
    $roleRoutes = [
        'admin' => '../admin/index.php',
        'staff' => '../admin/index.php',
        'coach' => '../website/website.php',
        'member' => '../website/website.php',
        'user' => '../website/website.php',
    ];
    
    if (isset($roleRoutes[$role])) {
        header("Location: " . $roleRoutes[$role]);
    } else {
        error_log("Unexpected role encountered: " . $role);
        header("Location: index.php");
    }
    exit();
}

function loginUser($username, $password) {
    global $pdo;

    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }

    try {
        // Modified query to include profile photo
        $sql = "SELECT u.id, u.password, u.role_id, r.role_name as role, u.is_banned,
                pp.photo_path
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
                WHERE u.username = :username AND u.is_active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();

            if ($user['is_banned']) {
                return ['success' => false, 'message' => 'Your account has been suspended due to violation of our terms of service. If you believe this is an error, please contact our support team for assistance.'];
            }

            if (password_verify($password, $user['password'])) {
                $validRoles = ['admin', 'staff', 'coach', 'member', 'user'];
                if (!in_array($user['role'], $validRoles)) {
                    return ['success' => false, 'message' => 'Unauthorized role detected.'];
                }

                // Set all necessary session variables at once
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['user_photo'] = $user['photo_path'] ?? '../cms_img/user.png';

                return [
                    'success' => true,
                    'user_id' => $user['id'],
                    'role' => $user['role'],
                    'role_id' => $user['role_id']
                ];
            }
        }

        return ['success' => false, 'message' => 'Invalid username or password.'];
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isUsernameExists($username) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM users WHERE username = :username AND u.is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking username: " . $e->getMessage());
        return true; // Assume exists in case of error
    }
}
function initializeNotificationSession($database, $user_id) {
    // Initialize session array for read notifications
    $_SESSION['read_notifications'] = [
        'transactions' => [],
        'memberships' => [],
        'announcements' => []
    ];
    
    // Fetch read notifications from database
    $pdo = $database->connect();
    $sql = "SELECT notification_type, notification_id 
            FROM notification_reads 
            WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_reads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Populate session with database reads
    foreach ($db_reads as $read) {
        $type = $read['notification_type'];
        $id = (int)$read['notification_id'];
        
        if (isset($_SESSION['read_notifications'][$type])) {
            $_SESSION['read_notifications'][$type][] = $id;
        }
    }
}