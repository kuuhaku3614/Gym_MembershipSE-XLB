<?php
// get_attendance_history.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "
            SELECT 
                ah.id,
                CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS full_name,
                ah.date,
                ah.time_in,
                ah.time_out,
                ah.status
            FROM attendance_history ah
            JOIN personal_details pd ON ah.user_id = pd.id
            ORDER BY ah.date DESC, ah.time_in DESC
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