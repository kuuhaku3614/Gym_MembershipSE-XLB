<?php
require_once(__DIR__ . '/../../../../config.php');

class Notifications {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    // Fetch summary info for all pending transactions (for notification list)
    public function getAllPendingRequests() {
        $query = "SELECT 
                    t.id as transaction_id,
                    t.created_at,
                    u.id as user_id,
                    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
                    pd.phone_number,
                    pd.sex,
                    pd.birthdate,
                    pp.photo_path as profile_picture,
                    TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) as age
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN personal_details pd ON u.id = pd.user_id
                LEFT JOIN profile_photos pp ON u.id = pp.user_id
                WHERE t.status = 'pending'
                ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getTransactionDetails($transactionId) {
        try {
            // 1. Main transaction/member info (single row)
            $query = "SELECT 
                    t.id as transaction_id,
                    t.status as transaction_status,
                    t.user_id,
                    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
                    pd.phone_number,
                    pd.sex,
                    pd.birthdate,
                    pp.photo_path as profile_picture,
                    TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) as age,
                    reg.membership_fee as registration_fee
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN personal_details pd ON u.id = pd.user_id
                LEFT JOIN profile_photos pp ON u.id = pp.user_id
                LEFT JOIN registration reg ON reg.id = (SELECT id FROM registration ORDER BY id DESC LIMIT 1)
                WHERE t.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$transactionId]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$details) {
                return null;
            }

            // 2. Memberships (array)
            $membershipsQuery = "SELECT mp.plan_name, m.amount, m.start_date, m.end_date
                                 FROM memberships m
                                 LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
                                 WHERE m.transaction_id = ?";
            $stmtMemberships = $this->db->prepare($membershipsQuery);
            $stmtMemberships->execute([$transactionId]);
            $details['memberships'] = $stmtMemberships->fetchAll(PDO::FETCH_ASSOC);

            // 3. Rentals (array)
            $rentalsQuery = "SELECT rs.service_name AS name, rsub.start_date AS start, rsub.end_date AS end, rsub.amount AS amount
                             FROM rental_subscriptions rsub
                             LEFT JOIN rental_services rs ON rsub.rental_service_id = rs.id
                             WHERE rsub.transaction_id = ?";
            $stmtRentals = $this->db->prepare($rentalsQuery);
            $stmtRentals->execute([$transactionId]);
            $details['rentals'] = $stmtRentals->fetchAll(PDO::FETCH_ASSOC);

            // 4. Walk-ins (array)
            $walkinsQuery = "SELECT date, time_in, amount, status FROM walk_in_records WHERE transaction_id = ?";
            $stmtWalkins = $this->db->prepare($walkinsQuery);
            $stmtWalkins->execute([$transactionId]);
            $details['walkins'] = $stmtWalkins->fetchAll(PDO::FETCH_ASSOC);

            return $details;
        } catch (PDOException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

}

    