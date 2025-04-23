<?php

require_once __DIR__ . '/../../config.php';

class Profile_class{

    public $id = '';
    public $user_id = '';
    public $first_name = '';
    public $middle_name = '';
    public $last_name = '';
    public $sex = '';
    public $birthdate = '';
    public $phone_number = '';



    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function getUserDetails($userId) {
        $conn = $this->db->connect();
        
        $query = "SELECT pd.*, 
                  CONCAT(pd.first_name, ' ', IFNULL(pd.middle_name, ''), ' ', pd.last_name) AS name,
                  r.role_name, u.role_id
                  FROM personal_details pd 
                  JOIN users u ON pd.user_id = u.id
                  JOIN roles r ON u.role_id = r.id
                  WHERE pd.user_id = :user_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isAdmin($userId = null) {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if (!$userId) return false;
        
        $conn = $this->db->connect();
        $query = "SELECT r.role_name 
                  FROM users u
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.id = :user_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if role is admin (you might adjust the role name based on your system)
        return ($role && ($role['role_name'] === 'admin' || $role['role_name'] === 'administrator'));
    }

    private function timeAgo($datetime) {
        $timezone = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $timezone);
        $ago = new DateTime($datetime, $timezone);
        $diff = $now->diff($ago);

        // If more than 7 days, return the actual date
        if ($diff->days > 7) {
            return $ago->format('M j, Y'); // Will show as "Jan 15, 2024"
        }

        // For times less than a week, show relative time
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return 'just now';
    }

    public function fetchAttendanceLog($searchDate = null) {
        $conn = $this->db->connect();
        $sql = "SELECT 
                ah.id,
                ah.attendance_id,
                DATE_FORMAT(CONVERT_TZ(ah.time_in, '+00:00', '+08:00'), '%h:%i %p') as time_in,
                DATE_FORMAT(CONVERT_TZ(ah.time_out, '+00:00', '+08:00'), '%h:%i %p') as time_out,
                ah.created_at
                FROM attendance_history ah
                JOIN attendance a ON ah.attendance_id = a.id
                WHERE a.user_id = :user_id";
        
        if ($searchDate) {
            $sql .= " AND DATE(ah.created_at) = :search_date";
        }
        
        $sql .= " ORDER BY ah.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        if ($searchDate) {
            $stmt->bindParam(':search_date', $searchDate);
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert created_at to time ago format
        foreach ($results as &$row) {
            $row['created_at'] = $this->timeAgo($row['created_at']);
        }
        
        return $results;
    }

    public function fetchAvailedServices() {
        $conn = $this->db->connect();
        
        // Fetch memberships
        $membership_query = "SELECT 
            m.id,
            'membership' as type,
            mp.plan_name as name,
            CONCAT(mp.duration, ' ', dt.type_name) as duration,
            DATE_FORMAT(m.end_date, '%M %d, %Y') as end_date,
            NULL as coach
        FROM transactions t
        JOIN memberships m ON t.id = m.transaction_id
        JOIN membership_plans mp ON m.membership_plan_id = mp.id
        JOIN duration_types dt ON mp.duration_type_id = dt.id
        WHERE t.user_id = :user_id AND m.status = 'active' AND t.status = 'confirmed'";

        // Fetch rentals
        $rental_query = "SELECT 
            rs.id,
            'rental' as type,
            r.service_name as name,
            CONCAT(r.duration, ' ', dt.type_name) as duration,
            DATE_FORMAT(rs.end_date, '%M %d, %Y') as end_date,
            NULL as coach
        FROM transactions t
        JOIN rental_subscriptions rs ON t.id = rs.transaction_id
        JOIN rental_services r ON rs.rental_service_id = r.id
        JOIN duration_types dt ON r.duration_type_id = dt.id
        WHERE t.user_id = :user_id AND rs.status = 'active' AND t.status = 'confirmed'";

        // Fetch walk-ins
        $walkin_query = "SELECT 
            w.id,
            'walkin' as type,
            DATE_FORMAT(w.date, '%M %d, %Y') as date,
            w.time_in,
            w.amount as price
        FROM transactions t
        JOIN walk_in_records w ON t.id = w.transaction_id
        WHERE t.user_id = :user_id AND t.status = 'confirmed' AND w.date >= CURDATE()
        ORDER BY w.date DESC";
        
        $result = [
            'memberships' => [],
            'rentals' => [],
            'walkins' => []
        ];

        // Execute membership query
        $stmt = $conn->prepare($membership_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result['memberships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Execute rental query
        $stmt = $conn->prepare($rental_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result['rentals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Execute walk-in query
        $stmt = $conn->prepare($walkin_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result['walkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function fetchExpiredServices() {
        try {
            $expired_services = array();

            // Fetch expired memberships
            $membership_query = "SELECT m.*, mp.plan_name, mp.description, 
                CONCAT(mp.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(m.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(m.end_date, '%M %d, %Y') as formatted_end_date,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as transaction_date,
                t.created_at as raw_transaction_date,
                t.id as transaction_id
                FROM memberships m
                LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
                LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
                LEFT JOIN transactions t ON m.transaction_id = t.id
                WHERE t.user_id = :user_id AND m.end_date < CURDATE()
                AND m.status = 'expired' AND t.status = 'confirmed'
                ORDER BY t.id DESC";
            
            $stmt = $this->db->connect()->prepare($membership_query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $expired_services['memberships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch expired rentals
            $rental_query = "SELECT rs.*, r.service_name as rental_name, r.description,
                CONCAT(r.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(rs.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(rs.end_date, '%M %d, %Y') as formatted_end_date,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as transaction_date,
                t.created_at as raw_transaction_date,
                t.id as transaction_id
                FROM rental_subscriptions rs
                LEFT JOIN rental_services r ON rs.rental_service_id = r.id
                LEFT JOIN duration_types dt ON r.duration_type_id = dt.id
                LEFT JOIN transactions t ON rs.transaction_id = t.id
                WHERE t.user_id = :user_id AND rs.end_date < CURDATE()
                AND rs.status = 'expired' AND t.status = 'confirmed'
                ORDER BY t.id DESC";
            
            $stmt = $this->db->connect()->prepare($rental_query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $expired_services['rentals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch expired walk-ins (walk-ins older than today)
            $walkin_query = "SELECT 
                w.id,
                DATE_FORMAT(w.date, '%M %d, %Y') as formatted_date,
                w.amount as price,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as transaction_date,
                t.created_at as raw_transaction_date,
                t.id as transaction_id
                FROM walk_in_records w
                LEFT JOIN transactions t ON w.transaction_id = t.id
                WHERE t.user_id = :user_id 
                AND w.date < CURDATE()
                AND t.status = 'confirmed'
                ORDER BY w.date DESC";

            $stmt = $this->db->connect()->prepare($walkin_query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $expired_services['walkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $expired_services;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return array();
        }
    }

    public function fetchServiceDetails($serviceId, $serviceType) {
        try {
            $query = "";
            
            switch($serviceType) {
                case 'membership':
                    $query = "SELECT m.*, mp.plan_name, mp.plan_type, mp.description,
                            CONCAT(mp.duration, ' ', dt.type_name) as duration_type,
                            t.created_at as transaction_date
                            FROM memberships m
                            JOIN membership_plans mp ON m.membership_plan_id = mp.id
                            JOIN duration_types dt ON mp.duration_type_id = dt.id
                            JOIN transactions t ON m.transaction_id = t.id
                            WHERE m.id = :service_id";
                    break;
                    
                case 'rental':
                    $query = "SELECT rs.*, r.service_name, r.description,
                            CONCAT(r.duration, ' ', dt.type_name) as duration_type,
                            t.created_at as transaction_date
                            FROM rental_subscriptions rs
                            JOIN rental_services r ON rs.rental_service_id = r.id
                            JOIN duration_types dt ON r.duration_type_id = dt.id
                            JOIN transactions t ON rs.transaction_id = t.id
                            WHERE rs.id = :service_id";
                    break;

                case 'walkin':
                    $query = "SELECT 
                            w.*,
                            DATE_FORMAT(w.date, '%M %d, %Y') as formatted_date,
                            DATE_FORMAT(w.time_in, '%h:%i %p') as formatted_time,
                            t.created_at as transaction_date,
                            DATE_FORMAT(t.created_at, '%M %d, %Y') as formatted_transaction_date,
                            t.status as transaction_status,
                            pd.first_name, 
                            pd.last_name,
                            pd.phone_number
                            FROM walk_in_records w
                            JOIN transactions t ON w.transaction_id = t.id
                            LEFT JOIN users u ON t.user_id = u.id
                            LEFT JOIN personal_details pd ON u.id = pd.user_id
                            WHERE w.id = :service_id";
                    break;
                    
                default:
                    return null;
            }
            
            $stmt = $this->db->connect()->prepare($query);
            $stmt->bindParam(':service_id', $serviceId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Format additional fields for walk-in
            if ($serviceType === 'walkin' && $result) {
                $result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];
                $result['formatted_amount'] = number_format($result['amount'], 2);
                $result['payment_status'] = $result['is_paid'] ? 'Paid' : 'Unpaid';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error fetching service details: " . $e->getMessage());
            return null;
        }
    }
}

class CoachingSystem {
    private $db;

    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            $this->db = new Database(); // Use the Database class if one isn't injected
        }
    }

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