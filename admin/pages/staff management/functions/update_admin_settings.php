<?php
// Database connection
require_once '../../../../config.php';  // Adjust the path based on the actual location
$database = new Database();
$pdo = $database->connect();

header('Content-Type: application/json');

// Get current admin ID from session
session_start();
$admin_id = $_SESSION['user_id'] ?? 0;

if (!$admin_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in or session expired'
    ]);
    exit;
}

// Get form data
$current_password = $_POST['current_password'] ?? '';
$new_username = $_POST['new_username'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($current_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Current password is required'
    ]);
    exit;
}

try {
    // First verify current password
    $query = "SELECT password FROM users WHERE id = :id AND role_id = (SELECT id FROM roles WHERE role_name = 'admin')";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin not found or not authorized'
        ]);
        exit;
    }
    
    // Verify password (assuming password is hashed in the database)
    if (!password_verify($current_password, $admin['password'])) {
        // If your app stores passwords differently, modify this verification
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Prepare update query based on what's being updated
    $updates = [];
    $params = [':id' => $admin_id];
    
    if (!empty($new_username)) {
        // Check if username already exists
        $checkQuery = "SELECT id FROM users WHERE username = :username AND id != :id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':username', $new_username);
        $checkStmt->bindParam(':id', $admin_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Username already exists'
            ]);
            exit;
        }
        
        $updates[] = "username = :username";
        $params[':username'] = $new_username;
    }
    
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updates[] = "password = :password";
        $params[':password'] = $hashed_password;
    }
    
    if (empty($updates)) {
        echo json_encode([
            'success' => true,
            'message' => 'No changes were made'
        ]);
        exit;
    }
    
    // Update admin settings
    $updateQuery = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    
    foreach ($params as $key => $value) {
        $updateStmt->bindValue($key, $value);
    }
    
    $updateStmt->execute();
    
    // Log the activity
    $activity = "Admin Settings Updated";
    $description = "Admin updated their account settings";
    
    $logQuery = "INSERT INTO staff_activity_log (staff_id, activity, description) VALUES (:staff_id, :activity, :description)";
    $logStmt = $pdo->prepare($logQuery);
    $logStmt->bindParam(':staff_id', $admin_id);
    $logStmt->bindParam(':activity', $activity);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin settings updated successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>