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
                    GROUP_CONCAT(DISTINCT ps.id) as subscription_ids,
                    CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            p.program_name, 
                            ' (', 
                            cpt.type,
                            ' - ',
                            ps.status,
                            ')'
                        ) SEPARATOR '<br>'
                    ) as programs,
                    pd.phone_number as contact,
                    MAX(ps.status) as subscription_status,
                    MAX(t.created_at) as latest_subscription_date,
                    u.id as member_id,
                    (
                        SELECT COUNT(*)
                        FROM program_subscription_schedule pss2
                        WHERE pss2.program_subscription_id IN (
                            SELECT ps2.id 
                            FROM program_subscriptions ps2 
                            WHERE ps2.user_id = u.id
                        )
                        AND pss2.is_paid = 1
                    ) as paid_sessions,
                    (
                        SELECT COUNT(*)
                        FROM program_subscription_schedule pss2
                        WHERE pss2.program_subscription_id IN (
                            SELECT ps2.id 
                            FROM program_subscriptions ps2 
                            WHERE ps2.user_id = u.id
                        )
                    ) as total_sessions
                FROM program_subscriptions ps
                INNER JOIN users u ON ps.user_id = u.id
                INNER JOIN personal_details pd ON u.id = pd.user_id
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                LEFT JOIN transactions t ON ps.transaction_id = t.id
                WHERE cpt.coach_id = ? AND ps.status = 'active'
                GROUP BY u.id, pd.first_name, pd.middle_name, pd.last_name, pd.phone_number
                ORDER BY latest_subscription_date DESC";
        
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
            cps.duration_rate,
            p.program_name,
            'personal' as schedule_type,
            cpt.id as coach_program_type_id
        FROM coach_personal_schedule cps
        JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
        JOIN programs p ON cpt.program_id = p.id
        WHERE cpt.coach_id = ? AND cpt.status = 'active'";

        // Get booked slots for personal schedules
        $bookedSlotsSql = "SELECT DISTINCT
            pss.day,
            TIME_FORMAT(pss.start_time, '%H:%i') as start_time,
            TIME_FORMAT(pss.end_time, '%H:%i') as end_time,
            cps.duration_rate,
            CONCAT(pd.first_name, ' ', pd.last_name) as member_name
        FROM program_subscription_schedule pss
        JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
        JOIN coach_personal_schedule cps ON pss.coach_personal_schedule_id = cps.id
        JOIN users u ON ps.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE ps.status IN ('active', 'pending')
        AND cps.coach_program_type_id IN (
            SELECT id FROM coach_program_types WHERE coach_id = ?
        )";

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

        // Get booked slots
        $stmt = $this->db->prepare($bookedSlotsSql);
        $stmt->execute([$coachId]);
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a map of booked slots by day and time
        $bookedSlotsMap = [];
        foreach ($bookedSlots as $slot) {
            $day = $slot['day'];
            
            // Use a reference date to compare times
            $startTime = strtotime("2000-01-01 " . $slot['start_time']);
            $endTime = strtotime("2000-01-01 " . $slot['end_time']);
            
            if (!isset($bookedSlotsMap[$day])) {
                $bookedSlotsMap[$day] = [];
            }
            
            $bookedSlotsMap[$day][] = [
                'start' => $startTime,
                'end' => $endTime,
                'member_name' => $slot['member_name']
            ];
        }

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
                    // Use the same reference date for comparison
                    $startTime = strtotime("2000-01-01 " . $row['start_time']);
                    $endTime = strtotime("2000-01-01 " . $row['end_time']);
                    $duration = $row['duration_rate']; // in minutes
                    
                    // Calculate number of slots
                    $totalMinutes = ($endTime - $startTime) / 60;
                    $numSlots = floor($totalMinutes / $duration);
                    
                    // Create slots
                    for ($i = 0; $i < $numSlots; $i++) {
                        $slotStart = $startTime + ($i * $duration * 60);
                        $slotEnd = $slotStart + ($duration * 60);
                        
                        // Check if this time slot is booked
                        $bookedMember = null;
                        if (isset($bookedSlotsMap[$row['day']])) {
                            foreach ($bookedSlotsMap[$row['day']] as $bookedSlot) {
                                if ($slotStart === $bookedSlot['start'] && $slotEnd === $bookedSlot['end']) {
                                    $bookedMember = $bookedSlot['member_name'];
                                    break;
                                }
                            }
                        }
                        
                        $eventStart = clone $currentDate;
                        $eventEnd = clone $currentDate;
                        
                        $eventStart->setTime(
                            (int)date('H', $slotStart),
                            (int)date('i', $slotStart)
                        );
                        $eventEnd->setTime(
                            (int)date('H', $slotEnd),
                            (int)date('i', $slotEnd)
                        );
                        
                        // Format title based on view type (will be used in JavaScript)
                        $title = $bookedMember ? $bookedMember : "Available";
                        
                        if ($bookedMember) {
                            $bgColor = '#FF6B6B'; // Coral red for booked personal slots
                            $borderColor = '#FF5252';
                        } else {
                            $bgColor = '#4CAF50'; // Green for available personal slots
                            $borderColor = '#45A049';
                        }
                        
                        $events[] = array(
                            'id' => 'personal_' . $row['id'] . '_slot_' . $i,
                            'title' => $title,
                            'start' => $eventStart->format('Y-m-d H:i:s'),
                            'end' => $eventEnd->format('Y-m-d H:i:s'),
                            'backgroundColor' => $bgColor,
                            'borderColor' => $borderColor,
                            'textColor' => '#ffffff',
                            'extendedProps' => [
                                'scheduleId' => $row['id'],
                                'coachProgramTypeId' => $row['coach_program_type_id'],
                                'isBooked' => !is_null($bookedMember),
                                'price' => $row['price'],
                                'programName' => $row['program_name'],
                                'type' => 'personal',
                                'display' => !is_null($bookedMember) ? 'auto' : null // Only show if booked
                            ]
                        );
                    }
                }
                $currentDate->modify('+1 day');
            }
        }

        // Get group schedule events
        $stmt = $this->db->prepare($groupSql);
        $stmt->execute([$coachId]);
        $groupSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get group program members
        $groupMembersSql = "SELECT 
            cgs.id as schedule_id,
            GROUP_CONCAT(DISTINCT CONCAT(pd.first_name, ' ', pd.last_name) SEPARATOR ', ') as member_names
        FROM coach_group_schedule cgs
        JOIN program_subscription_schedule pss ON pss.coach_group_schedule_id = cgs.id
        JOIN program_subscriptions ps ON ps.id = pss.program_subscription_id
        JOIN users u ON ps.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE ps.status = 'active'
        AND cgs.coach_program_type_id IN (
            SELECT id FROM coach_program_types WHERE coach_id = ?
        )
        GROUP BY cgs.id";
        
        $stmt = $this->db->prepare($groupMembersSql);
        $stmt->execute([$coachId]);
        $groupMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a map of group members by schedule ID
        $groupMembersMap = [];
        foreach ($groupMembers as $members) {
            $groupMembersMap[$members['schedule_id']] = $members['member_names'];
        }
        
        foreach ($groupSchedules as $row) {
            // Get the next 4 weeks of events for this schedule
            $currentDate = new DateTime();
            $endDate = (new DateTime())->modify('+4 weeks');
            
            while ($currentDate <= $endDate) {
                if ($currentDate->format('l') === $row['day']) {
                    $eventStart = clone $currentDate;
                    $eventEnd = clone $currentDate;
                    
                    // Set the time components
                    $eventStart->setTime(
                        (int)date('H', strtotime($row['start_time'])),
                        (int)date('i', strtotime($row['start_time']))
                    );
                    $eventEnd->setTime(
                        (int)date('H', strtotime($row['end_time'])),
                        (int)date('i', strtotime($row['end_time']))
                    );
                    
                    $memberNames = isset($groupMembersMap[$row['id']]) ? $groupMembersMap[$row['id']] : '';
                    
                    $events[] = array(
                        'id' => 'group_' . $row['id'] . '_' . $currentDate->format('Y-m-d'),
                        'title' => $memberNames ? $memberNames : $row['program_name'],
                        'start' => $eventStart->format('Y-m-d H:i:s'),
                        'end' => $eventEnd->format('Y-m-d H:i:s'),
                        'backgroundColor' => '#2196F3', // Blue for group slots
                        'borderColor' => '#1976D2',
                        'textColor' => '#ffffff',
                        'extendedProps' => [
                            'scheduleId' => $row['id'],
                            'currentMembers' => $row['current_members'],
                            'capacity' => $row['capacity'],
                            'price' => $row['price'],
                            'programName' => $row['program_name'],
                            'type' => 'group',
                            'memberNames' => $memberNames
                        ]
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
                $stmt->execute([$day, $startTime, $endTime, $capacity, $price, $scheduleId, $programTypeId]);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_group_schedule (coach_program_type_id, day, start_time, end_time, capacity, price)
                       VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
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
                $stmt->execute([$day, $startTime, $endTime, $price, $duration, $scheduleId, $programTypeId]);
            } else {
                // Insert new schedule
                $sql = "INSERT INTO coach_personal_schedule (coach_program_type_id, day, start_time, end_time, price, duration_rate)
                       VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
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

    public function getProgramSubscriptionSchedule($subscriptionId) {
        $sql = "SELECT 
                    pss.date,
                    pss.day,
                    pss.start_time,
                    pss.end_time,
                    pss.amount,
                    pss.is_paid,
                    CASE 
                        WHEN cgs.id IS NOT NULL THEN 'Group'
                        WHEN cps.id IS NOT NULL THEN 'Personal'
                    END as schedule_type,
                    p.program_name
                FROM program_subscription_schedule pss
                LEFT JOIN coach_group_schedule cgs ON pss.coach_group_schedule_id = cgs.id
                LEFT JOIN coach_personal_schedule cps ON pss.coach_personal_schedule_id = cps.id
                INNER JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                WHERE pss.program_subscription_id = ?
                ORDER BY pss.date, pss.start_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subscriptionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnpaidSchedules($memberId) {
        $sql = "SELECT 
                    pss.id as schedule_id,
                    pss.date,
                    pss.start_time,
                    pss.end_time,
                    pss.amount,
                    CASE 
                        WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                        WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                    END as schedule_type,
                    p.program_name,
                    CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name
                FROM program_subscription_schedule pss
                INNER JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                INNER JOIN users u ON ps.user_id = u.id
                INNER JOIN personal_details pd ON u.id = pd.user_id
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                WHERE u.id = ? AND pss.is_paid = 0
                ORDER BY pss.date ASC, pss.start_time ASC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function processSchedulePayments($scheduleIds) {
        try {
            $this->db->beginTransaction();

            // Update the is_paid status for selected schedules
            $sql = "UPDATE program_subscription_schedule 
                    SET is_paid = 1 
                    WHERE id IN (" . str_repeat('?,', count($scheduleIds) - 1) . "?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($scheduleIds);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
?>