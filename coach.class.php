<?php
// dashboard.class.php

require_once __DIR__ . '/../../config.php';

class CoachingSystem {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // Existing functions...

    // Get program subscriptions for a user
    public function getUserProgramSubscriptions($userId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT ps.*, u.username as coach_name, cpt.type as program_type_name, p.program_name
                FROM program_subscriptions ps
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id  -- Join to get coach's username
                WHERE ps.user_id = :user_id
                ORDER BY ps.created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get scheduled sessions for a user
    public function getUserScheduledSessions($userId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*, 
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                END as session_type,
                u.username as coach_name,  -- Get the coach's username
                p.program_name,  -- Get the program name
                cpt.type as program_type
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id  -- Join to get coach's username
                WHERE ps.user_id = :user_id AND pss.date >= CURDATE()
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get upcoming sessions for a user (today and tomorrow)
    public function getUserUpcomingSessions($userId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*,
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                END as session_type,
                u.username as coach_name,  -- Get the coach's username
                p.program_name,  -- Get the program name
                cpt.type as program_type
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id  -- Join to get coach's username
                WHERE pss.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND ps.user_id = :user_id
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get user's availed coaching sessions
    public function getUserAvailedSessions($userId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*, 
                       u.username as coach_name,
                       p.program_name,
                       cpt.type as program_type,
                       CASE 
                           WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                           WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                       END as session_type
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                WHERE ps.user_id = :user_id
                ORDER BY pss.date DESC, pss.start_time DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
     // Get stats for specific user
    public function getUserStats($userId) {
        try {
            $conn = $this->db->connect();
            
            // Total active subscriptions for this User
            $stmt1 = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM program_subscriptions ps
                WHERE  ps.user_id = :user_id AND ps.status = 'active'
            ");
            $stmt1->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt1->execute();
            $activeSubscriptions = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total scheduled sessions for this User
             $stmt2 = $conn->prepare("
                SELECT COUNT(*) as total
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                WHERE ps.user_id = :user_id AND pss.date >= CURDATE()
            ");
            $stmt2->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt2->execute();
            $scheduledSessions = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
           
            // Total amount spent by the user
            $stmt3 = $conn->prepare("
                SELECT SUM(pss.amount) as total_spent
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                WHERE ps.user_id = :user_id
            ");
            $stmt3->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt3->execute();
            $totalSpent = $stmt3->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;

            return [
                'activeSubscriptions' => $activeSubscriptions,
                'scheduledSessions' => $scheduledSessions,
                'totalSpent' => $totalSpent,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>
