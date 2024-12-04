<?php
require_once 'config.php';  // Database connection

try {
    // Start transaction
    $pdo->beginTransaction();

    // Update membership is_paid only if transaction exists
    $membershipQuery = "UPDATE memberships ms
                        JOIN transactions t ON ms.transaction_id = t.id
                        SET ms.is_paid = 1
                        WHERE t.user_id = :userId AND ms.is_paid = 0 
                        AND EXISTS (SELECT 1 FROM transactions WHERE user_id = :userId)";
    $stmt = $pdo->prepare($membershipQuery);
    $stmt->execute(['userId' => $userId]);

    // Update program subscriptions is_paid only if records exist
    $programQuery = "UPDATE program_subscriptions ps
                     JOIN transactions t ON ps.transaction_id = t.id
                     SET ps.is_paid = 1
                     WHERE t.user_id = :userId AND ps.is_paid = 0
                     AND EXISTS (SELECT 1 FROM program_subscriptions WHERE transaction_id IN (SELECT id FROM transactions WHERE user_id = :userId))";
    $stmt = $pdo->prepare($programQuery);
    $stmt->execute(['userId' => $userId]);

    // Update rental subscriptions is_paid only if records exist
    $rentalQuery = "UPDATE rental_subscriptions rs
                    JOIN transactions t ON rs.transaction_id = t.id
                    SET rs.is_paid = 1
                    WHERE t.user_id = :userId AND rs.is_paid = 0
                    AND EXISTS (SELECT 1 FROM rental_subscriptions WHERE transaction_id IN (SELECT id FROM transactions WHERE user_id = :userId))";
    $stmt = $pdo->prepare($rentalQuery);
    $stmt->execute(['userId' => $userId]);

    // Commit the transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'All applicable subscriptions paid successfully'
    ]);

} catch (Exception $e) {
    // Rollback in case of error
    $pdo->rollBack();

    // Return error response with specific error message
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage()
    ]);
}
?>