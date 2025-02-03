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
            WHERE u.role_id = 3
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
                u.id = :userId
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

            $stmt = $this->pdo->prepare($query);
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

    public function getPrograms($membershipDuration = null, $membershipDurationTypeId = null) {
        try {
            $connection = $this->pdo;
            $sql = "SELECT p.id, p.program_name, p.description, p.duration, dt.type_name as duration_type,
                           MIN(cpt.price) as price 
                    FROM programs p
                    JOIN duration_types dt ON p.duration_type_id = dt.id
                    LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id AND cpt.status = 'active'
                    WHERE p.status = 'active' AND p.is_removed = 0
                    GROUP BY p.id, p.program_name, p.description, p.duration, dt.type_name";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting programs: " . $e->getMessage());
            return [];
        }
    }

    public function getRentalServices($membershipDuration = null, $membershipDurationTypeId = null) {
        try {
            $connection = $this->pdo;
            $sql = "SELECT rs.id, rs.service_name as rental_name, rs.description, rs.price,
                           rs.duration, dt.type_name as duration_type 
                    FROM rental_services rs
                    JOIN duration_types dt ON rs.duration_type_id = dt.id
                    WHERE rs.status = 'active' AND rs.is_removed = 0";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting rental services: " . $e->getMessage());
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
}