<?php
// Include your database connection file
require_once '../../../../config.php';
session_start(); // Ensure session is started

// Include the activity logger
require_once 'activity_logger.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['newRegistrationFee'])) {
        echo "Error: Registration fee is required";
        exit;
    }
    
    // Use filter_var for proper numeric validation
    $newRegistrationFee = filter_var($_POST['newRegistrationFee'], FILTER_VALIDATE_FLOAT);
    if ($newRegistrationFee === false || $newRegistrationFee <= 0) {
        echo "Error: Please enter a valid positive number for the registration fee";
        exit;
    }

    try {
        // Get the current fee first (for activity log)
        $currentFeeQuery = "SELECT membership_fee FROM registration WHERE id = 1";
        $stmt = $pdo->prepare($currentFeeQuery);
        $stmt->execute();
        $currentFee = $stmt->fetchColumn();
        
        // Prepare the SQL query to update the registration fee
        $sql = "UPDATE registration SET membership_fee = :newFee WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':newFee' => $newRegistrationFee]);

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $description = "Changed registration fee from ₱" . number_format($currentFee, 2) . 
                           " to ₱" . number_format($newRegistrationFee, 2);
            logStaffActivity('Registration Fee Updated', $description);
            
            echo "success";
        } else {
            echo "Error: No changes were made. The fee might be the same as the current fee.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: Database error - " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request to fetch current registration fee
    try {
        $sql = "SELECT membership_fee FROM registration WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $currentFee = $stmt->fetchColumn();
        echo $currentFee !== false ? $currentFee : "Error: Fee not found";
    } catch (PDOException $e) {
        echo "Error: Database error - " . $e->getMessage();
    }
} else {
    echo "Error: Invalid request method";
}
?>