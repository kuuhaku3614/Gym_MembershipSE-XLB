<?php
require_once '../../../../config.php';

// Define upload folder
$uploadDir = __DIR__ . '/../../../../cms_img/gym_rates/';


// Fetch gym rate details
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
    exit;
}

// Update gym rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
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

        // Fetch current image from database
        $imageQuery = "SELECT image FROM membership_plans WHERE id = :id";
        $stmt = $pdo->prepare($imageQuery);
        $stmt->execute([':id' => $_POST['id']]);
        $existingImage = $stmt->fetchColumn();

        // Handle new image upload
        $newImage = $existingImage; // Default to existing image
        if (!empty($_FILES['editPromoImage']['name'])) {
            $fileName = basename($_FILES['editPromoImage']['name']);
            $newImage = time() . "_" . $fileName; 
            $targetFilePath = $uploadDir . $newImage;

            // Validate file type
            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed']);
                exit;
            }

            if (move_uploaded_file($_FILES['editPromoImage']['tmp_name'], $targetFilePath)) {
                // Delete old image if a new one is successfully uploaded
                if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload new image']);
                exit;
            }
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
                description = :description,
                image = :image
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
            ':description' => $_POST['description'] ?? null,
            ':image' => $newImage, 
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
    exit;
}

// Remove gym rate (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Gym rate ID is required']);
        exit;
    }

    try {
        $sql = "UPDATE membership_plans SET is_removed = 1, status = 'inactive' WHERE id = :id";
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
    exit;
}
?>
