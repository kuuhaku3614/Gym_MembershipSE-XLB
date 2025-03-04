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
                cpt.description as coach_program_description,
                cpt.type as coach_program_type
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
                    ps.coach_program_type_id,
                    cpt.program_id,
                    ps.status as subscription_status,
                    pd.first_name,
                    pd.last_name,
                    pd.phone_number as contact_no,
                    p.program_name,
                    cpt.type as type
                FROM program_subscriptions ps
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                INNER JOIN users u ON ps.user_id = u.id
                INNER JOIN personal_details pd ON u.id = pd.user_id
                WHERE cpt.coach_id = ?
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
        // Get personal schedules
        $personalSql = "SELECT 
            cps.id,
            cps.day,
            cps.start_time,
            cps.end_time,
            cpt.price,
            p.program_name,
            'personal' as schedule_type
        FROM coach_personal_schedule cps
        JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
        JOIN programs p ON cpt.program_id = p.id
        WHERE cpt.coach_id = ? AND cpt.status = 'active'";

        // Get group schedules
        $groupSql = "SELECT 
            cgs.id,
            cgs.day,
            cgs.start_time,
            cgs.end_time,
            cgs.capacity,
            p.program_name,
            COUNT(DISTINCT ps.user_id) as current_members,
            'group' as schedule_type
        FROM coach_group_schedule cgs
        JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
        JOIN programs p ON cpt.program_id = p.id
        LEFT JOIN program_subscription_schedule pss ON pss.coach_group_schedule_id = cgs.id
        LEFT JOIN program_subscriptions ps ON ps.id = pss.program_subscription_id AND ps.status = 'active'
        WHERE cpt.coach_id = ? AND cpt.status = 'active'
        GROUP BY cgs.id";

        $events = array();

        // Get personal schedule events
        $stmt = $this->db->prepare($personalSql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Get the next 4 weeks of events for this schedule
            $currentDate = new DateTime();
            $endDate = (new DateTime())->modify('+4 weeks');
            
            while ($currentDate <= $endDate) {
                if ($currentDate->format('l') === $row['day']) {
                    $eventStart = clone $currentDate;
                    $eventEnd = clone $currentDate;
                    
                    $startTime = new DateTime($row['start_time']);
                    $endTime = new DateTime($row['end_time']);
                    
                    $eventStart->setTime(
                        (int)$startTime->format('H'),
                        (int)$startTime->format('i')
                    );
                    $eventEnd->setTime(
                        (int)$endTime->format('H'),
                        (int)$endTime->format('i')
                    );
                    
                    $events[] = array(
                        'id' => 'personal_' . $row['id'],
                        'title' => $row['program_name'] . ' (Personal) - ₱' . number_format($row['price'], 2),
                        'start' => $eventStart->format('Y-m-d H:i:s'),
                        'end' => $eventEnd->format('Y-m-d H:i:s'),
                        'backgroundColor' => '#28a745',
                        'borderColor' => '#28a745',
                        'textColor' => '#ffffff'
                    );
                }
                $currentDate->modify('+1 day');
            }
        }

        // Get group schedule events
        $stmt = $this->db->prepare($groupSql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Get the next 4 weeks of events for this schedule
            $currentDate = new DateTime();
            $endDate = (new DateTime())->modify('+4 weeks');
            
            while ($currentDate <= $endDate) {
                if ($currentDate->format('l') === $row['day']) {
                    $eventStart = clone $currentDate;
                    $eventEnd = clone $currentDate;
                    
                    $startTime = new DateTime($row['start_time']);
                    $endTime = new DateTime($row['end_time']);
                    
                    $eventStart->setTime(
                        (int)$startTime->format('H'),
                        (int)$startTime->format('i')
                    );
                    $eventEnd->setTime(
                        (int)$endTime->format('H'),
                        (int)$endTime->format('i')
                    );
                    
                    $events[] = array(
                        'id' => 'group_' . $row['id'],
                        'title' => $row['program_name'] . ' (Group) - ' . $row['current_members'] . '/' . $row['capacity'],
                        'start' => $eventStart->format('Y-m-d H:i:s'),
                        'end' => $eventEnd->format('Y-m-d H:i:s'),
                        'backgroundColor' => '#007bff',
                        'borderColor' => '#007bff',
                        'textColor' => '#ffffff'
                    );
                }
                $currentDate->modify('+1 day');
            }
        }
        
        return $events;
    }
    
    public function deleteAvailability($id) {
        $query = "DELETE FROM coach_availability WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    public function getGroupSchedule($programTypeId) {
        $sql = "SELECT 
            cgs.*,
            COUNT(DISTINCT ps.user_id) AS current_members,
            GROUP_CONCAT(DISTINCT CONCAT(pd.first_name, ' ', pd.last_name) ORDER BY pd.first_name, pd.last_name) AS member_names
        FROM coach_group_schedule cgs
        LEFT JOIN program_subscription_schedule pss 
            ON pss.coach_group_schedule_id = cgs.id
        LEFT JOIN program_subscriptions ps 
            ON ps.id = pss.program_subscription_id AND ps.status = 'active'
        LEFT JOIN users u 
            ON ps.user_id = u.id
        LEFT JOIN personal_details pd 
            ON u.id = pd.user_id
        WHERE cgs.coach_program_type_id = ?
        GROUP BY cgs.id;";
            
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $programTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPersonalSchedule($programTypeId) {
        $sql = "SELECT * FROM coach_personal_schedule WHERE coach_program_type_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $programTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function saveGroupSchedule($scheduleId, $programTypeId, $day, $startTime, $endTime, $capacity) {
        try {
            if ($scheduleId) {
                // Update existing schedule
                $sql = "UPDATE coach_group_schedule 
                        SET day = ?, start_time = ?, end_time = ?, capacity = ?
                        WHERE id = ? AND coach_program_type_id = ?";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->bind_param("sssiis", $day, $startTime, $endTime, $capacity, $scheduleId, $programTypeId);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_group_schedule (coach_program_type_id, day, start_time, end_time, capacity)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->bind_param("isssi", $programTypeId, $day, $startTime, $endTime, $capacity);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function savePersonalSchedule($scheduleId, $programTypeId, $day, $startTime, $endTime, $price, $duration) {
        try {
            if ($scheduleId) {
                // Update existing schedule
                $sql = "UPDATE coach_personal_schedule 
                        SET day = ?, start_time = ?, end_time = ?, price = ?, duration_rate = ?
                        WHERE id = ? AND coach_program_type_id = ?";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->bind_param("sssdiii", $day, $startTime, $endTime, $price, $duration, $scheduleId, $programTypeId);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_personal_schedule (coach_program_type_id, day, start_time, end_time, price, duration_rate)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->bind_param("isssdi", $programTypeId, $day, $startTime, $endTime, $price, $duration);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteSchedule($scheduleId, $type) {
        try {
            $table = $type === 'group' ? 'coach_group_schedule' : 'coach_personal_schedule';
            $sql = "DELETE FROM " . $table . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $scheduleId);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getGroupScheduleMembers($scheduleId) {
        $sql = "SELECT 
                pd.first_name,
                pd.last_name
                FROM program_subscriptions ps 
                JOIN program_subscription_schedule pss ON ps.program_subscription_schedule_id = pss.id
                JOIN users u ON ps.user_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE pss.coach_group_schedule_id = ?
                AND ps.status = 'active'";
            
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>