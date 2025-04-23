<?php
require_once __DIR__ . '/../config.php';
if (!function_exists('getNotificationsWithReadStatus')) {
    /**
     * Get notifications with read status from session
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return array Notifications with read status
     */
    function getNotificationsWithReadStatus($database, $user_id) {
        // Get all notifications
        $transactions = getTransactionNotifications($database, $user_id);
        $memberships = getMembershipNotifications($database, $user_id);
        $announcements = getAnnouncementNotifications($database);
        $program_confirmations = getProgramConfirmationNotifications($database, $user_id);
        $program_cancellations = getProgramCancellationNotifications($database, $user_id);
        $session_notifications = getSessionNotifications($database, $user_id);
        $receipt_notifications = getTransactionReceiptNotifications($database, $user_id);
        
        // Initialize session array for read notifications if not exists
        if (!isset($_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'] = [
                'transactions' => [],
                'memberships' => [],
                'announcements' => [],
                'program_confirmations' => [],
                'program_cancellations' => [],
                'cancelled_sessions' => [],
                'completed_sessions' => [],
                'transaction_receipts' => []
            ];
        }
        
        // Mark transactions as read/unread based on session
        foreach ($transactions as &$transaction) {
            $transaction['is_read'] = in_array($transaction['id'], $_SESSION['read_notifications']['transactions']);
        }
        
        // Mark memberships as read/unread based on session
        foreach ($memberships as &$membership) {
            $membership['is_read'] = in_array($membership['id'], $_SESSION['read_notifications']['memberships']);
        }
        
        // Mark announcements as read/unread based on session
        foreach ($announcements as &$announcement) {
            $announcement['is_read'] = in_array($announcement['id'], $_SESSION['read_notifications']['announcements']);
        }
        
        // Ensure program_confirmations and program_cancellations arrays are always set
        if (!isset($_SESSION['read_notifications']['program_confirmations']) || !is_array($_SESSION['read_notifications']['program_confirmations'])) {
            $_SESSION['read_notifications']['program_confirmations'] = [];
        }
        if (!isset($_SESSION['read_notifications']['program_cancellations']) || !is_array($_SESSION['read_notifications']['program_cancellations'])) {
            $_SESSION['read_notifications']['program_cancellations'] = [];
        }
        
        // Ensure cancelled_sessions and completed_sessions arrays are always set
        if (!isset($_SESSION['read_notifications']['cancelled_sessions']) || !is_array($_SESSION['read_notifications']['cancelled_sessions'])) {
            $_SESSION['read_notifications']['cancelled_sessions'] = [];
        }
        if (!isset($_SESSION['read_notifications']['completed_sessions']) || !is_array($_SESSION['read_notifications']['completed_sessions'])) {
            $_SESSION['read_notifications']['completed_sessions'] = [];
        }
        
        // Ensure transaction_receipts array is always set
        if (!isset($_SESSION['read_notifications']['transaction_receipts']) || !is_array($_SESSION['read_notifications']['transaction_receipts'])) {
            $_SESSION['read_notifications']['transaction_receipts'] = [];
        }
        
        // Mark program confirmations as read/unread based on session
        foreach ($program_confirmations as &$confirmation) {
            $confirmation['is_read'] = in_array($confirmation['notification_id'], $_SESSION['read_notifications']['program_confirmations']);
        }
        
        // Mark program cancellations as read/unread based on session
        foreach ($program_cancellations as &$cancellation) {
            $cancellation['is_read'] = in_array($cancellation['notification_id'], $_SESSION['read_notifications']['program_cancellations']);
        }
        
        // Mark cancelled sessions as read/unread based on session
        foreach ($session_notifications['cancelled'] as &$cancelled_session) {
            $cancelled_session['is_read'] = in_array($cancelled_session['schedule_id'], $_SESSION['read_notifications']['cancelled_sessions']);
        }
        
        // Mark completed sessions as read/unread based on session
        foreach ($session_notifications['completed'] as &$completed_session) {
            $completed_session['is_read'] = in_array($completed_session['schedule_id'], $_SESSION['read_notifications']['completed_sessions']);
        }
        
        // Mark transaction receipts as read/unread based on session
        foreach ($receipt_notifications as &$receipt) {
            $receipt['is_read'] = in_array($receipt['transaction_id'], $_SESSION['read_notifications']['transaction_receipts']);
        }
        
        return [
            'transactions' => $transactions,
            'memberships' => $memberships,
            'announcements' => $announcements,
            'program_confirmations' => $program_confirmations,
            'program_cancellations' => $program_cancellations,
            'cancelled_sessions' => $session_notifications['cancelled'],
            'completed_sessions' => $session_notifications['completed'],
            'transaction_receipts' => $receipt_notifications
        ];
    }
}

if (!function_exists('getUnreadNotificationsCount')) {
   /**
     * Get count of unread notifications for a user using both session and database
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return int Count of unread notifications
     */
    function getUnreadNotificationsCount($database, $user_id) {
        $notifications = getNotificationsWithReadStatus($database, $user_id);
        
        $unread_count = 0;
        
        // Count unread transactions
        foreach ($notifications['transactions'] as $transaction) {
            if (!$transaction['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread memberships
        foreach ($notifications['memberships'] as $membership) {
            if (!$membership['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread announcements
        foreach ($notifications['announcements'] as $announcement) {
            if (!$announcement['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread transaction receipts
        foreach ($notifications['transaction_receipts'] as $receipt) {
            if (!$receipt['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread cancelled sessions
        foreach ($notifications['cancelled_sessions'] as $cancelled_session) {
            if (!$cancelled_session['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread completed sessions
        foreach ($notifications['completed_sessions'] as $completed_session) {
            if (!$completed_session['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread program confirmations
        foreach ($notifications['program_confirmations'] as $confirmation) {
            if (!$confirmation['is_read']) {
                $unread_count++;
            }
        }
        
        // Count unread program cancellations
        foreach ($notifications['program_cancellations'] as $cancellation) {
            if (!$cancellation['is_read']) {
                $unread_count++;
            }
        }
        
        return $unread_count;
    }
}

if (!function_exists('markNotificationAsRead')) {
   /**
 * Mark a notification as read in both session and database
 * 
 * @param string $type Notification type (transactions, memberships, announcements, program_confirmations, program_cancellations, cancelled_sessions, completed_sessions, transaction_receipts)
 * @param int $id Notification ID
 * @return void
 */
function markNotificationAsRead($type, $id) {
    global $database; // Make sure database is accessible or pass it as parameter
    
    // First, update session as before
    if (!isset($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [
            'transactions' => [],
            'memberships' => [],
            'announcements' => [],
            'program_confirmations' => [],
            'program_cancellations' => [],
            'cancelled_sessions' => [],
            'completed_sessions' => [],
            'transaction_receipts' => []
        ];
    }
    
    if (!in_array($id, $_SESSION['read_notifications'][$type])) {
        $_SESSION['read_notifications'][$type][] = $id;
    }
    
    // Then, persist to database if user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $pdo = $database->connect();
        
        // Insert or ignore if already exists
        $sql = "INSERT IGNORE INTO notification_reads 
                (user_id, notification_type, notification_id) 
                VALUES (?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $type, PDO::PARAM_STR);
        $stmt->bindParam(3, $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
}

if (!function_exists('markAllNotificationsAsRead')) {
 /**
 * Mark all notifications as read in both session and database
 * 
 * @param Database $database Database connection class
 * @param int $user_id User ID
 * @return void
 */
function markAllNotificationsAsRead($database, $user_id) {
    $notifications = getNotificationsWithReadStatus($database, $user_id);
    $pdo = $database->connect();
    
    // Initialize session array for read notifications if not exists
    if (!isset($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [
            'transactions' => [],
            'memberships' => [],
            'announcements' => [],
            'program_confirmations' => [],
            'program_cancellations' => [],
            'cancelled_sessions' => [],
            'completed_sessions' => [],
            'transaction_receipts' => []
        ];
    }
    
    // Prepare the database statement once for performance
    $sql = "INSERT IGNORE INTO notification_reads 
            (user_id, notification_type, notification_id) 
            VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // Mark all transactions as read
    foreach ($notifications['transactions'] as $transaction) {
        if (!in_array($transaction['id'], $_SESSION['read_notifications']['transactions'])) {
            $_SESSION['read_notifications']['transactions'][] = $transaction['id'];
            
            // Also persist to database
            $type = 'transactions';
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $type, PDO::PARAM_STR);
            $stmt->bindParam(3, $transaction['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Mark all memberships as read
    foreach ($notifications['memberships'] as $membership) {
        if (!in_array($membership['id'], $_SESSION['read_notifications']['memberships'])) {
            $_SESSION['read_notifications']['memberships'][] = $membership['id'];
            
            // Also persist to database
            $type = 'memberships';
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $type, PDO::PARAM_STR); 
            $stmt->bindParam(3, $membership['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Mark all announcements as read
    foreach ($notifications['announcements'] as $announcement) {
        if (!in_array($announcement['id'], $_SESSION['read_notifications']['announcements'])) {
            $_SESSION['read_notifications']['announcements'][] = $announcement['id'];
            
            // Also persist to database
            $type = 'announcements';
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $type, PDO::PARAM_STR);
            $stmt->bindParam(3, $announcement['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Mark all program confirmations as read
    foreach ($notifications['program_confirmations'] as $confirmation) {
        if (!in_array($confirmation['notification_id'], $_SESSION['read_notifications']['program_confirmations'])) {
            $_SESSION['read_notifications']['program_confirmations'][] = $confirmation['notification_id'];
            // Also persist to database
            $type = 'program_confirmations';
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $type, PDO::PARAM_STR);
            $stmt->bindParam(3, $confirmation['notification_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Mark all program cancellations as read
    if (isset($notifications['program_cancellations'])) {
        foreach ($notifications['program_cancellations'] as $cancellation) {
            if (!in_array($cancellation['notification_id'], $_SESSION['read_notifications']['program_cancellations'])) {
                $_SESSION['read_notifications']['program_cancellations'][] = $cancellation['notification_id'];
                // Also persist to database
                $type = 'program_cancellations';
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $type, PDO::PARAM_STR);
                $stmt->bindParam(3, $cancellation['notification_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Mark all cancelled sessions as read
    if (isset($notifications['cancelled_sessions'])) {
        foreach ($notifications['cancelled_sessions'] as $cancelled_session) {
            if (!in_array($cancelled_session['schedule_id'], $_SESSION['read_notifications']['cancelled_sessions'])) {
                $_SESSION['read_notifications']['cancelled_sessions'][] = $cancelled_session['schedule_id'];
                // Also persist to database
                $type = 'cancelled_sessions';
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $type, PDO::PARAM_STR);
                $stmt->bindParam(3, $cancelled_session['schedule_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Mark all completed sessions as read
    if (isset($notifications['completed_sessions'])) {
        foreach ($notifications['completed_sessions'] as $completed_session) {
            if (!in_array($completed_session['schedule_id'], $_SESSION['read_notifications']['completed_sessions'])) {
                $_SESSION['read_notifications']['completed_sessions'][] = $completed_session['schedule_id'];
                // Also persist to database
                $type = 'completed_sessions';
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $type, PDO::PARAM_STR);
                $stmt->bindParam(3, $completed_session['schedule_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Mark all transaction receipts as read
    if (isset($notifications['transaction_receipts'])) {
        foreach ($notifications['transaction_receipts'] as $receipt) {
            if (!in_array($receipt['transaction_id'], $_SESSION['read_notifications']['transaction_receipts'])) {
                $_SESSION['read_notifications']['transaction_receipts'][] = $receipt['transaction_id'];
                // Also persist to database
                $type = 'transaction_receipts';
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $type, PDO::PARAM_STR);
                $stmt->bindParam(3, $receipt['transaction_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
}
}

if (!function_exists('getTransactionNotifications')) {
    /**
     * Get recent transaction notifications for a user
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @param string $days_back How many days back to check (default 30)
     * @return array Transaction notifications
     */
    function getTransactionNotifications($database, $user_id, $days_back = '30') {
        $pdo = $database->connect();
        
        // Set a date for fetching recent transactions
        $user_created_at = date('Y-m-d H:i:s', strtotime('-' . $days_back . ' days'));
        
        // Fetch transaction notifications for the user
        $transaction_sql = "SELECT id, status, created_at 
                            FROM transactions 
                            WHERE user_id = ? AND created_at >= ?
                            ORDER BY created_at DESC";
        $stmt = $pdo->prepare($transaction_sql);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_created_at, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getMembershipNotifications')) {
    /**
     * Get membership notifications (expiring or expired) for a user
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return array Membership notifications
     */
    function getMembershipNotifications($database, $user_id) {
        $pdo = $database->connect();
        
        // Fetch memberships with expiring or expired status for the current user
        $membership_sql = "SELECT m.id, m.start_date, m.end_date, m.status, mp.plan_name as plan_name, m.amount
                          FROM memberships m
                          JOIN transactions t ON m.transaction_id = t.id
                          JOIN membership_plans mp ON m.membership_plan_id = mp.id
                          WHERE t.user_id = ? AND (m.status = 'expiring' OR m.status = 'expired')
                          ORDER BY m.end_date DESC";
        $membership_stmt = $pdo->prepare($membership_sql);
        $membership_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $membership_stmt->execute();
        
        return $membership_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getProgramConfirmationNotifications')) {
    /**
     * Get program confirmation notifications for a user (as member or coach), grouped by transaction_id
     * @param Database $database
     * @param int $user_id
     * @return array
     */
    function getProgramConfirmationNotifications($database, $user_id) {
        $pdo = $database->connect();
        $sql = "SELECT GROUP_CONCAT(ps.id) as subscription_ids, ps.transaction_id as notification_id, MAX(ps.status) as status, MAX(ps.created_at) as created_at,
                       GROUP_CONCAT(CONCAT(p.program_name, ' (', cpt.type, ')') SEPARATOR '\n') as programs, MAX(cpt.coach_id) as coach_id, MAX(ps.user_id) as user_id,
                       CONCAT_WS(' ', MAX(pd.first_name), MAX(pd.middle_name), MAX(pd.last_name)) as member_name,
                       CONCAT_WS(' ', MAX(pd_coach.first_name), MAX(pd_coach.middle_name), MAX(pd_coach.last_name)) as coach_name
                FROM program_subscriptions ps
                INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                INNER JOIN programs p ON cpt.program_id = p.id
                LEFT JOIN personal_details pd ON ps.user_id = pd.user_id
                LEFT JOIN personal_details pd_coach ON cpt.coach_id = pd_coach.user_id
                WHERE (ps.user_id = ? OR cpt.coach_id = ?) AND ps.status = 'active'
                GROUP BY ps.transaction_id
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getProgramCancellationNotifications')) {
    /**
     * Get program cancellation notifications for a user (as member or coach), grouped by transaction_id
     * @param Database $database
     * @param int $user_id
     * @return array
     */
    function getProgramCancellationNotifications($database, $user_id) {
    $pdo = $database->connect();
    $sql = "SELECT GROUP_CONCAT(ps.id) as subscription_ids, ps.transaction_id as notification_id, MAX(ps.status) as status, MAX(ps.created_at) as created_at,
                   GROUP_CONCAT(CONCAT(p.program_name, ' (', cpt.type, ')') SEPARATOR '\n') as programs, MAX(cpt.coach_id) as coach_id, MAX(ps.user_id) as user_id,
                   CONCAT_WS(' ', MAX(pd.first_name), MAX(pd.middle_name), MAX(pd.last_name)) as member_name,
                   CONCAT_WS(' ', MAX(pd_coach.first_name), MAX(pd_coach.middle_name), MAX(pd_coach.last_name)) as coach_name
            FROM program_subscriptions ps
            INNER JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
            INNER JOIN programs p ON cpt.program_id = p.id
            LEFT JOIN personal_details pd ON ps.user_id = pd.user_id
            LEFT JOIN personal_details pd_coach ON cpt.coach_id = pd_coach.user_id
            WHERE (ps.user_id = ? OR cpt.coach_id = ?) AND ps.status = 'cancelled'
            GROUP BY ps.transaction_id
            ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

if (!function_exists('getTransactionReceiptNotifications')) {
    /**
     * Get transaction receipt notifications for a user, grouped by schedule ID
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return array Transaction receipt notifications
     */
    function getTransactionReceiptNotifications($database, $user_id) {
        $pdo = $database->connect();
        
        // First, get all receipt notifications
        $query = "SELECT 
                t.id as transaction_id,
                ps.id as subscription_id,
                CONCAT_WS(' ', u2.username) as coach_name,
                u2.id as coach_id,
                p.program_name,
                cpt.type as program_type,
                pss.date as session_date,
                pss.start_time,
                pss.end_time,
                pss.amount,
                pss.is_paid,
                DATE(pss.updated_at) as payment_date,
                pss.updated_at as created_at,
                COALESCE(pss.coach_personal_schedule_id, pss.coach_group_schedule_id) as schedule_id,
                CASE 
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'personal'
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'group'
                    ELSE NULL
                END as schedule_type,
                0 as is_read
            FROM program_subscription_schedule pss
            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
            JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
            JOIN programs p ON cpt.program_id = p.id
            JOIN users u ON ps.user_id = u.id
            JOIN users u2 ON cpt.coach_id = u2.id
            LEFT JOIN transactions t ON ps.transaction_id = t.id
            WHERE ps.user_id = ? AND pss.is_paid = 1
            ORDER BY pss.updated_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $all_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group receipts by schedule_id
        $grouped_receipts = [];
        foreach ($all_receipts as $receipt) {
            $key = $receipt['coach_id'] . '_' . $receipt['schedule_id'] . '_' . $receipt['schedule_type'];
            
            if (!isset($grouped_receipts[$key])) {
                $grouped_receipts[$key] = $receipt;
                // Initialize a transactions array to store multiple transaction IDs
                $grouped_receipts[$key]['transaction_ids'] = [$receipt['transaction_id']];
                $grouped_receipts[$key]['total_amount'] = $receipt['amount'];
            } else {
                // Add transaction ID to the array if not already included
                if (!in_array($receipt['transaction_id'], $grouped_receipts[$key]['transaction_ids'])) {
                    $grouped_receipts[$key]['transaction_ids'][] = $receipt['transaction_id'];
                }
                // Update the total amount
                $grouped_receipts[$key]['total_amount'] += $receipt['amount'];
                
                // Keep the most recent date if this receipt is newer
                if (strtotime($receipt['created_at']) > strtotime($grouped_receipts[$key]['created_at'])) {
                    $grouped_receipts[$key]['created_at'] = $receipt['created_at'];
                    $grouped_receipts[$key]['payment_date'] = $receipt['payment_date'];
                }
            }
        }
        
        // Convert back to indexed array
        $result = array_values($grouped_receipts);
        
        // For each grouped receipt, add a comma-separated list of transaction IDs
        foreach ($result as &$item) {
            $item['transaction_ids_list'] = implode(',', $item['transaction_ids']);
            // Use the first transaction ID as the main one for display/linking
            $item['transaction_id'] = $item['transaction_ids'][0];
        }
        
        return $result;
    }
    function getTransactionProgramDetails($database, $transaction_ids) {
        $pdo = $database->connect();
        
        $query = "SELECT 
                t.id as transaction_id,
                cpt.coach_id,
                CONCAT_WS(' ', u2.username) as coach_name,
                p.program_name,
                CASE
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'personal'
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'group'
                    ELSE NULL
                END as schedule_type,
                COUNT(pss.id) as session_count
            FROM program_subscription_schedule pss
            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
            JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
            JOIN programs p ON cpt.program_id = p.id
            JOIN users u2 ON cpt.coach_id = u2.id
            LEFT JOIN transactions t ON ps.transaction_id = t.id
            WHERE t.id IN (" . $transaction_ids . ")
            AND pss.is_paid = 1
            GROUP BY p.program_name, schedule_type, cpt.coach_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('insertProgramCancellationNotification')) {
    /**
     * Insert a program cancellation notification for a user (uses transaction_id as notification_id)
     * @param Database $database
     * @param int $user_id
     * @param int $transaction_id
     */
    function insertProgramCancellationNotification($database, $user_id, $transaction_id) {
        $pdo = $database->connect();
        $type = 'program_cancellations';
        $sql = "INSERT IGNORE INTO notification_reads (user_id, notification_type, notification_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $type, $transaction_id]);
    }
}

if (!function_exists('insertProgramConfirmationNotification')) {
    /**
     * Insert a program confirmation notification for a user (uses transaction_id as notification_id)
     * @param Database $database
     * @param int $user_id
     * @param int $transaction_id
     */
    function insertProgramConfirmationNotification($database, $user_id, $transaction_id) {
        $pdo = $database->connect();
        $type = 'program_confirmations';
        $sql = "INSERT IGNORE INTO notification_reads (user_id, notification_type, notification_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $type, $transaction_id]);
    }
}

if (!function_exists('insertTransactionReceiptNotification')) {
    /**
     * Insert a transaction receipt notification for a user
     * @param Database $database
     * @param int $user_id
     * @param int $transaction_id
     */
    function insertTransactionReceiptNotification($database, $user_id, $transaction_id) {
        $pdo = $database->connect();
        $type = 'transaction_receipts';
        $sql = "INSERT IGNORE INTO notification_reads (user_id, notification_type, notification_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $type, $transaction_id]);
    }
}

if (!function_exists('getAnnouncementNotifications')) {
    /**
     * Get active announcements
     * 
     * @param Database $database Database connection class
     * @return array Announcement notifications
     */
    function getAnnouncementNotifications($database) {
        $pdo = $database->connect();
        
        // Fetch announcements from the database
        $sql = "SELECT id, message, applied_date, applied_time, announcement_type, created_at 
                FROM announcements 
                WHERE is_active = 1 
                ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
if (!function_exists('getActiveMemberships')) {
    /**
     * Get active memberships for a user
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return array Active membership records
     */
    function getActiveMemberships($database, $user_id) {
        $pdo = $database->connect();
        
        // Fetch active memberships for the current user
        $sql = "SELECT m.id, m.start_date, m.end_date, m.status, mp.plan_name as plan_name, m.amount
                FROM memberships m
                JOIN transactions t ON m.transaction_id = t.id
                JOIN membership_plans mp ON m.membership_plan_id = mp.id
                WHERE t.user_id = ? AND m.status = 'active'
                ORDER BY m.end_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getSessionNotifications')) {
    /**
     * Get session notifications for a user including cancelled and completed sessions
     * 
     * @param Database $database Database connection class
     * @param int $user_id User ID
     * @return array Session notifications categorized by status
     */
    function getSessionNotifications($database, $user_id) {
        $pdo = $database->connect();
        $notifications = [
            'cancelled' => [],
            'completed' => []
        ];
        
        // Get cancelled sessions
        $cancelled_query = "
            SELECT p.id AS schedule_id, 
                p.program_subscription_id, 
                p.date, 
                p.start_time, 
                p.end_time, 
                p.cancellation_reason, 
                p.created_at, 
                pt.description AS program_type_description, 
                pt.type AS session_type, 
                pr.program_name, 
                cu.username AS coach_username, 
                0 AS is_read 
            FROM program_subscription_schedule p 
            JOIN program_subscriptions ps ON p.program_subscription_id = ps.id 
            JOIN users u ON ps.user_id = u.id 
            JOIN coach_program_types pt ON ps.coach_program_type_id = pt.id 
            JOIN users cu ON pt.coach_id = cu.id 
            JOIN programs pr ON pt.program_id = pr.id 
            WHERE p.status = 'cancelled' AND ps.user_id = ? 
            ORDER BY p.date DESC, p.start_time";
        
        $stmt = $pdo->prepare($cancelled_query);
        $stmt->execute([$user_id]);
        $notifications['cancelled'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get completed sessions
        $completed_query = "
            SELECT p.id AS schedule_id, 
                p.program_subscription_id, 
                p.date, 
                p.start_time, 
                p.end_time, 
                p.created_at, 
                pt.description AS program_type_description, 
                pt.type AS session_type, 
                pr.program_name, 
                cu.username AS coach_username, 
                0 AS is_read 
            FROM program_subscription_schedule p 
            JOIN program_subscriptions ps ON p.program_subscription_id = ps.id 
            JOIN users u ON ps.user_id = u.id 
            JOIN coach_program_types pt ON ps.coach_program_type_id = pt.id 
            JOIN users cu ON pt.coach_id = cu.id 
            JOIN programs pr ON pt.program_id = pr.id 
            WHERE p.status = 'completed' AND ps.user_id = ? 
            ORDER BY p.date DESC, p.start_time";
        
        $stmt = $pdo->prepare($completed_query);
        $stmt->execute([$user_id]);
        $notifications['completed'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $notifications;
    }
}
?>