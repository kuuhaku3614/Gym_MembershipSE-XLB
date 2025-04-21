<?php
// delete_staff.php - with activity logging added
header('Content-Type: application/json');
require_once 'config.php';

// Include activity logger
require_once 'activity_logger.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->beginTransaction();

    // Get staff details for logging before deletion
    $stmt = $pdo->prepare("
        SELECT u.username, pd.first_name, pd.last_name, r.role_name as role
        FROM users u
        JOIN personal_details pd ON u.id = pd.user_id
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_POST['id']]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    // Due to ON DELETE CASCADE, related records in personal_details and coaches
    // tables will be automatically deleted

    // Log the activity
    if ($staffData) {
        $staffName = $staffData['first_name'] . ' ' . $staffData['last_name'];
        $activityDescription = "Deleted staff member: {$staffName} ({$staffData['username']}) with role {$staffData['role']}";
        logStaffActivity("Delete Staff", $activityDescription);
    } else {
        logStaffActivity("Delete Staff", "Deleted staff member with ID: {$_POST['id']} (details not available)");
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>