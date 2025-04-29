<?php
// Prevent any output before JSON response
ob_start();

// Error handling
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'cart.class.php';
require_once 'services.class.php';

// Function to send JSON response
function send_json_response($data, $status = true) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $status, 'data' => $data]);
    exit();
}

// Handle any uncaught errors
function exception_handler($e) {
    send_json_response(['message' => 'An error occurred: ' . $e->getMessage()], false);
}
set_exception_handler('exception_handler');

if (!isset($_SESSION['user_id'])) {
    send_json_response(['message' => 'Not logged in'], false);
}

$Cart = new Cart_Class();
$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate cart before proceeding
        $Cart->validateCart(); // This will throw an exception if validation fails
        
        $cart = $Cart->getCart();
        if (empty($cart)) {
            throw new Exception('Checkout-list is empty');
        }

        $db = new Database();
        $conn = $db->connect();
        
        $conn->beginTransaction();

        // First create the transaction record
        $sql = "INSERT INTO transactions (staff_id, user_id, status) VALUES (NULL, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $transaction_id = $conn->lastInsertId();

        // If there's a registration fee, record it
        if (isset($cart['registration_fee']) && $cart['registration_fee'] !== null) {
            $sql = "INSERT INTO registration_records (transaction_id, registration_id, amount) VALUES (?, 1, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$transaction_id, $cart['registration_fee']['price']]);
        }
        
        // Then create membership records
        foreach ($cart['memberships'] as $membership) {
            // Get membership plan price
            $sql = "SELECT price FROM membership_plans WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$membership['id']]);
            $plan_price = $stmt->fetchColumn();

            $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, 
                    start_date, end_date, status, is_paid, amount) 
                    VALUES (?, ?, ?, ?, 'active', 0, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $transaction_id,
                $membership['id'],
                $membership['start_date'],
                $membership['end_date'],
                $plan_price
            ]);
        }

        // Create program subscriptions
        if (!empty($cart['programs'])) {
            // Group programs by coach_program_type_id
            $programsByType = [];
            foreach ($cart['programs'] as $program) {
                // Get the coach_program_type_id from the appropriate schedule table
                if ($program['is_personal']) {
                    $sql = "SELECT coach_program_type_id FROM coach_personal_schedule WHERE id = ?";
                } else {
                    $sql = "SELECT coach_program_type_id FROM coach_group_schedule WHERE id = ?";
                }
                $stmt = $conn->prepare($sql);
                $stmt->execute([$program['schedule_id']]);
                $coachProgramType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$coachProgramType) {
                    throw new Exception("Invalid schedule ID");
                }

                $typeId = $coachProgramType['coach_program_type_id'];

                if (!isset($programsByType[$typeId])) {
                    $programsByType[$typeId] = [
                        'type_id' => $typeId,
                        'is_personal' => $program['is_personal'],
                        'sessions' => []
                    ];
                }
                $programsByType[$typeId]['sessions'][] = $program;
            }


            // Create one subscription per coach_program_type_id
            foreach ($programsByType as $typeData) {
                // Create the program subscription
                $sql = "INSERT INTO program_subscriptions (user_id, coach_program_type_id, status, transaction_id) 
                        VALUES (?, ?, 'pending', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $_SESSION['user_id'],
                    $typeData['type_id'],
                    $transaction_id
                ]);
                
                $program_subscription_id = $conn->lastInsertId();
                
                // Create schedule entries for all sessions of this type
                $sql = "INSERT INTO program_subscription_schedule 
                        (program_subscription_id, coach_group_schedule_id, coach_personal_schedule_id, 
                         date, day, start_time, end_time, amount, is_paid) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
                
                $stmt = $conn->prepare($sql);
                foreach ($typeData['sessions'] as $session) {
                    $stmt->execute([
                        $program_subscription_id,
                        $typeData['is_personal'] ? null : $session['schedule_id'],
                        $typeData['is_personal'] ? $session['schedule_id'] : null,
                        $session['session_date'],
                        $session['day'],
                        date('H:i', strtotime($session['start_time'])), // Convert to 24-hour format for database
                        date('H:i', strtotime($session['end_time'])), // Convert to 24-hour format for database
                        $session['price']
                    ]);
                }
            }
        }

        // Create rental subscriptions
        if (!empty($cart['rentals'])) {
            foreach ($cart['rentals'] as $rental) {
                // Get rental service price
                $sql = "SELECT price FROM rental_services WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$rental['id']]);
                $rental_price = $stmt->fetchColumn();

                $sql = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id, 
                        start_date, end_date, status, is_paid, amount) 
                        VALUES (?, ?, ?, ?, 'active', 0, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $transaction_id,
                    $rental['id'],
                    $rental['start_date'],
                    $rental['end_date'],
                    $rental_price
                ]);

                // Update available slots
                $sql = "UPDATE rental_services SET available_slots = available_slots - 1 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$rental['id']]);
            }
        }

        // Create walk-in records
        if (!empty($cart['walkins'])) {
            foreach ($cart['walkins'] as $walkin) {
                // Get personal details for the user including middle name
                $sql = "SELECT first_name, middle_name, last_name, phone_number FROM personal_details WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                $user_details = $stmt->fetch();
                
                $sql = "INSERT INTO walk_in_records (transaction_id, walk_in_id, first_name, middle_name, last_name,
                        phone_number, date, amount, is_paid, status)
                        VALUES (?, 1, ?, ?, ?, ?, ?, ?, 0, 'pending')";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $transaction_id,
                    $user_details['first_name'],
                    $user_details['middle_name'],
                    $user_details['last_name'],
                    $user_details['phone_number'],
                    $walkin['date'],
                    $walkin['price']
                ]);
            }
        }

        $conn->commit();

        // Clear the cart after successful transaction
        $Cart->clearCart();

        send_json_response(['message' => 'Services availed successfully!']);

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Checkout error: " . $e->getMessage());
        send_json_response(['message' => 'An error occurred while processing your request. Please try again.'], false);
    }
}
?>