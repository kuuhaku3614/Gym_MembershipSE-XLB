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
     * @return array - Array of rental subscriptions
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
     * Get unpaid but active rental subscriptions (These are considered pending rentals for the calendar)
     * @return array - Array of rental subscriptions
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
                 AND t.status = 'pending' -- Ensure the transaction is also pending
                AND (rs.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN rs.start_date AND rs.end_date)";

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
     * Get paid and active rental subscriptions with completed transactions
     * @return array - Array of rental subscriptions
     */
    public function getPaidActiveRentalSubscriptionsWithCompletedTransactions() {
         $query = "
            SELECT
                rs.id AS subscription_id,
                rs.transaction_id,
                rs.start_date,
                rs.end_date
            FROM
                rental_subscriptions rs
            JOIN
                transactions t ON rs.transaction_id = t.id
            WHERE
                rs.is_paid = 1
                AND rs.status = 'active'
                AND t.status = 'completed' -- Only consider completed transactions for fully active/paid
                AND (rs.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN rs.start_date AND rs.end_date)";

        try {
            $stmt = $this->pdo->prepare($query);
             $stmt->execute();
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching paid active rental subscriptions with completed transactions: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Get disabled dates for rentals. This function ONLY considers rental conflicts (paid active and unpaid active).
     * Returns an associative array where keys are dates (Y-m-d) and values are arrays
     * containing the status ('paid-active-rental' or 'unpaid-active-rental')
     * and transaction_id for unpaid rentals.
     *
     * @return array An associative array of dates and their conflict status information.
     */
    public function getRentalDisabledDates() {
        $disabledDates = [];

        // Fetch paid active rental subscriptions with pending transactions AND completed transactions
        $paidActiveRentals = array_merge(
            $this->getPaidActiveRentalSubscriptionsWithPendingTransactions(),
            $this->getPaidActiveRentalSubscriptionsWithCompletedTransactions()
        );


        foreach ($paidActiveRentals as $rental) {
            $startDate = new DateTime($rental['start_date']);
            $endDate = new DateTime($rental['end_date']);
            $endDate->modify('+1 day'); // Include the end date in the period

            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);

            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                // Mark all dates within the range as paid-active-rental
                $disabledDates[$dateStr] = ['status' => 'paid-active-rental'];
            }
        }

        // Fetch unpaid active rental subscriptions (these are the 'pending' ones for rental calendar)
        $unpaidRentals = $this->getUnpaidActiveRentalSubscriptions();
        foreach ($unpaidRentals as $rental) {
            $startDate = new DateTime($rental['start_date']);
            $endDate = new DateTime($rental['end_date']);
             $endDate->modify('+1 day'); // Include the end date in the period

            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);

            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                // Mark dates within the range as unpaid-active-rental and include transaction ID
                $disabledDates[$dateStr] = [
                    'status' => 'unpaid-active-rental',
                    'transactionId' => $rental['transaction_id'] // Include transaction ID
                 ];
            }
        }

        return $disabledDates;
    }

     /**
     * Checks if a transaction has other pending items associated with it in specified tables.
     *
     * @param int $transaction_id The ID of the transaction.
     * @param string $exclude_table The table from which an item is being deleted (e.g., 'walk_in_records', 'memberships', 'rental_subscriptions').
     * @return bool True if other pending items exist, false otherwise.
     */
    private function hasOtherPendingItems($transaction_id, $exclude_table) {
        $count = 0;

        // Check walk_in_records (if not the table being excluded)
        if ($exclude_table !== 'walk_in_records') {
            $query = "SELECT COUNT(*) FROM walk_in_records WHERE transaction_id = :transaction_id AND is_paid = 0 AND status = 'pending'";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt->execute();
            $count += $stmt->fetchColumn();
        }

        // Check memberships (if not the table being excluded)
        if ($exclude_table !== 'memberships') {
            $query = "SELECT COUNT(*) FROM memberships WHERE transaction_id = :transaction_id AND is_paid = 0 AND status = 'active'"; // Assuming 'active' means not cancelled, even if pending payment
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt->execute();
            $count += $stmt->fetchColumn();
        }

        // Check rental_subscriptions (if not the table being excluded)
        if ($exclude_table !== 'rental_subscriptions') {
            $query = "SELECT COUNT(*) FROM rental_subscriptions WHERE transaction_id = :transaction_id AND status = 'active' AND is_paid = 0"; // Assuming 'pending' status for rentals is is_paid=0
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt->execute();
            $count += $stmt->fetchColumn();
        }

        // You can add checks for other tables if needed (e.g., programs)

        return $count > 0;
    }


     /**
     * Delete a pending rental subscription record and potentially the transaction if no other pending items exist.
     *
     * @param int $user_id The ID of the user.
     * @param int $transaction_id The ID of the transaction associated with the rental.
     * @return bool True on success, false on failure.
     */
    public function deletePendingRentalTransaction($user_id, $transaction_id) {
        // First, verify the rental subscription belongs to the user and is pending (is_paid = 0)
        $checkQuery = "
            SELECT rs.id
            FROM rental_subscriptions rs
            JOIN transactions t ON rs.transaction_id = t.id
            WHERE rs.transaction_id = :transaction_id
              AND t.user_id = :user_id
              AND t.status = 'pending'
              AND rs.is_paid = 0"; // Check for pending payment status for rentals

        try {
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() == 0) {
                error_log("Attempt to delete non-existent or non-pending rental transaction ID: {$transaction_id} for user ID: {$user_id}");
                return false;
            }

            // Start a transaction for safety
            $this->pdo->beginTransaction();

            // Delete from rental_subscriptions table
            $deleteRentalQuery = "DELETE FROM rental_subscriptions WHERE transaction_id = :transaction_id";
            $deleteRentalStmt = $this->pdo->prepare($deleteRentalQuery);
            $deleteRentalStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $rentalDeleted = $deleteRentalStmt->execute();

            if (!$rentalDeleted) {
                 $this->pdo->rollBack();
                 error_log("Failed to delete rental subscription record for transaction ID: {$transaction_id}.");
                 return false;
            }

            // Check if there are any other pending items associated with this transaction
            if (!$this->hasOtherPendingItems($transaction_id, 'rental_subscriptions')) {
                // No other pending items, delete the transaction
                $deleteTransactionQuery = "DELETE FROM transactions WHERE id = :transaction_id";
                $deleteTransactionStmt = $this->pdo->prepare($deleteTransactionQuery);
                $deleteTransactionStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
                $transactionDeleted = $deleteTransactionStmt->execute();

                 if ($transactionDeleted) {
                     $this->pdo->commit();
                     return true; // Both rental and transaction deleted
                } else {
                     $this->pdo->rollBack();
                     error_log("Failed to delete transaction ID: {$transaction_id} after deleting rental.");
                     return false;
                }
            } else {
                // Other pending items exist, only rental was deleted
                $this->pdo->commit();
                return true; // Only rental deleted
            }

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Error deleting pending rental record or transaction: ' . $e->getMessage());
            return false;
        }
    }
}
?>