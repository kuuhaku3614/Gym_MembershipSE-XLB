<?php
session_start();
require_once 'cart.class.php';
require_once '../../config.php';

header('Content-Type: application/json');
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

try {
    $Cart = new Cart();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'remove':
                $type = $_POST['type'] ?? '';
                $id = isset($_POST['id']) ? $_POST['id'] : null;
                
                if ($type !== '' && $id !== null) {
                    $Cart->removeItem($type, $id);
                    echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
                } else {
                    throw new Exception('Missing type or id for remove action');
                }
                break;
                
            case 'get':
                echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
                break;
                
            case 'clear':
                $Cart->clearCart();
                echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
                break;
                
            case 'validate':
                $errors = $Cart->validateCart();
                if (empty($errors)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'errors' => $errors
                    ]);
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit();