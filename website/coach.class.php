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
                dt.type_name as duration_type,
                pss.day,
                pss.start_time,
                pss.end_time
            FROM program_subscriptions ps
            INNER JOIN programs p ON ps.program_id = p.id
            INNER JOIN users u ON ps.transaction_id IN (SELECT id FROM transactions WHERE user_id = u.id)
            INNER JOIN personal_details pd ON u.id = pd.user_id
            INNER JOIN duration_types dt ON p.duration_type_id = dt.id
            LEFT JOIN program_subscription_schedule pss ON ps.id = pss.program_subscription_id
            WHERE ps.coach_id = ? AND ps.is_paid = 1 AND ps.status = 'active'";
            
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $coachId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = array();
        while ($row = $result->fetch_assoc()) {
            // If there's a specific schedule, use it
            if ($row['day'] && $row['start_time'] && $row['end_time']) {
                // Get the next occurrence of this weekday
                $startDate = new DateTime($row['start_date']);
                $endDate = new DateTime($row['end_date']);
                $currentDate = new DateTime();
                
                // Find the next occurrence of the scheduled day
                while ($currentDate <= $endDate) {
                    if ($currentDate->format('l') === $row['day']) {
                        $eventStart = clone $currentDate;
                        $eventEnd = clone $currentDate;
                        
                        // Set the specific times
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
                        
                        // Only add if the event hasn't passed
                        if ($eventEnd > new DateTime()) {
                            $events[] = array(
                                'id' => $row['subscription_id'],
                                'title' => $row['first_name'] . ' ' . $row['last_name'] . ' - ' . $row['program_name'],
                                'start' => $eventStart->format('Y-m-d H:i:s'),
                                'end' => $eventEnd->format('Y-m-d H:i:s'),
                                'backgroundColor' => '#3788d8',
                                'borderColor' => '#3788d8',
                                'textColor' => '#ffffff',
                                'extendedProps' => array(
                                    'duration' => $row['duration'],
                                    'durationType' => $row['duration_type'],
                                    'status' => $row['subscription_status']
                                )
                            );
                        }
                    }
                    $currentDate->modify('+1 day');
                }
            } else {
                // If no specific schedule, show as all-day events
                $events[] = array(
                    'id' => $row['subscription_id'],
                    'title' => $row['first_name'] . ' ' . $row['last_name'] . ' - ' . $row['program_name'],
                    'start' => $row['start_date'],
                    'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')),
                    'backgroundColor' => '#3788d8',
                    'borderColor' => '#3788d8',
                    'textColor' => '#ffffff',
                    'allDay' => true,
                    'extendedProps' => array(
                        'duration' => $row['duration'],
                        'durationType' => $row['duration_type'],
                        'status' => $row['subscription_status']
                    )
                );
            }
        }
        
        return $events;
    }

    public function getCoachAvailability($programTypeId) {
        $query = "SELECT * FROM coach_availability WHERE coach_program_type_id = ? ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $programTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $availabilities = [];
        while ($row = $result->fetch_assoc()) {
            $availabilities[] = $row;
        }
        
        return $availabilities;
    }
    
    public function getAvailabilityDetails($id) {
        $query = "SELECT * FROM coach_availability WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function saveAvailability($data) {
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing availability
            $query = "UPDATE coach_availability SET day = ?, start_time = ?, end_time = ? WHERE id = ? AND coach_program_type_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssii", $data['day'], $data['start_time'], $data['end_time'], $data['id'], $data['coach_program_type_id']);
        } else {
            // Insert new availability
            $query = "INSERT INTO coach_availability (coach_program_type_id, day, start_time, end_time) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isss", $data['coach_program_type_id'], $data['day'], $data['start_time'], $data['end_time']);
        }
        
        return $stmt->execute();
    }
    
    public function deleteAvailability($id) {
        $query = "DELETE FROM coach_availability WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
}
?>