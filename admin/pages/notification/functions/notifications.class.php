<?php
require_once(__DIR__ . '/../../../../config.php');

class Notifications {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getMembershipRequests() {
        $query = "SELECT 
                    t.id as transaction_id,
                    t.status as transaction_status,
                    u.id as user_id,
                    u.role_id,
                    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
                    mp.plan_name,
                    m.amount as membership_amount,
                    m.start_date as membership_start,
                    m.end_date as membership_end,
                    t.created_at,
                    pd.phone_number,
                    pd.sex,
                    pd.birthdate,
                    pp.photo_path as profile_picture,
                    TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) as age,
                    rs.service_name,
                    rsub.start_date as rental_start,
                    rsub.end_date as rental_end,
                    rsub.amount as rental_amount,
                    reg.membership_fee as registration_fee,
                    CASE 
                        WHEN m.id IS NOT NULL THEN 'membership'
                        WHEN w.id IS NOT NULL THEN 'walk-in'
                        ELSE NULL
                    END as transaction_type,
                    CONCAT(w.first_name, ' ', COALESCE(w.middle_name, ''), ' ', w.last_name) as walk_in_name,
                    w.phone_number as walk_in_phone,
                    w.date as walk_in_date,
                    w.time_in as walk_in_time,
                    w.amount as walk_in_amount,
                    w.status as walk_in_status
                    FROM transactions t
                    LEFT JOIN users u ON t.user_id = u.id
                    LEFT JOIN personal_details pd ON u.id = pd.user_id
                    LEFT JOIN profile_photos pp ON u.id = pp.user_id
                    LEFT JOIN memberships m ON t.id = m.transaction_id
                    LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
                    LEFT JOIN rental_subscriptions rsub ON t.id = rsub.transaction_id
                    LEFT JOIN rental_services rs ON rsub.rental_service_id = rs.id
                    LEFT JOIN registration reg ON reg.id = (SELECT id FROM registration ORDER BY id DESC LIMIT 1)
                    LEFT JOIN walk_in_records w ON t.id = w.transaction_id
                    WHERE t.status = 'pending'
                    ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function formatNotification($request) {
        $requestDate = new DateTime($request['created_at']);
        $membershipStart = !empty($request['membership_start']) ? new DateTime($request['membership_start']) : null;
        $membershipEnd = !empty($request['membership_end']) ? new DateTime($request['membership_end']) : null;
        $rentalStart = !empty($request['rental_start']) ? new DateTime($request['rental_start']) : null;
        $rentalEnd = !empty($request['rental_end']) ? new DateTime($request['rental_end']) : null;
        
        $details = [
            'transaction_id' => $request['transaction_id'],
            'transaction_type' => $request['transaction_type'],
            'transaction_status' => $request['transaction_status'],
            'request_date' => $requestDate->format('F d, Y, h:i a')
        ];

        if ($request['transaction_type'] === 'membership') {
            $birthDate = new DateTime($request['birthdate']);
            $details += [
                'user_id' => $request['user_id'],
                'member_name' => $request['full_name'],
                'phone_number' => $request['phone_number'],
                'sex' => $request['sex'],
                'birthdate' => $birthDate->format('F d, Y'),
                'age' => $request['age'],
                'profile_picture' => $request['profile_picture'],
                'is_member' => ($request['role_id'] == 3) // Check if user is already a member
            ];

            // Only include registration fee for new members
            if (!empty($request['registration_fee']) && $request['role_id'] != 3) {
                $details['registration_fee'] = number_format($request['registration_fee'], 2);
            }
        } else if ($request['transaction_type'] === 'walk-in') {
            $walkInDate = new DateTime($request['walk_in_date']);
            $details += [
                'walk_in_name' => $request['walk_in_name'],
                'walk_in_phone' => $request['walk_in_phone'],
                'walk_in_date' => $walkInDate->format('F d, Y'),
                'walk_in_time' => $request['walk_in_time'],
                'walk_in_amount' => number_format($request['walk_in_amount'], 2)
            ];
        }

        // Add membership details if present
        if (!empty($request['plan_name'])) {
            $details['membership'] = [
                'plan_name' => $request['plan_name'],
                'start_date' => $membershipStart ? $membershipStart->format('F d, Y') : '',
                'end_date' => $membershipEnd ? $membershipEnd->format('F d, Y') : '',
                'amount' => number_format($request['membership_amount'], 2)
            ];
        }

        // Add rental details if present
        if (!empty($request['service_name'])) {
            $details['rental'] = [
                'service_name' => $request['service_name'],
                'amount' => number_format($request['rental_amount'], 2),
                'start_date' => $rentalStart ? $rentalStart->format('F d, Y') : '',
                'end_date' => $rentalEnd ? $rentalEnd->format('F d, Y') : ''
            ];
        }

        $title = $request['transaction_type'] === 'membership' ? 'Membership Request' : 'Walk-in Request';
        $message = $request['transaction_type'] === 'membership' 
            ? sprintf("%s wants to avail services starting %s", $request['full_name'],
                $membershipStart ? $membershipStart->format('F d, Y') : 
                ($rentalStart ? $rentalStart->format('F d, Y') : ''))
            : sprintf("Walk-in request from %s for %s", $request['walk_in_name'], 
                (new DateTime($request['walk_in_date']))->format('F d, Y'));
        
        return [
            'id' => $request['transaction_id'],
            'title' => $title,
            'message' => $message,
            'timestamp' => $requestDate->format('F d, Y, h:i a'),
            'details' => $details
        ];
    }

    public function getAllNotifications() {
        $requests = $this->getMembershipRequests();
        $notifications = [];
        
        foreach ($requests as $request) {
            $notifications[] = $this->formatNotification($request);
        }
        
        return $notifications;
    }

    public function confirmTransaction($transactionId, $userId = null) {
        try {
            if (!$this->db) {
                return false;
            }

            $this->db->beginTransaction();

            // Verify transaction exists and is pending
            $checkQuery = "SELECT t.status, CASE WHEN w.id IS NOT NULL THEN 'walk-in' ELSE 'membership' END as type 
                         FROM transactions t 
                         LEFT JOIN walk_in_records w ON t.id = w.transaction_id 
                         WHERE t.id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$transactionId]);
            $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                $this->db->rollBack();
                return false;
            }

            if ($transaction['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            // Update transaction status
            $query = "UPDATE transactions SET status = 'confirmed' WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$transactionId]);

            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            // Only update user role for membership transactions
            if ($transaction['type'] === 'membership' && $userId) {
                // Verify user exists
                $checkUserQuery = "SELECT id FROM users WHERE id = ?";
                $checkUserStmt = $this->db->prepare($checkUserQuery);
                $checkUserStmt->execute([$userId]);
                
                if (!$checkUserStmt->fetch()) {
                    $this->db->rollBack();
                    return false;
                }

                // Update user role to member (role_id = 3)
                $query = "UPDATE users SET role_id = 3 WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([$userId]);

                if (!$result) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function cancelTransaction($transactionId) {
        try {
            if (!$this->db) {
                return false;
            }

            // Verify transaction exists and is pending
            $checkQuery = "SELECT status FROM transactions WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$transactionId]);
            $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                return false;
            }

            if ($transaction['status'] !== 'pending') {
                return false;
            }

            // Update transaction status
            $query = "UPDATE transactions SET status = 'cancelled' WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$transactionId]);

            if (!$result) {
                return false;
            }

            return true;
        } catch (PDOException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}