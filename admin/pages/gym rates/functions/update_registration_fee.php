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

    // Get duration type
    $durationType = $_POST['durationType'] ?? 'specific';
    $duration = 0;
    $durationTypeId = null;
    
    // Handle lifetime vs specific duration
    if ($durationType === 'lifetime') {
        // For lifetime, set duration to 0 and get the lifetime duration type ID
        $durationTypeIdQuery = "SELECT id FROM duration_types WHERE type_name = 'lifetime'";
        $stmt = $pdo->prepare($durationTypeIdQuery);
        $stmt->execute();
        $durationTypeId = $stmt->fetchColumn();
    } else {
        // Validate specific duration
        if (empty($_POST['durationValue']) || empty($_POST['durationTypeId'])) {
            echo "Error: Duration value and type are required for specific duration";
            exit;
        }
        
        $duration = filter_var($_POST['durationValue'], FILTER_VALIDATE_INT);
        if ($duration === false || $duration <= 0) {
            echo "Error: Please enter a valid positive number for the duration";
            exit;
        }
        
        $durationTypeId = filter_var($_POST['durationTypeId'], FILTER_VALIDATE_INT);
        if ($durationTypeId === false) {
            echo "Error: Invalid duration type";
            exit;
        }
    }

    try {
        // Get the current fee first (for activity log)
        $currentFeeQuery = "SELECT r.membership_fee, r.duration, dt.type_name 
                            FROM registration r
                            LEFT JOIN duration_types dt ON r.duration_type_id = dt.id
                            WHERE r.id = 1";
        $stmt = $pdo->prepare($currentFeeQuery);
        $stmt->execute();
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prepare the SQL query to update the registration fee and duration
        $sql = "UPDATE registration 
                SET membership_fee = :newFee,
                    duration = :duration,
                    duration_type_id = :durationTypeId
                WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':newFee' => $newRegistrationFee,
            ':duration' => $duration,
            ':durationTypeId' => $durationTypeId
        ]);

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            // Get the new duration type name for logging
            $newDurationQuery = "SELECT type_name FROM duration_types WHERE id = :id";
            $stmt = $pdo->prepare($newDurationQuery);
            $stmt->execute([':id' => $durationTypeId]);
            $newDurationType = $stmt->fetchColumn();
            
            // Format the duration description
            $oldDurationDesc = empty($currentData['type_name']) ? "no duration" : 
                ($currentData['type_name'] === 'lifetime' ? 'lifetime' : 
                "{$currentData['duration']} {$currentData['type_name']}");
                
            $newDurationDesc = $durationType === 'lifetime' ? 'lifetime' : "$duration $newDurationType";
            
            // Log the activity
            $description = "Changed registration fee from ₱" . number_format($currentData['membership_fee'], 2) . 
                           " to ₱" . number_format($newRegistrationFee, 2) . 
                           " and duration from $oldDurationDesc to $newDurationDesc";
            logStaffActivity('Registration Fee Updated', $description);
            
            echo "success";
        } else {
            echo "Error: No changes were made. The fee and duration might be the same as the current values.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: Database error - " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request to fetch current registration fee and duration
    try {
        $sql = "SELECT r.membership_fee, r.duration, dt.id as duration_type_id, dt.type_name 
                FROM registration r
                LEFT JOIN duration_types dt ON r.duration_type_id = dt.id
                WHERE r.id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            // Return as JSON
            $response = [
                'status' => 'success',
                'fee' => $data['membership_fee'],
                'duration' => $data['duration'],
                'durationTypeId' => $data['duration_type_id'],
                'durationTypeName' => $data['type_name'],
                'durationType' => $data['type_name'] === 'lifetime' ? 'lifetime' : 'specific'
            ];
            echo json_encode($response);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Registration fee data not found'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error - ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>