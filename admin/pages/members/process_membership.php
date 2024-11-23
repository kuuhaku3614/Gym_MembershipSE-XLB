<?php
require_once 'config.php';

// Ensure proper error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Turn off display_errors but log them
ini_set('log_errors', 1);

// Set proper content type header
header('Content-Type: application/json');

function handleError($message, $details = null) {
    error_log("Membership Processing Error: " . $message . ($details ? " Details: " . json_encode($details) : ""));
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function validateRequiredFields($fields, $data) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        handleError("Missing required fields: " . implode(", ", $missing));
    }
}

function uploadProfilePhoto($userId, $profilePhoto) {
    // Validate file upload
    if (!isset($profilePhoto) || $profilePhoto['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Invalid file upload. Error code: " . 
            ($profilePhoto['error'] ?? 'Unknown'));
    }

    // Allowed file types and max file size
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    // Get file extension
    $fileExtension = strtolower(pathinfo($profilePhoto['name'], PATHINFO_EXTENSION));

    // Validate file type using extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception("Invalid file type. Only JPEG and PNG are allowed.");
    }

    if ($profilePhoto['size'] > $maxFileSize) {
        throw new Exception("File size exceeds 5MB limit.");
    }

    // Set upload directory relative to the project root
    $uploadDir = dirname(__DIR__, 3) . '/uploads'; // Navigate up three levels to reach the project root
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }

    // Generate unique filename
    $newFileName = 'profile_' . $userId . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . '/' . $newFileName; // Full file path for saving
    $relativePath = 'uploads/' . $newFileName; // Relative path for storing in the database

    // Log additional information for debugging
    error_log("Photo Upload Debug: " . json_encode([
        'userId' => $userId,
        'originalName' => $profilePhoto['name'],
        'newFileName' => $newFileName,
        'fullPath' => $uploadPath,
        'relativePath' => $relativePath
    ]));
    
    // Attempt to move uploaded file
    if (!move_uploaded_file($profilePhoto['tmp_name'], $uploadPath)) {
        throw new Exception("Failed to move uploaded file");
    }

    return $relativePath;
}

function insertNewUser($pdo, $data) {
    try {
        // 1. Insert into users table first
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role_id, is_active)
            VALUES (?, ?, ?, TRUE)
        ");
        
        if (!$stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            3 // role_id 3 for 'member'
        ])) {
            throw new Exception("Failed to insert user data");
        }
        
        $userId = $pdo->lastInsertId();

        // 2. Insert into personal_details
        $stmt = $pdo->prepare("
            INSERT INTO personal_details (
                user_id, 
                first_name, 
                middle_name, 
                last_name, 
                sex, 
                birthdate, 
                phone_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt->execute([
            $userId,
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['sex'],
            $data['birthdate'],
            $data['phone']
        ])) {
            throw new Exception("Failed to insert personal details");
        }

        // 3. Handle profile photo if provided
        if (isset($_FILES['profile_photo'])) {
            $photoPath = uploadProfilePhoto($userId, $_FILES['profile_photo']);
            if ($photoPath) {
                $stmt = $pdo->prepare("
                    INSERT INTO profile_photos (
                        user_id, 
                        photo_path, 
                        is_active
                    ) VALUES (?, ?, TRUE)
                ");
                $stmt->execute([$userId, $photoPath]);
            }
        }

        return $userId;
    } catch (PDOException $e) {
        throw new Exception("Database error during user insertion: " . $e->getMessage());
    }
}

function insertMembership($pdo, $userId, $data) {
    try {
        // First verify that the user exists in users table
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        if (!$stmt->execute([$userId])) {
            throw new Exception("Failed to verify user existence");
        }
        if (!$stmt->fetch()) {
            throw new Exception("User ID not found in users table");
        }

        // Now insert the membership with verified user_id
        $stmt = $pdo->prepare("
            INSERT INTO memberships (
                user_id,               -- Now correctly references users.id
                membership_plan_id,
                staff_id,
                start_date,
                end_date,
                total_amount,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        if (!$stmt->execute([
            $userId,
            $data['membership_plan'],
            $data['staff_id'],
            $data['start_date'],
            $data['end_date'],
            $data['total_amount']
        ])) {
            throw new Exception("Failed to insert membership");
        }
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception("Database error during membership insertion: " . $e->getMessage());
    }
}

function insertProgramSubscriptions($pdo, $membershipId, $data) {
    if (!isset($data['programs'])) {
        return;
    }

    $programs = json_decode($data['programs'], true);
    if (!is_array($programs)) {
        return;
    }

    foreach ($programs as $program) {
        if (!isset($program['id'])) continue;

        $stmt = $pdo->prepare("
            INSERT INTO program_subscriptions (
                membership_id, 
                program_id,
                coach_id,
                start_date,
                end_date,
                price,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $membershipId,
            $program['id'],
            $program['coach_id'] ?? 1,
            $data['start_date'],
            $data['end_date'],
            $program['price']
        ]);
    }
}

function insertRentalSubscriptions($pdo, $membershipId, $data) {
    if (!isset($data['rentals'])) {
        return;
    }

    $rentals = json_decode($data['rentals'], true);
    if (!is_array($rentals)) {
        return;
    }

    foreach ($rentals as $rental) {
        if (!isset($rental['id'])) continue;

        $stmt = $pdo->prepare("
            INSERT INTO rental_subscriptions (
                membership_id, 
                rental_service_id,
                start_date,
                end_date,
                price,
                status
            ) VALUES (?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $membershipId,
            $rental['id'],
            $data['start_date'],
            $data['end_date'],
            $rental['price']
        ]);
    }
}

try {
    // Validate required fields
    $required_fields = [
        'user_type',
        'membership_plan',
        'start_date',
        'end_date',
        'total_amount',
        'staff_id'
    ];

    // Additional fields required for new users
    $new_user_fields = [
        'first_name',
        'last_name',
        'sex',
        'birthdate',
        'phone',
        'username',
        'password'
    ];

    // Log incoming data for debugging
    error_log("Incoming POST data: " . json_encode($_POST));
    error_log("Incoming FILES data: " . json_encode($_FILES));

    // Validate required fields
    validateRequiredFields($required_fields, $_POST);

    // Additional validation for new users
    if ($_POST['user_type'] === 'new') {
        validateRequiredFields($new_user_fields, $_POST);
    } else if (!isset($_POST['existing_user_id']) || empty($_POST['existing_user_id'])) {
        handleError("Missing existing_user_id for existing user");
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Get or create user ID
    $userId = ($_POST['user_type'] === 'new') 
        ? insertNewUser($pdo, $_POST) 
        : $_POST['existing_user_id'];

    // Insert membership (now correctly references users.id)
    $membershipId = insertMembership($pdo, $userId, $_POST);

    // Insert related subscriptions
    insertProgramSubscriptions($pdo, $membershipId, $_POST);
    insertRentalSubscriptions($pdo, $membershipId, $_POST);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Membership created successfully',
        'user_id' => $userId,
        'membership_id' => $membershipId
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handleError($e->getMessage());
}
?>