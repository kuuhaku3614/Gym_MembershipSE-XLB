<?php
require_once '../../../../config.php';

// Function to fetch gym rate details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        $sql = "SELECT mp.*, dt.type_name as duration_type 
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                WHERE mp.id = :id AND mp.is_removed = 0";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $gymRate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($gymRate) {
            echo json_encode(['status' => 'success', 'data' => $gymRate]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gym rate not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to update gym rate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        // Validate required fields
        $requiredFields = ['id', 'promoName', 'promoType', 'duration', 'durationType', 'activationDate', 'deactivationDate', 'price'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
            exit;
        }
        
        try {
            // Get duration_type_id
            $durationTypeQuery = "SELECT id FROM duration_types WHERE type_name = :type_name";
            $stmt = $pdo->prepare($durationTypeQuery);
            $stmt->execute([':type_name' => $_POST['durationType']]);
            $durationTypeId = $stmt->fetchColumn();
            
            if ($durationTypeId === false) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid duration type']);
                exit;
            }
            
            // Update the membership plan
            $sql = "UPDATE membership_plans SET 
                    plan_name = :plan_name,
                    plan_type = :plan_type,
                    duration = :duration,
                    duration_type_id = :duration_type_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    price = :price,
                    description = :description
                    WHERE id = :id";
                    
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':plan_name' => $_POST['promoName'],
                ':plan_type' => $_POST['promoType'],
                ':duration' => $_POST['duration'],
                ':duration_type_id' => $durationTypeId,
                ':start_date' => $_POST['activationDate'],
                ':end_date' => $_POST['deactivationDate'],
                ':price' => $_POST['price'],
                ':description' => isset($_POST['description']) ? $_POST['description'] : null,
                ':id' => $_POST['id']
            ]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Gym rate updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update gym rate']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    // Handle remove (soft delete) action
    elseif (isset($_POST['action']) && $_POST['action'] === 'remove') {
        if (empty($_POST['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Gym rate ID is required']);
            exit;
        }

        try {
            $sql = "UPDATE membership_plans SET is_removed = 1 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([':id' => $_POST['id']]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Gym rate removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove gym rate']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
?>
