<?php

require_once __DIR__ . '/../../config.php';

class Services_class{

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

    public function saveMembership($user_id, $membership_plan_id, $total_amount) {
        $conn = $this->db->connect();
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Calculate end date based on duration and duration type
            $plan = $this->fetchGymrate($membership_plan_id);
            $duration = $plan['duration'];
            $duration_type = $plan['duration_type'];
            
            $start_date = date('Y-m-d'); // Today
            
            // Calculate end date based on duration type
            if ($duration_type == 'days') {
                $end_date = date('Y-m-d', strtotime("+$duration days"));
            } elseif ($duration_type == 'months') {
                $end_date = date('Y-m-d', strtotime("+$duration months"));
            } else { // years
                $end_date = date('Y-m-d', strtotime("+$duration years"));
            }

            // Insert into memberships table
            $sql = "INSERT INTO memberships (user_id, membership_plan_id, start_date, end_date, 
                    total_amount, status_id) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $membership_plan_id, $start_date, $end_date, $total_amount]);

            // Commit transaction
            $conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
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
        $sql = "SELECT DISTINCT c.id as coach_id, 
                CONCAT(pd.first_name, ' ', pd.last_name) as coach_name,
                cpt.price as coach_price
                FROM coaches c
                JOIN coach_program_types cpt ON c.id = cpt.coach_id
                JOIN users u ON c.user_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE cpt.program_id = ?
                AND u.is_active = 1";
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
}