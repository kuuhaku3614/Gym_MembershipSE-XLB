<?php
require_once '../../../../config.php';

header('Content-Type: application/json');

// Define upload folder
$uploadDir = __DIR__ . '/../../../../cms_img/rentals/';

// Fetch rental service details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $sql = "SELECT * FROM rental_services WHERE id = :id AND is_removed = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
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

// Update rental service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $requiredFields = ['id', 'serviceName', 'duration', 'durationType', 'totalSlots', 'price'];
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
        // Fetch existing image
        $stmt = $pdo->prepare("SELECT image FROM rental_services WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        $existingImage = $stmt->fetchColumn();

        // Handle new image upload
        $newImage = $existingImage;
        if (!empty($_FILES['editRentalImage']['name'])) {
            $fileName = basename($_FILES['editRentalImage']['name']);
            $newImage = time() . "_" . $fileName;
            $targetFilePath = $uploadDir . $newImage;

            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed']);
                exit;
            }

            if (move_uploaded_file($_FILES['editRentalImage']['tmp_name'], $targetFilePath)) {
                if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload new image']);
                exit;
            }
        }

        // Update rental service
        $sql = "UPDATE rental_services SET 
                service_name = :serviceName,
                duration = :duration,
                duration_type_id = :durationType,
                total_slots = :totalSlots,
                available_slots = :availableSlots,
                price = :price,
                description = :description,
                image = :image
                WHERE id = :id AND is_removed = 0";

        // Calculate new available slots
        $oldSlotsSql = "SELECT total_slots, available_slots FROM rental_services WHERE id = :id AND is_removed = 0";
        $oldSlotsStmt = $pdo->prepare($oldSlotsSql);
        $oldSlotsStmt->execute([':id' => $_POST['id']]);
        $oldData = $oldSlotsStmt->fetch(PDO::FETCH_ASSOC);
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
            ':description' => $_POST['description'] ?? '',
            ':image' => $newImage
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

// Remove rental service (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Rental service ID is required']);
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

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
exit;
?>
