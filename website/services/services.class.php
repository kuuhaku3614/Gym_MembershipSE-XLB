<?php

require_once __DIR__ . '/../../config.php';

class Services_class{

    // Properties for membership
    public $membership_plan_id = '';
    public $start_date = '';
    public $end_date = '';
    public $total_amount = '';
    
    // Properties for program
    public $program_id = '';
    public $coach_id = '';
    public $program_price = '';
    
    // Properties for rental
    public $rental_id = '';
    public $rental_price = '';

    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function displayStandardPlans(){
        $conn = $this->db->connect();
        $sql = "SELECT mp.id as plan_id, mp.plan_name, mp.price,
                CONCAT(mp.duration, ' ', dt.type_name) as validity
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                WHERE mp.status_id = 1 
                AND (mp.plan_type = 'standard' OR mp.plan_type = 'walk-in')
                ORDER BY mp.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displaySpecialPlans(){
        $conn = $this->db->connect();
        $sql = "SELECT mp.id as plan_id, mp.plan_name, mp.price,
                CONCAT(mp.duration, ' ', dt.type_name) as validity
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                WHERE mp.status_id = 1 AND mp.plan_type = 'special'
                ORDER BY mp.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function displayPrograms() {
        $conn = $this->db->connect();
        $sql = "SELECT p.id as program_id, p.program_name,
                CONCAT(p.duration, ' ', dt.type_name) as validity,
                pt.type_name as program_type
                FROM programs p
                LEFT JOIN duration_types dt ON p.duration_type_id = dt.id
                LEFT JOIN program_types pt ON p.program_type_id = pt.id
                WHERE p.status_id = 1
                ORDER BY p.program_type_id";
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
                WHERE rs.status_id = 1 AND rs.available_slots > 0
                ORDER BY rs.price";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetchGymrate($membership_plan_id){
        $conn = $this->db->connect();
        $sql = "SELECT mp.*, dt.type_name as duration_type 
                FROM membership_plans mp
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id 
                WHERE mp.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$membership_plan_id]);
        return $stmt->fetch();
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

    public function fetchProgram($program_id) {
        $conn = $this->db->connect();
        $sql = "SELECT p.*, dt.type_name as duration_type, 
                pt.type_name as program_type
                FROM programs p
                LEFT JOIN duration_types dt ON p.duration_type_id = dt.id
                LEFT JOIN program_types pt ON p.program_type_id = pt.id
                WHERE p.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$program_id]);
        return $stmt->fetch();
    }

    public function fetchProgramCoaches($program_id) {
        $conn = $this->db->connect();
        $sql = "SELECT DISTINCT u.id as coach_id, 
                CONCAT(pd.first_name, ' ', pd.last_name) as coach_name,
                cpt.price as coach_price,
                cpt.description
                FROM users u
                JOIN personal_details pd ON u.id = pd.user_id
                JOIN coach_program_types cpt ON u.id = cpt.coach_id
                WHERE cpt.program_id = ?
                AND u.is_active = 1 
                AND u.role_id = (SELECT id FROM roles WHERE role_name = 'coach')
                ORDER BY pd.last_name, pd.first_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$program_id]);
        return $stmt->fetchAll();
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

    public function saveProgram($membership_id, $program_id, $coach_id, $start_date, $end_date, $price) {
        $conn = $this->db->connect();
        try {
            $conn->beginTransaction();

            // Verify coach exists and is active
            $verify_coach = "SELECT id FROM users 
                            WHERE id = ? AND is_active = 1 
                            AND role_id = (SELECT id FROM roles WHERE role_name = 'coach')";
            $stmt = $conn->prepare($verify_coach);
            $stmt->execute([$coach_id]);
            if (!$stmt->fetch()) {
                return false;
            }

            $sql = "INSERT INTO program_subscriptions (membership_id, program_id, coach_id, 
                    start_date, end_date, price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$membership_id, $program_id, $coach_id, $start_date, $end_date, $price]);

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
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
}