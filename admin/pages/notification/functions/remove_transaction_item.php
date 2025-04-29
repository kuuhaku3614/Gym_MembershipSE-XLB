<?php
require_once(__DIR__ . '/../../../../config.php');
require_once('notifications.class.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$type = $_POST['type'] ?? '';
$transactionId = $_POST['transaction_id'] ?? '';
$recordId = $_POST['record_id'] ?? '';

if (!$type || !$transactionId || !$recordId || !is_numeric($recordId)) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $db->beginTransaction();
    if ($type === 'membership') {
        $stmt = $db->prepare("DELETE FROM memberships WHERE id = ? AND transaction_id = ? LIMIT 1");
        $stmt->execute([$recordId, $transactionId]);
    } elseif ($type === 'rental') {
        $stmt = $db->prepare("DELETE FROM rental_subscriptions WHERE id = ? AND transaction_id = ? LIMIT 1");
        $stmt->execute([$recordId, $transactionId]);
    } elseif ($type === 'walkin') {
        $stmt = $db->prepare("DELETE FROM walk_in_records WHERE id = ? AND transaction_id = ? LIMIT 1");
        $stmt->execute([$recordId, $transactionId]);
    } else {
        throw new Exception('Invalid type.');
    }
    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
