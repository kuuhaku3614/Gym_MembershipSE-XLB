<?php
require_once(__DIR__ . '/../../../../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $success = false;
    $error = '';

    if ($transaction_id && ($type === 'overdue_pending_membership' || $type === 'overdue_pending_walkin')) {
        try {
            $pdo->beginTransaction();
            if ($type === 'overdue_pending_membership') {
                // Only delete the membership record
                $stmt = $pdo->prepare('DELETE FROM memberships WHERE transaction_id = ?');
                $stmt->execute([$transaction_id]);
            } elseif ($type === 'overdue_pending_walkin') {
                // Only delete the walk-in record
                $stmt = $pdo->prepare('DELETE FROM walk_in_records WHERE transaction_id = ?');
                $stmt->execute([$transaction_id]);
            }
            // Do NOT delete the transaction itself
            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid request';
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'error' => $error]);
    exit;
}
