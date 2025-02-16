<?php
require_once __DIR__ . '/../config.php';

class Coach_class {
    protected $db;

    public function __construct() {
        $this->db = new mysqli('localhost', 'root', '', 'gym_managementdb');
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function getCoachPrograms($coachId) {
        $sql = "SELECT p.*, cpt.id as coach_program_type_id, cpt.price, cpt.status as coach_program_status, 
                dt.type_name as duration_type,
                cpt.price as coach_program_price,
                cpt.status as coach_program_status,
                cpt.description as coach_program_description
                FROM programs p 
                INNER JOIN coach_program_types cpt ON p.id = cpt.program_id 
                JOIN duration_types dt ON p.duration_type_id = dt.id
                WHERE cpt.coach_id = ? AND p.is_removed = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getProgramMembers($coachId) {
        $sql = "SELECT 
                    ps.id as subscription_id,
                    u.id as member_id,
                    ps.program_id,
                    ps.start_date,
                    ps.end_date,
                    ps.status as subscription_status,
                    pd.first_name,
                    pd.last_name,
                    pd.phone_number as contact_no,
                    p.program_name,
                    p.duration,
                    dt.type_name as duration_type
                FROM program_subscriptions ps
                INNER JOIN programs p ON ps.program_id = p.id
                INNER JOIN users u ON ps.transaction_id IN (SELECT id FROM transactions WHERE user_id = u.id)
                INNER JOIN personal_details pd ON u.id = pd.user_id
                INNER JOIN duration_types dt ON p.duration_type_id = dt.id
                WHERE ps.coach_id = ? AND ps.is_paid = 1
                ORDER BY ps.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function toggleProgramStatus($coachProgramTypeId, $coachId, $currentStatus) {
        try {
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE coach_program_types SET status = ? WHERE id = ? AND coach_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sii", $newStatus, $coachProgramTypeId, $coachId);
            return $stmt->execute() && $stmt->affected_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCalendarEvents($coachId) {
        $sql = "SELECT 
                    ps.id as subscription_id,
                    ps.program_id,
                    ps.start_date,
                    ps.end_date,
                    ps.status as subscription_status,
                    pd.first_name,
                    pd.last_name,
                    p.program_name,
                    p.duration,
                    dt.type_name as duration_type
                FROM program_subscriptions ps
                INNER JOIN programs p ON ps.program_id = p.id
                INNER JOIN users u ON ps.transaction_id IN (SELECT id FROM transactions WHERE user_id = u.id)
                INNER JOIN personal_details pd ON u.id = pd.user_id
                INNER JOIN duration_types dt ON p.duration_type_id = dt.id
                WHERE ps.coach_id = ? AND ps.is_paid = 1 AND ps.status = 'active'";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = array();
        while ($row = $result->fetch_assoc()) {
            $events[] = array(
                'id' => $row['subscription_id'],
                'title' => $row['first_name'] . ' ' . $row['last_name'] . ' - ' . $row['program_name'],
                'start' => $row['start_date'],
                'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')), // Add 1 day to include the end date
                'backgroundColor' => '#3788d8',
                'borderColor' => '#3788d8',
                'textColor' => '#ffffff',
                'allDay' => true
            );
        }
        
        return $events;
    }
}
?>