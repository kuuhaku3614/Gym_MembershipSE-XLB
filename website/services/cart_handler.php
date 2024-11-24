<?php
session_start();
require_once 'cart.class.php';

header('Content-Type: application/json');

try {
    $Cart = new Cart();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'remove':
                $type = $_POST['type'] ?? '';
                $id = $_POST['id'] ?? '';
                if ($type && $id) {
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
                
            case 'update_quantity':
                $rental_id = $_POST['rental_id'] ?? '';
                $quantity = $_POST['quantity'] ?? '';
                if ($rental_id && $quantity) {
                    $Cart->updateRentalQuantity($rental_id, $quantity);
                    echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
                } else {
                    throw new Exception('Missing rental_id or quantity for update');
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
        'message' => $e->getMessage(),
        'debug' => [
            'action' => $_POST['action'] ?? null,
            'post_data' => $_POST,
            'session' => isset($_SESSION['cart']) ? 'exists' : 'not set'
        ]
    ]);
} 