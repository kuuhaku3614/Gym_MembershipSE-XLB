<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $query = "
        SELECT 
            u.id AS member_id, 
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
            mp.plan_name,
            m.start_date,
            m.end_date,
            st.status_name AS status,
            pp.photo_path
        FROM users u
        JOIN personal_details pd ON u.id = pd.user_id
        JOIN memberships m ON u.id = m.user_id
        JOIN membership_plans mp ON m.membership_plan_id = mp.id
        JOIN status_types st ON m.status_id = st.id
        LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = TRUE
        WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'member')
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $members
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}