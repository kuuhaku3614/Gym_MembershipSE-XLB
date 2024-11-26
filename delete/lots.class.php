<?php

require_once __DIR__ . '/../database.php';

class Reservation{

    public $reservation_date = '';
    public $payment_plan_id = '';
    public $account_id = '';
    public $lot_id = '';
    public $balance = '';
    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    function displayAvailable_lots(){

        $sql = "SELECT * FROM lots WHERE status LIKE 'Available';";
        $query = $this->db->connect()->prepare($sql);
        $data = null;

        if($query->execute()){
            $data = $query->fetchAll();
        }

        return $data;
    }

    public function fetchLotRecord($lot_id) {
        $sql = "SELECT * FROM lots WHERE lot_id = :lot_id";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':lot_id', $lot_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAccountRecord($account_id) {
        $sql = "SELECT * FROM account WHERE account_id = :account_id";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':account_id', $account_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchPayment_plan() {
        $sql = "SELECT payment_plan_id, CONCAT(plan, ' (', down_payment, '% down payment with ', interest_rate, '% interest rate)') AS pplan FROM payment_plan ;";
    
        $query = $this->db->connect()->prepare($sql);
        $data = null;
        if ($query->execute()) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }

    function addReservation() {
        // Fetch lot price
        $sqlPrice = "SELECT price FROM lots WHERE lot_id = :lot_id";
        $queryPrice = $this->db->connect()->prepare($sqlPrice);
        $queryPrice->bindParam(':lot_id', $this->lot_id);
        $queryPrice->execute();
        $result = $queryPrice->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            $lotPrice = $result['price'];
    
            // Fetch payment plan details
            $sqlPlan = "SELECT * FROM payment_plan WHERE payment_plan_id = :payment_plan_id";
            $queryPlan = $this->db->connect()->prepare($sqlPlan);
            $queryPlan->bindParam(':payment_plan_id', $this->payment_plan_id);
            $queryPlan->execute();
            $paymentPlan = $queryPlan->fetch(PDO::FETCH_ASSOC);
    
            if ($paymentPlan) {
                // Calculate amortization
                $amortizationDetails = $this->calculateAmortization($lotPrice, $paymentPlan);
                
                // Set balance and monthly payment
                $this->balance = $amortizationDetails['totalBalance'];
                $monthlyPayment = $amortizationDetails['monthlyPayment'];

                // Insert the reservation
                $sql = "INSERT INTO reservation (account_id, lot_id, reservation_date, payment_plan_id, balance, monthly_payment) 
                        VALUES (:account_id, :lot_id, :reservation_date, :payment_plan_id, :balance, :monthly_payment)";
                
                $query = $this->db->connect()->prepare($sql);
    
                $query->bindParam(':account_id', $this->account_id);
                $query->bindParam(':lot_id', $this->lot_id);
                $query->bindParam(':reservation_date', $this->reservation_date);
                $query->bindParam(':payment_plan_id', $this->payment_plan_id);
                $query->bindParam(':balance', $this->balance);
                $query->bindParam(':monthly_payment', $monthlyPayment);
    
                if ($query->execute()) {
                    // Update the lot status to 'On Request'
                    $sqlUpdateStatus = "UPDATE lots SET status = 'On Request' WHERE lot_id = :lot_id";
                    $queryUpdateStatus = $this->db->connect()->prepare($sqlUpdateStatus);
                    $queryUpdateStatus->bindParam(':lot_id', $this->lot_id);
                    $queryUpdateStatus->execute();
    
                    return true;
                } else {
                    return false;
                }
            } else {
                echo 'Payment plan not found';
                return false;
            }
        } else {
            echo 'Lot not found';
            return false;
        }
    }

    private function calculateAmortization($lotPrice, $paymentPlan) {
        // Calculate down payment
        $downPaymentAmount = ($paymentPlan['down_payment'] / 100) * $lotPrice;
        $principalAmount = $lotPrice - $downPaymentAmount;

        // Convert annual interest rate to monthly
        $monthlyInterestRate = ($paymentPlan['interest_rate'] / 100) / 12;
        $numberOfPayments = $paymentPlan['duration'];

        // Amortization formula
        if ($monthlyInterestRate > 0) {
            $monthlyPayment = $principalAmount * 
                            ($monthlyInterestRate * pow(1 + $monthlyInterestRate, $numberOfPayments)) / 
                            (pow(1 + $monthlyInterestRate, $numberOfPayments) - 1);
        } else {
            // If interest rate is 0, just divide the principal by the number of payments
            $monthlyPayment = $principalAmount / $numberOfPayments;
        }

        // Calculate total balance
        $totalBalance = ($monthlyPayment * $numberOfPayments) + $downPaymentAmount;

        return [
            'totalBalance' => $totalBalance,
            'monthlyPayment' => $monthlyPayment,
            'downPayment' => $downPaymentAmount
        ];
    }




    
    function getReservationsByAccountId($account_id) {
        $sql = "SELECT r.reservation_id, l.lot_name, r.reservation_date, pp.plan, r.balance, r.request
                FROM reservation r
                JOIN lots l ON r.lot_id = l.lot_id
                JOIN payment_plan pp ON r.payment_plan_id = pp.payment_plan_id
                WHERE r.account_id = :account_id
                ORDER BY r.reservation_date DESC";

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':account_id', $account_id);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

}