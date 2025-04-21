<?php
require_once '../../../config.php';
require_once 'activity_logger.php'; // Added activity logger inclusion

class Walk_in_class {
    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function fetchWalkin(){
        try {
            $connection = $this->db->connect();
            $sql = "SELECT 
                        w.*, 
                        t.status as transaction_status,
                        t.created_at as transaction_date
                    FROM walk_in_records w
                    JOIN transactions t ON w.transaction_id = t.id
                    WHERE t.status = 'confirmed'
                    ORDER BY 
                        CASE WHEN w.status = 'pending' THEN 0 ELSE 1 END,
                        t.created_at DESC";
            $query = $connection->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching walk-in records: " . $e->getMessage());
            return array();
        }
    }

    public function addWalkInRecord($first_name, $middle_name, $last_name, $phone_number) {
        try {
            $connection = $this->db->connect();
            $connection->beginTransaction();

            // First, create a transaction record
            $sql = "INSERT INTO transactions (status) VALUES ('confirmed')";
            $query = $connection->prepare($sql);
            $query->execute();
            $transaction_id = $connection->lastInsertId();

            // Get current walk-in price
            $sql = "SELECT price FROM walk_in WHERE id = 1";
            $query = $connection->prepare($sql);
            $query->execute();
            $walk_in_price = $query->fetch(PDO::FETCH_ASSOC)['price'];

            // Insert walk-in record with name fields directly
            $sql = "INSERT INTO walk_in_records (
                        transaction_id, 
                        walk_in_id,
                        first_name,
                        middle_name,
                        last_name,
                        phone_number, 
                        date, 
                        time_in, 
                        amount, 
                        is_paid, 
                        status
                    ) VALUES (
                        :transaction_id, 
                        1,
                        :first_name,
                        :middle_name,
                        :last_name,
                        :phone_number, 
                        CURDATE(), 
                        CURTIME(), 
                        :amount, 
                        1, 
                        'walked-in'
                    )";
            $query = $connection->prepare($sql);
            $query->execute([
                'transaction_id' => $transaction_id,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'phone_number' => $phone_number,
                'amount' => $walk_in_price
            ]);
            
            $walk_in_id = $connection->lastInsertId();

            $connection->commit();
            
            // Log the activity
            $full_name = $first_name . (!empty($middle_name) ? ' ' . $middle_name : '') . ' ' . $last_name;
            logStaffActivity('Add Walk-in', "Added walk-in record #$walk_in_id for {$full_name}");
            
            return true;
        } catch (PDOException $e) {
            if ($connection) {
                $connection->rollBack();
            }
            error_log("Error adding walk-in record: " . $e->getMessage());
            return false;
        }
    }

    public function getWalkInPrice() {
        try {
            $connection = $this->db->connect();
            $sql = "SELECT price FROM walk_in WHERE id = 1";
            $query = $connection->prepare($sql);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['price'] : 0;
        } catch (PDOException $e) {
            error_log("Error fetching walk-in price: " . $e->getMessage());
            return 0;
        }
    }

    public function updateWalkInPrice($price) {
        try {
            $connection = $this->db->connect();
            $sql = "UPDATE walk_in SET price = :price WHERE id = 1";
            $query = $connection->prepare($sql);
            $result = $query->execute(['price' => $price]);
            
            if ($result) {
                // Log the price update
                logStaffActivity('Update Walk-in Price', "Updated walk-in price to ₱" . number_format($price, 2));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating walk-in price: " . $e->getMessage());
            return false;
        }
    }

    public function processWalkInRecord($id) {
        try {
            $connection = $this->db->connect();
            $connection->beginTransaction();
            
            // Get walk-in details for the log
            $sql = "SELECT first_name, middle_name, last_name FROM walk_in_records WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);
            $walkin_info = $query->fetch(PDO::FETCH_ASSOC);

            // Update walk-in record status and payment status
            $sql = "UPDATE walk_in_records SET 
                    status = 'walked-in',
                    time_in = CURTIME(),
                    is_paid = 1
                    WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);

            // Get transaction ID for this walk-in record
            $sql = "SELECT transaction_id FROM walk_in_records WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);
            $transaction_id = $query->fetch(PDO::FETCH_ASSOC)['transaction_id'];

            // Update transaction status
            $sql = "UPDATE transactions SET 
                    status = 'confirmed'
                    WHERE id = :transaction_id";
            $query = $connection->prepare($sql);
            $query->execute(['transaction_id' => $transaction_id]);

            $connection->commit();
            
            // Log the activity
            $full_name = $walkin_info['first_name'] . 
                       (!empty($walkin_info['middle_name']) ? ' ' . $walkin_info['middle_name'] : '') . 
                       ' ' . $walkin_info['last_name'];
            logStaffActivity('Process Walk-in', "Processed walk-in record #$id for {$full_name}");
            
            return true;
        } catch (PDOException $e) {
            if ($connection) {
                $connection->rollBack();
            }
            error_log("Error processing walk-in record: " . $e->getMessage());
            return false;
        }
    }

    public function removeWalkInRecord($id) {
        try {
            $connection = $this->db->connect();
            $connection->beginTransaction();
            
            // Get walk-in details for the log
            $sql = "SELECT first_name, middle_name, last_name FROM walk_in_records WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);
            $walkin_info = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$walkin_info) {
                throw new PDOException("Walk-in record not found");
            }
            
            // Get transaction ID before deleting the walk-in record
            $sql = "SELECT transaction_id FROM walk_in_records WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new PDOException("Walk-in record not found");
            }
            
            $transaction_id = $result['transaction_id'];

            // Build full name for the log
            $full_name = $walkin_info['first_name'] . 
                       (!empty($walkin_info['middle_name']) ? ' ' . $walkin_info['middle_name'] : '') . 
                       ' ' . $walkin_info['last_name'];

            // Delete the walk-in record
            $sql = "DELETE FROM walk_in_records WHERE id = :id";
            $query = $connection->prepare($sql);
            $query->execute(['id' => $id]);

            // Delete the associated transaction
            $sql = "DELETE FROM transactions WHERE id = :transaction_id";
            $query = $connection->prepare($sql);
            $query->execute(['transaction_id' => $transaction_id]);

            $connection->commit();
            
            // Log the activity
            logStaffActivity('Remove Walk-in', "Removed walk-in record #$id for {$full_name}");
            
            return true;
        } catch (PDOException $e) {
            if ($connection) {
                $connection->rollBack();
            }
            error_log("Error removing walk-in record: " . $e->getMessage());
            return false;
        }
    }
}