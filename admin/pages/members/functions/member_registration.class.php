<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/config.php");

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

    public function addMember($data) {
        try {
            $this->pdo->beginTransaction();

            // 1. Insert into users table
            $sql = "INSERT INTO users (username, password, role_id, is_active) VALUES (:username, :password, 3, 1)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            $userId = $this->pdo->lastInsertId();

            // 2. Insert personal details
            $sql = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) 
                    VALUES (:user_id, :first_name, :middle_name, :last_name, :sex, :birthdate, :phone_number)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => trim($data['first_name']),
                ':middle_name' => trim($data['middle_name'] ?? ''),
                ':last_name' => trim($data['last_name']),
                ':sex' => $data['sex'],
                ':birthdate' => $data['birthdate'],
                ':phone_number' => trim($data['phone_number'])
            ]);

            // 3. Create transaction record
            $sql = "INSERT INTO transactions (user_id, status) VALUES (:user_id, 'pending')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $transactionId = $this->pdo->lastInsertId();

            // 4. Insert registration record
            $registrationFee = $this->getRegistrationFee();
            $sql = "INSERT INTO registration_records (transaction_id, registration_id, amount, is_paid) 
                    VALUES (:transaction_id, 1, :amount, 0)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':transaction_id' => $transactionId,
                ':amount' => $registrationFee
            ]);

            // 5. Insert membership record if membership plan is selected
            if (!empty($data['membership_plan_id'])) {
                $membershipPlan = $this->getMembershipPlanDuration($data['membership_plan_id']);
                if ($membershipPlan) {
                    $startDate = date('Y-m-d');
                    $endDate = $this->calculateEndDate($startDate, $membershipPlan['duration'], $membershipPlan['duration_type_id']);
                    
                    $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) 
                            VALUES (:transaction_id, :plan_id, :start_date, :end_date, :amount, 'pending', 0)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':transaction_id' => $transactionId,
                        ':plan_id' => $data['membership_plan_id'],
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':amount' => $data['membership_amount']
                    ]);
                }
            }

            // 6. Insert program subscriptions if programs are selected
            if (!empty($data['program_coaches'])) {
                foreach ($data['program_coaches'] as $programId => $coachProgramTypeId) {
                    if (empty($coachProgramTypeId)) continue;

                    $sql = "INSERT INTO program_subscriptions (user_id, coach_program_type_id, status) 
                            VALUES (:user_id, :coach_program_type_id, 'pending')";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':coach_program_type_id' => $coachProgramTypeId
                    ]);
                    $programSubscriptionId = $this->pdo->lastInsertId();

                    // Add program schedules if selected
                    if (!empty($data['program_schedules'][$coachProgramTypeId])) {
                        foreach ($data['program_schedules'][$coachProgramTypeId] as $schedule) {
                            $sql = "INSERT INTO program_subscription_schedule 
                                    (program_subscription_id, coach_group_schedule_id, coach_personal_schedule_id, 
                                    date, day, start_time, end_time, amount, is_paid) 
                                    VALUES (:subscription_id, :group_id, :personal_id, :date, :day, 
                                    :start_time, :end_time, :amount, 0)";
                            $stmt = $this->pdo->prepare($sql);
                            $stmt->execute([
                                ':subscription_id' => $programSubscriptionId,
                                ':group_id' => $schedule['type'] === 'group' ? $schedule['schedule_id'] : null,
                                ':personal_id' => $schedule['type'] === 'personal' ? $schedule['schedule_id'] : null,
                                ':date' => $schedule['date'],
                                ':day' => $schedule['day'],
                                ':start_time' => $schedule['start_time'],
                                ':end_time' => $schedule['end_time'],
                                ':amount' => $schedule['amount']
                            ]);
                        }
                    }
                }
            }

            // 7. Insert rental subscriptions if rentals are selected
            if (!empty($data['rental_services'])) {
                foreach ($data['rental_services'] as $rentalId) {
                    $rentalService = $this->getRentalServiceDetails($rentalId);
                    if (!$rentalService) continue;

                    $startDate = date('Y-m-d');
                    $endDate = $this->calculateEndDate($startDate, $rentalService['duration'], $rentalService['duration_type_id']);

                    $sql = "INSERT INTO rental_subscriptions 
                            (transaction_id, rental_service_id, start_date, end_date, amount, status, is_paid) 
                            VALUES (:transaction_id, :rental_id, :start_date, :end_date, :amount, 'pending', 0)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':transaction_id' => $transactionId,
                        ':rental_id' => $rentalId,
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':amount' => $rentalService['price']
                    ]);
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'user_id' => $userId, 'transaction_id' => $transactionId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in addMember: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function calculateEndDate($startDate, $duration, $durationType) {
        $start = new DateTime($startDate);
        switch ($durationType) {
            case 1: // days
                return $start->modify("+{$duration} days")->format('Y-m-d');
            case 2: // months
                return $start->modify("+{$duration} months")->format('Y-m-d');
            case 3: // years
                return $start->modify("+{$duration} years")->format('Y-m-d');
            default:
                throw new Exception("Invalid duration type");
        }
    }

    private function getRentalServiceDetails($rentalId) {
        try {
            $sql = "SELECT * FROM rental_services WHERE id = :id AND status = 'active'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $rentalId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
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
