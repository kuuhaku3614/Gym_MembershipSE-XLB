<?php
// get_staff_details.php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        throw new Exception('Staff ID is required');
    }
    
    $database = new Database();
    $pdo = $database->connect();
    
    $query = "SELECT 
        u.id, 
        u.username, 
        r.role_name as role,
        pd.first_name, 
        pd.middle_name, 
        pd.last_name, 
        pd.sex,
        pd.birthdate,
        pd.phone_number
      FROM users u
      LEFT JOIN personal_details pd ON u.id = pd.user_id
      JOIN roles r ON u.role_id = r.id
      WHERE u.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        throw new Exception('Staff not found');
    }
    
    echo json_encode(['success' => true, 'data' => $staff]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>