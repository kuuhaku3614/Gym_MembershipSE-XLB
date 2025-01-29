<?php
require_once '../../../config.php';

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

class AddMember {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    public function addNewMember($data) {
        try {
            $this->pdo->beginTransaction();

            // Insert into users table
            $userQuery = "INSERT INTO users (username, password, role_id, is_active) VALUES (?, ?, 3, 1)";
            $userStmt = $this->pdo->prepare($userQuery);
            $userStmt->execute([$data['username'], password_hash($data['password'], PASSWORD_DEFAULT)]);
            
            $userId = $this->pdo->lastInsertId();

            // Insert into personal_details table
            $detailsQuery = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $detailsStmt = $this->pdo->prepare($detailsQuery);
            $detailsStmt->execute([
                $userId,
                $data['first_name'],
                $data['middle_name'],
                $data['last_name'],
                $data['sex'],
                $data['birthdate'],
                $data['phone_number']
            ]);

            // Handle profile photo if uploaded
            if (isset($data['photo']) && $data['photo']['error'] === 0) {
                $uploadDir = __DIR__ . '/../../../../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = strtolower(pathinfo($data['photo']['name'], PATHINFO_EXTENSION));
                $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                move_uploaded_file($data['photo']['tmp_name'], $targetPath);
                
                $photoQuery = "INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (?, ?, 1)";
                $photoStmt = $this->pdo->prepare($photoQuery);
                $photoStmt->execute([$userId, 'uploads/' . $newFileName]);
            }

            $this->pdo->commit();
            return ['success' => true, 'user_id' => $userId];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
