<?php
require_once '../../../../config.php';

if (!isset($_POST['userId'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = $_POST['userId'];

$sql = "
SELECT 
    pd.id AS user_id,
    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
    u.username,
    pp.photo_path
FROM personal_details pd
JOIN users u ON pd.user_id = u.id
LEFT JOIN profile_photos pp ON u.id = pp.user_id
WHERE pd.id = ? AND u.is_active = 1;
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo json_encode(['success' => true, 'user' => $userData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}