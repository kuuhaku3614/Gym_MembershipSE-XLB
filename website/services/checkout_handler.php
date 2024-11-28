<?php
session_start();
require_once 'cart.class.php';
require_once 'services.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$membership_id = '';
$total_amount = '';

// Error variables
$membershipErr = $programErr = $rentalErr = '';

$Cart = new Cart();
$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate cart before proceeding
    $errors = $Cart->validateCart();
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
        exit();
    }
    
    $cart = $Cart->getCart();
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $conn->beginTransaction();
        
        // First create the transaction record
        $sql = "INSERT INTO transactions (staff_id, user_id) VALUES (NULL, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $transaction_id = $conn->lastInsertId();
        
        // Check if the user has an active membership
        $hasActiveMembership = $Services->checkActiveMembership($_SESSION['user_id']);
        
        // Then create membership records
        foreach ($cart['memberships'] as $membership) {
            $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, 
                    start_date, end_date, status, is_paid) 
                    VALUES (?, ?, ?, ?, 'active', 0)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $transaction_id,
                $membership['id'],
                $membership['start_date'],
                $membership['end_date']
            ]);
            
            $membership_id = $conn->lastInsertId();
        }

        // Create program subscriptions
        if (!empty($cart['programs'])) {
            foreach ($cart['programs'] as $program) {
                $sql = "INSERT INTO program_subscriptions (transaction_id, program_id, 
                        coach_id, start_date, end_date, status, is_paid) 
                        VALUES (?, ?, ?, ?, ?, 'active', 0)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $transaction_id,
                    $program['id'],
                    $program['coach_id'],
                    $program['start_date'],
                    $program['end_date']
                ]);
            }
        }

        // Create rental subscriptions
        if (!empty($cart['rentals'])) {
            foreach ($cart['rentals'] as $rental) {
                // Check if the rental is included in the membership plan or program
                $sql = "SELECT * FROM memberships m 
                        JOIN membership_plans mp ON m.membership_plan_id = mp.id 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = ? AND mp.id = ? AND m.status = 'active'";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $rental['id']]);
                $rental_included = $stmt->fetch();

                // If the user does not have an active membership, check if they are availing a membership or program
                if (!$hasActiveMembership && !$rental_included) {
                    $_SESSION['error'] = "You can only avail rentals included in your membership plan or program.";
                    header("Location: ../services.php");
                    exit();
                }

                // Proceed to insert rental subscription
                $sql = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id,
                        start_date, end_date, status, is_paid) 
                        VALUES (?, ?, ?, ?, 'active', 0)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $transaction_id,
                    $rental['id'],
                    $rental['start_date'],
                    $rental['end_date']
                ]);
                
                // Update available slots for rental services
                $sql = "UPDATE rental_services 
                       SET available_slots = available_slots - 1 
                       WHERE id = ? AND available_slots > 0";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$rental['id']]);
            }
        }
        
        $conn->commit();
        $Cart->clearCart();
        
        echo json_encode([
            'success' => true,
            'message' => 'Checkout completed successfully!',
            'redirect' => 'avail_success.php?id=' . $transaction_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 