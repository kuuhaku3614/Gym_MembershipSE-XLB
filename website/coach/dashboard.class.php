<?php
require_once __DIR__ . '/../../config.php';
class CoachingSystem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get all personal coaching schedules for specific coach
    public function getPersonalSchedules($coachId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cps.*, cpt.type as program_type_name, p.program_name, u.username as coach_name
                FROM coach_personal_schedule cps
                JOIN coach_program_types cpt ON cps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                WHERE cpt.coach_id = :coach_id
                ORDER BY cps.day, cps.start_time
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get all group coaching schedules for specific coach
    public function getGroupSchedules($coachId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cgs.*, cpt.type as program_type_name, p.program_name, u.username as coach_name
                FROM coach_group_schedule cgs
                JOIN coach_program_types cpt ON cgs.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                WHERE cpt.coach_id = :coach_id
                ORDER BY cgs.day, cgs.start_time
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get program subscriptions for specific coach
    public function getProgramSubscriptions($coachId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT ps.*, u.username, cpt.type as program_type_name, p.program_name
                FROM program_subscriptions ps
                JOIN users u ON ps.user_id = u.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                JOIN programs p ON cpt.program_id = p.id
                WHERE cpt.coach_id = :coach_id
                ORDER BY ps.created_at DESC
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get scheduled sessions for specific coach
    public function getScheduledSessions($coachId) {
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
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE cpt.coach_id = :coach_id AND pss.date >= CURDATE()
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get coach program types for specific coach
    public function getCoachProgramTypes($coachId) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT cpt.*, p.program_name, u.username as coach_name, cpt.type, cpt.description
                FROM coach_program_types cpt
                JOIN programs p ON cpt.program_id = p.id
                JOIN users u ON cpt.coach_id = u.id
                WHERE cpt.coach_id = :coach_id
                ORDER BY p.program_name, cpt.type
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get stats for specific coach
    public function getStats($coachId) {
        try {
            $conn = $this->db->connect();
            
            // Total active subscriptions for this coach
            $stmt1 = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM program_subscriptions ps
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE ps.status = 'active' AND cpt.coach_id = :coach_id
            ");
            $stmt1->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt1->execute();
            $activeSubscriptions = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total scheduled sessions for this coach
            $stmt2 = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.date >= CURDATE() AND pss.is_paid = 0 AND cpt.coach_id = :coach_id
            ");
            $stmt2->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt2->execute();
            $scheduledSessions = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total revenue from upcoming sessions for this coach
            $stmt3 = $conn->prepare("
                SELECT SUM(pss.amount) as total 
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.date >= CURDATE() AND cpt.coach_id = :coach_id
            ");
            $stmt3->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt3->execute();
            $revenue = $stmt3->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Total personal coaching sessions for this coach
            $stmt4 = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.coach_personal_schedule_id IS NOT NULL 
                AND pss.date >= CURDATE() 
                AND pss.is_paid = 0
                AND cpt.coach_id = :coach_id
            ");
            $stmt4->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt4->execute();
            $personalSessions = $stmt4->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total group coaching sessions for this coach
            $stmt5 = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.coach_group_schedule_id IS NOT NULL 
                AND pss.date >= CURDATE() 
                AND pss.is_paid = 0
                AND cpt.coach_id = :coach_id
            ");
            $stmt5->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
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
    
    // Get upcoming sessions for today and tomorrow for specific coach
    public function getUpcomingSessions($coachId) {
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
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND cpt.coach_id = :coach_id
                ORDER BY pss.date, pss.start_time
            ");
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get single session details for specific coach
    public function getSessionById($sessionId, $coachId) {
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
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.id = :id AND cpt.coach_id = :coach_id
            ");
            $stmt->bindParam(':id', $sessionId, PDO::PARAM_INT);
            $stmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Update session status (cancel or complete) - needs to verify coach ownership
    public function updateSessionStatus($sessionId, $status, $coachId, $reason = null) {
        try {
            $conn = $this->db->connect();
            
            // First verify this session belongs to the coach
            $verifyStmt = $conn->prepare("
                SELECT pss.id
                FROM program_subscription_schedule pss
                JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                WHERE pss.id = :id AND cpt.coach_id = :coach_id
            ");
            $verifyStmt->bindParam(':id', $sessionId, PDO::PARAM_INT);
            $verifyStmt->bindParam(':coach_id', $coachId, PDO::PARAM_INT);
            $verifyStmt->execute();
            
            if ($verifyStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'error' => 'Not authorized to update this session'
                ];
            }
            
            // Base SQL statement
            $sql = "
                UPDATE program_subscription_schedule 
                SET status = :status,
                    updated_at = NOW()";
            
            // Add cancellation_reason to SQL if reason is provided
            if ($reason !== null) {
                $sql .= ", cancellation_reason = :reason";
            }
            
            // Complete the SQL statement
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            
            // Base parameters
            $params = [
                ':status' => $status,
                ':id' => $sessionId
            ];
            
            // Add reason parameter if provided
            if ($reason !== null) {
                $params[':reason'] = $reason;
            }
            
            $stmt->execute($params);
            
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
}


// Initialize database
$database = new Database();

// Get current user ID from session
$currentUserId = $_SESSION['user_id'] ?? 0;

try {
    // Test database connection
    $pdo = $database->connect();
    
    // Create coaching system instance
    $coachingSystem = new CoachingSystem($database);
    
    // Get data for the dashboard with current user's ID filter
    $personalSchedules = $coachingSystem->getPersonalSchedules($currentUserId);
    $groupSchedules = $coachingSystem->getGroupSchedules($currentUserId);
    $programSubscriptions = $coachingSystem->getProgramSubscriptions($currentUserId);
    $scheduledSessions = $coachingSystem->getScheduledSessions($currentUserId);
    $coachProgramTypes = $coachingSystem->getCoachProgramTypes($currentUserId);
    $stats = $coachingSystem->getStats($currentUserId);
    $upcomingSessions = $coachingSystem->getUpcomingSessions($currentUserId);
    
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
?>