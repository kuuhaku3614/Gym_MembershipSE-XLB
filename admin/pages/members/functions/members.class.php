<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/config.php");

class Members {
    private $pdo;
    private $tempUserId = null;

    public function __construct() {
        try {
            $database = new Database();
            $this->pdo = $database->connect();
            if (!$this->pdo) {
                throw new Exception("Failed to get PDO connection");
            }
            error_log("Database connection established in Members class");
        } catch (Exception $e) {
            error_log("Failed to connect to database in Members class: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getAllMembers() {
        try {
            $query = "SELECT 
                u.id as user_id,
                pd.first_name,
                pd.middle_name,
                pd.last_name,
                pp.photo_path,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM transactions t 
                        JOIN memberships m ON t.id = m.transaction_id
                        WHERE t.user_id = u.id 
                        AND t.status = 'confirmed'
                        AND m.status IN ('active', 'expiring')
                    ) THEN 'active'
                    ELSE 'inactive'
                END as status,
                (
                    SELECT COUNT(*)
                    FROM transactions t2
                    JOIN memberships m2 ON t2.id = m2.transaction_id
                    WHERE t2.user_id = u.id
                    AND t2.status = 'confirmed'
                    AND m2.status IN ('active', 'expiring')
                    AND m2.is_paid = 0
                ) as unpaid_memberships,
                (
                    SELECT COUNT(*)
                    FROM transactions t3
                    JOIN rental_subscriptions rs ON t3.id = rs.transaction_id
                    WHERE t3.user_id = u.id
                    AND t3.status = 'confirmed'
                    AND rs.is_paid = 0
                    AND rs.end_date >= CURRENT_DATE
                ) as unpaid_rentals,
                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN rs.id IS NOT NULL AND rs.is_paid = 0 AND rs.end_date >= CURRENT_DATE THEN
                            CONCAT(rs.id, ':', rs.status, ':', rs.end_date)
                    END
                ) as unpaid_rental_details
            FROM users u
            LEFT JOIN personal_details pd ON u.id = pd.user_id
            LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
            LEFT JOIN transactions t3 ON u.id = t3.user_id AND t3.status = 'confirmed'
            LEFT JOIN rental_subscriptions rs ON t3.id = rs.transaction_id
            WHERE u.role_id = 3
            GROUP BY u.id, pd.first_name, pd.middle_name, pd.last_name, pp.photo_path
            ORDER BY pd.last_name, pd.first_name";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $members = array();
            foreach ($results as $row) {
                if (!empty($row['unpaid_rental_details'])) {
                    error_log("Member {$row['user_id']} unpaid rental details: " . $row['unpaid_rental_details']);
                }
                
                $members[] = array(
                    'user_id' => $row['user_id'],
                    'full_name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']),
                    'status' => $row['status'],
                    'unpaid_memberships' => (int)$row['unpaid_memberships'],
                    'unpaid_rentals' => (int)$row['unpaid_rentals'],
                    'photo_path' => $row['photo_path'] ?? 'uploads/default.jpg'
                );
            }
            
            return $members;
        } catch (PDOException $e) {
            error_log("Error in getAllMembers: " . $e->getMessage());
            return array();
        }
    }

    public function getMemberDetails($userId) {
        try {
            $query = "SELECT 
                u.id as user_id,
                u.username,
                pd.first_name,
                pd.middle_name,
                pd.last_name,
                pd.sex,
                pd.birthdate,
                pd.phone_number,
                pp.photo_path,
                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN m.id IS NOT NULL AND m.status != 'expired' THEN
                            JSON_OBJECT(
                                'id', m.id,
                                'plan_name', mp.plan_name,
                                'start_date', m.start_date,
                                'end_date', m.end_date,
                                'status', m.status,
                                'is_paid', m.is_paid,
                                'amount', m.amount
                            )
                    END
                    SEPARATOR ';;;'
                ) as memberships
            FROM users u 
            JOIN roles roles ON u.role_id = roles.id AND roles.id = 3 
            LEFT JOIN personal_details pd ON u.id = pd.user_id 
            LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
            LEFT JOIN transactions tm ON u.id = tm.user_id AND tm.status = 'confirmed'
            LEFT JOIN memberships m ON tm.id = m.transaction_id
            LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
            WHERE u.id = :userId
            GROUP BY u.id, u.username, pd.first_name, pd.middle_name, pd.last_name, 
                     pd.sex, pd.birthdate, pd.phone_number, pp.photo_path";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new PDOException("Member not found");
            }

            if ($result['memberships']) {
                $memberships = explode(';;;', $result['memberships']);
                $result['memberships'] = array_map(function($item) {
                    $data = json_decode($item, true);
                    if ($data) {
                        $data['is_paid'] = (bool)$data['is_paid'];
                        return $data;
                    }
                    return null;
                }, array_filter($memberships));
                $result['memberships'] = array_filter($result['memberships']);
            } else {
                $result['memberships'] = [];
            }

            $rentalQuery = "SELECT 
                rs.id,
                srv.service_name,
                rs.start_date,
                rs.end_date,
                rs.status,
                rs.is_paid,
                rs.amount
            FROM transactions tr
            JOIN rental_subscriptions rs ON tr.id = rs.transaction_id
            JOIN rental_services srv ON rs.rental_service_id = srv.id
            WHERE tr.user_id = :userId 
            AND tr.status = 'confirmed'
            AND rs.status != 'expired'
            ORDER BY rs.start_date DESC, rs.id DESC";

            $stmt = $this->pdo->prepare($rentalQuery);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result['rental_services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result['rental_services'] as &$service) {
                $service['is_paid'] = $service['is_paid'] == '1';
            }

            $registrationQuery = "SELECT 
                rr.id,
                rr.amount,
                rr.is_paid,
                rr.payment_date,
                rr.created_at as registration_date,
                'Registration Fee' as payment_type
            FROM registration_records rr
            JOIN transactions t ON rr.transaction_id = t.id
            WHERE t.user_id = :userId
            ORDER BY rr.created_at DESC";

            $stmt = $this->pdo->prepare($registrationQuery);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result['registration_records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result['registration_records'] as &$record) {
                $record['is_paid'] = $record['is_paid'] == '1';
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in getMemberDetails: " . $e->getMessage());
            throw $e;
        }
    }

    public function handlePhotoUpload($userId, $photo) {
        try {
            if ($photo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file: " . $photo['error']);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($photo['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPEG, PNG, and GIF are allowed.");
            }

            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($photo['size'] > $maxFileSize) {
                throw new Exception("File is too large. Maximum size is 5MB.");
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/uploads/profile_photos/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($photo['name']);
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($photo['tmp_name'], $targetPath)) {
                throw new Exception("Failed to move uploaded file.");
            }

            $this->pdo->beginTransaction();

            $sql = "UPDATE profile_photos SET is_active = 0 WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);

            $sql = "INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (:user_id, :photo_path, 1)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':photo_path' => 'uploads/profile_photos/' . $fileName
            ]);

            $this->pdo->commit();
            return ['success' => true, 'photo_path' => 'uploads/profile_photos/' . $fileName];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in handlePhotoUpload: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function processPayment($userId, $type = 'all', $itemId = null) {
        try {
            $this->pdo->beginTransaction();

            if ($type === 'membership' && $itemId) {
                $this->processMembershipPayment($itemId);
            } elseif ($type === 'registration' && $itemId) {
                $this->processRegistrationPayment($itemId);
            } elseif ($type === 'rental' && $itemId) {
                $this->processRentalPayment($itemId);
            } else {
                throw new Exception("Invalid payment type or item ID");
            }

            $this->pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in processPayment: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processMembershipPayment($membershipId) {
        $sql = "UPDATE memberships SET is_paid = 1, payment_date = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $membershipId]);
    }

    private function processRegistrationPayment($registrationId) {
        $sql = "UPDATE registration_records SET is_paid = 1, payment_date = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $registrationId]);
    }

    private function processRentalPayment($rentalId) {
        $sql = "UPDATE rental_subscriptions SET is_paid = 1, payment_date = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $rentalId]);
    }

    public function cancelMembership($membershipId) {
        try {
            $sql = "UPDATE memberships SET status = 'cancelled' WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $membershipId]);
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error in cancelMembership: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancelRental($rentalId) {
        try {
            $sql = "UPDATE rental_subscriptions SET status = 'cancelled' WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $rentalId]);
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error in cancelRental: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getUnpaidItemCounts($userId) {
        try {
            $sql = "SELECT
                    (SELECT COUNT(*) FROM transactions t1
                     JOIN memberships m ON t1.id = m.transaction_id
                     WHERE t1.user_id = :user_id1
                     AND t1.status = 'confirmed'
                     AND m.status IN ('active', 'expiring')
                     AND m.is_paid = 0) as unpaid_memberships,
                    (SELECT COUNT(*) FROM transactions t2
                     JOIN rental_subscriptions rs ON t2.id = rs.transaction_id
                     WHERE t2.user_id = :user_id2
                     AND t2.status = 'confirmed'
                     AND rs.is_paid = 0
                     AND rs.end_date >= CURRENT_DATE) as unpaid_rentals";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id1' => $userId,
                ':user_id2' => $userId
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getUnpaidItemCounts: " . $e->getMessage());
            return ['unpaid_memberships' => 0, 'unpaid_rentals' => 0];
        }
    }

    public function deleteRegistrationAndUpdateRole($userId) {
        try {
            $this->pdo->beginTransaction();

            $sql = "DELETE rr FROM registration_records rr
                    JOIN transactions t ON rr.transaction_id = t.id
                    WHERE t.user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);

            $sql = "UPDATE users SET role_id = 3 WHERE id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);

            $this->pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in deleteRegistrationAndUpdateRole: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>