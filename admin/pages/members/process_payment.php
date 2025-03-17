<?php
// Set error reporting before anything else
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once("../../../config.php");
require_once("./functions/members.class.php");

// Ensure JSON response
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
    $pdo = $members->getPdo(); // Get PDO connection from Members class
    $success = true;
    $errors = [];

    try {
        $pdo->beginTransaction();

        foreach ($payments as $payment) {
            try {
                if (!isset($payment['type']) || !isset($payment['id'])) {
                    throw new Exception('Invalid payment data structure');
                }

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
                error_log("Payment processing error: " . $e->getMessage());
                $success = false;
                $errors[] = $e->getMessage();
            }
        }

        if (!$success) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Some payments failed: ' . implode(', ', $errors)
            ]);
        } else {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'All payments processed successfully'
            ]);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (Exception $e) {
    error_log("Process payment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
