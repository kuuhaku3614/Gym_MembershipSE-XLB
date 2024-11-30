<?php
// get_attendance_history.php
require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "
SELECT 
    ah.id AS history_id,
    u.id AS user_id,
    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
    u.username,
    a.date,
    ah.time_in,
    ah.time_out,
    LOWER(ast.status_name) AS status,
    ah.created_at AS history_timestamp
FROM attendance_history ah
JOIN attendance a ON ah.attendance_id = a.id
JOIN users u ON a.user_id = u.id
JOIN personal_details pd ON u.id = pd.user_id
LEFT JOIN attendance_status ast ON ah.status_id = ast.id
ORDER BY ah.created_at DESC;
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