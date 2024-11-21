<?php
require_once 'config.php';

header('Content-Type: application/json'); // Add this line to set JSON content type

if (isset($_GET['id'])) {
    $programId = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT p.*, pt.type_name, c.id as coach_id, 
        c.user_id as coach_user_id, st.status_name,
        pd.first_name, pd.last_name
        FROM programs p 
        JOIN program_types pt ON p.program_type_id = pt.id
        JOIN coaches c ON p.coach_id = c.id 
        JOIN status_types st ON p.status_id = st.id
        JOIN users u ON c.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE p.id = ? AND st.status_name = 'active';");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {
        echo json_encode([
            'success' => true,
            'data' => $program
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Program details not found.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid program ID.'
    ]);
}
?>