<?php
header('Content-Type: application/json');
require_once '../../config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Start transaction
    $pdo->beginTransaction();

    // Update expired memberships
    $expiredQuery = "
        UPDATE memberships m
        INNER JOIN transactions t ON m.transaction_id = t.id
        SET m.status = 'expired'
        WHERE m.end_date < CURDATE() 
        AND m.status != 'expired'
        AND t.status = 'confirmed'
        AND m.is_paid = 1";
    $pdo->exec($expiredQuery);

    // Update expiring memberships (within 7 days of expiration)
    $expiringQuery = "
        UPDATE memberships m
        INNER JOIN transactions t ON m.transaction_id = t.id
        SET m.status = 'expiring'
        WHERE m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND m.status != 'expired'
        AND m.status != 'expiring'
        AND t.status = 'confirmed'
        AND m.is_paid = 1";
    $pdo->exec($expiringQuery);

    // Update active memberships
    $activeQuery = "
        UPDATE memberships m
        INNER JOIN transactions t ON m.transaction_id = t.id
        SET m.status = 'active'
        WHERE m.end_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND m.status != 'expired'
        AND m.status != 'expiring'
        AND t.status = 'confirmed'
        AND m.is_paid = 1";
    $pdo->exec($activeQuery);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Membership statuses updated successfully'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error updating membership statuses: ' . $e->getMessage()
    ]);
}
