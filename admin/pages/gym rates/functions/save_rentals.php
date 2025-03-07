<?php
require_once '../../../../config.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Handle rental service status toggle
    if (!empty($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $newStatus = $_POST['new_status'] ?? '';

        if ($id <= 0) throw new Exception('Invalid rental service ID');
        if (!in_array($newStatus, ['active', 'inactive'])) throw new Exception('Invalid status value');

        $stmt = $pdo->prepare("UPDATE rental_services SET status = :status WHERE id = :id");
        if ($stmt->execute([':status' => $newStatus, ':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Rental service status updated successfully']);
        } else {
            throw new Exception('Failed to update rental service status');
        }
        exit;
    }

    // Retrieve and sanitize input values
    $serviceName = trim($_POST['serviceName'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $totalSlots = filter_var($_POST['totalSlots'] ?? 0, FILTER_VALIDATE_INT);
    $duration = filter_var($_POST['duration'] ?? 0, FILTER_VALIDATE_INT);
    $durationType = filter_var($_POST['durationType'] ?? 0, FILTER_VALIDATE_INT);
    $description = trim($_POST['description'] ?? '');
    $status = 'active';

    // Validation checks
    $errors = [];
    if (empty($serviceName)) $errors['serviceName'] = 'Service name is required';
    if ($price <= 0) $errors['price'] = 'Price must be greater than 0';
    if ($totalSlots <= 0) $errors['totalSlots'] = 'Total slots must be greater than 0';
    if ($duration <= 0) $errors['duration'] = 'Duration must be greater than 0';
    if ($durationType <= 0) $errors['durationType'] = 'Valid duration type must be selected';

    if (!empty($errors)) {
        echo json_encode(['message' => 'Please correct the errors below', 'errors' => $errors]);
        exit;
    }

    // Validate duration type
    $stmt = $pdo->prepare("SELECT id FROM duration_types WHERE id = :id");
    $stmt->execute([':id' => $durationType]);
    if (!$stmt->fetch()) {
        echo json_encode(['message' => 'Invalid duration type selected']);
        exit;
    }

    // Handle image upload
    $uploadDir = __DIR__ . '/../../../../cms_img/rentals/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // Create directory if needed
    $imagePath = NULL;

    if (!empty($_FILES['rentalImage']['name']) && $_FILES['rentalImage']['error'] === 0) {
        $fileTmpPath = $_FILES['rentalImage']['tmp_name'];
        $fileName = basename($_FILES['rentalImage']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed extensions & max size (5MB)
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        if (!in_array($fileExt, $allowedTypes)) {
            echo json_encode(['message' => 'Invalid image format. Only JPG, JPEG, and PNG allowed.']);
            exit;
        }
        if ($_FILES['rentalImage']['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['message' => 'Image size exceeds 5MB limit.']);
            exit;
        }

        // Generate unique filename
        $newFileName = uniqid() . "_" . $fileName;
        $filePath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $filePath)) {
            $imagePath =  $newFileName;
        } else {
            echo json_encode(['message' => 'Error uploading image.']);
            exit;
        }
    }

    // Insert into database
    $stmt = $pdo->prepare(
        "INSERT INTO rental_services (service_name, price, total_slots, available_slots, duration, duration_type_id, description, status, image)
        VALUES (:service_name, :price, :total_slots, :available_slots, :duration, :duration_type_id, :description, :status, :image)"
    );

    $params = [
        ':service_name' => $serviceName,
        ':price' => $price,
        ':total_slots' => $totalSlots,
        ':available_slots' => $totalSlots, // Initially available slots equal total slots
        ':duration' => $duration,
        ':duration_type_id' => $durationType,
        ':description' => $description,
        ':status' => $status,
        ':image' => $imagePath
    ];

    if ($stmt->execute($params)) {
        echo json_encode(['status' => 'success', 'message' => 'Rental service added successfully']);
    } else {
        throw new PDOException("Database error: " . implode(", ", $stmt->errorInfo()));
    }

} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    echo json_encode(['message' => 'Database error occurred.']);
} catch (Exception $e) {
    error_log("General Exception: " . $e->getMessage());
    echo json_encode(['message' => 'An unexpected error occurred.']);
}

?>