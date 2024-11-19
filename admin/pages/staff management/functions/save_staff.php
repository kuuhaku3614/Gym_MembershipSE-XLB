<?php
// save_staff.php
header('Content-Type: application/json');
require_once '../../../config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->beginTransaction();

    // Get role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
    $stmt->execute([$_POST['role']]);
    $role_id = $stmt->fetchColumn();

    if (!$role_id) {
        throw new PDOException("Invalid role");
    }

    // Insert into users table
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['username'],
        $password_hash,
        $role_id
    ]);
    
    $user_id = $pdo->lastInsertId();

    // Insert into personal_details table
    $stmt = $pdo->prepare("INSERT INTO personal_details (user_id, first_name, middle_name, last_name, phone_number) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['last_name'],
        $_POST['phone_number']
    ]);

    // If role is coach, insert into coaches table and program types
    if ($_POST['role'] === 'coach') {
        // Insert into coaches table
        $stmt = $pdo->prepare("INSERT INTO coaches (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $coach_id = $pdo->lastInsertId();

        // Determine which program types to insert
        $program_types = [];
        if ($_POST['program_type'] === 'both') {
            $program_types = ['personal', 'group'];
        } else {
            $program_types = [$_POST['program_type']];
        }

        // Get and insert program types
        $stmt = $pdo->prepare("SELECT id FROM program_types WHERE type_name = ?");
        foreach ($program_types as $type) {
            $stmt->execute([$type]);
            $program_type_id = $stmt->fetchColumn();
            
            if ($program_type_id) {
                $stmt2 = $pdo->prepare("INSERT INTO coach_program_types (coach_id, program_type_id) VALUES (?, ?)");
                $stmt2->execute([$coach_id, $program_type_id]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}