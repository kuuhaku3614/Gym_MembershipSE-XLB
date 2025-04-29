<?php
require_once(__DIR__ . '/../../../../config.php');

class ExpiryNotifications {
    private $pdo;
    
    public function __construct() {
        global $database;
        $this->pdo = $database->connect();
        date_default_timezone_set('Asia/Manila');
    }
    
    /**
     * Get all expiring and expired memberships and rentals
     * @param int $user_id Current user ID to check read status
     * @return array Array of notifications
     */
    public function getExpiryNotifications($user_id = 0) {
        $notifications = array_merge(
            $this->getExpiringMemberships(),
            $this->getExpiredMemberships(),
            $this->getExpiringRentals(),
            $this->getExpiredRentals(),
            $this->getOverduePendingTransactions()
        );
        
        // Sort notifications by date (newest first)
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Check read status for each notification if user is logged in
        if ($user_id > 0) {
            foreach ($notifications as &$notification) {
                $notification['is_read'] = $this->isNotificationRead($user_id, $notification['id'], $notification['type']);
            }
        }
        
        return $notifications;
    }
    
    /**
     * Check if a notification has been read by a user
     * @param int $user_id User ID
     * @param int $notification_id Notification ID
     * @param string $notification_type Notification type
     * @return bool True if notification has been read
     */
    public function isNotificationRead($user_id, $notification_id, $notification_type) {
        $query = "SELECT id FROM notification_reads 
                  WHERE user_id = :user_id 
                  AND notification_id = :notification_id 
                  AND notification_type = :notification_type 
                  LIMIT 1";
                  
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':notification_type', $notification_type, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Count unread notifications for a user
     * @param int $user_id User ID
     * @return array Count of unread notifications by type and total
     */
    public function countUnreadNotifications($user_id) {
        $allNotifications = $this->getExpiryNotifications($user_id);
        
        $counts = [
            'expiring' => 0,
            'expired' => 0,
            'total' => 0
        ];
        
        foreach ($allNotifications as $notification) {
            if (!$notification['is_read']) {
                $counts['total']++;
                
                if (strpos($notification['type'], 'expiring') !== false) {
                    $counts['expiring']++;
                } else {
                    $counts['expired']++;
                }
            }
        }
        
        return $counts;
    }
    
    /**
     * Mark a notification as read
     * @param int $user_id User ID
     * @param int $notification_id Notification ID
     * @param string $notification_type Notification type
     * @return bool True if successful
     */
    public function markNotificationAsRead($user_id, $notification_id, $notification_type) {
        // Check if already marked as read
        if ($this->isNotificationRead($user_id, $notification_id, $notification_type)) {
            return true; // Already marked as read, consider it a success
        }
        
        $query = "INSERT INTO notification_reads 
                 (user_id, notification_id, notification_type) 
                 VALUES (:user_id, :notification_id, :notification_type)
                 ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP";
                 
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':notification_type', $notification_type, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    /**
     * Mark all notifications as read for a user
     * @param int $user_id User ID
     * @return bool True if successful
     */
    public function markAllNotificationsAsRead($user_id) {
        // Get all unread notifications
        $allNotifications = $this->getExpiryNotifications($user_id);
        $success = true;
        
        foreach ($allNotifications as $notification) {
            if (!$notification['is_read']) {
                // Mark each unread notification as read
                $result = $this->markNotificationAsRead(
                    $user_id, 
                    $notification['id'], 
                    $notification['type']
                );
                
                // If any operation fails, set success to false
                if (!$result) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Get expiring memberships (within 7 days)
     * @return array Array of expiring membership notifications
     */
    private function getExpiringMemberships() {
        $query = "SELECT 
                    m.id AS membership_id,
                    m.transaction_id,
                    p.first_name,
                    p.last_name,
                    u.id AS user_id,
                    u.username,
                    m.start_date,
                    m.end_date,
                    m.amount,
                    m.status,
                    mp.plan_name AS plan_name
                FROM 
                    memberships m
                JOIN 
                    transactions t ON m.transaction_id = t.id
                JOIN 
                    users u ON t.user_id = u.id
                JOIN 
                    personal_details p ON u.id = p.user_id
                JOIN
                    membership_plans mp ON m.membership_plan_id = mp.id
                WHERE 
                    m.status = 'expiring'";
        
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysRemaining = (strtotime($row['end_date']) - time()) / (60 * 60 * 24);
                $daysRemaining = ceil($daysRemaining);
                
                // Set the timestamp to when the membership became "expiring" status
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date'] . ' -7 days'));
                
                $notifications[] = [
                    'id' => $row['membership_id'],
                    'type' => 'expiring_membership',
                    'title' => 'Membership Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['plan_name'] . ' membership expires in ' . $daysRemaining . ' days',
                    'timestamp' => $timestamp,
                    'is_read' => false, // Default value, will be updated later if needed
                    'details' => [
                        'membership_id' => $row['membership_id'],
                        'transaction_id' => $row['transaction_id'],
                        'user_id' => $row['user_id'],
                        'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                        'username' => $row['username'],
                        'plan_name' => $row['plan_name'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'amount' => $row['amount'],
                        'days_remaining' => $daysRemaining
                    ]
                ];
            }
        }
        
        return $notifications;
    }
    
    /**
     * Get expired memberships
     * @return array Array of expired membership notifications
     */
    private function getExpiredMemberships() {
        $query = "SELECT 
                    m.id AS membership_id,
                    m.transaction_id,
                    p.first_name,
                    p.last_name,
                    u.id AS user_id,
                    u.username,
                    m.start_date,
                    m.end_date,
                    m.amount,
                    m.status,
                    mp.plan_name AS plan_name
                FROM 
                    memberships m
                JOIN 
                    transactions t ON m.transaction_id = t.id
                JOIN 
                    users u ON t.user_id = u.id
                JOIN 
                    personal_details p ON u.id = p.user_id
                JOIN
                    membership_plans mp ON m.membership_plan_id = mp.id
                WHERE 
                    m.status = 'expired'";
        
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysSince = (time() - strtotime($row['end_date'])) / (60 * 60 * 24);
                $daysSince = floor($daysSince);
                
                // Use the actual expiration date and time as the timestamp
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date']));
                
                $notifications[] = [
                    'id' => $row['membership_id'],
                    'type' => 'expired_membership',
                    'title' => 'Membership Expired',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['plan_name'] . ' membership expired ' . $daysSince . ' days ago',
                    'timestamp' => $timestamp,
                    'is_read' => false, // Default value, will be updated later if needed
                    'details' => [
                        'membership_id' => $row['membership_id'],
                        'transaction_id' => $row['transaction_id'],
                        'user_id' => $row['user_id'],
                        'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                        'username' => $row['username'],
                        'plan_name' => $row['plan_name'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'amount' => $row['amount'],
                        'days_since' => $daysSince
                    ]
                ];
            }
        }
        
        return $notifications;
    }
    
    /**
     * Get expiring rental subscriptions (within 7 days)
     * @return array Array of expiring rental notifications
     */
    private function getExpiringRentals() {
        $query = "SELECT 
                    rs.id AS rental_subscription_id,
                    rs.transaction_id,
                    p.first_name,
                    p.last_name,
                    u.id AS user_id,
                    u.username,
                    rs.start_date,
                    rs.end_date,
                    rs.amount,
                    rs.status,
                    s.service_name AS service_name
                FROM 
                    rental_subscriptions rs
                JOIN 
                    transactions t ON rs.transaction_id = t.id
                JOIN 
                    users u ON t.user_id = u.id
                JOIN 
                    personal_details p ON u.id = p.user_id
                JOIN
                    rental_services s ON rs.rental_service_id = s.id
                WHERE 
                    rs.status = 'expiring'";
        
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysRemaining = (strtotime($row['end_date']) - time()) / (60 * 60 * 24);
                $daysRemaining = ceil($daysRemaining);
                
                // Set the timestamp to when the rental became "expiring" status
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date'] . ' -7 days'));
                
                $notifications[] = [
                    'id' => $row['rental_subscription_id'],
                    'type' => 'expiring_rental',
                    'title' => 'Rental Subscription Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['service_name'] . ' rental expires in ' . $daysRemaining . ' days',
                    'timestamp' => $timestamp,
                    'is_read' => false, // Default value, will be updated later if needed
                    'details' => [
                        'rental_id' => $row['rental_subscription_id'],
                        'transaction_id' => $row['transaction_id'],
                        'user_id' => $row['user_id'],
                        'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                        'username' => $row['username'],
                        'service_name' => $row['service_name'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'amount' => $row['amount'],
                        'days_remaining' => $daysRemaining
                    ]
                ];
            }
        }
        
        return $notifications;
    }
    
    /**
     * Get expired rental subscriptions
     * @return array Array of expired rental notifications
     */
    private function getExpiredRentals() {
        $query = "SELECT 
                    rs.id AS rental_subscription_id,
                    rs.transaction_id,
                    p.first_name,
                    p.last_name,
                    u.id AS user_id,
                    u.username,
                    rs.start_date,
                    rs.end_date,
                    rs.amount,
                    rs.status,
                    s.service_name AS service_name
                FROM 
                    rental_subscriptions rs
                JOIN 
                    transactions t ON rs.transaction_id = t.id
                JOIN 
                    users u ON t.user_id = u.id
                JOIN 
                    personal_details p ON u.id = p.user_id
                JOIN
                    rental_services s ON rs.rental_service_id = s.id
                WHERE 
                    rs.status = 'expired'";
        
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysSince = (time() - strtotime($row['end_date'])) / (60 * 60 * 24);
                $daysSince = floor($daysSince);
                
                // Use the actual expiration date and time as the timestamp
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date']));
                
                $notifications[] = [
                    'id' => $row['rental_subscription_id'],
                    'type' => 'expired_rental',
                    'title' => 'Rental Subscription Expired',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['service_name'] . ' rental expired ' . $daysSince . ' days ago',
                    'timestamp' => $timestamp,
                    'is_read' => false, // Default value, will be updated later if needed
                    'details' => [
                        'rental_id' => $row['rental_subscription_id'],
                        'transaction_id' => $row['transaction_id'],
                        'user_id' => $row['user_id'],
                        'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                        'username' => $row['username'],
                        'service_name' => $row['service_name'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'amount' => $row['amount'],
                        'days_since' => $daysSince
                    ]
                ];
            }
        }
        
        return $notifications;
    }

    /**
     * Get overdue pending transactions (unpaid memberships/walk-ins at least 1 day overdue)
     * @return array Array of overdue pending notifications
     */
    private function getOverduePendingTransactions() {
        $notifications = [];
        // Overdue memberships (pending transaction, is_paid=0, start_date <= yesterday)
        $membershipQuery = "SELECT m.id AS membership_id, m.transaction_id, m.start_date, m.end_date, m.amount, mp.plan_name, u.id AS user_id, u.username, pd.first_name, pd.last_name, t.created_at
            FROM memberships m
            JOIN membership_plans mp ON m.membership_plan_id = mp.id
            JOIN transactions t ON m.transaction_id = t.id
            JOIN users u ON t.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE t.status = 'pending' AND m.is_paid = 0 AND m.start_date <= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $stmt = $this->pdo->query($membershipQuery);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $daysOverdue = (strtotime(date('Y-m-d')) - strtotime($row['start_date'])) / (60 * 60 * 24);
            $daysOverdue = floor($daysOverdue);
            $timestamp = date('F d, Y, h:i a', strtotime($row['created_at']));
            $notifications[] = [
                'id' => 'm_' . $row['membership_id'],
                'type' => 'overdue_pending_membership',
                'title' => 'Overdue Unpaid Membership',
                'message' => $row['first_name'] . ' ' . $row['last_name'] . "'s " . $row['plan_name'] . " membership is unpaid and overdue by $daysOverdue days.",
                'timestamp' => $timestamp,
                'is_read' => false,
                'details' => [
                    'membership_id' => $row['membership_id'],
                    'transaction_id' => $row['transaction_id'],
                    'user_id' => $row['user_id'],
                    'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'username' => $row['username'],
                    'plan_name' => $row['plan_name'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'amount' => $row['amount'],
                    'days_overdue' => $daysOverdue
                ]
            ];
        }
        // Overdue walk-ins (pending transaction, is_paid=0, date <= yesterday)
        $walkinQuery = "SELECT w.id AS walkin_id, w.transaction_id, w.date, w.amount, u.id AS user_id, u.username, pd.first_name, pd.last_name, t.created_at
            FROM walk_in_records w
            JOIN transactions t ON w.transaction_id = t.id
            JOIN users u ON t.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE t.status = 'pending' AND w.is_paid = 0 AND w.date <= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $stmt = $this->pdo->query($walkinQuery);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $daysOverdue = (strtotime(date('Y-m-d')) - strtotime($row['date'])) / (60 * 60 * 24);
            $daysOverdue = floor($daysOverdue);
            $timestamp = date('F d, Y, h:i a', strtotime($row['created_at']));
            $notifications[] = [
                'id' => 'w_' . $row['walkin_id'],
                'type' => 'overdue_pending_walkin',
                'title' => 'Overdue Unpaid Walk-in',
                'message' => $row['first_name'] . ' ' . $row['last_name'] . "'s walk-in is unpaid and overdue by $daysOverdue days.",
                'timestamp' => $timestamp,
                'is_read' => false,
                'details' => [
                    'walkin_id' => $row['walkin_id'],
                    'transaction_id' => $row['transaction_id'],
                    'user_id' => $row['user_id'],
                    'member_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'username' => $row['username'],
                    'plan_name' => 'Walk-in',
                    'start_date' => $row['date'],
                    'end_date' => '',
                    'amount' => $row['amount'],
                    'days_overdue' => $daysOverdue
                ]
            ];
        }
        return $notifications;
    }
}