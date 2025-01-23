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
        
        $query = "SELECT pd.*, CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS name,
                  r.role_name
                  FROM personal_details pd 
                  JOIN users u ON pd.user_id = u.id
                  JOIN roles r ON u.role_id = r.id
                  WHERE pd.user_id = :user_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

        // Fetch programs
        $program_query = "SELECT 
            ps.id,
            'program' as type,
            p.program_name as name,
            CONCAT(p.duration, ' ', dt.type_name) as duration,
            DATE_FORMAT(ps.end_date, '%M %d, %Y') as end_date,
            CONCAT(pd.first_name, ' ', pd.last_name) as coach
        FROM transactions t
        JOIN program_subscriptions ps ON t.id = ps.transaction_id
        JOIN programs p ON ps.program_id = p.id
        JOIN duration_types dt ON p.duration_type_id = dt.id
        JOIN users u ON ps.coach_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE t.user_id = :user_id AND ps.status = 'active' AND t.status = 'confirmed'";

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
            'programs' => [],
            'rentals' => [],
            'walkins' => []
        ];

        // Execute membership query
        $stmt = $conn->prepare($membership_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result['memberships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Execute program query
        $stmt = $conn->prepare($program_query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result['programs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            // Fetch expired programs
            $program_query = "SELECT ps.*, p.program_name, p.description, 
                CONCAT(p.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(ps.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(ps.end_date, '%M %d, %Y') as formatted_end_date,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as transaction_date,
                t.created_at as raw_transaction_date,
                t.id as transaction_id,
                CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
                FROM program_subscriptions ps
                LEFT JOIN programs p ON ps.program_id = p.id
                LEFT JOIN duration_types dt ON p.duration_type_id = dt.id
                LEFT JOIN transactions t ON ps.transaction_id = t.id
                LEFT JOIN users u ON ps.coach_id = u.id
                LEFT JOIN personal_details pd ON u.id = pd.user_id
                WHERE t.user_id = :user_id AND ps.end_date < CURDATE()
                AND ps.status = 'expired' AND t.status = 'confirmed'
                ORDER BY t.id DESC";
            
            $stmt = $this->db->connect()->prepare($program_query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $expired_services['programs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    
                case 'program':
                    $query = "SELECT ps.*, p.program_name, p.description,
                            pt.type_name as program_type,
                            CONCAT(p.duration, ' ', dt.type_name) as duration_type,
                            t.created_at as transaction_date,
                            pd.first_name as coach_fname, pd.last_name as coach_lname
                            FROM program_subscriptions ps
                            JOIN programs p ON ps.program_id = p.id
                            JOIN program_types pt ON p.program_type_id = pt.id
                            JOIN duration_types dt ON p.duration_type_id = dt.id
                            JOIN transactions t ON ps.transaction_id = t.id
                            LEFT JOIN users u ON ps.coach_id = u.id
                            LEFT JOIN personal_details pd ON u.id = pd.user_id
                            WHERE ps.id = :service_id";
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