<?php
require_once 'config.php';

header('Content-Type: application/json');

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
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Check additional fields for new users
    if ($_POST['user_type'] === 'new') {
        foreach ($new_user_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field for new user: {$field}");
            }
        }
    } else if (!isset($_POST['existing_user_id']) || empty($_POST['existing_user_id'])) {
        throw new Exception("Missing existing_user_id for existing user");
    }

    $pdo->beginTransaction();

    // Process user and membership
    $userId = null;
    if ($_POST['user_type'] === 'new') {
        // 1. Insert into users table (authentication data)
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role_id, is_active)
            VALUES (?, ?, ?, TRUE)
        ");
        $stmt->execute([
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
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
            $_POST['first_name'],
            $_POST['middle_name'] ?? null,
            $_POST['last_name'],
            $_POST['sex'],
            $_POST['birthdate'],
            $_POST['phone']
        ]);

        // 3. Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            // Define upload directory relative to the project root
            $uploadDir = __DIR__ . '/uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileExtension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $newFileName = 'profile_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
                // Store only the filename in database, not the full path
                $stmt = $pdo->prepare("
                    INSERT INTO profile_photos (
                        user_id, 
                        photo_path, 
                        is_active
                    ) VALUES (?, ?, TRUE)
                ");
                $stmt->execute([$userId, $newFileName]);
            } else {
                throw new Exception('Failed to upload profile photo');
            }
        }
    } else {
        $userId = $_POST['existing_user_id'];
    }

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
        $_POST['membership_plan'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['total_amount'],
        1 // status_id 1 for 'active' as per database
    ]);
    $membershipId = $pdo->lastInsertId();

    // 5. Insert program subscriptions
    $programs = isset($_POST['programs']) ? json_decode($_POST['programs'], true) : [];
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
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $program['price'],
                    1 // status_id 1 for 'active'
                ]);
            }
        }
    }

    // 6. Insert rental subscriptions
    $rentals = isset($_POST['rentals']) ? json_decode($_POST['rentals'], true) : [];
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
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $rental['price'],
                    1 // status_id 1 for 'active'
                ]);
            }
        }
    }

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
