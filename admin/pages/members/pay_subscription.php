<?php
require_once 'config.php';  // Database connection

try {
    // Validate user ID
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        throw new Exception('Invalid user ID');
    }

    $userId = $_POST['user_id'];

    // Start transaction
    $pdo->beginTransaction();

    // List of tables with is_paid column
    $tables = [
        'memberships',
        'program_subscriptions',
        'rental_subscriptions'
    ];

    foreach ($tables as $table) {
        $updateQuery = "UPDATE $table 
                        JOIN transactions t ON t.id = $table.transaction_id
                        SET $table.is_paid = 1
                        WHERE t.user_id = :userId AND $table.is_paid = 0";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute(['userId' => $userId]);
    }

    // Commit the transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'All subscriptions paid successfully'
    ]);

} catch (Exception $e) {
    // Rollback in case of error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Return error response with specific error message
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage()
    ]);
}
?>