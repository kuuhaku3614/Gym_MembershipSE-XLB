<?php
require_once 'config.php';

header('Content-Type: application/json');

// Mock function to send SMS
function sendVerificationSMS($phone, $verificationCode) {
    if (!preg_match('/^(09|\\+639)\\d{9}$/', $phone)) {
        return false;
    }
    return true; // Simulate successful sending
}

try {
    $pdo->beginTransaction();

    // Generate verification code
    $verificationCode = sprintf('%06d', random_int(0, 999999));
    $phone = $_POST['phone'];

    if (!sendVerificationSMS($phone, $verificationCode)) {
        throw new Exception('Failed to send verification code');
    }

    // Process user and membership
    $userId = null;
    if ($_POST['user_type'] === 'new') {
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, middle_name, last_name, sex, birthdate, phone, username, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'] ?? null,
            $_POST['last_name'],
            $_POST['sex'],
            $_POST['birthdate'],
            $_POST['phone'],
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $_POST['existing_user_id'];
    }

    // Insert membership
    $stmt = $pdo->prepare("
        INSERT INTO memberships (user_id, membership_plan_id, start_date, end_date, total_amount)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $_POST['membership_plan'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['total_amount']
    ]);
    $membershipId = $pdo->lastInsertId();

    // Insert programs and rentals
    $programs = json_decode($_POST['programs'], true) ?? [];
    foreach ($programs as $program) {
        $stmt = $pdo->prepare("
            INSERT INTO membership_programs (membership_id, program_id) VALUES (?, ?)
        ");
        $stmt->execute([$membershipId, $program['id']]);
    }

    $rentals = json_decode($_POST['rentals'], true) ?? [];
    foreach ($rentals as $rental) {
        $stmt = $pdo->prepare("
            INSERT INTO membership_rentals (membership_id, rental_service_id) VALUES (?, ?)
        ");
        $stmt->execute([$membershipId, $rental['id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Membership created successfully',
        'verificationCode' => $verificationCode // For mock display
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => true,
        'verificationCode' => $verificationCode
    ]);
}
?>
