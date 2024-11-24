<?php
session_start();
require_once 'cart.class.php';
require_once 'services.class.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Please login to avail services.");
    }

    $Cart = new Cart();
    $Services = new Services_Class();
    
    // Validate cart before proceeding
    $errors = $Cart->validateCart();
    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }
    
    $cart = $Cart->getCart();
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // 1. Create membership record
        $sql = "INSERT INTO memberships (user_id, membership_plan_id, start_date, end_date, 
                total_amount, status) VALUES (?, ?, ?, ?, ?, 'active')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            $cart['membership']['id'],
            $cart['membership']['start_date'],
            $cart['membership']['end_date'],
            $cart['membership']['price']
        ]);
        
        $membership_id = $conn->lastInsertId();
        
        // 2. Create program subscriptions if any
        if (!empty($cart['programs'])) {
            $sql = "INSERT INTO program_subscriptions (membership_id, program_id, coach_id, 
                    start_date, end_date, price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')";
            
            foreach ($cart['programs'] as $program) {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $membership_id,
                    $program['id'],
                    $program['coach_id'],
                    $program['start_date'],
                    $program['end_date'],
                    $program['price']
                ]);
            }
        }
        
        // 3. Create rental subscriptions if any
        if (!empty($cart['rentals'])) {
            $sql = "INSERT INTO rental_subscriptions (membership_id, rental_service_id, 
                    start_date, end_date, price, status) 
                    VALUES (?, ?, ?, ?, ?, 'active')";
            
            foreach ($cart['rentals'] as $rental) {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $membership_id,
                    $rental['id'],
                    $rental['start_date'],
                    $rental['end_date'],
                    $rental['price']
                ]);
                
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
        $stmt->execute([
            $membership_id,
            $cart['total']
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Clear the cart after successful availing
        $Cart->clearCart();
        
        echo json_encode([
            'success' => true,
            'message' => 'Services availed successfully!',
            'membership_id' => $membership_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw new Exception("Failed to avail services: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 