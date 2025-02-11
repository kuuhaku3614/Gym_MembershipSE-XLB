<?php
class AttendanceSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getTodayCheckins() {
        $query = "SELECT 
            u.id AS user_id,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
            u.username,
            pp.photo_path,
            a.time_in,
            a.time_out,
            a.status,
            a.date
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        LEFT JOIN profile_photos pp ON u.id = pp.user_id
        WHERE a.date = CURRENT_DATE()
        AND a.status = 'checked_in'
        ORDER BY a.time_in DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAttendanceHistory() {
        $query = "SELECT 
            a.date,
            a.time_in,
            a.time_out,
            a.status,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
            u.username
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        ORDER BY a.date DESC, a.time_in DESC
        LIMIT 1000"; // Limiting to last 1000 records for performance

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}