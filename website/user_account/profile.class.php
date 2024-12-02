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
                id,
                attendance_id,
                DATE_FORMAT(CONVERT_TZ(time_in, '+00:00', '+08:00'), '%h:%i %p') as time_in,
                DATE_FORMAT(CONVERT_TZ(time_out, '+00:00', '+08:00'), '%h:%i %p') as time_out,
                created_at
                FROM attendance_history";
        
        if ($searchDate) {
            $sql .= " WHERE DATE(created_at) = :search_date";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
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
}