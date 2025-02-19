<?php
// update_staff.php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->beginTransaction();

    // Validate required fields
    $requiredFields = ['id', 'username', 'role', 'first_name', 'last_name', 'phone_number', 'sex', 'birthdate'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new PDOException("Missing or empty required field: $field");
        }
    }

    // Check if username exists (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$_POST['username'], $_POST['id']]);
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

    // Validate phone number
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['phone_number'])) {
        throw new PDOException("Invalid phone number format");
    }

    // Update users table
    if (!empty($_POST['password'])) {
        // Update with new password
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['username'],
            $password_hash,
            $role_id,
            $_POST['id']
        ]);
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['username'],
            $role_id,
            $_POST['id']
        ]);
    }

    // Update personal_details table
    $stmt = $pdo->prepare("UPDATE personal_details SET 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?, 
        sex = ?, 
        birthdate = ?, 
        phone_number = ?
        WHERE user_id = ?");
    $stmt->execute([
        $_POST['first_name'],
        $_POST['middle_name'] ?? null,
        $_POST['last_name'],
        $_POST['sex'],
        $_POST['birthdate'],
        $_POST['phone_number'],
        $_POST['id']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Staff member updated successfully']);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Staff Update Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
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