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
                    'coach_program_type_id' => $row['coach_program_type_id']
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
                           rs.duration, dt.type_name as duration_type 
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
            $sql = "SELECT amount FROM registration_fee WHERE is_active = 1 LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['amount']) : 0;
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

            // Insert into users table
            $sql = "INSERT INTO users (username, password, role_id) VALUES (:username, :password, 3)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            $userId = $this->pdo->lastInsertId();

            // Insert personal details
            $sql = "INSERT INTO personal_details (user_id, first_name, last_name, sex, birthdate, phone_number) 
                    VALUES (:user_id, :first_name, :last_name, :sex, :birthdate, :phone_number)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':sex' => $data['sex'],
                ':birthdate' => $data['birthdate'],
                ':phone_number' => $data['phone_number']
            ]);

            // Create transaction record
            $sql = "INSERT INTO transactions (user_id, status) VALUES (:user_id, 'pending')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $transactionId = $this->pdo->lastInsertId();

            // Insert registration record
            $sql = "INSERT INTO registration_records (transaction_id, amount) 
                    VALUES (:transaction_id, :amount)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':transaction_id' => $transactionId,
                ':amount' => $this->getRegistrationFee()
            ]);

            $this->pdo->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
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
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':coach_program_type_id' => $coachProgramTypeId]);
            
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
}
