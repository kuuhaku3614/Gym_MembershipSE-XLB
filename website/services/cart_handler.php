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
    // // Check session
    // if (!isset($_SESSION['user_id'])) {
    //     handle_error('Not logged in');
    // }

    // Get the action
    $input = file_get_contents('php://input');
    $action = '';
    $data = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = clean_input($_POST['action']);
        } else {
            $data = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $action = clean_input($data['action'] ?? '');
            }
        }
    } else if (strpos($input, 'action=get') !== false) {
        $action = 'get';
    }
    
    if (empty($action)) {
        handle_error('No action specified');
    }

    // Initialize cart
    $Cart = new Cart_Class();

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
            // Check for data in both $_POST and JSON input
            $type = null;
            $index = null;
            
            if (isset($_POST['type'], $_POST['index'])) {
                $type = clean_input($_POST['type']);
                $index = clean_input($_POST['index']);
            } else if (isset($data['type'], $data['index'])) {
                $type = clean_input($data['type']);
                $index = clean_input($data['index']);
            }
            
            if ($type === null || $index === null) {
                handle_error('Missing type or index');
            }

            switch ($type) {
                case 'membership':
                    $success = $Cart->removeItem($type, $index);
                    break;
                case 'rental':
                    $success = $Cart->removeItem($type, $index);
                    break;
                case 'program':
                    $success = $Cart->removeProgram($index);
                    break;
                case 'walkin':
                    $success = $Cart->removeWalkin($index);
                    break;
                default:
                    handle_error('Invalid item type');
            }

            if ($success) {
                send_json_response(['success' => true]);
            } else {
                handle_error('Failed to remove item');
            }
            break;

        case 'add_program_schedule':
            if (!isset($data['schedule_id'], $data['day'], $data['start_time'], 
                      $data['end_time'], $data['price'], $data['program_name'], $data['coach_name'])) {
                send_json_response(['message' => 'Missing required schedule data'], false);
            }

            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                send_json_response(['message' => 'Please log in to add programs to cart.'], false);
            }

            // Check for active membership or membership in cart
            $Services = new Services_Class();
            $activeMembership = $Services->checkActiveMembership($_SESSION['user_id']);
            $hasMembershipInCart = $Cart->hasMembershipInCart();

            // Determine if this is a personal schedule by checking the database
            $isPersonal = $Services->isPersonalSchedule($data['schedule_id']);
            if (!$activeMembership && !$hasMembershipInCart) {
                send_json_response(['message' => 'You need to have an active membership or include a membership plan in your cart to avail this program.'], false);
            }

            // Add membership start date to item for program date calculation
            if ($activeMembership) {
                $item['membership_start_date'] = $activeMembership['start_date'];
            } else if ($hasMembershipInCart) {
                $cart = $Cart->getCart();
                $item['membership_start_date'] = $cart['memberships'][0]['start_date'];
            }

            // Add to cart
            $item = [
                'schedule_id' => $data['schedule_id'],
                'program_name' => $data['program_name'],
                'coach_name' => $data['coach_name'],
                'day' => $data['day'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'price' => $data['price'],
                'is_personal' => $isPersonal
            ];


            try {
                if ($Cart->addProgramSchedule($item)) {
                    send_json_response(['message' => 'Schedule added to cart']);
                } else {
                    send_json_response(['message' => 'Failed to add schedule to cart'], false);
                }
            } catch (Exception $e) {
                send_json_response(['message' => $e->getMessage()], false);
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