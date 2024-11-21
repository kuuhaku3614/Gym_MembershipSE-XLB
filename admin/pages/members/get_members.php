<?php
error_log("get_members.php accessed at: " . date('Y-m-d H:i:s'));
error_log("Current directory: " . __DIR__);

require_once '../../../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $dsn = "mysql:host=localhost;dbname=gym_managementdb;charset=utf8mb4";
    $username = "root";
    $password = "";
    
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Updated query to handle profile photo paths correctly
    $query = "
        SELECT 
            u.id as member_id,
            COALESCE(pp.photo_path, '../uploads/default.png') as photo_path,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
            mp.plan_name,
            DATE_FORMAT(m.start_date, '%Y-%m-%d') as start_date,
            DATE_FORMAT(m.end_date, '%Y-%m-%d') as end_date,
            CASE 
                WHEN m.end_date < CURDATE() THEN 'Expired'
                WHEN m.status_id = 1 THEN 'Active'
                ELSE 'Inactive'
            END as status
        FROM memberships m
        JOIN users u ON m.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
        LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
        WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'member')
        ORDER BY 
            CASE 
                WHEN m.end_date >= CURDATE() AND m.status_id = 1 THEN 1
                WHEN m.end_date < CURDATE() THEN 2
                ELSE 3
            END,
            m.start_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . implode(" ", $stmt->errorInfo()));
    }
    
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $members ?: []
    ]);
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Database Error: " . $e->getMessage()
    ]);
}
?>