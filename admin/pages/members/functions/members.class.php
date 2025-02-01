<?php 
require_once '../../../config.php';

class Members {
    private $pdo;
    private $tempUserId = null;

    public function __construct() {
        try {
            $database = new Database();
            $this->pdo = $database->connect();
            error_log("Database connection established in Members class");
        } catch (Exception $e) {
            error_log("Failed to connect to database in Members class: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllMembers() {
        try {
            $connection = $this->pdo;
            if (!$connection) return array();

            $query = "SELECT 
                u.id as user_id,
                pd.first_name,
                pd.middle_name,
                pd.last_name,
                latest_membership.membership_id,
                latest_membership.membership_status,
                latest_membership.is_paid,
                latest_membership.transaction_status,
                pp.photo_path
            FROM users u
            LEFT JOIN personal_details pd ON u.id = pd.user_id
            LEFT JOIN (
                SELECT 
                    t.user_id,
                    m.id as membership_id,
                    m.status as membership_status,
                    m.is_paid,
                    t.status as transaction_status
                FROM transactions t
                INNER JOIN memberships m ON t.id = m.transaction_id
                WHERE t.id = (
                    SELECT t2.id
                    FROM transactions t2
                    INNER JOIN memberships m2 ON t2.id = m2.transaction_id
                    WHERE t2.user_id = t.user_id
                    ORDER BY t2.created_at DESC, t2.id DESC
                    LIMIT 1
                )
            ) latest_membership ON u.id = latest_membership.user_id
            LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
            WHERE u.role_id = 3 AND u.is_active = 1
            ORDER BY pd.last_name, pd.first_name";
            
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $finalResults = array();
            foreach ($results as $row) {
                $status = 'inactive';
                if ($row['membership_id'] !== null) {
                    if ($row['membership_status'] === 'active' && $row['transaction_status'] === 'confirmed' && $row['is_paid']) {
                        $status = 'active';
                    } else {
                        $status = 'pending';
                    }
                }

                $paymentStatus = ' ';
                if ($row['membership_id'] !== null) {
                    $paymentStatus = $row['is_paid'] ? 'paid' : 'unpaid';
                }

                $finalResults[] = array(
                    'user_id' => $row['user_id'],
                    'full_name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']),
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'photo_path' => $row['photo_path']
                );
            }
            
            return $finalResults;
        } catch (PDOException $e) {
            return array();
        }
    }

    public function getMemberDetails($userId) {
        try {
            $connection = $this->pdo;
            if (!$connection) {
                throw new PDOException("Database connection failed");
            }
            
            $query = "SELECT 
                u.id AS user_id, 
                u.username, 
                pd.first_name, 
                pd.middle_name, 
                pd.last_name, 
                pd.sex, 
                pd.birthdate, 
                pd.phone_number, 
                COALESCE(pp.photo_path, NULL) AS photo_path, 
                
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active' 
                        AND m.end_date >= CURDATE()
                        AND m.is_paid = 1
                    ) THEN 'Active'
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active' 
                        AND m.end_date >= CURDATE()
                        AND m.is_paid = 0
                    ) THEN 'Pending'
                    ELSE 'Inactive'
                END AS membership_status,
                
                CONCAT(mp.plan_name, ' - ', mp.plan_type) AS membership_plan_name,
                mp.plan_name, 
                mp.plan_type,
                m.start_date,
                m.end_date,
                rr.amount as registration_fee,
                CASE 
                    WHEN rr.amount > 0 THEN 'Yes'
                    ELSE 'No'
                END as has_registration_fee,
                DATE_FORMAT(m.start_date, '%M %d, %Y') AS membership_start,
                DATE_FORMAT(m.end_date, '%M %d, %Y') AS membership_end,
                
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active'
                        AND m.end_date >= CURDATE()
                    ) THEN 
                        CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM memberships m 
                                JOIN transactions t ON m.transaction_id = t.id
                                WHERE t.user_id = u.id 
                                AND m.status = 'active'
                                AND m.end_date >= CURDATE()
                                AND m.is_paid = 1
                            ) THEN 'Paid'
                            ELSE 'Unpaid'
                        END
                    ELSE ' '
                END AS payment_status,
                
                (
                    COALESCE(m.amount, 0) + 
                    COALESCE(
                        (SELECT SUM(amount) 
                         FROM program_subscriptions ps 
                         WHERE ps.transaction_id = t.id), 
                        0
                    ) + 
                    COALESCE(
                        (SELECT SUM(amount) 
                         FROM rental_subscriptions rs 
                         WHERE rs.transaction_id = t.id), 
                        0
                    ) + 
                    COALESCE(rr.amount, 0)
                ) AS total_price,

                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN ps.end_date >= CURDATE() AND ps.status = 'active' THEN
                            CONCAT(
                                p.program_name, ' | ',
                                'Coach: ', COALESCE(CONCAT(coach_details.last_name, ', ', coach_details.first_name, ' ', COALESCE(coach_details.middle_name, '')), 'Not Assigned'), ' | ',
                                'Duration: ', DATE_FORMAT(ps.start_date, '%M %d, %Y'), ' to ', DATE_FORMAT(ps.end_date, '%M %d, %Y'), ' | ',
                                'Price: ₱', FORMAT(COALESCE(ps.amount, 0), 2), ' | ',
                                'Status: ', CASE WHEN ps.is_paid = 1 THEN 'Paid' ELSE 'Pending' END
                            )
                        END
                    SEPARATOR '\n'
                ) as program_details,

                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN rs.end_date >= CURDATE() AND rs.status = 'active' THEN
                            CONCAT(
                                srv.service_name, ' | ',
                                'Duration: ', DATE_FORMAT(rs.start_date, '%M %d, %Y'), ' to ', DATE_FORMAT(rs.end_date, '%M %d, %Y'), ' | ',
                                'Price: ₱', rs.amount, ' | ',
                                'Status: ', CASE 
                                    WHEN rs.is_paid = 1 THEN 'Active'
                                    ELSE 'Pending'
                                END
                            )
                        END
                    SEPARATOR '\n'
                ) as rental_details,
                
                m.amount as membership_amount

            FROM 
                users u 
            JOIN 
                roles roles ON u.role_id = roles.id AND roles.id = 3 
            LEFT JOIN 
                transactions t ON u.id = t.user_id
            LEFT JOIN 
                memberships m ON t.id = m.transaction_id
            LEFT JOIN 
                membership_plans mp ON m.membership_plan_id = mp.id
            LEFT JOIN 
                personal_details pd ON u.id = pd.user_id 
            LEFT JOIN 
                profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1 
            LEFT JOIN 
                registration_records rr ON t.id = rr.transaction_id
            LEFT JOIN 
                program_subscriptions ps ON t.id = ps.transaction_id AND ps.status = 'active'
            LEFT JOIN 
                programs p ON ps.program_id = p.id
            LEFT JOIN 
                users coach ON ps.coach_id = coach.id
            LEFT JOIN 
                personal_details coach_details ON coach.id = coach_details.user_id
            LEFT JOIN 
                rental_subscriptions rs ON t.id = rs.transaction_id
            LEFT JOIN 
                rental_services srv ON rs.rental_service_id = srv.id
            WHERE 
                u.is_active = 1 
                AND u.id = :userId
            GROUP BY 
                u.id, 
                u.username, 
                pd.first_name, 
                pd.middle_name, 
                pd.last_name, 
                pd.sex, 
                pd.birthdate, 
                pd.phone_number, 
                pp.photo_path,
                m.id,
                m.start_date,
                m.end_date,
                mp.plan_name
            ORDER BY 
                m.start_date DESC, 
                m.id DESC
            LIMIT 1";

            $stmt = $connection->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new PDOException("Member not found");
            }

            return $result;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function addCompleteMember($memberData, $membershipData) {
        try {
            $connection = $this->pdo;
            $connection->beginTransaction();

            // 1. Create user account
            $createUserQuery = "INSERT INTO users (username, password, role_id, is_active) VALUES (?, ?, 3, 1)";
            $userStmt = $connection->prepare($createUserQuery);
            $userStmt->execute([$memberData['username'], password_hash($memberData['password'], PASSWORD_DEFAULT)]);
            $userId = $connection->lastInsertId();

            // 2. Add personal details
            $personalQuery = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $personalStmt = $connection->prepare($personalQuery);
            $personalStmt->execute([
                $userId,
                $memberData['first_name'],
                $memberData['middle_name'],
                $memberData['last_name'],
                $memberData['sex'],
                $memberData['birthdate'],
                $memberData['phone_number']
            ]);

            // 3. Handle profile photo if provided
            if (isset($memberData['photo'])) {
                $photo = $memberData['photo'];
                $fileExtension = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $newFileName = $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = '../../../uploads/profile_photos/' . $newFileName;

                if (move_uploaded_file($photo['tmp_name'], $uploadPath)) {
                    $photoQuery = "INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (?, ?, 1)";
                    $photoStmt = $connection->prepare($photoQuery);
                    $photoStmt->execute([$userId, $newFileName]);
                }
            }

            // 4. Create transaction record
            $transactionQuery = "INSERT INTO transactions (user_id, status, created_at) VALUES (?, 'pending', NOW())";
            $transactionStmt = $connection->prepare($transactionQuery);
            $transactionStmt->execute([$userId]);
            $transactionId = $connection->lastInsertId();

            // 5. Add registration fee
            $regFeeQuery = "INSERT INTO registration_records (transaction_id, amount) VALUES (?, ?)";
            $regFeeStmt = $connection->prepare($regFeeQuery);
            $regFeeStmt->execute([$transactionId, $membershipData['registration_fee']]);

            // 6. Add membership
            if (!empty($membershipData['membership_plan'])) {
                $membershipQuery = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) 
                                  VALUES (?, ?, ?, ?, ?, 'active', 1)";
                $membershipStmt = $connection->prepare($membershipQuery);
                
                // Get plan info and calculate end date
                $planQuery = "SELECT plan_type, price FROM membership_plans WHERE id = ?";
                $planStmt = $connection->prepare($planQuery);
                $planStmt->execute([$membershipData['membership_plan']]);
                $planInfo = $planStmt->fetch(PDO::FETCH_ASSOC);
                
                $startDate = $membershipData['start_date'];
                $endDate = ($planInfo['plan_type'] === 'Monthly') 
                    ? date('Y-m-d', strtotime($startDate . ' +1 month'))
                    : date('Y-m-d', strtotime($startDate . ' +1 year'));
                
                $membershipStmt->execute([
                    $transactionId,
                    $membershipData['membership_plan'],
                    $startDate,
                    $endDate,
                    $planInfo['price']
                ]);
            }

            // 7. Add programs if selected
            if (!empty($membershipData['programs'])) {
                $programSubQuery = "INSERT INTO program_subscriptions (transaction_id, program_id, coach_id, start_date, end_date, amount, status, is_paid) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'active', 0)";
                $programSubStmt = $connection->prepare($programSubQuery);
                
                foreach ($membershipData['programs'] as $programId) {
                    $coachId = $membershipData['program_coach'][$programId];
                    
                    // Get price from coach_program_types
                    $priceQuery = "SELECT price FROM coach_program_types WHERE program_id = ? AND coach_id = ?";
                    $priceStmt = $connection->prepare($priceQuery);
                    $priceStmt->execute([$programId, $coachId]);
                    $price = $priceStmt->fetch(PDO::FETCH_ASSOC)['price'];
                    
                    $startDate = $membershipData['start_date'];
                    $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
                    
                    $programSubStmt->execute([
                        $transactionId,
                        $programId,
                        $coachId,
                        $startDate,
                        $endDate,
                        $price
                    ]);
                }
            }

            // 8. Add rental services if selected
            if (!empty($membershipData['rentals'])) {
                $rentalSubQuery = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id, start_date, end_date, amount, status, is_paid) 
                                 VALUES (?, ?, ?, ?, ?, 'active', 0)";
                $rentalSubStmt = $connection->prepare($rentalSubQuery);
                
                foreach ($membershipData['rentals'] as $rentalId) {
                    $rentalPriceQuery = "SELECT price FROM rental_services WHERE id = ?";
                    $rentalPriceStmt = $connection->prepare($rentalPriceQuery);
                    $rentalPriceStmt->execute([$rentalId]);
                    $rentalPrice = $rentalPriceStmt->fetch(PDO::FETCH_ASSOC)['price'];
                    
                    $startDate = $membershipData['start_date'];
                    $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
                    
                    $rentalSubStmt->execute([
                        $transactionId,
                        $rentalId,
                        $startDate,
                        $endDate,
                        $rentalPrice
                    ]);
                }
            }

            $connection->commit();
            return ['success' => true, 'message' => 'Member registration completed successfully'];

        } catch (Exception $e) {
            if ($connection) {
                $connection->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Helper functions for member registration
    public function getMembershipPlans() {
        try {
            $connection = $this->pdo;
            $query = "SELECT * FROM membership_plans WHERE status = 'active'";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPrograms() {
        try {
            $connection = $this->pdo;
            $query = "SELECT * FROM programs WHERE status = 'active'";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getProgramCoaches() {
        try {
            $connection = $this->pdo;
            $query = "SELECT 
                        cpt.program_id,
                        cpt.coach_id,
                        cpt.price,
                        CONCAT(pd.last_name, ', ', pd.first_name, ' ', COALESCE(pd.middle_name, '')) as coach_name
                      FROM coach_program_types cpt
                      JOIN users u ON cpt.coach_id = u.id
                      JOIN personal_details pd ON u.id = pd.user_id
                      WHERE cpt.status = 'active'";
            
            error_log("Executing coach query: " . $query);
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Coach query result: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log("Error getting program coaches: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getRentalServices() {
        try {
            $connection = $this->pdo;
            $query = "SELECT * FROM rental_services WHERE status = 'active'";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRegistrationFee() {
        try {
            $connection = $this->pdo;
            $query = "SELECT membership_fee FROM registration";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['membership_fee'];
        } catch (Exception $e) {
            return 0;
        }
    }

    // Validate Phase 1 data
    private function validatePhase1Data($data) {
        $errors = [];
        
        // Required fields
        $requiredFields = ['first_name', 'last_name', 'sex', 'birthdate', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        // Validate birthdate
        if (isset($data['birthdate']) && trim($data['birthdate']) !== '') {
            $birthdate = strtotime($data['birthdate']);
            $today = strtotime(date('Y-m-d'));
            if ($birthdate >= $today) {
                $errors['birthdate'] = 'Birthdate cannot be today or a future date';
            }
        }

        // Validate sex
        if (isset($data['sex']) && !in_array($data['sex'], ['Male', 'Female'])) {
            $errors['sex'] = 'Invalid sex value';
        }

        // Validate phone number format (optional, add your specific validation rules)
        if (isset($data['phone_number']) && trim($data['phone_number']) !== '') {
            // Add your phone number validation logic here if needed
            // For example: if (!preg_match('/^[0-9]{11}$/', $data['phone_number'])) {
            //     $errors['phone_number'] = 'Invalid phone number format';
            // }
        }

        return $errors;
    }

    // Validate Phase 2 data
    private function validatePhase2Data($data) {
        $errors = [];
        
        // Check if membership plan is selected
        if (!isset($data['membership_plan']) || trim($data['membership_plan']) === '') {
            $errors['membership_plan'] = 'Please select a membership plan';
        }

        return $errors;
    }

    // Save personal details (Phase 1)
    public function savePhase1($data) {
        try {
            // Validate the data first
            $validationErrors = $this->validatePhase1Data($data);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'errors' => $validationErrors,
                    'message' => 'Validation failed'
                ];
            }

            $this->pdo->beginTransaction();

            // Generate username and password
            $username = strtolower($data['first_name']) . rand(100, 999);
            $password = bin2hex(random_bytes(4)); // 8 characters

            // Insert into users table
            $userQuery = "INSERT INTO users (username, password, role_id, is_active) VALUES (?, ?, 3, 0)";
            $userStmt = $this->pdo->prepare($userQuery);
            if (!$userStmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)])) {
                throw new Exception("Failed to create user account");
            }
            $userId = $this->pdo->lastInsertId();
            $this->tempUserId = $userId;

            // Insert personal details
            $detailsQuery = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $detailsStmt = $this->pdo->prepare($detailsQuery);
            if (!$detailsStmt->execute([
                $userId,
                $data['first_name'],
                $data['middle_name'] ?? '',
                $data['last_name'],
                $data['sex'],
                $data['birthdate'],
                $data['phone_number']
            ])) {
                throw new Exception("Failed to save personal details");
            }

            // Handle photo if present
            if (isset($data['photo']) && $data['photo']['error'] === 0) {
                $this->handlePhotoUpload($userId, $data['photo']);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Personal details saved successfully',
                'user_id' => $userId,
                'username' => $username,
                'password' => $password
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Save membership plan (Phase 2)
    public function savePhase2($userId, $data) {
        try {
            $connection = $this->pdo;
            $connection->beginTransaction();
            
            error_log("Starting Phase 2 transaction");

            // Get membership plan details
            $planQuery = "SELECT * FROM membership_plans WHERE id = ? AND status = 'active'";
            $stmt = $connection->prepare($planQuery);
            $stmt->execute([$data['membership_plan']]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                throw new Exception("Invalid membership plan selected");
            }

            // Create transaction record - note: staff_id is NULL for self-registration
            $transactionQuery = "INSERT INTO transactions (staff_id, user_id, status, created_at) 
                                VALUES (NULL, ?, 'pending', NOW())";
            $stmt = $connection->prepare($transactionQuery);
            $stmt->execute([$userId]);
            $transactionId = $connection->lastInsertId();

            // Create membership record
            $membershipQuery = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) 
                               VALUES (?, ?, ?, ?, ?, 'active', 1)";
            $stmt = $connection->prepare($membershipQuery);
                
            // Get plan info and calculate end date
            $planQuery = "SELECT plan_type, price FROM membership_plans WHERE id = ?";
            $planStmt = $connection->prepare($planQuery);
            $planStmt->execute([$data['membership_plan']]);
            $planInfo = $planStmt->fetch(PDO::FETCH_ASSOC);
                
            $startDate = $data['start_date'];
            $endDate = ($planInfo['plan_type'] === 'Monthly') 
                ? date('Y-m-d', strtotime($startDate . ' +1 month'))
                : date('Y-m-d', strtotime($startDate . ' +1 year'));
                
            $stmt->execute([
                $transactionId,
                $plan['id'],
                $startDate,
                $endDate,
                $plan['price']
            ]);

            $connection->commit();
            return ['success' => true, 'transaction_id' => $transactionId];
        } catch (Exception $e) {
            $connection->rollBack();
            error_log("Error in savePhase2: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Save programs and services (Phase 3)
    public function savePhase3($userId, $transactionId, $data) {
        try {
            $this->pdo->beginTransaction();
            error_log("Starting Phase 3 transaction");
            error_log("Phase 3 data received: " . print_r($data, true));

            // Add programs and coaches
            if (!empty($data['program_coach'])) {
                error_log("Processing programs: " . print_r($data['program_coach'], true));
                
                foreach ($data['program_coach'] as $programId => $coachId) {
                    if (!empty($coachId)) {
                        // Get program price
                        $priceQuery = "SELECT price FROM coach_program_types WHERE program_id = ? AND coach_id = ?";
                        $priceStmt = $this->pdo->prepare($priceQuery);
                        $priceStmt->execute([$programId, $coachId]);
                        $priceInfo = $priceStmt->fetch(PDO::FETCH_ASSOC);
                        error_log("Program price info: " . print_r($priceInfo, true));
                        
                        $programQuery = "INSERT INTO program_subscriptions (transaction_id, program_id, coach_id, amount, status, is_paid) 
                                       VALUES (?, ?, ?, ?, 'pending', 0)";
                        $programStmt = $this->pdo->prepare($programQuery);
                        if (!$programStmt->execute([
                            $transactionId,
                            $programId,
                            $coachId,
                            $priceInfo['price']
                        ])) {
                            throw new Exception("Failed to save program subscription");
                        }
                        error_log("Program subscription saved successfully");
                    }
                }
            }

            // Add rental services
            if (!empty($data['rentals'])) {
                foreach ($data['rentals'] as $rentalId) {
                    $priceQuery = "SELECT price FROM rental_services WHERE id = ?";
                    $priceStmt = $this->pdo->prepare($priceQuery);
                    $priceStmt->execute([$rentalId]);
                    $priceInfo = $priceStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $rentalQuery = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id, amount, status, is_paid) 
                                  VALUES (?, ?, ?, 'pending', 0)";
                    $rentalStmt = $this->pdo->prepare($rentalQuery);
                    if (!$rentalStmt->execute([
                        $transactionId,
                        $rentalId,
                        $priceInfo['price']
                    ])) {
                        throw new Exception("Failed to save rental subscription");
                    }
                }
            }

            $this->pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Phase 3 error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Finalize registration (Phase 4)
    public function finalizeRegistration($userId, $transactionId) {
        try {
            $connection = $this->pdo;
            $connection->beginTransaction();
            error_log("Starting finalization transaction");

            // Update user status
            $userQuery = "UPDATE users SET is_active = 1 WHERE id = ?";
            $stmt = $connection->prepare($userQuery);
            $stmt->execute([$userId]);

            // Update memberships status and is_paid
            $membershipQuery = "UPDATE memberships SET status = 'active', is_paid = 1 WHERE transaction_id = ?";
            $stmt = $connection->prepare($membershipQuery);
            $stmt->execute([$transactionId]);

            // Update transaction status to confirmed
            $transactionQuery = "UPDATE transactions SET status = 'confirmed' WHERE id = ?";
            $stmt = $connection->prepare($transactionQuery);
            $stmt->execute([$transactionId]);

            // Update program subscriptions status and is_paid
            $programQuery = "UPDATE program_subscriptions SET status = 'active', is_paid = 1 WHERE transaction_id = ?";
            $stmt = $connection->prepare($programQuery);
            $stmt->execute([$transactionId]);

            // Update rental subscriptions status and is_paid
            $rentalQuery = "UPDATE rental_subscriptions SET status = 'active', is_paid = 1 WHERE transaction_id = ?";
            $stmt = $connection->prepare($rentalQuery);
            $stmt->execute([$transactionId]);

            $connection->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $connection->rollBack();
            error_log("Error in finalizeRegistration: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Rollback registration
    public function rollbackRegistration($userId) {
        try {
            $this->pdo->beginTransaction();
            error_log("Starting rollback for user ID: " . $userId);

            // Delete rental subscriptions
            $rentalQuery = "DELETE rs FROM rental_subscriptions rs 
                           INNER JOIN transactions t ON rs.transaction_id = t.id 
                           WHERE t.user_id = ?";
            $this->pdo->prepare($rentalQuery)->execute([$userId]);

            // Delete program subscriptions
            $programQuery = "DELETE ps FROM program_subscriptions ps 
                           INNER JOIN transactions t ON ps.transaction_id = t.id 
                           WHERE t.user_id = ?";
            $this->pdo->prepare($programQuery)->execute([$userId]);

            // Delete memberships
            $membershipQuery = "DELETE m FROM memberships m 
                              INNER JOIN transactions t ON m.transaction_id = t.id 
                              WHERE t.user_id = ?";
            $this->pdo->prepare($membershipQuery)->execute([$userId]);

            // Delete transactions
            $transactionQuery = "DELETE FROM transactions WHERE user_id = ?";
            $this->pdo->prepare($transactionQuery)->execute([$userId]);

            // Delete profile photos
            $photoQuery = "DELETE FROM profile_photos WHERE user_id = ?";
            $this->pdo->prepare($photoQuery)->execute([$userId]);

            // Delete personal details
            $detailsQuery = "DELETE FROM personal_details WHERE user_id = ?";
            $this->pdo->prepare($detailsQuery)->execute([$userId]);

            // Delete user
            $userQuery = "DELETE FROM users WHERE id = ?";
            $this->pdo->prepare($userQuery)->execute([$userId]);

            $this->pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Rollback error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Handle termination of incomplete registration
    public function terminateRegistration($userId) {
        try {
            if (!$userId) {
                return ['success' => false, 'message' => 'No user ID provided for termination'];
            }

            // Call the rollback function
            $result = $this->rollbackRegistration($userId);
            
            if ($result['success']) {
                return ['success' => true, 'message' => 'Registration terminated successfully'];
            } else {
                throw new Exception($result['message'] ?? 'Failed to terminate registration');
            }
        } catch (Exception $e) {
            error_log("Termination error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function handlePhotoUpload($userId, $photo) {
        $fileExtension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
        $newFileName = $userId . '_' . time() . '.' . $fileExtension;
        $uploadPath = '../../../uploads/profile_photos/' . $newFileName;
        
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        if (!move_uploaded_file($photo['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to upload photo");
        }

        $photoQuery = "INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (?, ?, 1)";
        $photoStmt = $this->pdo->prepare($photoQuery);
        if (!$photoStmt->execute([$userId, $newFileName])) {
            throw new Exception("Failed to save photo information to database");
        }
    }
}