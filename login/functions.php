<?php
require_once __DIR__ . '/../config.php';

$database = new Database();
$pdo = $database->connect();

/**
 * Sanitizes input data to prevent XSS attacks
 * @param string $data The input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = sanitizeInput($value);
        }
        return $sanitized;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizes data for output to prevent XSS attacks
 * @param mixed $data The data to be sanitized for output
 * @return mixed Sanitized data
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeOutput($value);
        }
        return $data;
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects user based on their role
 * @param string $role User role
 */
function redirectBasedOnRole($role) {
    $roleRoutes = [
        'admin' => '../admin/index.php',
        'staff' => '../admin/index.php',
        'coach' => '../website/website.php',
        'member' => '../website/website.php',
        'user' => '../website/website.php',
    ];
    
    $role = sanitizeInput($role);
    
    if (isset($roleRoutes[$role])) {
        header("Location: " . $roleRoutes[$role]);
    } else {
        error_log("Unexpected role encountered: " . $role);
        header("Location: index.php");
    }
    exit();
}

/**
 * Authenticates a user and returns result
 * @param string $username Username
 * @param string $password Password
 * @return array Authentication result
 */
function loginUser($username, $password) {
    global $pdo;

    // Sanitize inputs
    $username = sanitizeInput($username);

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
        return ['success' => false, 'message' => 'Database error occurred. Please try again later.'];
    }
}

/**
 * Checks if a username already exists
 * @param string $username Username to check
 * @return bool True if username exists, false otherwise
 */
function isUsernameExists($username) {
    global $pdo;
    
    // Sanitize input
    $username = sanitizeInput($username);
    
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

/**
 * Initializes notification session data
 * @param Database $database Database instance
 * @param int $user_id User ID
 */
function initializeNotificationSession($database, $user_id) {
    // Sanitize input
    $user_id = (int)$user_id;
    
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
        $type = sanitizeInput($read['notification_type']);
        $id = (int)$read['notification_id'];
        
        if (isset($_SESSION['read_notifications'][$type])) {
            $_SESSION['read_notifications'][$type][] = $id;
        }
    }
}

/**
 * Executes a database query with prepared statements
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Query results
 */
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return sanitizeOutput($result);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}