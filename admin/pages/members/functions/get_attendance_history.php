<?php
// get_attendance_history.php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "
SELECT 
    u.id AS user_id,
    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
    u.username,
    pp.photo_path,
    a.time_in,
    a.time_out,
    LOWER(ast.status_name) as status,
    a.date
FROM personal_details pd
JOIN users u ON pd.user_id = u.id
LEFT JOIN profile_photos pp ON u.id = pp.user_id
LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURRENT_DATE()
LEFT JOIN attendance_status ast ON a.status_id = ast.id
WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'member')
AND u.is_active = 1;
";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => "Error fetching attendance history: " . $e->getMessage()
        ]);
    }
}
?>