<?php
require_once('config.php');

class MemberRegistration {
    private $pdo;

    public function __construct() {
        try {
            $database = new Database();
            $this->pdo = $database->connect();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getPrograms() {
        try {
            $sql = "SELECT 
                    p.id as program_id,
                    p.program_name,
                    p.description as program_description,
                    cpt.type as program_type,
                    cpt.id as coach_program_type_id,
                    cpt.description as program_type_description,
                    CONCAT(pd.first_name, ' ', pd.last_name) as coach_name,
                    u.id as coach_id
                FROM programs p
                JOIN coach_program_types cpt ON p.id = cpt.program_id
                JOIN users u ON cpt.coach_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE p.status = 'active' 
                AND cpt.status = 'active' 
                AND p.is_removed = 0
                AND u.is_active = 1
                AND u.is_banned = 0
                AND u.role_id = 4
                ORDER BY p.program_name, cpt.type, pd.first_name, pd.last_name";
            
            $results = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by program and type
            $programs = [];
            foreach ($results as $row) {
                $key = $row['program_id'] . '_' . $row['program_type'];
                if (!isset($programs[$key])) {
                    $programs[$key] = [
                        'program_id' => $row['program_id'],
                        'program_name' => $row['program_name'],
                        'program_description' => $row['program_description'],
                        'program_type' => $row['program_type'],
                        'coaches' => []
                    ];
                }
                
                $programs[$key]['coaches'][] = [
                    'coach_id' => $row['coach_id'],
                    'coach_name' => $row['coach_name'],
                    'coach_program_type_id' => $row['coach_program_type_id'],
                    'program_type_description' => $row['program_type_description']
                ];
            }
            
            return array_values($programs);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRentalServices() {
        try {
            $sql = "SELECT rs.id, rs.service_name as rental_name, rs.description, rs.price,
                           rs.duration, rs.duration_type_id, dt.type_name as duration_type 
                    FROM rental_services rs
                    JOIN duration_types dt ON rs.duration_type_id = dt.id
                    WHERE rs.status = 'active' AND rs.is_removed = 0";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRegistrationFee() {
        try {
            $sql = "SELECT membership_fee FROM registration WHERE id = 1";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['membership_fee']) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getMembershipPlans() {
        try {
            $sql = "SELECT mp.*, dt.type_name as duration_type, dt.id as duration_type_id 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.status = 'active'";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
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
            return null;
        }
    }

    private function validateUsername($username) {
        // Check if username already exists
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists");
        }

        // Simple validation: just check if not empty and within reasonable length
        if (empty($username) || strlen($username) > 50) {
            throw new Exception("Username must not be empty and should be less than 50 characters");
        }

        return true;
    }

    private function validatePassword($password) {
        // Simple validation: just check if not empty and within reasonable length
        if (empty($password) || strlen($password) > 50) {
            throw new Exception("Password must not be empty and should be less than 50 characters");
        }

        return true;
    }

    public function addMember($data) {
        try {
            error_log("\n=== START MEMBER REGISTRATION DEBUG ===");
            error_log("Received data: " . print_r($data, true));
            
            $this->pdo->beginTransaction();
            error_log("Transaction started");

            // Validate username and password first
            if (!isset($data['username']) || !isset($data['password'])) {
                error_log("ERROR: Missing username or password");
                throw new Exception("Username and password are required");
            }

            try {
                error_log("Validating username...");
                $this->validateUsername($data['username']);
                error_log("Username validation passed");
                
                error_log("Validating password...");
                $this->validatePassword($data['password']);
                error_log("Password validation passed");
            } catch (Exception $e) {
                error_log("Validation Error: " . $e->getMessage());
                throw $e;
            }

            // 1. Insert into users table
            error_log("Step 1: Inserting into users table");
            $sql = "INSERT INTO users (username, password, role_id, is_active, created_at, updated_at, is_banned, last_password_change) 
                    VALUES (:username, :password, 3, 1, NOW(), NOW(), 0, NOW())";
            $stmt = $this->pdo->prepare($sql);
            try {
                $result = $stmt->execute([
                    ':username' => $data['username'],
                    ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
                ]);
                if (!$result) {
                    error_log("ERROR: Failed to insert user. PDO Error: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to create user account");
                }
                $userId = $this->pdo->lastInsertId();
                error_log("User created successfully with ID: " . $userId);
            } catch (PDOException $e) {
                error_log("PDO ERROR in user creation: " . $e->getMessage());
                throw new Exception("Database error while creating user");
            }

            // 2. Insert personal details
            error_log("Step 2: Inserting personal details");
            try {
                $sql = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) 
                        VALUES (:user_id, :first_name, :middle_name, :last_name, :sex, :birthdate, :phone_number)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    ':user_id' => $userId,
                    ':first_name' => $data['first_name'],
                    ':middle_name' => $data['middle_name'] ?: null,
                    ':last_name' => $data['last_name'],
                    ':sex' => $data['sex'],
                    ':birthdate' => $data['birthdate'],
                    ':phone_number' => $data['contact']
                ]);
                if (!$result) {
                    error_log("ERROR: Failed to insert personal details. PDO Error: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to save personal details");
                }
                error_log("Personal details saved successfully");
            } catch (PDOException $e) {
                error_log("PDO ERROR in personal details: " . $e->getMessage());
                throw new Exception("Database error while saving personal details");
            }

            // Create a single transaction for all records
            error_log("Creating main transaction record");
            try {
                $sql = "INSERT INTO transactions (user_id, status, created_at) 
                        VALUES (:user_id, 'confirmed', NOW())";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    ':user_id' => $userId
                ]);
                if (!$result) {
                    error_log("ERROR: Failed to create transaction. PDO Error: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to create transaction record");
                }
                $transactionId = $this->pdo->lastInsertId();
                error_log("Main transaction created with ID: " . $transactionId);

                // 3. Insert registration record
                error_log("Step 3: Creating registration record");
                try {
                    $registrationFee = $this->getRegistrationFee();
                    $sql = "INSERT INTO registration_records (transaction_id, registration_id, amount, is_paid) 
                            VALUES (:transaction_id, 1, :amount, 0)";
                    $stmt = $this->pdo->prepare($sql);
                    $result = $stmt->execute([
                        ':transaction_id' => $transactionId,
                        ':amount' => $registrationFee
                    ]);
                    if (!$result) {
                        error_log("ERROR: Failed to create registration record. PDO Error: " . print_r($stmt->errorInfo(), true));
                        throw new Exception("Failed to create registration record");
                    }
                    error_log("Registration record created successfully");
                } catch (PDOException $e) {
                    error_log("PDO ERROR in registration record: " . $e->getMessage());
                    throw new Exception("Database error while creating registration record");
                }

                // 4. Handle membership plan if selected
                if (!empty($data['membership_plan'])) {
                    error_log("Step 4: Processing membership plan");
                    try {
                        // Get membership plan details
                        $planDetails = $this->getMembershipPlanDetails($data['membership_plan']);
                        if (!$planDetails) {
                            throw new Exception("Invalid membership plan selected");
                        }
                        error_log("Membership plan details: " . print_r($planDetails, true));
                        
                        $startDate = $data['membership_start_date'] ?? date('Y-m-d');
                        $endDate = $this->calculateEndDate($startDate, $planDetails['duration'], $planDetails['duration_type']);
                        
                        $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) 
                                VALUES (:transaction_id, :plan_id, :start_date, :end_date, :amount, 'active', 0)";
                        $stmt = $this->pdo->prepare($sql);
                        $result = $stmt->execute([
                            ':transaction_id' => $transactionId,
                            ':plan_id' => $data['membership_plan'],
                            ':start_date' => $startDate,
                            ':end_date' => $endDate,
                            ':amount' => $planDetails['price']
                        ]);
                        if (!$result) {
                            error_log("ERROR: Failed to create membership. PDO Error: " . print_r($stmt->errorInfo(), true));
                            throw new Exception("Failed to process membership plan");
                        }
                        error_log("Membership plan processed successfully");
                    } catch (PDOException $e) {
                        error_log("PDO ERROR in membership plan: " . $e->getMessage());
                        throw new Exception("Database error while processing membership");
                    }
                }

                // 6. Handle rental services
                if (!empty($data['rental_services'])) {
                    error_log("Step 6: Processing rental services");
                    try {
                        foreach ($data['rental_services'] as $rentalId) {
                            $rentalDetails = $this->getRentalServiceDetails($rentalId);
                            if (!$rentalDetails) {
                                throw new Exception("Invalid rental service selected");
                            }
                            error_log("Processing rental service: " . print_r($rentalDetails, true));
                            
                            $startDate = date('Y-m-d');
                            $endDate = $this->calculateEndDate($startDate, $rentalDetails['duration'], $rentalDetails['duration_type']);
                            
                            $sql = "INSERT INTO rental_subscriptions 
                                    (transaction_id, rental_service_id, start_date, end_date, amount, status, is_paid) 
                                    VALUES (:transaction_id, :rental_id, :start_date, :end_date, :amount, 'active', 0)";
                            $stmt = $this->pdo->prepare($sql);
                            $result = $stmt->execute([
                                ':transaction_id' => $transactionId,
                                ':rental_id' => $rentalId,
                                ':start_date' => $startDate,
                                ':end_date' => $endDate,
                                ':amount' => $rentalDetails['price']
                            ]);
                            if (!$result) {
                                error_log("ERROR: Failed to create rental subscription. PDO Error: " . print_r($stmt->errorInfo(), true));
                                throw new Exception("Failed to process rental service");
                            }
                            error_log("Rental service processed successfully");
                        }
                    } catch (PDOException $e) {
                        error_log("PDO ERROR in rental services: " . $e->getMessage());
                        throw new Exception("Database error while processing rentals");
                    }
                }

                // 5. Handle program subscriptions
                if (!empty($data['selected_programs'])) {
                    error_log("Step 5: Processing program subscriptions");
                    try {
                        $selectedPrograms = json_decode($data['selected_programs'], true);
                        error_log("Selected programs: " . print_r($selectedPrograms, true));
                        
                        foreach ($selectedPrograms as $program) {
                            // Insert program subscription
                            $sql = "INSERT INTO program_subscriptions (user_id, coach_program_type_id, status) 
                                    VALUES (:user_id, :program_id, 'pending')";
                            $stmt = $this->pdo->prepare($sql);
                            $result = $stmt->execute([
                                ':user_id' => $userId,
                                ':program_id' => $program['coach_program_type_id']
                            ]);
                            if (!$result) {
                                error_log("ERROR: Failed to create program subscription. PDO Error: " . print_r($stmt->errorInfo(), true));
                                throw new Exception("Failed to process program subscription");
                            }
                            
                            $subscriptionId = $this->pdo->lastInsertId();
                            error_log("Program subscription created with ID: " . $subscriptionId);
                            
                            // Insert schedule
                            $sql = "INSERT INTO program_subscription_schedule 
                                    (program_subscription_id, coach_group_schedule_id, coach_personal_schedule_id, date, day, start_time, end_time, amount, is_paid) 
                                    VALUES (:sub_id, :group_id, :personal_id, :date, :day, :start_time, :end_time, :amount, 0)";
                            $stmt = $this->pdo->prepare($sql);
                            $result = $stmt->execute([
                                ':sub_id' => $subscriptionId,
                                ':group_id' => $program['type'] === 'group' ? $program['id'] : null,
                                ':personal_id' => $program['type'] === 'personal' ? $program['id'] : null,
                                ':date' => date('Y-m-d'),
                                ':day' => $program['day'],
                                ':start_time' => $program['startTime'],
                                ':end_time' => $program['endTime'],
                                ':amount' => $program['price']
                            ]);
                            if (!$result) {
                                error_log("ERROR: Failed to create program schedule. PDO Error: " . print_r($stmt->errorInfo(), true));
                                throw new Exception("Failed to process program schedule");
                            }
                            error_log("Program schedule created successfully");
                        }
                    } catch (PDOException $e) {
                        error_log("PDO ERROR in program subscriptions: " . $e->getMessage());
                        throw new Exception("Database error while processing programs");
                    }
                }

            } catch (PDOException $e) {
                error_log("PDO ERROR in main transaction: " . $e->getMessage());
                throw new Exception("Database error while processing main transaction");
            }

            $this->pdo->commit();
            error_log("Transaction committed successfully");
            error_log("=== END MEMBER REGISTRATION DEBUG ===\n");
            
            return ['success' => true, 'message' => 'Member registered successfully'];
            
        } catch (Exception $e) {
            error_log("CRITICAL ERROR: " . $e->getMessage());
            error_log("Rolling back transaction");
            $this->pdo->rollBack();
            error_log("=== END MEMBER REGISTRATION DEBUG WITH ERROR ===\n");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getMembershipPlanDetails($planId) {
        try {
            $sql = "SELECT mp.*, dt.type_name as duration_type 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.id = :plan_id AND mp.status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMembershipPlanDetails: " . $e->getMessage());
            throw new Exception("Failed to get membership plan details");
        }
    }

    private function calculateEndDate($startDate, $duration, $durationType) {
        $start = new DateTime($startDate);
        switch ($durationType) {
            case 'days':
                return $start->modify("+{$duration} days")->format('Y-m-d');
            case 'months':
                return $start->modify("+{$duration} months")->format('Y-m-d');
            case 'year':
                return $start->modify("+{$duration} year")->format('Y-m-d');
            default:
                throw new Exception("Invalid duration type");
        }
    }

    private function getRentalServiceDetails($rentalId) {
        try {
            $sql = "SELECT rs.*, dt.type_name as duration_type 
                    FROM rental_services rs
                    JOIN duration_types dt ON rs.duration_type_id = dt.id
                    WHERE rs.id = :rental_id AND rs.status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':rental_id' => $rentalId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getRentalServiceDetails: " . $e->getMessage());
            throw new Exception("Failed to get rental service details");
        }
    }

    public function getCoachProgramType($coachProgramTypeId) {
        try {
            $sql = "SELECT type FROM coach_program_types WHERE id = :id";
            $stmt = $this->executeQuery($sql, [':id' => $coachProgramTypeId]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error in getCoachProgramType: " . $e->getMessage());
            return null;
        }
    }

    public function getCoachGroupSchedule($coachProgramTypeId) {
        try {
            $sql = "SELECT 
                    cgs.id,
                    cgs.day,
                    TIME_FORMAT(cgs.start_time, '%h:%i %p') as start_time,
                    TIME_FORMAT(cgs.end_time, '%h:%i %p') as end_time,
                    cgs.capacity,
                    cgs.price,
                    CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
                FROM coach_group_schedule cgs
                JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                JOIN users u ON cpt.coach_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE cgs.coach_program_type_id = :coach_program_type_id
                ORDER BY FIELD(cgs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
            
            $stmt = $this->executeQuery($sql, [':coach_program_type_id' => $coachProgramTypeId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($schedules)) {
                return ['message' => 'No schedules found for this coach'];
            }
            return $schedules;
            
        } catch (Exception $e) {
            error_log("Error in getCoachGroupSchedule: " . $e->getMessage());
            return ['error' => 'Failed to fetch schedule'];
        }
    }

    public function getCoachPersonalSchedule($coachProgramTypeId) {
        try {
            $sql = "SELECT 
                    cps.id,
                    cps.day,
                    TIME_FORMAT(cps.start_time, '%h:%i %p') as start_time,
                    TIME_FORMAT(cps.end_time, '%h:%i %p') as end_time,
                    cps.duration_rate,
                    cps.price,
                    CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
                FROM coach_personal_schedule cps
                JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
                JOIN users u ON cpt.coach_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE cps.coach_program_type_id = :coach_program_type_id
                ORDER BY FIELD(cps.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
            
            $stmt = $this->executeQuery($sql, [':coach_program_type_id' => $coachProgramTypeId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($schedules)) {
                return ['message' => 'No schedules found for this coach'];
            }
            return $schedules;
            
        } catch (Exception $e) {
            error_log("Error in getCoachPersonalSchedule: " . $e->getMessage());
            return ['error' => 'Failed to fetch schedule'];
        }
    }

    private function executeQuery($sql, $params = []) {
        try {
            error_log("Executing query: " . $sql);
            error_log("Parameters: " . print_r($params, true));
            
            if (!$this->pdo) {
                error_log("PDO connection is null");
                throw new Exception("Database connection not available");
            }
            
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log("Failed to prepare statement");
                throw new Exception("Failed to prepare statement");
            }
            
            $success = $stmt->execute($params);
            if (!$success) {
                error_log("Failed to execute statement: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to execute statement");
            }
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error executing query: " . $e->getMessage());
            throw $e;
        }
    }
}
