<?php 
require_once '../../../config.php';

class Members {
    protected $db;
    
    function __construct(){
        $this->db = new Database();
    }

    public function getAllMembers() {
        try {
            $connection = $this->db->connect();
            if (!$connection) return array();

            $query = "SELECT 
                        u.id as user_id,
                        pd.*,
                        m.id as membership_id,
                        m.is_paid,
                        m.status as membership_status,
                        t.status as transaction_status,
                        pp.photo_path
                    FROM users u
                    LEFT JOIN personal_details pd ON u.id = pd.user_id
                    LEFT JOIN transactions t ON t.user_id = u.id
                    LEFT JOIN memberships m ON m.transaction_id = t.id
                    LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
                    WHERE u.role_id = 3 AND u.is_active = 1
                    ORDER BY pd.last_name, pd.first_name";
            
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $finalResults = array();
            foreach ($results as $row) {
                $status = 'inactive';
                if ($row['membership_id'] !== null) {
                    if ($row['membership_status'] === 'active' && $row['transaction_status'] === 'confirmed' && $row['is_paid']) {
                        $status = 'active';
                    } else {
                        $status = 'pending';
                    }
                }

                $paymentStatus = ' ';
                if ($row['membership_id'] !== null) {
                    $paymentStatus = $row['is_paid'] ? 'paid' : 'unpaid';
                }

                $finalResults[] = array(
                    'user_id' => $row['user_id'],
                    'full_name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']),
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'photo_path' => $row['photo_path']
                );
            }
            
            return $finalResults;
        } catch (PDOException $e) {
            return array();
        }
    }

    public function getMemberDetails($userId) {
        try {
            error_log("Attempting to get member details for user ID: " . $userId);
            
            $connection = $this->db->connect();
            if (!$connection) {
                error_log("Database connection failed");
                throw new PDOException("Database connection failed");
            }

            error_log("Database connection successful");
            
            $query = "SELECT 
                u.id AS user_id, 
                u.username, 
                pd.first_name, 
                pd.middle_name, 
                pd.last_name, 
                pd.sex, 
                pd.birthdate, 
                pd.phone_number, 
                COALESCE(pp.photo_path, NULL) AS photo_path, 
                
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active' 
                        AND m.end_date >= CURDATE()
                        AND m.is_paid = 1
                    ) THEN 'Active'
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active' 
                        AND m.end_date >= CURDATE()
                        AND m.is_paid = 0
                    ) THEN 'Pending'
                    ELSE 'Inactive'
                END AS membership_status,
                
                CONCAT(mp.plan_name, ' - ', mp.plan_type) AS membership_plan_name,
                mp.plan_name, 
                mp.plan_type,
                m.start_date,
                m.end_date,
                rr.amount as registration_fee,
                CASE 
                    WHEN rr.amount > 0 THEN 'Yes'
                    ELSE 'No'
                END as has_registration_fee,
                DATE_FORMAT(m.start_date, '%M %d, %Y') AS membership_start,
                DATE_FORMAT(m.end_date, '%M %d, %Y') AS membership_end,
                
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM memberships m 
                        JOIN transactions t ON m.transaction_id = t.id
                        WHERE t.user_id = u.id 
                        AND m.status = 'active'
                        AND m.end_date >= CURDATE()
                    ) THEN 
                        CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM memberships m 
                                JOIN transactions t ON m.transaction_id = t.id
                                WHERE t.user_id = u.id 
                                AND m.status = 'active'
                                AND m.end_date >= CURDATE()
                                AND m.is_paid = 1
                            ) THEN 'Paid'
                            ELSE 'Unpaid'
                        END
                    ELSE ' '
                END AS payment_status,
                
                (
                    COALESCE(m.amount, 0) + 
                    COALESCE(
                        (SELECT SUM(amount) 
                         FROM program_subscriptions ps 
                         WHERE ps.transaction_id = t.id), 
                        0
                    ) + 
                    COALESCE(
                        (SELECT SUM(amount) 
                         FROM rental_subscriptions rs 
                         WHERE rs.transaction_id = t.id), 
                        0
                    ) + 
                    COALESCE(rr.amount, 0)
                ) AS total_price,

                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN ps.end_date >= CURDATE() AND ps.status = 'active' THEN
                            CONCAT(
                                p.program_name, ' | ',
                                'Coach: ', COALESCE(CONCAT(coach_details.last_name, ', ', coach_details.first_name, ' ', COALESCE(coach_details.middle_name, '')), 'Not Assigned'), ' | ',
                                'Duration: ', DATE_FORMAT(ps.start_date, '%M %d, %Y'), ' to ', DATE_FORMAT(ps.end_date, '%M %d, %Y'), ' | ',
                                'Price: ₱', FORMAT(COALESCE(ps.amount, 0), 2), ' | ',
                                'Status: ', CASE WHEN ps.is_paid = 1 THEN 'Paid' ELSE 'Pending' END
                            )
                        END
                    SEPARATOR '\n'
                ) as program_details,

                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN rs.end_date >= CURDATE() AND rs.status = 'active' THEN
                            CONCAT(
                                srv.service_name, ' | ',
                                'Duration: ', DATE_FORMAT(rs.start_date, '%M %d, %Y'), ' to ', DATE_FORMAT(rs.end_date, '%M %d, %Y'), ' | ',
                                'Price: ₱', rs.amount, ' | ',
                                'Status: ', CASE 
                                    WHEN rs.is_paid = 1 THEN 'Active'
                                    ELSE 'Pending'
                                END
                            )
                        END
                    SEPARATOR '\n'
                ) as rental_details,
                
                m.amount as membership_amount

            FROM 
                users u 
            JOIN 
                roles roles ON u.role_id = roles.id AND roles.id = 3 
            LEFT JOIN 
                transactions t ON u.id = t.user_id
            LEFT JOIN 
                memberships m ON t.id = m.transaction_id
            LEFT JOIN 
                membership_plans mp ON m.membership_plan_id = mp.id
            LEFT JOIN 
                personal_details pd ON u.id = pd.user_id 
            LEFT JOIN 
                profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1 
            LEFT JOIN 
                registration_records rr ON t.id = rr.transaction_id
            LEFT JOIN 
                program_subscriptions ps ON t.id = ps.transaction_id AND ps.status = 'active'
            LEFT JOIN 
                programs p ON ps.program_id = p.id
            LEFT JOIN 
                users coach ON ps.coach_id = coach.id
            LEFT JOIN 
                personal_details coach_details ON coach.id = coach_details.user_id
            LEFT JOIN 
                rental_subscriptions rs ON t.id = rs.transaction_id
            LEFT JOIN 
                rental_services srv ON rs.rental_service_id = srv.id
            WHERE 
                u.is_active = 1 
                AND u.id = :userId
            GROUP BY 
                u.id, 
                u.username, 
                pd.first_name, 
                pd.middle_name, 
                pd.last_name, 
                pd.sex, 
                pd.birthdate, 
                pd.phone_number, 
                pp.photo_path,
                m.id,
                m.start_date,
                m.end_date,
                mp.plan_name
            ORDER BY 
                m.start_date DESC, 
                m.id DESC
            LIMIT 1";

            error_log("Executing query: " . $query);
            error_log("With user ID: " . $userId);
            
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Query result: " . print_r($result, true));
            
            if (!$result) {
                error_log("No member found for user ID: " . $userId);
                throw new PDOException("Member not found");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("PDO Exception in getMemberDetails: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}