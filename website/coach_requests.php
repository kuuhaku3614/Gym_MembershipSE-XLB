<?php
require_once __DIR__ . '/../config.php';

class CoachRequests {
    private $database;

    /**
     * Confirm a program subscription request
     * 
     * @param int $subscription_id The ID of the subscription
     * @return array Array containing success status and message
     */
    public function confirmRequest($subscription_id) {
        try {
            $pdo = $this->database->connect();
            $pdo->beginTransaction();

            $query = "UPDATE program_subscriptions SET status = 'active' WHERE transaction_id = (SELECT transaction_id FROM program_subscriptions WHERE id = ?)";
            $stmt = $pdo->prepare($query);
            $success = $stmt->execute([$subscription_id]);
            
            if ($success && $stmt->rowCount() > 0) {
                // Fetch the coach_id, user_id, and transaction_id for the confirmed request
                $infoQuery = "SELECT ps.user_id, cpt.coach_id, ps.transaction_id
                              FROM program_subscriptions ps
                              INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                              WHERE ps.id = ?";
                $infoStmt = $pdo->prepare($infoQuery);
                $infoStmt->execute([$subscription_id]);
                $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
                // No notification_reads entry should be created here. Only update the program_subscriptions status.
                $pdo->commit();
                return ['success' => true, 'message' => 'Program request confirmed successfully'];
            } else {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to confirm program request'];
            }
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Error in confirmRequest: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    /**
     * Cancel a program subscription request
     * 
     * @param int $subscription_id The ID of the subscription
     * @return array Array containing success status and message
     */
    public function cancelRequest($subscription_id) {
        try {
            $pdo = $this->database->connect();
            $pdo->beginTransaction();

            // Get info for notification and cancellation
            $infoQuery = "SELECT ps.user_id, cpt.coach_id, ps.transaction_id FROM program_subscriptions ps INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id WHERE ps.id = ?";
            $infoStmt = $pdo->prepare($infoQuery);
            $infoStmt->execute([$subscription_id]);
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Request not found'];
            }
            $member_id = $info['user_id'];
            $coach_id = $info['coach_id'];
            $transaction_id = $info['transaction_id'];
            $cancelled_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            // Update status only (no cancelled_by, no updated_at)
            $query = "UPDATE program_subscriptions SET status = 'cancelled' WHERE transaction_id = ?";
            $stmt = $pdo->prepare($query);
            $success = $stmt->execute([$transaction_id]);
            
            if ($success && $stmt->rowCount() > 0) {

                $pdo->commit();
                return ['success' => true, 'message' => 'Program request cancelled successfully'];
            } else {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to cancel program request'];
            }
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Error in cancelRequest: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

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
        WHERE ps.transaction_id = (SELECT transaction_id FROM program_subscriptions WHERE id = ?)
        ORDER BY p.program_name, pss.date, pss.start_time";
        
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['subscription_id'])) {
    $database = new Database();
    $coach_requests = new CoachRequests($database);
    if ($_POST['action'] === 'confirm_request') {
        $result = $coach_requests->confirmRequest($_POST['subscription_id']);
    } elseif ($_POST['action'] === 'cancel_request') {
        $result = $coach_requests->cancelRequest($_POST['subscription_id']);
    } else {
        $result = ['success' => false, 'message' => 'Unknown action'];
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
