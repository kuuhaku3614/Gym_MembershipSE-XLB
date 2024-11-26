<?php
session_start();
require_once 'services.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize error variables
$membership_plan_idErr = $start_dateErr = $end_dateErr = $total_amountErr = '';
$program_idErr = $coach_idErr = '';
$rental_idErr = '';

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $membership_plan_id = clean_input($_POST['membership_plan_id'] ?? '');
    $start_date = clean_input($_POST['start_date'] ?? '');
    $end_date = clean_input($_POST['end_date'] ?? '');
    $total_amount = clean_input($_POST['total_amount'] ?? '');
    
    // Validate membership inputs
    if(empty($membership_plan_id)) {
        $membership_plan_idErr = 'Membership plan is required';
    }
    if(empty($start_date)) {
        $start_dateErr = 'Start date is required';
    }
    if(empty($end_date)) {
        $end_dateErr = 'End date is required';
    }
    if(empty($total_amount)) {
        $total_amountErr = 'Total amount is required';
    }

    // If no membership errors, proceed
    if(empty($membership_plan_idErr) && empty($start_dateErr) && 
       empty($end_dateErr) && empty($total_amountErr)) {
        
        // Save membership
        if ($Services->saveMembership($_SESSION['user_id'], $membership_plan_id, 
                                    $start_date, $end_date, $total_amount)) {
            $membership_id = $conn->lastInsertId();
            
            // Save programs if any
            if (!empty($_POST['programs'])) {
                foreach ($_POST['programs'] as $program) {
                    // Validate program inputs
                    if(empty($program['id'])) {
                        $program_idErr = 'Program is required';
                        break;
                    }
                    if(empty($program['coach_id'])) {
                        $coach_idErr = 'Coach is required';
                        break;
                    }

                    if(empty($program_idErr) && empty($coach_idErr)) {
                        if (!$Services->saveProgram(
                            $membership_id,
                            $program['id'],
                            $program['coach_id'],
                            $program['start_date'],
                            $program['end_date'],
                            $program['price']
                        )) {
                            $_SESSION['error'] = "Failed to save program";
                            header("Location: ../services.php");
                            exit();
                        }
                    }
                }
            }
            
            // Save rentals if any
            if (!empty($_POST['rentals'])) {
                foreach ($_POST['rentals'] as $rental) {
                    // Validate rental inputs
                    if(empty($rental['id'])) {
                        $rental_idErr = 'Rental service is required';
                        break;
                    }

                    if(empty($rental_idErr)) {
                        if (!$Services->saveRental(
                            $membership_id,
                            $rental['id'],
                            $rental['start_date'],
                            $rental['end_date'],
                            $rental['price']
                        )) {
                            $_SESSION['error'] = "Failed to save rental";
                            header("Location: ../services.php");
                            exit();
                        }
                    }
                }
            }
            
            $_SESSION['success'] = "Services availed successfully!";
            header("Location: avail_success.php?id=" . $membership_id);
            exit();
        } else {
            $_SESSION['error'] = "Failed to save membership";
            header("Location: ../services.php");
            exit();
        }
    } else {
        // Set error messages
        if($membership_plan_idErr) $_SESSION['error'] = $membership_plan_idErr;
        if($start_dateErr) $_SESSION['error'] = $start_dateErr;
        if($end_dateErr) $_SESSION['error'] = $end_dateErr;
        if($total_amountErr) $_SESSION['error'] = $total_amountErr;
        header("Location: ../services.php");
        exit();
    }
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?> 