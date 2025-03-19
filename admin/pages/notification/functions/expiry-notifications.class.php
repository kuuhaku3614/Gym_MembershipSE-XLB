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
     * @return array Array of notifications
     */
    public function getExpiryNotifications() {
        $notifications = array_merge(
            $this->getExpiringMemberships(),
            $this->getExpiredMemberships(),
            $this->getExpiringRentals(),
            $this->getExpiredRentals()
        );
        
        // Sort notifications by date (newest first)
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return $notifications;
    }
    
    /**
     * Check if a notification has been read by the user
     * @param int $userId User ID
     * @param string $type Notification type
     * @param int $notificationId Notification ID
     * @return bool True if notification has been read
     */
    public function isNotificationRead($userId, $type, $notificationId) {
        // First check session
        if (isset($_SESSION['read_notifications'][$type]) && 
            in_array($notificationId, $_SESSION['read_notifications'][$type])) {
            return true;
        }
        
        // Otherwise check database
        $stmt = $this->pdo->prepare("SELECT id FROM notification_reads 
                                    WHERE user_id = ? AND notification_type = ? AND notification_id = ?");
        $stmt->execute([$userId, $type, $notificationId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Mark a notification as read
     * @param int $userId User ID
     * @param string $type Notification type
     * @param int $notificationId Notification ID
     * @return bool True if successful
     */
    public function markAsRead($userId, $type, $notificationId) {
        // Validate inputs
        $userId = (int)$userId;
        $notificationId = (int)$notificationId;
        $type = filter_var($type, FILTER_SANITIZE_STRING);
        
        if (!$userId || !$notificationId || empty($type)) {
            return false;
        }
        
        // Check if already read
        if ($this->isNotificationRead($userId, $type, $notificationId)) {
            return true;
        }
        
        try {
            // Insert a new record
            $stmt = $this->pdo->prepare("INSERT INTO notification_reads 
                                        (user_id, notification_type, notification_id, read_at) 
                                        VALUES (?, ?, ?, NOW())");
            $success = $stmt->execute([$userId, $type, $notificationId]);
            
            // Also update the session
            if (!isset($_SESSION['read_notifications'])) {
                $_SESSION['read_notifications'] = [
                    'transactions' => [],
                    'memberships' => [],
                    'announcements' => [],
                    'expiring_membership' => [],
                    'expired_membership' => [],
                    'expiring_rental' => [],
                    'expired_rental' => []
                ];
            }
            
            // Store the notification ID in the session
            // Make sure the key exists in the session array
            if (!isset($_SESSION['read_notifications'][$type])) {
                $_SESSION['read_notifications'][$type] = [];
            }

            // Then check if the notification is already in the array
            if (!in_array($notificationId, $_SESSION['read_notifications'][$type])) {
                $_SESSION['read_notifications'][$type][] = $notificationId;
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a specific user
     * 
     * @param int $userId The ID of the user
     * @param array $notificationData Array of notification data to mark as read
     * @return bool True on success, false on failure
     */
    public function markAllAsRead($userId, $notificationData) {
        try {
            // Start transaction for multiple inserts
            $this->pdo->beginTransaction();
            
            // Prepare the statement
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO notification_reads 
                                  (user_id, notification_type, notification_id, read_at) 
                                  VALUES (?, ?, ?, NOW())");
            
            // For each notification, insert a record
            foreach ($notificationData as $notification) {
                // Check if the notification is already marked as read
                if (!$this->isNotificationRead($userId, $notification['type'], $notification['id'])) {
                    $stmt->execute([
                        $userId, 
                        $notification['type'], // e.g., 'expiring_membership', 'expired_rental', etc.
                        $notification['id']
                    ]);
                    
                    // Also update session
                    if (!isset($_SESSION['read_notifications'])) {
                        $_SESSION['read_notifications'] = [
                            'transactions' => [],
                            'memberships' => [],
                            'announcements' => [],
                            'expiring_membership' => [],
                            'expired_membership' => [],
                            'expiring_rental' => [],
                            'expired_rental' => []
                        ];
                    }
                    
                    // Store the notification ID in the session
                    // Make sure the key exists in the session array
                    if (!isset($_SESSION['read_notifications'][$notification['type']])) {
                        $_SESSION['read_notifications'][$notification['type']] = [];
                    }

                    // Then check if the notification is already in the array
                    if (!in_array($notification['id'], $_SESSION['read_notifications'][$notification['type']])) {
                        $_SESSION['read_notifications'][$notification['type']][] = $notification['id'];
                    }
                }
            }
            
            // Commit the transaction
            $this->pdo->commit();
            
            return true;
        } catch (Exception $e) {
            // Roll back the transaction in case of error
            $this->pdo->rollBack();
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
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
                // For 7-day expiring memberships, this would typically be 7 days before end_date
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date'] . ' -7 days'));
                
                $notifications[] = [
                    'id' => $row['membership_id'],
                    'type' => 'expiring_membership',
                    'title' => 'Membership Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['plan_name'] . ' membership expires in ' . $daysRemaining . ' days',
                    'timestamp' => $timestamp,
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
                // For 7-day expiring rentals, this would typically be 7 days before end_date
                $timestamp = date('F d, Y, h:i a', strtotime($row['end_date'] . ' -7 days'));
                
                $notifications[] = [
                    'id' => $row['rental_subscription_id'],
                    'type' => 'expiring_rental',
                    'title' => 'Rental Subscription Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['service_name'] . ' rental expires in ' . $daysRemaining . ' days',
                    'timestamp' => $timestamp,
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
}