<?php
class RentalValidation {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Set default timezone to Asia/Manila
        date_default_timezone_set('Asia/Manila');
    }
    
    /**
     * Get paid and active rental subscriptions with pending transactions
     * * @return array - Array of rental subscriptions
     */
    public function getPaidActiveRentalSubscriptionsWithPendingTransactions() {
        $query = "
            SELECT 
                rs.id AS subscription_id,
                rs.transaction_id,
                rs.rental_service_id,
                rs.start_date,
                rs.end_date,
                rs.amount,
                rs.status,
                rs.is_paid,
                rs.payment_date,
                t.status AS transaction_status
            FROM 
                rental_subscriptions rs
            JOIN 
                transactions t ON rs.transaction_id = t.id
            WHERE 
                rs.is_paid = 1
                AND rs.status = 'active'
                AND t.status = 'pending'
                AND (rs.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN rs.start_date AND rs.end_date)
            ORDER BY rs.start_date";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching paid active rental subscriptions with pending transactions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unpaid but active rental subscriptions
     * * @return array - Array of rental subscriptions
     */
    public function getUnpaidActiveRentalSubscriptions() {
        $query = "
            SELECT 
                rs.id AS subscription_id,
                rs.transaction_id,
                rs.rental_service_id,
                rs.start_date,
                rs.end_date,
                rs.amount,
                rs.status,
                rs.is_paid,
                rs.payment_date,
                t.status AS transaction_status
            FROM 
                rental_subscriptions rs
            JOIN 
                transactions t ON rs.transaction_id = t.id
            WHERE 
                rs.is_paid = 0
                AND rs.status = 'active'
                AND (rs.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN rs.start_date AND rs.end_date)
            ORDER BY 
                rs.start_date";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching unpaid active rental subscriptions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get disabled dates for rentals.  This function ONLY considers rental conflicts.
     *
     * @param int $user_id The user ID.
     * @return array An array of dates that are disabled for rentals.
     */
    public function getRentalDisabledDates($user_id) {
        $disabledDates = [];

        // Fetch paid active rental subscriptions with pending transactions
        $pendingTransactions = $this->getPaidActiveRentalSubscriptionsWithPendingTransactions();
        foreach ($pendingTransactions as $rental) {
            $startDate = new DateTime($rental['start_date']);
            $endDate = new DateTime($rental['end_date']);
            while ($startDate <= $endDate) {
                $disabledDates[] = $startDate->format('Y-m-d');
                $startDate->modify('+1 day');
            }
        }

        // Fetch unpaid active rental subscriptions
        $unpaidRentals = $this->getUnpaidActiveRentalSubscriptions();
        foreach ($unpaidRentals as $rental) {
            $startDate = new DateTime($rental['start_date']);
            $endDate = new DateTime($rental['end_date']);
            while ($startDate <= $endDate) {
                $disabledDates[] = $startDate->format('Y-m-d');
                $startDate->modify('+1 day');
            }
        }
        
        // Remove duplicate dates.
        $disabledDates = array_unique($disabledDates);
        
        return $disabledDates;
    }
}
?>