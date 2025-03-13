<?php
require_once __DIR__ . '/../config.php';

class Coach_class {
    protected $db;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->connect();
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getCoachPrograms($coachId) {
        $sql = "SELECT 
                p.*,
                cpt.id as coach_program_type_id,
                cpt.status as coach_program_status,
                cpt.description as coach_program_description,
                cpt.type as coach_program_type,
                COALESCE(cgs.price, cps.price) as coach_program_price
            FROM programs p 
            INNER JOIN coach_program_types cpt ON p.id = cpt.program_id 
            LEFT JOIN coach_group_schedule cgs ON cgs.coach_program_type_id = cpt.id
            LEFT JOIN coach_personal_schedule cps ON cps.coach_program_type_id = cpt.id
            WHERE cpt.coach_id = ? AND p.is_removed = 0
            GROUP BY cpt.id";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    cpt.type as type,
                    COALESCE(cgs.price, cps.price) as program_price
                FROM program_subscriptions ps
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                INNER JOIN users u ON ps.user_id = u.id
                INNER JOIN personal_details pd ON u.id = pd.user_id
                LEFT JOIN coach_group_schedule cgs ON cgs.coach_program_type_id = cpt.id
                LEFT JOIN coach_personal_schedule cps ON cps.coach_program_type_id = cpt.id
                WHERE cpt.coach_id = ?
                ORDER BY ps.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleProgramStatus($coachProgramTypeId, $coachId, $currentStatus) {
        try {
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE coach_program_types SET status = ? WHERE id = ? AND coach_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newStatus, $coachProgramTypeId, $coachId]);
            return $stmt->rowCount() > 0;
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
            cps.price,
            p.program_name,
            'personal' as schedule_type,
            cps.duration_rate
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
            cgs.price,
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
        $stmt->execute([$coachId]);
        $personalSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($personalSchedules as $row) {
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
                        'title' => $row['program_name'] . ' (Personal) - ₱' . number_format($row['price'], 2) . ' - ' . $row['duration_rate'] . ' mins',
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
        $stmt->execute([$coachId]);
        $groupSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($groupSchedules as $row) {
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
                        'title' => $row['program_name'] . ' (Group) - ' . $row['current_members'] . '/' . $row['capacity'] . ' - ₱' . number_format($row['price'], 2),
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
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function getGroupSchedule($programTypeId) {
        $sql = "SELECT 
                cgs.*,
                COUNT(DISTINCT pss.program_subscription_id) as current_members
            FROM coach_group_schedule cgs
            LEFT JOIN program_subscription_schedule pss ON pss.coach_group_schedule_id = cgs.id
            LEFT JOIN program_subscriptions ps ON ps.id = pss.program_subscription_id AND ps.status = 'active'
            WHERE cgs.coach_program_type_id = ?
            GROUP BY cgs.id";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$programTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPersonalSchedule($programTypeId) {
        $sql = "SELECT 
            cps.id,
            cps.day,
            cps.start_time,
            cps.end_time,
            cps.price,
            cps.duration_rate,
            p.program_name
        FROM coach_personal_schedule cps
        JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
        JOIN programs p ON cpt.program_id = p.id
        WHERE cps.coach_program_type_id = ?";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$programTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveGroupSchedule($scheduleId, $programTypeId, $day, $startTime, $endTime, $capacity, $price) {
        try {
            if ($scheduleId) {
                // Update existing schedule
                $sql = "UPDATE coach_group_schedule 
                       SET day = ?, start_time = ?, end_time = ?, capacity = ?, price = ?
                       WHERE id = ? AND coach_program_type_id = ?";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->execute([$day, $startTime, $endTime, $capacity, $price, $scheduleId, $programTypeId]);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_group_schedule (coach_program_type_id, day, start_time, end_time, capacity, price)
                       VALUES (?, ?, ?, ?, ?, ?)";
                if (!$stmt = $this->db->prepare($sql)) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->execute([$programTypeId, $day, $startTime, $endTime, $capacity, $price]);
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
                
                $stmt->execute([$day, $startTime, $endTime, $price, $duration, $scheduleId, $programTypeId]);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_personal_schedule (coach_program_type_id, day, start_time, end_time, price, duration_rate)
                       VALUES (?, ?, ?, ?, ?, ?)";
                if (!$stmt = $this->db->prepare($sql)) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }
                
                $stmt->execute([$programTypeId, $day, $startTime, $endTime, $price, $duration]);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteSchedule($scheduleId, $type) {
        try {
            $sql = $type === 'group' 
                ? "DELETE FROM coach_group_schedule WHERE id = ?"
                : "DELETE FROM coach_personal_schedule WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->execute([$scheduleId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getScheduleMembers($scheduleId) {
        $sql = "SELECT 
                pd.first_name,
                pd.last_name,
                pd.phone_number as contact_no,
                ps.status
            FROM program_subscription_schedule pss
            JOIN program_subscriptions ps ON ps.id = pss.program_subscription_id
            JOIN users u ON ps.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE pss.coach_group_schedule_id = ?
                AND ps.status = 'active'";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>