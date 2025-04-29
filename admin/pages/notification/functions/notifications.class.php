<?php
require_once(__DIR__ . '/../../../../config.php');

class Notifications {
    /**
     * Mark all related records as paid and confirm the transaction.
     * Updates is_paid in memberships, rental_subscriptions, walk_in_records, and sets transaction status.
     * @param int $transactionId
     * @return true|string True on success, error message on failure
     */
    public function markTransactionPaid($transactionId) {
        try {
            $this->db->beginTransaction();
            // 1. Update memberships (set is_paid and payment_date)
            $stmt1 = $this->db->prepare("UPDATE memberships SET is_paid = 1, payment_date = NOW() WHERE transaction_id = ?");
            $stmt1->execute([$transactionId]);
            // 2. Update registration_records (set is_paid and payment_date)
            $stmt2 = $this->db->prepare("UPDATE registration_records SET is_paid = 1, payment_date = NOW() WHERE transaction_id = ?");
            $stmt2->execute([$transactionId]);
            // 3. Update rental_subscriptions (set is_paid and payment_date)
            $stmt3 = $this->db->prepare("UPDATE rental_subscriptions SET is_paid = 1, payment_date = NOW() WHERE transaction_id = ?");
            $stmt3->execute([$transactionId]);
            // 4. Update walk_in_records (set is_paid and payment_date)
            $stmt4 = $this->db->prepare("UPDATE walk_in_records SET is_paid = 1 WHERE transaction_id = ?");
            $stmt4->execute([$transactionId]);
            // 5. Update transaction status and payment_date
            $stmt5 = $this->db->prepare("UPDATE transactions SET status = 'confirmed' WHERE id = ?");
            $stmt5->execute([$transactionId]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return $e->getMessage();
        } catch (Exception $e) {
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

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
                    TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) as age
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN personal_details pd ON u.id = pd.user_id
                LEFT JOIN profile_photos pp ON u.id = pp.user_id
                WHERE t.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$transactionId]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$details) {
                return null;
            }

            // 2. Registration Records (array)
            $registrationQuery = "SELECT id, amount, is_paid FROM registration_records WHERE transaction_id = ? AND is_paid = 0";
            $stmtReg = $this->db->prepare($registrationQuery);
            $stmtReg->execute([$transactionId]);
            $details['registration_records'] = $stmtReg->fetchAll(PDO::FETCH_ASSOC);

            // 2. Memberships (array)
            $membershipsQuery = "SELECT m.id, mp.plan_name, m.amount, m.start_date, m.end_date
                                 FROM memberships m
                                 LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
                                 WHERE m.transaction_id = ? AND m.is_paid = 0";
            $stmtMemberships = $this->db->prepare($membershipsQuery);
            $stmtMemberships->execute([$transactionId]);
            $details['memberships'] = $stmtMemberships->fetchAll(PDO::FETCH_ASSOC);

            // 3. Rentals (array)
            $rentalsQuery = "SELECT rs.id, r.service_name AS name, rs.start_date AS start, rs.end_date AS end, rs.amount AS amount
                             FROM rental_subscriptions rs
                             LEFT JOIN rental_services r ON rs.rental_service_id = r.id
                             WHERE rs.transaction_id = ? AND rs.is_paid = 0";
            $stmtRentals = $this->db->prepare($rentalsQuery);
            $stmtRentals->execute([$transactionId]);
            $details['rentals'] = $stmtRentals->fetchAll(PDO::FETCH_ASSOC);

            // 4. Walk-ins (array)
            $walkinsQuery = "SELECT id, date, time_in, amount, status FROM walk_in_records WHERE transaction_id = ? AND is_paid = 0";
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

    