<?php
require_once(__DIR__ . '/../../../config.php');

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
        // Check if already read
        if ($this->isNotificationRead($userId, $type, $notificationId)) {
            return true;
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO notification_reads 
                                     (user_id, notification_type, notification_id, read_at) 
                                     VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$userId, $type, $notificationId]);
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
                
                $notifications[] = [
                    'id' => $row['membership_id'],
                    'type' => 'expiring_membership',
                    'title' => 'Membership Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['plan_name'] . ' membership expires in ' . $daysRemaining . ' days',
                    'timestamp' => date('Y-m-d H:i:s'),
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
        
        // Rest of the function remains the same
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysSince = (time() - strtotime($row['end_date'])) / (60 * 60 * 24);
                $daysSince = floor($daysSince);
                
                $notifications[] = [
                    'id' => $row['membership_id'],
                    'type' => 'expired_membership',
                    'title' => 'Membership Expired',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['plan_name'] . ' membership expired ' . $daysSince . ' days ago',
                    'timestamp' => date('Y-m-d H:i:s'),
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
                    rs.status = 'expiring' ";
        
        $stmt = $this->pdo->query($query);
        $notifications = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $daysRemaining = (strtotime($row['end_date']) - time()) / (60 * 60 * 24);
                $daysRemaining = ceil($daysRemaining);
                
                $notifications[] = [
                    'id' => $row['rental_subscription_id'],
                    'type' => 'expiring_rental',
                    'title' => 'Rental Subscription Expiring Soon',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['service_name'] . ' rental expires in ' . $daysRemaining . ' days',
                    'timestamp' => date('Y-m-d H:i:s'),
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
                
                $notifications[] = [
                    'id' => $row['rental_subscription_id'],
                    'type' => 'expired_rental',
                    'title' => 'Rental Subscription Expired',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . '\'s ' . $row['service_name'] . ' rental expired ' . $daysSince . ' days ago',
                    'timestamp' => date('Y-m-d H:i:s'),
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
?>