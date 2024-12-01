<?php
require_once '../../../../config.php';

// Handle GET request to fetch rental service details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID is required']);
        exit;
    }

    try {
        $sql = "SELECT * FROM rental_services WHERE id = :id AND is_removed = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $_GET['id']]);
        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rental) {
            echo json_encode(['status' => 'success', 'data' => $rental]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Rental service not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle POST request to update or remove rental service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle remove action
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        if (empty($_POST['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required for removal']);
            exit;
        }

        try {
            $sql = "UPDATE rental_services SET is_removed = 1, status = 'inactive' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([':id' => $_POST['id']]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Rental service removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove rental service']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Handle update action
    // Validate required fields
    $required = ['id', 'serviceName', 'duration', 'durationType', 'totalSlots', 'price'];
    $missing = array_filter($required, function($field) {
        return empty($_POST[$field]);
    });

    if (!empty($missing)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit;
    }

    try {
        $sql = "UPDATE rental_services SET 
                service_name = :serviceName,
                duration = :duration,
                duration_type_id = :durationType,
                total_slots = :totalSlots,
                available_slots = :availableSlots,
                price = :price,
                description = :description
                WHERE id = :id AND is_removed = 0";

        // Calculate available slots difference
        $oldSlotsSql = "SELECT total_slots, available_slots FROM rental_services WHERE id = :id AND is_removed = 0";
        $oldSlotsStmt = $pdo->prepare($oldSlotsSql);
        $oldSlotsStmt->execute([':id' => $_POST['id']]);
        $oldData = $oldSlotsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate new available slots
        $slotsDifference = $_POST['totalSlots'] - $oldData['total_slots'];
        $newAvailableSlots = $oldData['available_slots'] + $slotsDifference;

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':id' => $_POST['id'],
            ':serviceName' => $_POST['serviceName'],
            ':duration' => $_POST['duration'],
            ':durationType' => $_POST['durationType'],
            ':totalSlots' => $_POST['totalSlots'],
            ':availableSlots' => $newAvailableSlots,
            ':price' => $_POST['price'],
            ':description' => $_POST['description'] ?? ''
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Rental service updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update rental service']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
exit;