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
                -- Count unpaid active/expiring memberships
                (
                    SELECT COUNT(*)
                    FROM transactions t2
                    JOIN memberships m2 ON t2.id = m2.transaction_id
                    WHERE t2.user_id = u.id
                    AND t2.status = 'confirmed'
                    AND m2.status IN ('active', 'expiring')
                    AND m2.is_paid = 0
                ) as unpaid_memberships,
                -- Count all unpaid rentals regardless of status
                (
                    SELECT COUNT(*)
                    FROM transactions t3
                    JOIN rental_subscriptions rs ON t3.id = rs.transaction_id
                    WHERE t3.user_id = u.id
                    AND t3.status = 'confirmed'
                    AND rs.is_paid = 0
                    AND rs.end_date >= CURRENT_DATE -- Only count non-expired rentals
                ) as unpaid_rentals,
                -- For debugging: Get details of unpaid rentals
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
                // Debug rental details
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
            // First query to get member details and memberships
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
                -- Get all non-expired memberships
                COALESCE(
                    GROUP_CONCAT(
                        DISTINCT
                        CASE 
                            WHEN m.id IS NOT NULL AND m.status != 'expired' THEN
                                CONCAT_WS('|',
                                    'membership',
                                    mp.plan_name,
                                    m.start_date,
                                    m.end_date,
                                    m.status,
                                    m.is_paid,
                                    m.amount
                                )
                        END
                    ), ''
                ) as memberships
            FROM users u 
            JOIN roles roles ON u.role_id = roles.id AND roles.id = 3 
            LEFT JOIN personal_details pd ON u.id = pd.user_id 
            LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
            -- Get memberships
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

            // Parse memberships
            if ($result['memberships']) {
                $memberships = explode(',', $result['memberships']);
                $result['memberships'] = array_map(function($item) {
                    list($type, $name, $start, $end, $status, $isPaid, $amount) = explode('|', $item);
                    return [
                        'plan_name' => $name,
                        'start_date' => $start,
                        'end_date' => $end,
                        'status' => $status,
                        'is_paid' => $isPaid == '1',
                        'amount' => $amount
                    ];
                }, array_filter($memberships)); // Remove null values
            } else {
                $result['memberships'] = [];
            }

            // Second query to get rental services
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

            // Convert is_paid to boolean
            foreach ($result['rental_services'] as &$service) {
                $service['is_paid'] = $service['is_paid'] == '1';
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in getMemberDetails: " . $e->getMessage());
            throw $e;
        }
    }

    public function getMembershipPlans() {
        try {
            $connection = $this->pdo;
            $sql = "SELECT mp.*, dt.type_name as duration_type, dt.id as duration_type_id 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.status = 'active'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting membership plans: " . $e->getMessage());
            return [];
        }
    }

    public function getMembershipPlan($planId) {
        try {
            $sql = "SELECT mp.*, dt.type_name as duration_type, dt.id as duration_type_id 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($plan) {
                $_SESSION['membership_plan'] = [
                    'duration' => $plan['duration'],
                    'duration_type_id' => $plan['duration_type_id']
                ];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error getting membership plan: " . $e->getMessage());
            return false;
        }
    }

    public function storeMembershipPlan($planId) {
        try {
            if (!isset($planId)) {
                throw new Exception('No plan ID provided');
            }

            $sql = "SELECT mp.*, dt.type_name as duration_type, dt.id as duration_type_id 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                throw new Exception('Plan not found');
            }

            // Store plan in session
            $_SESSION['membership_plan'] = [
                'id' => $plan['id'],
                'duration' => $plan['duration'],
                'duration_type_id' => $plan['duration_type_id'],
                'duration_type' => $plan['duration_type']
            ];

            return true;
        } catch (Exception $e) {
            error_log("Error storing membership plan: " . $e->getMessage());
            return false;
        }
    }

    public function getMembershipPlanDuration($planId) {
        try {
            $sql = "SELECT mp.duration, mp.duration_type_id 
                    FROM membership_plans mp
                    WHERE mp.id = :plan_id AND mp.status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting membership plan duration: " . $e->getMessage());
            return null;
        }
    }

    public function getPrograms($membershipDuration = null, $membershipDurationTypeId = null) {
        try {
            $connection = $this->pdo;
            $sql = "SELECT p.id, p.program_name, p.description, p.duration, dt.type_name as duration_type,
                           MIN(cpt.price) as price 
                    FROM programs p
                    JOIN duration_types dt ON p.duration_type_id = dt.id
                    LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id AND cpt.status = 'active'
                    WHERE p.status = 'active' AND p.is_removed = 0";
            
            // Add duration filter if membership duration is provided
            if ($membershipDuration !== null && $membershipDurationTypeId !== null) {
                $sql .= " AND (
                    -- If same duration type, directly compare durations
                    (p.duration_type_id = :duration_type_id AND p.duration <= :duration)
                    OR 
                    -- If different duration types, convert both to days and compare
                    (p.duration_type_id != :duration_type_id AND 
                        CASE p.duration_type_id
                            WHEN 1 THEN p.duration -- days
                            WHEN 2 THEN p.duration * 30 -- months to days
                            WHEN 3 THEN p.duration * 365 -- years to days
                        END <= 
                        CASE :duration_type_id
                            WHEN 1 THEN :duration -- days
                            WHEN 2 THEN :duration * 30 -- months to days
                            WHEN 3 THEN :duration * 365 -- years to days
                        END
                    )
                )";
            }
            
            $sql .= " GROUP BY p.id, p.program_name, p.description, p.duration, dt.type_name";
            
            $stmt = $connection->prepare($sql);
            
            if ($membershipDuration !== null && $membershipDurationTypeId !== null) {
                $stmt->bindParam(':duration_type_id', $membershipDurationTypeId, PDO::PARAM_INT);
                $stmt->bindParam(':duration', $membershipDuration, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting programs: " . $e->getMessage());
            return [];
        }
    }

    public function getProgramsByDuration($duration, $durationTypeId) {
        return $this->getPrograms($duration, $durationTypeId);
    }

    public function getRentalServices($membershipDuration = null, $membershipDurationTypeId = null) {
        try {
            $connection = $this->pdo;
            $sql = "SELECT rs.id, rs.service_name as rental_name, rs.description, rs.price,
                           rs.duration, dt.type_name as duration_type 
                    FROM rental_services rs
                    JOIN duration_types dt ON rs.duration_type_id = dt.id
                    WHERE rs.status = 'active' AND rs.is_removed = 0";
            
            // Add duration filter if membership duration is provided
            if ($membershipDuration !== null && $membershipDurationTypeId !== null) {
                $sql .= " AND (
                    -- If same duration type, directly compare durations
                    (rs.duration_type_id = :duration_type_id AND rs.duration <= :duration)
                    OR 
                    -- If different duration types, convert both to days and compare
                    (rs.duration_type_id != :duration_type_id AND 
                        CASE rs.duration_type_id
                            WHEN 1 THEN rs.duration -- days
                            WHEN 2 THEN rs.duration * 30 -- months to days
                            WHEN 3 THEN rs.duration * 365 -- years to days
                        END <= 
                        CASE :duration_type_id
                            WHEN 1 THEN :duration -- days
                            WHEN 2 THEN :duration * 30 -- months to days
                            WHEN 3 THEN :duration * 365 -- years to days
                        END
                    )
                )";
            }
            
            $stmt = $connection->prepare($sql);
            
            if ($membershipDuration !== null && $membershipDurationTypeId !== null) {
                $stmt->bindParam(':duration_type_id', $membershipDurationTypeId, PDO::PARAM_INT);
                $stmt->bindParam(':duration', $membershipDuration, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting rental services: " . $e->getMessage());
            return [];
        }
    }

    public function getRentalServicesByDuration($duration, $durationTypeId) {
        return $this->getRentalServices($duration, $durationTypeId);
    }

    public function getProgramCoaches() {
        try {
            $query = "SELECT 
                        cpt.id as program_type_id,
                        cpt.coach_id,
                        cpt.program_id,
                        cpt.price,
                        cpt.type,
                        CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
                    FROM coach_program_types cpt
                    JOIN users u ON cpt.coach_id = u.id
                    JOIN personal_details pd ON u.id = pd.user_id
                    WHERE cpt.status = 'active'
                    ORDER BY pd.last_name, pd.first_name";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getProgramCoaches: " . $e->getMessage());
            return array();
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

    private function handlePhotoUpload($userId, $photo) {
        $fileExtension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
        $newFileName = 'uploads/profile_' . $userId . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = '../../../' . $newFileName;
        
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

    public function validatePhase1($data) {
        $errors = [];
        
        // Validate first name
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }
        
        // Validate last name
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }
        
        // Validate sex
        if (empty($data['sex'])) {
            $errors['sex'] = 'Please select your sex';
        }
        
        // Validate birthdate
        if (empty($data['birthdate'])) {
            $errors['birthdate'] = 'Birthdate is required';
        } else {
            $birthdate = new DateTime($data['birthdate']);
            $today = new DateTime();
            if ($birthdate > $today) {
                $errors['birthdate'] = 'Birthdate cannot be in the future';
            }
        }
        
        // Validate phone (must be 11 digits)
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[0-9]{11}$/', $data['phone'])) {
            $errors['phone'] = 'Phone number must be 11 digits';
        }
        
        return $errors;
    }

    public function generateUsername($firstName) {
        error_log("Starting username generation for: " . $firstName);
        try {
            if (!$this->pdo) {
                error_log("No database connection!");
                throw new Exception("Database connection not available");
            }

            $baseUsername = strtolower($firstName);
            error_log("Base username: " . $baseUsername);
            $randomNumbers = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $username = $baseUsername . $randomNumbers;
            error_log("Initial username attempt: " . $username);

            // Keep trying until we find an available username
            while (true) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                if (!$stmt) {
                    error_log("Failed to prepare statement: " . print_r($this->pdo->errorInfo(), true));
                    throw new Exception("Database error while checking username");
                }
                
                $result = $stmt->execute([$username]);
                if (!$result) {
                    error_log("Failed to execute statement: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Database error while checking username");
                }
                
                if ($stmt->fetchColumn() == 0) {
                    error_log("Found available username: " . $username);
                    return $username;
                }
                error_log("Username taken, trying another...");
                $randomNumbers = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $username = $baseUsername . $randomNumbers;
            }
        } catch (Exception $e) {
            error_log("Error in generateUsername: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function generateCredentials($firstName) {
        try {
            error_log("Starting credential generation for: " . $firstName);
            
            if (!$this->pdo) {
                error_log("No database connection in generateCredentials!");
                throw new Exception("Database connection not available");
            }

            if (empty($firstName)) {
                error_log("First name is empty!");
                throw new Exception("First name cannot be empty");
            }

            error_log("Generating username...");
            $username = $this->generateUsername($firstName);
            error_log("Generated username: " . $username);
            
            error_log("Generating password...");
            $password = $this->generatePassword();
            error_log("Generated password: " . $password);
            
            $credentials = [
                'username' => $username,
                'password' => $password
            ];
            error_log("Credentials generated successfully: " . print_r($credentials, true));
            
            return $credentials;
        } catch (Exception $e) {
            error_log('Error in generateCredentials: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw new Exception('Failed to generate credentials: ' . $e->getMessage());
        }
    }

    private function generatePassword($length = 8) {
        error_log("Starting password generation with length: " . $length);
        try {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $characters[rand(0, strlen($characters) - 1)];
            }
            error_log("Generated password successfully");
            return $password;
        } catch (Exception $e) {
            error_log("Error in generatePassword: " . $e->getMessage());
            throw $e;
        }
    }

    public function addMember($data) {
        try {
            error_log('Starting member registration with data: ' . print_r($data, true));
            
            $this->pdo->beginTransaction();

            // Use pre-generated credentials from hidden fields
            $username = $data['username'];
            $password = $data['password'];

            // 1. Create user account
            $sql = "INSERT INTO users (username, password, role_id, is_active) VALUES (:username, :password, 3, 1)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            $userId = $this->pdo->lastInsertId();
            error_log('User created with ID: ' . $userId);

            // Store credentials for display in Phase 4
            $data['generated_username'] = $username;
            $data['generated_password'] = $password;

            // 2. Add personal details
            $sql = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) 
                    VALUES (:user_id, :first_name, :middle_name, :last_name, :sex, :birthdate, :phone)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $data['first_name'],
                ':middle_name' => $data['middle_name'] ?? '',
                ':last_name' => $data['last_name'],
                ':sex' => $data['sex'],
                ':birthdate' => $data['birthdate'],
                ':phone' => $data['phone']
            ]);
            error_log('Personal details added');

            // 3. Create transaction record
            $sql = "INSERT INTO transactions (user_id, status, created_at) VALUES (:user_id, 'confirmed', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $transactionId = $this->pdo->lastInsertId();
            error_log('Transaction created with ID: ' . $transactionId);

            // 4. Add registration fee
            $sql = "INSERT INTO registration_records (transaction_id, amount) VALUES (:transaction_id, :amount)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':transaction_id' => $transactionId,
                ':amount' => $this->getRegistrationFee()
            ]);
            error_log('Registration fee added');

            // 5. Add membership
            // Get plan info and calculate end date
            $sql = "SELECT mp.plan_type, mp.price, mp.duration, dt.type_name as duration_type 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.id = :plan_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':plan_id' => $data['membership_plan']]);
            $planInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $startDate = $data['start_date'];
            $endDate = date('Y-m-d', strtotime($startDate . ' +' . $planInfo['duration'] . ' ' . strtolower($planInfo['duration_type'])));

            // Convert membership duration to days for comparison
            $membershipDays = 0;
            switch(strtolower($planInfo['duration_type'])) {
                case 'days':
                    $membershipDays = $planInfo['duration'];
                    break;
                case 'months':
                    $membershipDays = $planInfo['duration'] * 30;
                    break;
                case 'year':
                    $membershipDays = $planInfo['duration'] * 365;
                    break;
            }

            $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) 
                    VALUES (:transaction_id, :plan_id, :start_date, :end_date, :amount, 'active', 1)";
            $stmt = $this->pdo->prepare($sql);
                
            $stmt->execute([
                ':transaction_id' => $transactionId,
                ':plan_id' => $data['membership_plan'],
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':amount' => $planInfo['price']
            ]);
            error_log('Membership added');

            // 6. Add programs if selected
            if (isset($data['program_coach']) && !empty($data['program_coach'])) {
                $programCoaches = is_array($data['program_coach']) ? $data['program_coach'] : json_decode($data['program_coach'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid program_coach data format: ' . json_last_error_msg());
                }
                
                error_log('Program coaches data: ' . print_r($programCoaches, true));

                if (!empty($programCoaches)) {
                    $programSubQuery = "INSERT INTO program_subscriptions (transaction_id, program_id, coach_id, start_date, end_date, amount, status, is_paid) 
                                      VALUES (:transaction_id, :program_id, :coach_id, :start_date, :end_date, :amount, 'active', 1)";
                    $programSubStmt = $this->pdo->prepare($programSubQuery);
                    
                    foreach ($programCoaches as $program) {
                        $programId = $program['program_id'];
                        $coachId = $program['coach_id'];
                        
                        // Get price and duration info from programs table
                        $priceQuery = "SELECT cpt.price, p.duration, dt.type_name as duration_type 
                                     FROM coach_program_types cpt
                                     JOIN programs p ON p.id = cpt.program_id
                                     JOIN duration_types dt ON p.duration_type_id = dt.id
                                     WHERE cpt.program_id = :program_id AND cpt.coach_id = :coach_id";
                        $priceStmt = $this->pdo->prepare($priceQuery);
                        $priceStmt->execute([
                            ':program_id' => $programId,
                            ':coach_id' => $coachId
                        ]);
                        $programInfo = $priceStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$programInfo) {
                            throw new Exception("Could not find price for program $programId with coach $coachId");
                        }

                        // Convert program duration to days and validate
                        $programDays = 0;
                        switch(strtolower($programInfo['duration_type'])) {
                            case 'days':
                                $programDays = $programInfo['duration'];
                                break;
                            case 'months':
                                $programDays = $programInfo['duration'] * 30;
                                break;
                            case 'year':
                                $programDays = $programInfo['duration'] * 365;
                                break;
                        }

                        if ($programDays > $membershipDays) {
                            throw new Exception("Program duration exceeds membership duration. Please choose a program with shorter duration.");
                        }
                        
                        $programStartDate = $data['start_date'];
                        $programEndDate = date('Y-m-d', strtotime($programStartDate . ' +' . $programInfo['duration'] . ' ' . strtolower($programInfo['duration_type'])));
                        
                        error_log("Adding program subscription: Program ID: $programId, Coach ID: $coachId, Price: {$programInfo['price']}");
                        
                        $programSubStmt->execute([
                            ':transaction_id' => $transactionId,
                            ':program_id' => $programId,
                            ':coach_id' => $coachId,
                            ':start_date' => $programStartDate,
                            ':end_date' => $programEndDate,
                            ':amount' => $programInfo['price']
                        ]);
                        $programSubscriptionId = $this->pdo->lastInsertId();

                        // Insert schedules for this program subscription
                        if (isset($program['schedules']) && !empty($program['schedules'])) {
                            $scheduleQuery = "INSERT INTO program_subscription_schedule (program_subscription_id, day, start_time, end_time) 
                                            VALUES (:program_subscription_id, :day, :start_time, :end_time)";
                            $scheduleStmt = $this->pdo->prepare($scheduleQuery);
                            foreach ($program['schedules'] as $schedule) {
                                $scheduleStmt->execute([
                                    ':program_subscription_id' => $programSubscriptionId,
                                    ':day' => $schedule['day'],
                                    ':start_time' => $schedule['start_time'],
                                    ':end_time' => $schedule['end_time']
                                ]);
                            }
                        }
                    }
                }
            }

            // 7. Add rentals if selected
            if (isset($data['rentals']) && !empty($data['rentals'])) {
                $rentals = is_array($data['rentals']) ? $data['rentals'] : json_decode($data['rentals'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid rentals data format: ' . json_last_error_msg());
                }
                
                error_log('Rentals data: ' . print_r($rentals, true));

                if (!empty($rentals)) {
                    foreach ($rentals as $rentalId) {
                        // Get rental info including duration
                        $rentalQuery = "SELECT rs.price, rs.duration, dt.type_name as duration_type 
                                      FROM rental_services rs
                                      JOIN duration_types dt ON rs.duration_type_id = dt.id 
                                      WHERE rs.id = :rental_id";
                        $rentalStmt = $this->pdo->prepare($rentalQuery);
                        $rentalStmt->execute([':rental_id' => $rentalId]);
                        $rentalInfo = $rentalStmt->fetch(PDO::FETCH_ASSOC);

                        // Convert rental duration to days and validate
                        $rentalDays = 0;
                        switch(strtolower($rentalInfo['duration_type'])) {
                            case 'days':
                                $rentalDays = $rentalInfo['duration'];
                                break;
                            case 'months':
                                $rentalDays = $rentalInfo['duration'] * 30;
                                break;
                            case 'year':
                                $rentalDays = $rentalInfo['duration'] * 365;
                                break;
                        }

                        if ($rentalDays > $membershipDays) {
                            throw new Exception("Rental duration exceeds membership duration. Please choose a rental with shorter duration.");
                        }
                        
                        $rentalStartDate = $data['start_date'];
                        $rentalEndDate = date('Y-m-d', strtotime($rentalStartDate . ' +' . $rentalInfo['duration'] . ' ' . strtolower($rentalInfo['duration_type'])));
                        
                        $rentalSubQuery = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id, start_date, end_date, amount, status, is_paid) 
                                         VALUES (:transaction_id, :rental_id, :start_date, :end_date, :amount, 'active', 1)";
                        $rentalSubStmt = $this->pdo->prepare($rentalSubQuery);
                        $rentalSubStmt->execute([
                            ':transaction_id' => $transactionId,
                            ':rental_id' => $rentalId,
                            ':start_date' => $rentalStartDate,
                            ':end_date' => $rentalEndDate,
                            ':amount' => $rentalInfo['price']
                        ]);
                    }
                }
            }

            // 8. Handle photo upload if provided
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                error_log('Processing photo upload');
                $uploadDir = '../../../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'uploads/profile_' . $userId . '_' . uniqid() . '.' . $extension;
                $targetPath = '../../../' . $filename;

                error_log('Moving uploaded file to: ' . $targetPath);
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $sql = "INSERT INTO profile_photos (user_id, photo_path, is_active) VALUES (:user_id, :photo_path, 1)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':photo_path' => $filename
                    ]);
                    error_log('Photo path added to database: ' . $filename);
                }
            }

            $this->pdo->commit();
            error_log('Member registration completed successfully');
            return [
                'success' => true, 
                'message' => 'Member registered successfully',
                'generated_username' => $username,
                'generated_password' => $password
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Error in addMember - Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('General error in addMember: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function processPayment($userId, $type = 'all', $itemId = null) {
        try {
            error_log("Processing payment - Type: $type, User: $userId, Item: $itemId");
            $this->pdo->beginTransaction();

            $success = false;
            
            if ($type === 'all') {
                // Update membership payment status
                $sql = "UPDATE memberships m 
                       JOIN transactions t ON m.transaction_id = t.id 
                       SET m.is_paid = 1, t.status = 'confirmed'
                       WHERE t.user_id = ? AND m.status = 'active'";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([$userId]);
                error_log("Membership payment update result: " . ($success ? "Success" : "Failed"));

                // Update program subscriptions payment status
                $sql = "UPDATE program_subscriptions ps 
                       JOIN transactions t ON ps.transaction_id = t.id 
                       SET ps.is_paid = 1 
                       WHERE t.user_id = ? AND ps.status = 'active'";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([$userId]) && $success;
                error_log("Program subscriptions payment update result: " . ($success ? "Success" : "Failed"));

                // Update rental subscriptions payment status
                $sql = "UPDATE rental_subscriptions rs 
                       JOIN transactions t ON rs.transaction_id = t.id 
                       SET rs.is_paid = 1 
                       WHERE t.user_id = ? AND rs.status = 'active'";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([$userId]) && $success;
                error_log("Rental subscriptions payment update result: " . ($success ? "Success" : "Failed"));
            } else {
                // Keep the original single-item payment logic
                switch($type) {
                    case 'membership':
                        $sql = "UPDATE memberships m 
                               SET m.is_paid = 1 
                               WHERE m.id = ? AND EXISTS (
                                   SELECT 1 FROM transactions t 
                                   WHERE t.id = m.transaction_id 
                                   AND t.user_id = ?
                               )";
                        $stmt = $this->pdo->prepare($sql);
                        $success = $stmt->execute([$itemId, $userId]);
                        
                        if ($success) {
                            $sql = "UPDATE transactions t 
                                   JOIN memberships m ON t.id = m.transaction_id 
                                   SET t.status = 'confirmed' 
                                   WHERE m.id = ? AND t.user_id = ?";
                            $stmt = $this->pdo->prepare($sql);
                            $success = $stmt->execute([$itemId, $userId]);
                        }
                        break;

                    case 'program':
                        $sql = "UPDATE program_subscriptions ps 
                               SET ps.is_paid = 1 
                               WHERE ps.id = ? AND EXISTS (
                                   SELECT 1 FROM transactions t 
                                   WHERE t.id = ps.transaction_id 
                                   AND t.user_id = ?
                               )";
                        $stmt = $this->pdo->prepare($sql);
                        $success = $stmt->execute([$itemId, $userId]);
                        break;

                    case 'rental':
                        $sql = "UPDATE rental_subscriptions rs 
                               SET rs.is_paid = 1 
                               WHERE rs.id = ? AND EXISTS (
                                   SELECT 1 FROM transactions t 
                                   WHERE t.id = rs.transaction_id 
                                   AND t.user_id = ?
                               )";
                        $stmt = $this->pdo->prepare($sql);
                        $success = $stmt->execute([$itemId, $userId]);
                        break;
                }
            }

            if ($success) {
                $this->pdo->commit();
                error_log("Transaction committed successfully");
                return ['success' => true, 'message' => 'Payment processed successfully'];
            } else {
                $this->pdo->rollBack();
                error_log("Transaction rolled back - no rows updated");
                return ['success' => false, 'message' => 'No records were updated'];
            }
        } catch (PDOException $e) {
            error_log("Database error in processPayment: " . $e->getMessage());
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function handleRequest($data) {
        try {
            if (!isset($data['action'])) {
                throw new Exception('No action specified');
            }

            switch ($data['action']) {
                case 'validate_phase1':
                    return $this->validatePhase1($data);
                case 'add_member':
                    return $this->addMember($data);
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            error_log('Error in handleRequest: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getCoachSchedule($programTypeId) {
        try {
            error_log("Getting schedule for program type ID: " . $programTypeId);
            
            // First get the coach's full availability
            $query = "SELECT day, start_time, end_time 
                     FROM coach_availability 
                     WHERE coach_program_type_id = :program_type_id
                     ORDER BY 
                        CASE day
                            WHEN 'Monday' THEN 1
                            WHEN 'Tuesday' THEN 2
                            WHEN 'Wednesday' THEN 3
                            WHEN 'Thursday' THEN 4
                            WHEN 'Friday' THEN 5
                            WHEN 'Saturday' THEN 6
                            WHEN 'Sunday' THEN 7
                        END,
                        start_time";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':program_type_id', $programTypeId, PDO::PARAM_INT);
            $stmt->execute();
            
            $coachAvailability = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Coach availability: " . json_encode($coachAvailability));
            
            // Get all reserved times for this coach program type
            $query = "SELECT pss.day, pss.start_time, pss.end_time
                     FROM program_subscription_schedule pss
                     JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                     JOIN coach_program_types cpt ON ps.coach_id = cpt.coach_id 
                        AND cpt.program_id = ps.program_id
                     WHERE cpt.id = :program_type_id
                        AND ps.status = 'active'
                     ORDER BY 
                        CASE pss.day
                            WHEN 'Monday' THEN 1
                            WHEN 'Tuesday' THEN 2
                            WHEN 'Wednesday' THEN 3
                            WHEN 'Thursday' THEN 4
                            WHEN 'Friday' THEN 5
                            WHEN 'Saturday' THEN 6
                            WHEN 'Sunday' THEN 7
                        END,
                        pss.start_time";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':program_type_id', $programTypeId, PDO::PARAM_INT);
            $stmt->execute();
            
            $reservedTimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Reserved times: " . json_encode($reservedTimes));
            
            // Process each day's availability
            $availableDays = array();
            
            // Group availability by day
            $availabilityByDay = array();
            foreach ($coachAvailability as $availability) {
                $day = $availability['day'];
                if (!isset($availabilityByDay[$day])) {
                    $availabilityByDay[$day] = array();
                }
                $availabilityByDay[$day][] = $availability;
            }
            
            // Process each day
            foreach ($availabilityByDay as $day => $dayAvailabilities) {
                $dayReservations = array_filter($reservedTimes, function($res) use ($day) {
                    return $res['day'] === $day;
                });
                
                $timeSlots = array();
                
                // Process each availability slot for the day
                foreach ($dayAvailabilities as $availability) {
                    $availStart = strtotime($availability['start_time']);
                    $availEnd = strtotime($availability['end_time']);
                    $currentStart = $availStart;
                    
                    // Find reservations that overlap with this availability slot
                    $slotReservations = array_filter($dayReservations, function($res) use ($availStart, $availEnd) {
                        $resStart = strtotime($res['start_time']);
                        $resEnd = strtotime($res['end_time']);
                        return ($resStart < $availEnd && $resEnd > $availStart);
                    });
                    
                    // Sort reservations by start time
                    usort($slotReservations, function($a, $b) {
                        return strtotime($a['start_time']) - strtotime($b['start_time']);
                    });
                    
                    // Process each reservation within this availability slot
                    foreach ($slotReservations as $reservation) {
                        $resStart = strtotime($reservation['start_time']);
                        $resEnd = strtotime($reservation['end_time']);
                        
                        // If there's a gap before this reservation, add it
                        if ($currentStart < $resStart) {
                            $timeSlots[] = array(
                                'start_time' => date("g:i A", $currentStart),
                                'end_time' => date("g:i A", $resStart)
                            );
                        }
                        
                        $currentStart = $resEnd;
                    }
                    
                    // Add remaining time after last reservation in this slot
                    if ($currentStart < $availEnd) {
                        $timeSlots[] = array(
                            'start_time' => date("g:i A", $currentStart),
                            'end_time' => date("g:i A", $availEnd)
                        );
                    }
                }
                
                if (!empty($timeSlots)) {
                    if (!isset($availableDays[$day])) {
                        $availableDays[$day] = array();
                    }
                    $availableDays[$day] = $timeSlots;
                }
                
                error_log("Processed day $day: " . json_encode($timeSlots));
            }
            
            // Create a complete week schedule
            $allDays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            $schedule = array();
            
            foreach ($allDays as $day) {
                if (isset($availableDays[$day]) && !empty($availableDays[$day])) {
                    $schedule[] = array(
                        'day' => $day,
                        'time_slots' => $availableDays[$day],
                        'available' => true
                    );
                } else {
                    $schedule[] = array(
                        'day' => $day,
                        'time_slots' => array(),
                        'available' => false
                    );
                }
            }
            
            $response = array(
                'success' => true,
                'schedule' => $schedule
            );
            error_log("Final schedule response: " . json_encode($response));
            return $response;
            
        } catch (PDOException $e) {
            error_log("Error in getCoachSchedule: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error fetching schedule: ' . $e->getMessage()
            );
        }
    }
}