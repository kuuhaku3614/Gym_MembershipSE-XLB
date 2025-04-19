<?php
require_once __DIR__ . '/../../config.php';
class CoachingSystem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get all personal coaching schedules
    public function getPersonalSchedules() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cps.*, cpt.type as program_type_name, p.program_name, u.username as coach_name
                FROM coach_personal_schedule cps
                JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                ORDER BY cps.day, cps.start_time
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get all group coaching schedules
    public function getGroupSchedules() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cgs.*, cpt.type as program_type_name, p.program_name, u.username as coach_name
                FROM coach_group_schedule cgs
                JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                ORDER BY cgs.day, cgs.start_time
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get all program subscriptions
    public function getProgramSubscriptions() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT ps.*, u.username, cpt.type as program_type_name, p.program_name
                FROM program_subscriptions ps
                JOIN users u ON ps.user_id = u.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                ORDER BY ps.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get all scheduled sessions
    public function getScheduledSessions() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*, ps.user_id, u.username,
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                END as session_type
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN users u ON ps.user_id = u.id
                WHERE pss.date >= CURDATE()
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get coach program types
    public function getCoachProgramTypes() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cpt.*, p.program_name, u.username as coach_name, cpt.type, cpt.description
                FROM coach_program_types cpt
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                ORDER BY p.program_name, cpt.type
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get stats
    public function getStats() {
        try {
            $conn = $this->db->connect();
            
            // Total active subscriptions
            $stmt1 = $conn->prepare("SELECT COUNT(*) as total FROM program_subscriptions WHERE status = 'active'");
            $stmt1->execute();
            $activeSubscriptions = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total scheduled sessions
            $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM program_subscription_schedule WHERE date >= CURDATE() AND is_paid = 0");
            $stmt2->execute();
            $scheduledSessions = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total revenue from upcoming sessions
            $stmt3 = $conn->prepare("SELECT SUM(amount) as total FROM program_subscription_schedule WHERE date >= CURDATE()");
            $stmt3->execute();
            $revenue = $stmt3->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Total personal coaching sessions
            $stmt4 = $conn->prepare("SELECT COUNT(*) as total FROM program_subscription_schedule WHERE coach_personal_schedule_id IS NOT NULL AND date >= CURDATE() AND is_paid = 0");
            $stmt4->execute();
            $personalSessions = $stmt4->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total group coaching sessions
            $stmt5 = $conn->prepare("SELECT COUNT(*) as total FROM program_subscription_schedule WHERE coach_group_schedule_id IS NOT NULL AND date >= CURDATE() AND is_paid = 0");
            $stmt5->execute();
            $groupSessions = $stmt5->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'activeSubscriptions' => $activeSubscriptions,
                'scheduledSessions' => $scheduledSessions,
                'revenue' => $revenue,
                'personalSessions' => $personalSessions,
                'groupSessions' => $groupSessions
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get upcoming sessions for today and tomorrow
    public function getUpcomingSessions() {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*, ps.user_id, u.username, 
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                END as session_type,
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN (
                        SELECT CONCAT(p.program_name, ' - ', cpt.type)
                        FROM coach_group_schedule cgs
                        JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                        JOIN programs p ON cpt.program_id = p.id
                        WHERE cgs.id = pss.coach_group_schedule_id
                    )
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN (
                        SELECT CONCAT(p.program_name, ' - ', cpt.type)
                        FROM coach_personal_schedule cps
                        JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
                        JOIN programs p ON cpt.program_id = p.id
                        WHERE cps.id = pss.coach_personal_schedule_id
                    )
                END as program_name
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN users u ON ps.user_id = u.id
                WHERE pss.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Update session status (cancel or complete)
    public function updateSessionStatus($sessionId, $status) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                UPDATE program_subscription_schedule 
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':id' => $sessionId
            ]);
            
            return [
                'success' => true,
                'message' => 'Session status updated successfully',
                'rows_affected' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get single session details
    public function getSessionById($sessionId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT pss.*, ps.user_id, u.username,
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                END as session_type,
                CASE
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN (
                        SELECT CONCAT(p.program_name, ' - ', cpt.type)
                        FROM coach_group_schedule cgs
                        JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                        JOIN programs p ON cpt.program_id = p.id
                        WHERE cgs.id = pss.coach_group_schedule_id
                    )
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN (
                        SELECT CONCAT(p.program_name, ' - ', cpt.type)
                        FROM coach_personal_schedule cps
                        JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
                        JOIN programs p ON cpt.program_id = p.id
                        WHERE cps.id = pss.coach_personal_schedule_id
                    )
                END as program_name
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN users u ON ps.user_id = u.id
                WHERE pss.id = :id
            ");
            $stmt->execute([':id' => $sessionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}


// Initialize database
$database = new Database();

try {
    // Test database connection
    $pdo = $database->connect();
    
    // Create coaching system instance
    $coachingSystem = new CoachingSystem($database);
    
    // Get data for the dashboard
    $personalSchedules = $coachingSystem->getPersonalSchedules();
    $groupSchedules = $coachingSystem->getGroupSchedules();
    $programSubscriptions = $coachingSystem->getProgramSubscriptions();
    $scheduledSessions = $coachingSystem->getScheduledSessions();
    $coachProgramTypes = $coachingSystem->getCoachProgramTypes();
    $stats = $coachingSystem->getStats();
    $upcomingSessions = $coachingSystem->getUpcomingSessions();
    
    // Check for errors
    $hasErrors = isset($personalSchedules['error']) || 
                isset($groupSchedules['error']) || 
                isset($programSubscriptions['error']) || 
                isset($scheduledSessions['error']) ||
                isset($coachProgramTypes['error']) ||
                isset($stats['error']) ||
                isset($upcomingSessions['error']);
                
} catch (Exception $e) {
    $error = "Connection failed: " . $e->getMessage();
}