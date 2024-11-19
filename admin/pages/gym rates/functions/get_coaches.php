<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

if (isset($_POST['programTypeId'])) {
    $programTypeId = $_POST['programTypeId'];
    $coachSql = "
        SELECT 
            c.id AS coach_id, 
            CONCAT(pd.first_name, ' ', pd.last_name) AS coach_name, 
            pd.phone_number 
        FROM coaches c
        JOIN users u ON c.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        JOIN coach_program_types cpt ON c.id = cpt.coach_id
        WHERE cpt.program_type_id = :programTypeId
    ";
    $coachStmt = $pdo->prepare($coachSql);
    $coachStmt->execute(['programTypeId' => $programTypeId]);
    echo json_encode($coachStmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
