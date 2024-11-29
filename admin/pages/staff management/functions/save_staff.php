<?php
// save_staff.php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->beginTransaction();

    // Validate required fields with more specific checks
    $requiredFields = ['username', 'password', 'role', 'first_name', 'last_name', 'phone_number', 'sex', 'birthdate'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new PDOException("Missing or empty required field: $field");
        }
    }

    // Check username uniqueness
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    if ($stmt->fetchColumn() > 0) {
        throw new PDOException("Username already exists");
    }

    // Get role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
    $stmt->execute([$_POST['role']]);
    $role_id = $stmt->fetchColumn();

    if (!$role_id) {
        throw new PDOException("Invalid role");
    }

    // Validate phone number (basic format check)
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['phone_number'])) {
        throw new PDOException("Invalid phone number format");
    }

    // Insert into users table
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['username'],
        $password_hash,
        $role_id
    ]);
    
    $user_id = $pdo->lastInsertId();

    // Insert into personal_details table
    $stmt = $pdo->prepare("INSERT INTO personal_details (
        user_id, 
        first_name, 
        middle_name, 
        last_name, 
        sex, 
        birthdate, 
        phone_number
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $_POST['first_name'],
        $_POST['middle_name'] ?? null,
        $_POST['last_name'],
        $_POST['sex'],
        $_POST['birthdate'],
        $_POST['phone_number']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Staff member added successfully']);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Staff Save Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    error_log("Unexpected Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred',
        'details' => $e->getMessage()
    ]);
}
?>