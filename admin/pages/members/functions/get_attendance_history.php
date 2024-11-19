<?php
// get_attendance_history.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "
            SELECT 
                ah.id,
                CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
                a.date,
                a.time_in,
                a.time_out,
                ast.status_name as status
            FROM attendance_history ah
            JOIN attendance a ON ah.attendance_id = a.id
            JOIN personal_details pd ON a.user_id = pd.id
            JOIN attendance_status ast ON ah.status_id = ast.id
            ORDER BY a.date DESC, a.time_in DESC;
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