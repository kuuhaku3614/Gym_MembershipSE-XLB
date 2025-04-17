<?php
require_once __DIR__ . '/../config.php';

class CoachRequests {
    private $database;
    
    public function __construct($database) {
        $this->database = $database;
    }
    
    /**
     * Get all pending program requests for a coach
     * 
     * @param int $coach_id The ID of the coach
     * @return array Array of pending program requests
     */
    /**
     * Get program schedules for a subscription
     * 
     * @param int $subscriptionId The ID of the program subscription
     * @return array Array of program schedules
     */
    public function getProgramSchedules($subscriptionId) {
        $query = "SELECT 
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
        INNER JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
        INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
        INNER JOIN programs p ON cpt.program_id = p.id
        LEFT JOIN coach_group_schedule cgs ON pss.coach_group_schedule_id = cgs.id
        LEFT JOIN coach_personal_schedule cps ON pss.coach_personal_schedule_id = cps.id
        WHERE pss.program_subscription_id = ?
        ORDER BY pss.date, pss.start_time";
        
        try {
            $pdo = $this->database->connect();
            $stmt = $pdo->prepare($query);
            $stmt->execute([$subscriptionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching program schedules: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all pending program requests for a coach
     * 
     * @param int $coach_id The ID of the coach
     * @return array Array of pending program requests
     */
    public function getPendingRequests($coach_id) {
        $query = "SELECT 
            MIN(ps.id) as subscription_id,
            CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
            GROUP_CONCAT(
                CONCAT(
                    p.program_name, 
                    ' (', 
                    cpt.type,
                    ')')
                SEPARATOR '\n'
            ) as programs,
            pd.phone_number as contact,
            ps.transaction_id,
            t.created_at as latest_subscription_date,
            u.id as member_id,
            MIN(ps.created_at) as created_at,
            'pending' as request_status
        FROM program_subscriptions ps
        INNER JOIN users u ON ps.user_id = u.id
        INNER JOIN personal_details pd ON u.id = pd.user_id
        INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
        INNER JOIN programs p ON cpt.program_id = p.id
        LEFT JOIN transactions t ON ps.transaction_id = t.id
        WHERE cpt.coach_id = ? AND ps.status = 'pending'
        GROUP BY ps.transaction_id, pd.first_name, pd.middle_name, pd.last_name, pd.phone_number, u.id
        ORDER BY MIN(ps.created_at) DESC";
        
        $pdo = $this->database->connect();
        $stmt = $pdo->prepare($query);
        $stmt->execute([$coach_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
