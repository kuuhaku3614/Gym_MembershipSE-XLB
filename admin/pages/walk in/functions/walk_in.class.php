<?php
require_once '../../../config.php';

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
                    ORDER BY t.created_at DESC";
            $query = $connection->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching walk-in records: " . $e->getMessage());
            return array();
        }
    }

    public function addWalkInRecord($name, $phone_number) {
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

            // Insert walk-in record
            $sql = "INSERT INTO walk_in_records (transaction_id, walk_in_id, name, phone_number, date, time_in, amount, is_paid, status) 
                   VALUES (:transaction_id, 1, :name, :phone_number, CURDATE(), CURTIME(), :amount, 1, 'walked-in')";
            $query = $connection->prepare($sql);
            $query->execute([
                'transaction_id' => $transaction_id,
                'name' => $name,
                'phone_number' => $phone_number,
                'amount' => $walk_in_price
            ]);

            $connection->commit();
            return true;
        } catch (PDOException $e) {
            if ($connection) {
                $connection->rollBack();
            }
            error_log("Error adding walk-in record: " . $e->getMessage());
            return false;
        }
    }
}