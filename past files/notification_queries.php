<?php
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
        
        // Initialize session array for read notifications if not exists
        if (!isset($_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'] = [
                'transactions' => [],
                'memberships' => [],
                'announcements' => []
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
        
        return [
            'transactions' => $transactions,
            'memberships' => $memberships,
            'announcements' => $announcements
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
    
    return $unread_count;
}
}

if (!function_exists('markNotificationAsRead')) {
    /**
 * Mark a notification as read in both session and database
 * 
 * @param string $type Notification type (transactions, memberships, announcements)
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
            'announcements' => []
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
            'announcements' => []
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
?>