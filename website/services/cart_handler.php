<?php
// Prevent any output before JSON response
ob_start();

// Error handling
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to send JSON response and exit
function send_json_response($data, $status = true) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $status, 'data' => $data]);
    exit;
}

// Handle any errors
function handle_error($message) {
    send_json_response(['message' => $message], false);
}

// Set exception handler
function exception_handler($e) {
    handle_error($e->getMessage());
}
set_exception_handler('exception_handler');

// Required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/sanitize.php';
require_once __DIR__ . '/cart.class.php';
require_once __DIR__ . '/services.class.php';

try {
    // Check session
    if (!isset($_SESSION['user_id'])) {
        handle_error('Not logged in');
    }

    // Get the action
    $input = file_get_contents('php://input');
    $action = '';
    
    if (isset($_POST['action'])) {
        $action = clean_input($_POST['action']);
    } else if (strpos($input, 'action=get') !== false) {
        $action = 'get';
    }
    
    if (empty($action)) {
        handle_error('No action specified');
    }

    // Initialize cart
    $Cart = new Cart();

    // Process action
    switch ($action) {
        case 'get':
            try {
                $cart = $Cart->getCart();
                if (!is_array($cart)) {
                    handle_error('Invalid cart data');
                }
                send_json_response(['cart' => $cart]);
            } catch (Exception $e) {
                handle_error($e->getMessage());
            }
            break;
            
        case 'clear':
            try {
                if ($Cart->clearCart()) {
                    $newCart = $Cart->getCart();
                    send_json_response(['cart' => $newCart]);
                } else {
                    handle_error('Failed to clear cart');
                }
            } catch (Exception $e) {
                handle_error($e->getMessage());
            }
            break;
            
        case 'remove':
            try {
                $type = isset($_POST['type']) ? clean_input($_POST['type']) : '';
                $id = isset($_POST['id']) ? clean_input($_POST['id']) : '';
                
                if ($type && $id !== '') {
                    if ($Cart->removeItem($type, $id)) {
                        send_json_response(['cart' => $Cart->getCart()]);
                    } else {
                        handle_error('Failed to remove item');
                    }
                } else {
                    handle_error('Invalid parameters');
                }
            } catch (Exception $e) {
                handle_error($e->getMessage());
            }
            break;
            
        case 'validate':
            try {
                $Cart->validateCart();
                send_json_response(['showConfirm' => true]);
            } catch (Exception $e) {
                send_json_response(['errors' => true, 'message' => $e->getMessage()], false);
            }
            break;
            
        default:
            handle_error('Invalid action');
    }
} catch (Exception $e) {
    handle_error($e->getMessage());
}

// If we somehow get here, send an error
handle_error('Unknown error occurred');
?>