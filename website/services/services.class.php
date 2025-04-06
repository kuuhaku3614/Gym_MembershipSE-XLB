<?php

require_once __DIR__ . '/../../config.php';

class Services_class{

    // Properties for membership
    public $membership_plan_id = '';
    public $start_date = '';
    public $end_date = '';
    public $total_amount = '';
    
    // Properties for rental
    public $rental_id = '';
    public $rental_price = '';

    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function checkActiveMembership($user_id) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT m.* 
                    FROM memberships m
                    JOIN transactions t ON m.transaction_id = t.id
                    WHERE t.user_id = ? 
                    AND m.status = 'active' 
                    AND m.end_date >= CURDATE()
                    AND m.start_date <= CURDATE()
                    AND m.is_paid = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetch() ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function displayStandardPlans(){
        $conn = $this->db->connect();
        $sql = "SELECT mp.id as plan_id, mp.plan_name, mp.price, mp.image,
                CONCAT(mp.duration, ' ', dt.type_name) as validity
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                WHERE mp.status = 'active' 
                AND (mp.plan_type = 'standard' OR mp.plan_type = 'walk-in')
                ORDER BY mp.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displaySpecialPlans(){
        $conn = $this->db->connect();
        $sql = "SELECT mp.id as plan_id, mp.plan_name, mp.price, mp.image,
                CONCAT(mp.duration, ' ', dt.type_name) as validity
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                WHERE mp.status = 'active' AND mp.plan_type = 'special'
                ORDER BY mp.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displayProgramServices() {
        $conn = $this->db->connect();
        $sql = "SELECT DISTINCT 
                p.id as program_id,
                p.program_name,
                p.description,
                p.image,
                GROUP_CONCAT(DISTINCT cpt.type) as available_types
                FROM programs p
                LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id
                WHERE p.status = 'active'
                GROUP BY p.id, p.program_name, p.description, p.image
                ORDER BY p.program_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displayRentalServices(){
        $conn = $this->db->connect();
        $sql = "SELECT rs.id as rental_id, rs.service_name, rs.price,
                CONCAT(rs.duration, ' ', dt.type_name) as validity,
                rs.available_slots
                FROM rental_services rs
                LEFT JOIN duration_types dt ON rs.duration_type_id = dt.id
                WHERE rs.status = 'active' AND rs.available_slots > 0
                ORDER BY rs.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displayWalkinServices(){
        $conn = $this->db->connect();
        $sql = "SELECT w.id as walkin_id, w.price,
                CONCAT(w.duration, ' ', dt.type_name) as validity
                FROM walk_in w
                LEFT JOIN duration_types dt ON w.duration_type_id = dt.id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchGymrate($membership_plan_id) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT mp.*, dt.type_name as duration_type 
                    FROM membership_plans mp
                    LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id 
                    WHERE mp.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$membership_plan_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            // Log error or handle it
            return null; // Return null if there's an error
        }
    }

    public function saveMembership($user_id, $membership_plan_id, $start_date, $end_date, $total_amount) {
        $conn = $this->db->connect();
        try {
            $sql = "INSERT INTO memberships (user_id, membership_plan_id, start_date, end_date, 
                    total_amount, status) VALUES (?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $user_id,
                $membership_plan_id,
                $start_date,
                $end_date,
                $total_amount
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fetchRental($rental_id) {
        $conn = $this->db->connect();
        $sql = "SELECT rs.*, dt.type_name as duration_type 
                FROM rental_services rs
                LEFT JOIN duration_types dt ON rs.duration_type_id = dt.id 
                WHERE rs.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$rental_id]);
        return $stmt->fetch();
    }

    public function saveRental($membership_id, $rental_id, $start_date, $end_date, $price) {
        $conn = $this->db->connect();
        try {
            $conn->beginTransaction();

            // Check available slots
            $check_slots = "SELECT available_slots FROM rental_services WHERE id = ?";
            $stmt = $conn->prepare($check_slots);
            $stmt->execute([$rental_id]);
            $result = $stmt->fetch();
            
            if ($result['available_slots'] < 1) {
                return false;
            }

            // Insert rental subscription
            $sql = "INSERT INTO rental_subscriptions (membership_id, rental_service_id,
                    start_date, end_date, price, status) 
                    VALUES (?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$membership_id, $rental_id, $start_date, $end_date, $price]);

            // Update available slots
            $update_sql = "UPDATE rental_services 
                          SET available_slots = available_slots - 1 
                          WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->execute([$rental_id]);

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function fetchMembership($plan_id) {
        $conn = $this->db->connect();
        $sql = "SELECT mp.*, dt.type_name as duration_type 
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id 
                WHERE mp.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$plan_id]);
        return $stmt->fetch();
    }

    public function fetchWalkin($walkin_id) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT w.id, w.price, w.duration,
                    dt.type_name as duration_type
                    FROM walk_in w
                    LEFT JOIN duration_types dt ON w.duration_type_id = dt.id
                    WHERE w.id = :walkin_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':walkin_id', $walkin_id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function checkUserRole($user_id) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT role_id FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            // Check if user is a member (role_id = 4) or a coach (role_id = 3)
            return $result['role_id'] == 4 || $result['role_id'] == 3;
        } catch (Exception $e) {
            throw new Exception('Error checking user role: ' . $e->getMessage());
        }
    }

    public function getPrograms($programId = null) {
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
                LEFT JOIN (
                    SELECT coach_program_type_id, 'group' as schedule_type
                    FROM coach_group_schedule
                    UNION
                    SELECT coach_program_type_id, 'personal' as schedule_type
                    FROM coach_personal_schedule
                ) schedules ON cpt.id = schedules.coach_program_type_id
                WHERE p.status = 'active' 
                AND p.is_removed = 0
                AND u.is_active = 1 
                AND u.is_banned = 0 
                AND u.role_id = 4
                AND schedules.coach_program_type_id IS NOT NULL";

            if ($programId !== null) {
                $sql .= " AND p.id = ?";
            }

            $sql .= " ORDER BY p.program_name, cpt.type, pd.first_name, pd.last_name";

            $stmt = $this->db->connect()->prepare($sql);
            if ($programId !== null) {
                $stmt->execute([$programId]);
            } else {
                $stmt->execute();
            }

            $results = $stmt->fetchAll();
            if (empty($results)) {
                return null;
            }

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
                    'program_type_description' => $row['program_type_description'],
                    'program_type' => $row['program_type']
                ];
            }

            return array_values($programs);
        } catch (Exception $e) {
            error_log("Error in getPrograms: " . $e->getMessage());
            return null;
        }
    }

    public function getProgram($programId) {
        try {
            $sql = "SELECT id as program_id, program_name, program_description, program_type
                    FROM programs 
                    WHERE id = ? AND status = 'active' AND is_removed = 0";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$programId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error in getProgram: " . $e->getMessage());
            return null;
        }
    }

    public function getPersonalScheduleById($scheduleId) {
        try {
            $sql = "SELECT id, day, TIME_FORMAT(time, '%H:%i') as time, duration
                    FROM coach_personal_schedule 
                    WHERE id = ? AND is_removed = 0";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$scheduleId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error in getPersonalScheduleById: " . $e->getMessage());
            return null;
        }
    }

    public function getGroupScheduleById($scheduleId) {
        try {
            $sql = "SELECT id, day, TIME_FORMAT(time, '%H:%i') as time, capacity
                    FROM coach_group_schedule 
                    WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$scheduleId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error in getGroupScheduleById: " . $e->getMessage());
            return null;
        }
    }

    public function getCoachProgramType($coachProgramTypeId) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT cpt.*, p.program_name, p.description as program_description,
                           CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
                    FROM coach_program_types cpt
                    JOIN programs p ON cpt.program_id = p.id
                    JOIN users u ON cpt.coach_id = u.id
                    JOIN personal_details pd ON u.id = pd.user_id
                    WHERE cpt.id = :id 
                    AND cpt.status = 'active'
                    AND p.status = 'active'
                    AND p.is_removed = 0
                    AND u.is_active = 1
                    AND u.is_banned = 0";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $coachProgramTypeId);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error in getCoachProgramType: " . $e->getMessage());
            return null;
        }
    }

    public function getCoachPersonalSchedule($coachProgramTypeId) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT cps.*, 
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM program_subscription_schedule pss
                            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                            WHERE pss.coach_personal_schedule_id = cps.id
                            AND ps.status = 'active'
                        ) THEN 'booked'
                        ELSE 'available'
                    END as availability_status
                    FROM coach_personal_schedule cps
                    WHERE cps.coach_program_type_id = :id
                    ORDER BY FIELD(cps.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    cps.start_time";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $coachProgramTypeId);
            $stmt->execute();
            $schedules = $stmt->fetchAll();
            
            if (empty($schedules)) {
                return ['message' => 'No personal training schedules available for this coach'];
            }
            
            return $schedules;
        } catch (Exception $e) {
            error_log("Error in getCoachPersonalSchedule: " . $e->getMessage());
            return ['error' => 'Failed to fetch personal training schedules'];
        }
    }

    public function getCoachGroupSchedule($coachProgramTypeId) {
        $conn = $this->db->connect();
        try {
            $sql = "SELECT cgs.*,
                    (
                        SELECT COUNT(DISTINCT ps.id)
                        FROM program_subscription_schedule pss
                        JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                        WHERE pss.coach_group_schedule_id = cgs.id
                        AND ps.status = 'active'
                    ) as current_members,
                    CASE 
                        WHEN (
                            SELECT COUNT(DISTINCT ps.id)
                            FROM program_subscription_schedule pss
                            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                            WHERE pss.coach_group_schedule_id = cgs.id
                            AND ps.status = 'active'
                        ) >= cgs.capacity THEN 'full'
                        ELSE 'available'
                    END as availability_status
                    FROM coach_group_schedule cgs
                    WHERE cgs.coach_program_type_id = :id
                    ORDER BY FIELD(cgs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    cgs.start_time";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $coachProgramTypeId);
            $stmt->execute();
            $schedules = $stmt->fetchAll();
            
            if (empty($schedules)) {
                return ['message' => 'No group training schedules available for this coach'];
            }
            
            return $schedules;
        } catch (Exception $e) {
            error_log("Error in getCoachGroupSchedule: " . $e->getMessage());
            return ['error' => 'Failed to fetch group training schedules'];
        }
    }

}