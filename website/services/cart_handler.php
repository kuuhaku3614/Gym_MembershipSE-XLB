<?php
session_start();
require_once 'cart.class.php';
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$action = $type = $id = '';

// Error variables
$actionErr = $typeErr = $idErr = '';

$Cart = new Cart();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    
    switch ($action) {
        case 'remove':
            $type = clean_input($_POST['type'] ?? '');
            $id = isset($_POST['id']) ? clean_input($_POST['id']) : null;
            
            if (empty($type)) {
                $typeErr = 'Type is required for remove action';
                echo json_encode(['success' => false, 'message' => $typeErr]);
                exit;
            }
            
            if ($id === null) {
                $idErr = 'ID is required for remove action';
                echo json_encode(['success' => false, 'message' => $idErr]);
                exit;
            }
            
            if($Cart->removeItem($type, $id)) {
                echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
            }
            break;
            
        case 'clear':
            if($Cart->clearCart()) {
                echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
            break;
            
        case 'get':
            echo json_encode(['success' => true, 'cart' => $Cart->getCart()]);
            break;
            
        case 'validate':
            $errors = $Cart->validateCart();
            if (empty($errors)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'errors' => $errors]);
            }
            break;
            
        default:
            $actionErr = 'Invalid action';
            echo json_encode(['success' => false, 'message' => $actionErr]);
    }
    exit;
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>