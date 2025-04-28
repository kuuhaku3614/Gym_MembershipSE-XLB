<?php

require_once __DIR__ . '/../../../config.php';

class Profile_class{

    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    /**
     * Fetches user details including name, role name, and role ID.
     * @param int $userId The ID of the user.
     * @return array|false User details or false if not found.
     */
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

    /**
     * Checks if a user is an admin.
     * @param int|null $userId The user ID. Defaults to session user ID if null.
     * @return bool True if the user is an admin, false otherwise.
     */
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

        // Check if role is admin or administrator
        return ($role && ($role['role_name'] === 'admin' || $role['role_name'] === 'administrator'));
    }

    /**
     * Calculates time ago string from a datetime.
     * @param string $datetime The datetime string.
     * @return string Time ago string or formatted date.
     */
    private function timeAgo($datetime) {
        $timezone = new DateTimeZone('Asia/Manila'); // Manila timezone
        $now = new DateTime('now', $timezone);
        
        // Since the database is already configured to use Asia/Manila time zone (+08:00)
        // as per config.php, we don't need to convert from UTC
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
        if ($diff->s > 0) {
            return $diff->s . ' second' . ($diff->s > 1 ? 's' : '') . ' ago';
        }
        return 'just now';
    }

    /**
     * Fetches attendance log for the logged-in user.
     * @param string|null $searchDate Optional date to filter logs.
     * @return array Attendance log entries.
     */
    public function fetchAttendanceLog($searchDate = null) {
        $conn = $this->db->connect();
        $sql = "SELECT
                ah.id,
                ah.attendance_id,
                DATE_FORMAT(ah.time_in, '%h:%i %p') as time_in,
                DATE_FORMAT(ah.time_out, '%h:%i %p') as time_out,
                ah.created_at
                FROM attendance_history ah
                JOIN attendance a ON ah.attendance_id = a.id
                WHERE a.user_id = :user_id";

        if ($searchDate) {
            $sql .= " AND DATE(ah.created_at) = :search_date"; // Filter by date
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
            // Use the raw created_at timestamp for timeAgo calculation
            $row['created_at'] = $this->timeAgo($row['created_at']);
        }

        return $results;
    }

    /**
     * Fetches active availed services (memberships, programs, rentals, upcoming walk-ins).
     * @return array Categorized active services.
     */
    public function fetchAvailedServices() {
        $conn = $this->db->connect();
        $userId = $_SESSION['user_id'];
        $result = [
            'memberships' => [],
            'programs' => [],
            'rentals' => [],
            'walkins' => [] // For upcoming walk-ins
        ];

        // Fetch active memberships
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
        WHERE t.user_id = :user_id AND m.status IN ('active', 'expiring') AND t.status = 'confirmed'
        AND m.end_date >= CURDATE()"; // Ensure end date is today or in the future

        $stmt = $conn->prepare($membership_query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result['memberships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rental_query = "SELECT 
            rs.id,
            'rental' as type,
            r.service_name as name,
            r.description,
            rs.start_date,
            rs.end_date,
            DATE_FORMAT(rs.end_date, '%M %d, %Y') as end_date,
            rs.amount,
            rs.status,
            rs.is_paid,
            u.username AS user_name
        FROM rental_subscriptions rs
        JOIN transactions t ON rs.transaction_id = t.id
        JOIN rental_services r ON rs.rental_service_id = r.id
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = :user_id 
            AND rs.status IN ('active', 'expiring')
            AND rs.is_paid = 1
            AND t.status = 'confirmed'
            AND rs.end_date >= CURDATE()"; // Ensure end date is today or in the future

        $stmt = $conn->prepare($rental_query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result['rentals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch upcoming walk-ins (walk-ins on or after today)
        $walkin_query = "SELECT
        w.id,
        'walkin' as type,
        DATE_FORMAT(w.date, '%M %d, %Y') as date,
        w.amount as price,
        w.status  /* Add this line to select the status field */
        FROM transactions t
        JOIN walk_in_records w ON t.id = w.transaction_id
        WHERE t.user_id = :user_id AND w.status = 'pending' AND w.is_paid = 0 AND w.date >= CURDATE()
        ORDER BY w.date ASC"; // Order by date ascending for upcoming

        $stmt = $conn->prepare($walkin_query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result['walkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    
   /**
     * Fetches expired services (memberships, programs, rentals, past walk-ins).
     * @return array Categorized expired services.
     */
    public function fetchExpiredServices() {
        try {
            $conn = $this->db->connect();
            $userId = $_SESSION['user_id'];
            $expired_services = array(
                'memberships' => [],
                'programs' => [],
                'rentals' => [],
                'walkins' => [] // For past walk-ins
            );

            // Fetch expired memberships - only checking 'expired' status
            $membership_query = "SELECT m.id, m.membership_plan_id, m.transaction_id,
                    m.status, m.start_date, m.end_date, 
                    mp.plan_name, mp.description,
                    CONCAT(mp.duration, ' ', dt.type_name) as duration_name,
                    DATE_FORMAT(m.start_date, '%M %d, %Y') as formatted_start_date,
                    DATE_FORMAT(m.end_date, '%M %d, %Y') as formatted_end_date,
                    m.amount, t.created_at as transaction_date
                FROM memberships m
                JOIN membership_plans mp ON m.membership_plan_id = mp.id
                JOIN duration_types dt ON mp.duration_type_id = dt.id
                JOIN transactions t ON m.transaction_id = t.id
                WHERE t.user_id = :user_id 
                    AND m.status = 'expired'
                    AND t.status = 'confirmed'
                ORDER BY m.end_date DESC";

            $stmt = $conn->prepare($membership_query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $expired_services['memberships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch expired rentals - only checking 'expired' status
            $rental_query = "SELECT 
                rs.id AS subscription_id,
                r.id AS rental_service_id,
                r.service_name,
                r.price,
                r.description,
                rs.start_date,
                rs.end_date,
                rs.amount,
                rs.status,
                rs.is_paid,
                rs.payment_date,
                t.id AS transaction_id,
                t.status AS transaction_status,
                t.created_at AS transaction_date,
                CONCAT(r.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(rs.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(rs.end_date, '%M %d, %Y') as formatted_end_date
            FROM 
                transactions t
            JOIN 
                rental_subscriptions rs ON t.id = rs.transaction_id
            JOIN 
                rental_services r ON rs.rental_service_id = r.id
            JOIN 
                duration_types dt ON r.duration_type_id = dt.id
            WHERE 
                t.user_id = :user_id
                AND t.status = 'confirmed'
                AND rs.status = 'expired'
            ORDER BY 
                rs.end_date DESC";
                
            $stmt = $conn->prepare($rental_query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $expired_services['rentals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch past walk-ins - using a simpler approach
            $walkin_query = "SELECT
                w.id,
                w.date,
                DATE_FORMAT(w.date, '%M %d, %Y') as formatted_date,
                w.amount as price,
                DATE_FORMAT(t.created_at, '%M %d, %Y') as transaction_date,
                t.created_at as raw_transaction_date,
                t.id as transaction_id
                FROM walk_in_records w
                LEFT JOIN transactions t ON w.transaction_id = t.id
                WHERE t.user_id = :user_id
                AND w.date < CURDATE() -- Consider this criteria but keep it simple
                AND w.status = 'walked-in'
                ORDER BY w.date DESC";

            $stmt = $conn->prepare($walkin_query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $expired_services['walkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $expired_services;
        } catch (PDOException $e) {
            error_log("Error fetching expired services: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Fetches all rental services availed by the logged-in user.
     * @return array All rental services with complete details.
     */
    public function fetchAllRentals() {
        try {
            $conn = $this->db->connect();
            $userId = $_SESSION['user_id'];
            
            $query = "SELECT 
                rs.id AS subscription_id,
                r.id AS rental_service_id,
                r.service_name,
                r.price,
                r.description,
                rs.start_date,
                rs.end_date,
                rs.amount,
                rs.status,
                rs.is_paid,
                rs.payment_date,
                t.id AS transaction_id,
                t.status AS transaction_status,
                t.created_at AS transaction_date,
                CONCAT(r.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(rs.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(rs.end_date, '%M %d, %Y') as formatted_end_date
            FROM 
                transactions t
            JOIN 
                rental_subscriptions rs ON t.id = rs.transaction_id
            JOIN 
                rental_services r ON rs.rental_service_id = r.id
            JOIN 
                duration_types dt ON r.duration_type_id = dt.id
            WHERE 
                t.user_id = :user_id
                AND t.status = 'confirmed'
            ORDER BY 
                rs.created_at DESC";
                
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all rentals: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Fetches expired rental services availed by the logged-in user.
     * @return array Expired rental services with complete details.
     */
    public function fetchExpiredRentals() {
        try {
            $conn = $this->db->connect();
            $userId = $_SESSION['user_id'];
            
            $query = "SELECT 
                rs.id AS subscription_id,
                r.id AS rental_service_id,
                r.service_name,
                r.price,
                r.description,
                rs.start_date,
                rs.end_date,
                rs.amount,
                rs.status,
                rs.is_paid,
                rs.payment_date,
                t.id AS transaction_id,
                t.status AS transaction_status,
                t.created_at AS transaction_date,
                CONCAT(r.duration, ' ', dt.type_name) as duration_name,
                DATE_FORMAT(rs.start_date, '%M %d, %Y') as formatted_start_date,
                DATE_FORMAT(rs.end_date, '%M %d, %Y') as formatted_end_date
            FROM 
                transactions t
            JOIN 
                rental_subscriptions rs ON t.id = rs.transaction_id
            JOIN 
                rental_services r ON rs.rental_service_id = r.id
            JOIN 
                duration_types dt ON r.duration_type_id = dt.id
            WHERE 
                t.user_id = :user_id
                AND t.status = 'confirmed'
                AND rs.status = 'expired'
            ORDER BY 
                rs.end_date DESC";
                
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching expired rentals: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Fetches detailed information for a specific service.
     * @param int $serviceId The ID of the service subscription/record.
     * @param string $serviceType The type of service ('membership', 'program', 'rental', 'walkin').
     * @return array|null Service details or null if not found or invalid type.
     */
    public function fetchServiceDetails($serviceId, $serviceType) {
        try {
            $conn = $this->db->connect();
            $query = "";

            switch($serviceType) {
                case 'membership':
                    $query = "SELECT m.*, mp.plan_name, mp.plan_type, mp.description,
                            CONCAT(mp.duration, ' ', dt.type_name) as duration_type,
                            t.created_at as transaction_date, t.amount
                            FROM memberships m
                            JOIN membership_plans mp ON m.membership_plan_id = mp.id
                            JOIN duration_types dt ON mp.duration_type_id = dt.id
                            JOIN transactions t ON m.transaction_id = t.id
                            WHERE m.id = :service_id";
                    break;

                case 'rental':
                    $query = "SELECT 
                            rs.id AS subscription_id, 
                            r.id AS rental_service_id, 
                            r.service_name, 
                            r.description,
                            r.price AS service_price,
                            rs.start_date,
                            rs.end_date,
                            rs.amount,
                            rs.status,
                            rs.is_paid,
                            rs.payment_date,
                            CONCAT(r.duration, ' ', dt.type_name) as duration_type,
                            t.id AS transaction_id,
                            t.status AS transaction_status,
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
                            t.amount, -- Fetch amount from transactions table for walk-in
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

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':service_id', $serviceId);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Format additional fields for walk-in and add common formatted fields
            if ($result) {
                 // Format common date and amount fields if they exist in the result
                if (isset($result['start_date'])) {
                     $result['formatted_start_date'] = date('F d, Y', strtotime($result['start_date']));
                }
                if (isset($result['end_date'])) {
                    $result['formatted_end_date'] = date('F d, Y', strtotime($result['end_date']));
                }
                 if (isset($result['amount'])) {
                    $result['formatted_amount'] = number_format($result['amount'], 2);
                }
                if (isset($result['transaction_date'])) {
                     $result['formatted_transaction_date'] = date('F d, Y', strtotime($result['transaction_date']));
                }


                if ($serviceType === 'walkin') {
                    $result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];
                    $result['payment_status'] = $result['is_paid'] ? 'Paid' : 'Unpaid';
                }
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error fetching service details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetches program schedules for a specific user, including program details, session type, and coach.
     * @param int $userId The ID of the user.
     * @return array Program schedule entries.
     */
    public function fetchProgramSchedules($userId) {
        $conn = $this->db->connect();

        $query = "SELECT 
            pss.id AS schedule_id,
            pss.date,
            pss.day,
            pss.start_time,
            pss.end_time,
            pss.amount,
            pss.status,
            pss.is_paid,
            p.program_name,
            CASE 
                WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'Personal'
                WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'Group'
            END AS session_type,
            CONCAT_WS(' ', coach_pd.first_name, NULLIF(coach_pd.middle_name, ''), coach_pd.last_name) AS coach_name
        FROM program_subscription_schedule pss
        JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
        JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
        JOIN programs p ON cpt.program_id = p.id
        JOIN users coach_u ON cpt.coach_id = coach_u.id
        LEFT JOIN personal_details coach_pd ON coach_u.id = coach_pd.user_id
        WHERE ps.user_id = :user_id
        ORDER BY pss.date, pss.start_time";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and times for display
        foreach ($results as &$row) {
            if (isset($row['date'])) {
                $row['formatted_date'] = date('F d, Y', strtotime($row['date']));
            }
            if (isset($row['start_time'])) {
                $row['formatted_start_time'] = date('h:i A', strtotime($row['start_time']));
            }
            if (isset($row['end_time'])) {
                $row['formatted_end_time'] = date('h:i A', strtotime($row['end_time']));
            }
            // Add payment status label
            $row['payment_status'] = $row['is_paid'] ? 'Paid' : 'Unpaid';
        }
        
        return $results;
    }

    
}

?>