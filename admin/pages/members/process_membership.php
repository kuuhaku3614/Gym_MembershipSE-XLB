<?php
require_once 'config.php';

header('Content-Type: application/json');

function validateRequiredFields($fields, $data, $isNewUser = false) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
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
    // 1. Insert into users table (authentication data)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role_id, is_active)
        VALUES (?, ?, ?, TRUE)
    ");
    $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        3 // role_id 3 for 'member' as per database
    ]);
    $userId = $pdo->lastInsertId();

    // 2. Insert into personal_details table
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
    $stmt->execute([
        $userId,
        $data['first_name'],
        $data['middle_name'] ?? null,
        $data['last_name'],
        $data['sex'],
        $data['birthdate'],
        $data['phone']
    ]);

    // 3. Handle profile photo upload
    error_log("New User ID Created: " . $userId);
    error_log("POST Data: " . json_encode($_POST));
    error_log("FILES Data: " . json_encode($_FILES));

    // 3. Handle profile photo upload
    $photoPath = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        try {
            error_log("Profile Photo Upload Attempt for User ID: " . $userId);
            $photoPath = uploadProfilePhoto($userId, $_FILES['profile_photo']);
            
            error_log("Photo Path Generated: " . $photoPath);
            
            // Insert photo path into database
            $stmt = $pdo->prepare("
                INSERT INTO profile_photos (
                    user_id, 
                    photo_path, 
                    is_active, 
                    uploaded_at
                ) VALUES (?, ?, TRUE, NOW())
            ");
            $stmt->execute([$userId, $photoPath]);
            
            error_log("Photo Path Inserted Successfully");
        } catch (Exception $photoUploadError) {
            error_log("Profile photo upload failed: " . $photoUploadError->getMessage());
        }
    } else {
        error_log("No profile photo uploaded or upload failed");
    }

    return $userId;
}

function insertMembership($pdo, $userId, $data) {
    // 4. Insert membership
    $stmt = $pdo->prepare("
        INSERT INTO memberships (
            user_id, 
            membership_plan_id, 
            start_date, 
            end_date, 
            total_amount, 
            status_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $data['membership_plan'],
        $data['start_date'],
        $data['end_date'],
        $data['total_amount'],
        1 // status_id 1 for 'active' as per database
    ]);
    return $pdo->lastInsertId();
}

function insertProgramSubscriptions($pdo, $membershipId, $data) {
    // 5. Insert program subscriptions
    $programs = isset($data['programs']) ? json_decode($data['programs'], true) : [];
    if (is_array($programs)) {
        foreach ($programs as $program) {
            if (isset($program['id'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO program_subscriptions (
                        membership_id, 
                        program_id,
                        coach_id,
                        start_date,
                        end_date,
                        price,
                        status_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $membershipId,
                    $program['id'],
                    $program['coach_id'] ?? 1, // Default coach_id if not specified
                    $data['start_date'],
                    $data['end_date'],
                    $program['price'],
                    1 // status_id 1 for 'active'
                ]);
            }
        }
    }
}

function insertRentalSubscriptions($pdo, $membershipId, $data) {
    // 6. Insert rental subscriptions
    $rentals = isset($data['rentals']) ? json_decode($data['rentals'], true) : [];
    if (is_array($rentals)) {
        foreach ($rentals as $rental) {
            if (isset($rental['id'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO rental_subscriptions (
                        membership_id, 
                        rental_service_id,
                        start_date,
                        end_date,
                        price,
                        status_id
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $membershipId,
                    $rental['id'],
                    $data['start_date'],
                    $data['end_date'],
                    $rental['price'],
                    1 // status_id 1 for 'active'
                ]);
            }
        }
    }
}

try {
    // Validate required fields
    $required_fields = [
        'user_type',
        'membership_plan',
        'start_date',
        'end_date',
        'total_amount'
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

    // Check for required fields
    validateRequiredFields($required_fields, $_POST);

    // Check additional fields for new users
    if ($_POST['user_type'] === 'new') {
        validateRequiredFields($new_user_fields, $_POST, true);
    } else if (!isset($_POST['existing_user_id']) || empty($_POST['existing_user_id'])) {
        throw new Exception("Missing existing_user_id for existing user");
    }

    $pdo->beginTransaction();

    // Process user and membership
    $userId = ($_POST['user_type'] === 'new') 
        ? insertNewUser($pdo, $_POST) 
        : $_POST['existing_user_id'];

    // Insert membership
    $membershipId = insertMembership($pdo, $userId, $_POST);

    // Insert program subscriptions
    insertProgramSubscriptions($pdo, $membershipId, $_POST);

    // Insert rental subscriptions
    insertRentalSubscriptions($pdo, $membershipId, $_POST);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Membership created successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in process_membership.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>