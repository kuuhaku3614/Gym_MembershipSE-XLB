<?php
require_once(__DIR__ . '/../../../../config.php');

class MemberRegistration {
    private $pdo;

    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            try {
                $database = new Database();
                $this->pdo = $database->connect();
            } catch (PDOException $e) {
                throw $e;
            }
        }
    }

    public function getPrograms() {
        try {
            $sql = "SELECT DISTINCT
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
                LEFT JOIN coach_group_schedule cgs ON cpt.id = cgs.coach_program_type_id
                LEFT JOIN coach_personal_schedule cps ON cpt.id = cps.coach_program_type_id
                WHERE p.status = 'active' 
                AND cpt.status = 'active' 
                AND p.is_removed = 0
                AND u.is_active = 1
                AND u.is_banned = 0
                AND u.role_id IN (4, 6)
                AND (
                    (cpt.type = 'group' AND cgs.id IS NOT NULL)
                    OR 
                    (cpt.type = 'personal' AND cps.id IS NOT NULL)
                )
                ORDER BY p.program_name, cpt.type, pd.first_name, pd.last_name";
            
            $results = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
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
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists");
        }

        if (empty($username) || strlen($username) > 50) {
            throw new Exception("Username must not be empty and should be less than 50 characters");
        }

        return true;
    }

    private function validatePassword($password) {
        if (empty($password) || strlen($password) > 50) {
            throw new Exception("Password must not be empty and should be less than 50 characters");
        }

        return true;
    }

    public function addMember($data) {
        try {
            $this->pdo->beginTransaction();

            if (!isset($data['username']) || !isset($data['password'])) {
                throw new Exception("Username and password are required");
            }

            try {
                $this->validateUsername($data['username']);
                $this->validatePassword($data['password']);
            } catch (Exception $e) {
                throw $e;
            }

            $sql = "INSERT INTO users (username, password, role_id, is_active, created_at, updated_at, is_banned, last_password_change) 
                    VALUES (:username, :password, 3, 1, NOW(), NOW(), 0, NOW())";
            $stmt = $this->pdo->prepare($sql);
            try {
                $result = $stmt->execute([
                    ':username' => $data['username'],
                    ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
                ]);
                if (!$result) {
                    throw new Exception("Failed to create user account");
                }
                $userId = $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                throw new Exception("Database error while creating user");
            }

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
                    throw new Exception("Failed to save personal details");
                }
            } catch (PDOException $e) {
                throw new Exception("Database error while saving personal details");
            }

            try {
                $sql = "INSERT INTO transactions (user_id, status, created_at) 
                        VALUES (:user_id, 'confirmed', NOW())";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    ':user_id' => $userId
                ]);
                if (!$result) {
                    throw new Exception("Failed to create transaction record");
                }
                $transactionId = $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                throw new Exception("Database error while creating transaction");
            }

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
                    throw new Exception("Failed to create registration record");
                }
            } catch (PDOException $e) {
                throw new Exception("Database error while creating registration record");
            }

            if (!empty($data['membership_plan'])) {
                try {
                    $planDetails = $this->getMembershipPlanDetails($data['membership_plan']);
                    if (!$planDetails) {
                        throw new Exception("Invalid membership plan selected");
                    }

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
                        throw new Exception("Failed to process membership plan");
                    }
                } catch (PDOException $e) {
                    throw new Exception("Database error while processing membership");
                }
            }

            if (!empty($data['rental_services'])) {
                try {
                    foreach ($data['rental_services'] as $rentalId) {
                        $rentalDetails = $this->getRentalServiceDetails($rentalId);
                        if (!$rentalDetails) {
                            throw new Exception("Invalid rental service selected");
                        }

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
                            throw new Exception("Failed to process rental service");
                        }
                    }
                } catch (PDOException $e) {
                    throw new Exception("Database error while processing rentals");
                }
            }

            if (!empty($data['selected_programs'])) {
                try {
                    $selectedPrograms = json_decode($data['selected_programs'], true);

                    foreach ($selectedPrograms as $program) {
                        $sql = "INSERT INTO program_subscriptions (user_id, coach_program_type_id, status, transaction_id) 
                                VALUES (:user_id, :program_id, 'pending', :transaction_id)";
                        $stmt = $this->pdo->prepare($sql);
                        $result = $stmt->execute([
                            ':user_id' => $userId,
                            ':program_id' => $program['coach_program_type_id'],
                            ':transaction_id' => $transactionId
                        ]);
                        if (!$result) {
                            throw new Exception("Failed to process program subscription");
                        }

                        $subscriptionId = $this->pdo->lastInsertId();

                        foreach ($program['schedules'] as $schedule) {
                            $sql = "INSERT INTO program_subscription_schedule 
                                    (program_subscription_id, coach_group_schedule_id, coach_personal_schedule_id, 
                                    date, day, start_time, end_time, amount, is_paid) 
                                    VALUES (:sub_id, :group_id, :personal_id, :date, :day, 
                                    :start_time, :end_time, :amount, 0)";
                            $stmt = $this->pdo->prepare($sql);
                            $result = $stmt->execute([
                                ':sub_id' => $subscriptionId,
                                ':group_id' => $program['type'] === 'group' ? $program['id'] : null,
                                ':personal_id' => $program['type'] === 'personal' ? $program['id'] : null,
                                ':date' => $schedule['date'],
                                ':day' => $schedule['day'],
                                ':start_time' => date('H:i', strtotime($schedule['start_time'])),
                                ':end_time' => date('H:i', strtotime($schedule['end_time'])),
                                ':amount' => $schedule['amount']
                            ]);
                            if (!$result) {
                                throw new Exception("Failed to process program schedule");
                            }
                        }
                    }
                } catch (PDOException $e) {
                    throw new Exception("Database error while processing programs");
                }
            }

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Member registered successfully'];
        } catch (Exception $e) {
            $this->pdo->rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getMembershipPlanDetails($planId) {
        try {
            $sql = "SELECT mp.*, dt.type_name as duration_type 
                    FROM membership_plans mp
                    JOIN duration_types dt ON mp.duration_type_id = dt.id
                    WHERE mp.id = :plan_id AND mp.status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get membership plan details");
        }
    }

    public function calculateEndDate($startDate, $duration, $durationType) {
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

    public function getRentalServiceDetails($rentalId) {
        try {
            $sql = "SELECT rs.*, dt.type_name as duration_type 
                    FROM rental_services rs
                    JOIN duration_types dt ON rs.duration_type_id = dt.id
                    WHERE rs.id = :rental_id AND rs.status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':rental_id' => $rentalId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get rental service details");
        }
    }

    public function getCoachProgramType($coachProgramTypeId) {
        try {
            $sql = "SELECT type, description FROM coach_program_types WHERE id = :id";
            $stmt = $this->executeQuery($sql, [':id' => $coachProgramTypeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
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
                    CONCAT(pd.first_name, ' ', pd.last_name) as coach_name,
                    (
                        SELECT COUNT(DISTINCT ps.id) 
                        FROM program_subscription_schedule pss
                        JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id 
                        WHERE pss.coach_group_schedule_id = cgs.id 
                        AND (ps.status = 'active' OR ps.status = 'pending')
                    ) as current_subscribers
                FROM coach_group_schedule cgs
                JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                JOIN users u ON cpt.coach_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE cgs.coach_program_type_id = :coach_program_type_id
                HAVING current_subscribers < capacity
                ORDER BY FIELD(cgs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
            
            $stmt = $this->executeQuery($sql, [':coach_program_type_id' => $coachProgramTypeId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($schedules)) {
                return ['message' => 'No available schedules found for this coach'];
            }
            return $schedules;
        } catch (Exception $e) {
            return ['error' => 'Failed to fetch schedule'];
        }
    }

    public function getCoachPersonalSchedule($coachProgramTypeId) {
        try {
            $bookedSlotsQuery = "
                SELECT DISTINCT
                    pss.coach_personal_schedule_id,
                    pss.day,
                    TIME_FORMAT(pss.start_time, '%H:%i') as start_time,
                    TIME_FORMAT(pss.end_time, '%H:%i') as end_time,
                    cps.duration_rate
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_personal_schedule cps ON pss.coach_personal_schedule_id = cps.id
                WHERE ps.status IN ('active', 'pending')
                AND cps.coach_program_type_id = :coach_program_type_id
                AND pss.day = cps.day";

            $stmt = $this->executeQuery($bookedSlotsQuery, [':coach_program_type_id' => $coachProgramTypeId]);
            $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bookedSlotsMap = [];
            foreach ($bookedSlots as $slot) {
                $day = $slot['day'];
                
                $startTime = strtotime("2000-01-01 " . $slot['start_time']);
                $endTime = strtotime("2000-01-01 " . $slot['end_time']);
                
                if (!isset($bookedSlotsMap[$day])) {
                    $bookedSlotsMap[$day] = [];
                }
                
                $bookedSlotsMap[$day][] = [
                    'start' => $startTime,
                    'end' => $endTime,
                    'day' => $day
                ];
            }

            $sql = "SELECT 
                    cps.id,
                    cps.day,
                    TIME_FORMAT(cps.start_time, '%H:%i') as start_time_raw,
                    TIME_FORMAT(cps.end_time, '%H:%i') as end_time_raw,
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
            $rawSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($rawSchedules)) {
                return ['message' => 'No schedules found for this coach'];
            }

            $processedSchedules = [];
            foreach ($rawSchedules as $schedule) {
                $startTime = strtotime("2000-01-01 " . $schedule['start_time_raw']);
                $endTime = strtotime("2000-01-01 " . $schedule['end_time_raw']);
                $duration = $schedule['duration_rate'];
                
                $totalMinutes = ($endTime - $startTime) / 60;
                $numSlots = floor($totalMinutes / $duration);
                
                for ($i = 0; $i < $numSlots; $i++) {
                    $slotStart = $startTime + ($i * $duration * 60);
                    $slotEnd = $slotStart + ($duration * 60);
                    
                    $isBooked = false;
                    if (isset($bookedSlotsMap[$schedule['day']])) {
                        foreach ($bookedSlotsMap[$schedule['day']] as $bookedSlot) {
                            if ($schedule['day'] === $bookedSlot['day'] && 
                                $slotStart < $bookedSlot['end'] && 
                                $slotEnd > $bookedSlot['start']) {
                                $isBooked = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$isBooked) {
                        $processedSchedules[] = [
                            'id' => $schedule['id'],
                            'day' => $schedule['day'],
                            'start_time' => date('h:i A', $slotStart),
                            'end_time' => date('h:i A', $slotEnd),
                            'duration_rate' => $schedule['duration_rate'],
                            'price' => $schedule['price'],
                            'coach_name' => $schedule['coach_name'],
                            'slot_index' => $i + 1,
                            'total_slots' => $numSlots
                        ];
                    }
                }
            }
            
            if (empty($processedSchedules)) {
                return ['message' => 'No available time slots found for this coach'];
            }
            
            return $processedSchedules;
            
        } catch (Exception $e) {
            return ['error' => 'Failed to fetch schedule'];
        }
    }

    private function executeQuery($sql, $params = []) {
        try {
            
            if (!$this->pdo) {
                throw new Exception("Database connection not available");
            }
            
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            
            $success = $stmt->execute($params);
            if (!$success) {
                throw new Exception("Failed to execute statement");
            }
            
            return $stmt;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
