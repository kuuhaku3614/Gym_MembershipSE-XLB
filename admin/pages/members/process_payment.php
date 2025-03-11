<?php
require_once("../../../config.php");
require_once("./functions/members.class.php");

header('Content-Type: application/json');

try {
    if (!isset($_POST['user_id']) || !isset($_POST['payments'])) {
        throw new Exception('Missing required parameters');
    }

    $userId = $_POST['user_id'];
    $payments = json_decode($_POST['payments'], true);

    if (!is_array($payments)) {
        throw new Exception('Invalid payments data');
    }

    $members = new Members();
    $success = true;
    $errors = [];

    foreach ($payments as $payment) {
        try {
            switch ($payment['type']) {
                case 'registration':
                    $result = $members->processRegistrationPayment($payment['id']);
                    break;
                case 'membership':
                    $result = $members->processMembershipPayment($payment['id']);
                    break;
                case 'rental':
                    $result = $members->processRentalPayment($payment['id']);
                    break;
                default:
                    throw new Exception("Invalid payment type: {$payment['type']}");
            }

            if (!$result) {
                $success = false;
                $errors[] = "Failed to process {$payment['type']} payment ID: {$payment['id']}";
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = $e->getMessage();
        }
    }

    if (!$success) {
        echo json_encode([
            'success' => false,
            'message' => 'Some payments failed: ' . implode(', ', $errors)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'All payments processed successfully'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
