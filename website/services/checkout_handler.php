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
        // Begin transaction
        $conn->beginTransaction();
        
        // 1. Create membership records - now handling multiple memberships
        foreach ($cart['memberships'] as $membership) {
            $sql = "INSERT INTO memberships (user_id, membership_plan_id, staff_id, start_date, end_date, 
                    total_amount, status) VALUES (?, ?, NULL, ?, ?, ?, 'active')";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt->execute([
                $_SESSION['user_id'],
                $membership['id'],
                $membership['start_date'],
                $membership['end_date'],
                $membership['price']
            ])) {
                throw new Exception("Failed to create membership record");
            }
            
            $membership_id = $conn->lastInsertId();
            
            // 2. Create program subscriptions if any
            if (!empty($cart['programs'])) {
                $sql = "INSERT INTO program_subscriptions (membership_id, program_id, coach_id, staff_id,
                        start_date, end_date, price, status) 
                        VALUES (?, ?, ?, NULL, ?, ?, ?, 'active')";
                
                foreach ($cart['programs'] as $program) {
                    // Verify coach exists and is active
                    $verify_coach = "SELECT id FROM users 
                                    WHERE id = ? AND is_active = 1 
                                    AND role_id = (SELECT id FROM roles WHERE role_name = 'coach')";
                    $stmt = $conn->prepare($verify_coach);
                    $stmt->execute([$program['coach_id']]);
                    if (!$stmt->fetch()) {
                        throw new Exception("Selected coach is no longer available");
                    }

                    $stmt = $conn->prepare($sql);
                    if (!$stmt->execute([
                        $membership_id,
                        $program['id'],
                        $program['coach_id'],
                        $program['start_date'],
                        $program['end_date'],
                        $program['price']
                    ])) {
                        throw new Exception("Failed to create program subscription");
                    }
                }
            }
            
            // 3. Create rental subscriptions if any
            if (!empty($cart['rentals'])) {
                $sql = "INSERT INTO rental_subscriptions (membership_id, rental_service_id, staff_id,
                        start_date, end_date, price, status) 
                        VALUES (?, ?, NULL, ?, ?, ?, 'active')";
                
                foreach ($cart['rentals'] as $rental) {
                    $stmt = $conn->prepare($sql);
                    if (!$stmt->execute([
                        $membership_id,
                        $rental['id'],
                        $rental['start_date'],
                        $rental['end_date'],
                        $rental['price']
                    ])) {
                        throw new Exception("Failed to create rental subscription");
                    }
                    
                    // Update available slots for rental services
                    $update_sql = "UPDATE rental_services 
                                 SET available_slots = available_slots - 1 
                                 WHERE id = ? AND available_slots >= 1";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->execute([$rental['id']]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("Not enough available slots for rental service: " . $rental['name']);
                    }
                }
            }
            
            // 4. Create transaction record
            $sql = "INSERT INTO transactions (membership_id, total_amount) 
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt->execute([
                $membership_id,
                $cart['total']
            ])) {
                throw new Exception("Failed to create transaction record");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear the cart after successful checkout
        $Cart->clearCart();
        
        echo json_encode([
            'success' => true,
            'message' => 'Checkout completed successfully!',
            'redirect' => '../profile.php'
        ]);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}
?> 