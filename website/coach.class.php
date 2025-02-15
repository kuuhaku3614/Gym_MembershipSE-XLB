<?php
require_once __DIR__ . '/../config.php';

class Coach_class {
    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    public function getCoachPrograms($coachId) {
        try {
            $sql = "SELECT cpt.program_id, cpt.coach_id, cpt.status,
                           pt.program_name, pt.description, 
                           CONCAT(pt.duration, ' ', dt.type_name) as program_duration,
                           cpt.price as program_price,
                           cpt.status as program_status
                    FROM coach_program_types cpt
                    JOIN programs pt ON cpt.program_id = pt.id
                    JOIN duration_types dt ON pt.duration_type_id = dt.id
                    WHERE cpt.coach_id = ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$coachId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getCoachPrograms: " . $e->getMessage());
            return [];
        }
    }

    public function getProgramMembers($coachId) {
        try {
            $sql = "SELECT DISTINCT 
                        u.user_id,
                        CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as member_name,
                        p.program_name,
                        CONCAT(p.duration, ' ', dt.type_name) as program_duration,
                        cpt.price as program_price,
                        ps.start_date,
                        ps.end_date,
                        ps.status as membership_status
                    FROM coach_program_types cpt
                    JOIN programs p ON cpt.program_id = p.id
                    JOIN duration_types dt ON p.duration_type_id = dt.id
                    JOIN program_subscriptions ps ON ps.program_id = cpt.program_id
                    JOIN users u ON ps.user_id = u.user_id
                    JOIN personal_details pd ON u.user_id = pd.user_id
                    LEFT JOIN transactions t ON ps.transaction_id = t.id
                    LEFT JOIN memberships m ON t.membership_id = m.id
                    WHERE cpt.coach_id = ? 
                    AND (t.status = 'confirmed' OR t.status IS NULL)
                    ORDER BY ps.start_date DESC";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$coachId]);
            
            // Add debug logging
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Number of program members found: " . count($results));
            return $results;
        } catch (PDOException $e) {
            error_log("Error in getProgramMembers: " . $e->getMessage());
            return [];
        }
    }

    public function toggleProgramStatus($programId, $coachId, $currentStatus) {
        try {
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            
            $sql = "UPDATE coach_program_types 
                    SET status = ? 
                    WHERE program_id = ? AND coach_id = ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $result = $stmt->execute([$newStatus, $programId, $coachId]);
            
            if (!$result) {
                error_log("Failed to update program status. Program ID: $programId, Coach ID: $coachId");
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error in toggleProgramStatus: " . $e->getMessage());
            return false;
        }
    }
}
?>