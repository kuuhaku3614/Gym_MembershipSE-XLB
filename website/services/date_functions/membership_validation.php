<?php
class MembershipValidation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all dates that should be disabled in the calendar
     *
     * @param int $user_id - The ID of the current user
     * @return array - Array of disabled dates with their status and extra info if needed
     */
    public function getDisabledDates($user_id) {
        $disabledDates = [];

        // Add dates from pending unpaid memberships
        $pendingMemberships = $this->getPendingMemberships($user_id);
        foreach ($pendingMemberships as $membership) {
            // Store status, end date, and transaction ID for pending memberships
            $pendingInfo = [
                'status' => 'pending-membership',
                'endDate' => $membership['end_date'],
                'transactionId' => $membership['transaction_id'] // Added transaction ID
            ];
            $disabledDates = $this->addDateRange($disabledDates, $membership['start_date'], $membership['end_date'], $pendingInfo);
        }

        // Add dates from active paid memberships
        $activeMemberships = $this->getActiveMemberships($user_id);
        foreach ($activeMemberships as $membership) {
            $disabledDates = $this->addDateRange($disabledDates, $membership['start_date'], $membership['end_date'], ['status' => 'active-membership']);
        }

        // Add dates from pending walk-ins
        $pendingWalkins = $this->getPendingWalkins($user_id);
        foreach ($pendingWalkins as $walkin) {
            // You might want to include walkin transaction ID here too if needed later
             $disabledDates[$walkin['date']] = ['status' => 'pending-walkin'];
        }

        return $disabledDates;
    }

    /**
     * Add a range of dates to the disabled dates array
     *
     * @param array $disabledDates - Existing disabled dates
     * @param string $startDate - Start date (Y-m-d)
     * @param string $endDate - End date (Y-m-d)
     * @param mixed $statusInfo - Status code (string) or an array containing status and other info
     * @return array - Updated disabled dates array
     */
    private function addDateRange($disabledDates, $startDate, $endDate, $statusInfo) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include the end date

        $interval = new DateInterval('P1D'); // 1 day interval
        $dateRange = new DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            // Store the full info object/array for each date in the range
            $disabledDates[$dateStr] = $statusInfo;
        }

        return $disabledDates;
    }

    /**
     * Get pending, unpaid memberships for a user
     *
     * @param int $user_id - The ID of the current user
     * @return array - Array of pending memberships including transaction_id
     */
    private function getPendingMemberships($user_id) {
        $query = "
            SELECT
                m.id AS membership_id,
                mp.plan_name,
                m.start_date,
                m.end_date,
                t.id AS transaction_id  -- Fetch the transaction ID
            FROM
                users u
            JOIN
                transactions t ON u.id = t.user_id
            JOIN
                memberships m ON t.id = m.transaction_id
            JOIN
                membership_plans mp ON m.membership_plan_id = mp.id
            WHERE
                u.id = :user_id
                AND t.status = 'pending'
                AND m.is_paid = 0
                AND m.status = 'active' -- Assuming 'active' means not cancelled, even if pending payment
                AND (m.start_date >= CURRENT_DATE() OR CURRENT_DATE() BETWEEN m.start_date AND m.end_date)"; // Corrected date condition

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching pending memberships: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active, paid memberships for a user
     *
     * @param int $user_id - The ID of the current user
     * @return array - Array of active memberships
     */
    private function getActiveMemberships($user_id) {
        // No changes needed here for this feature
        $query = "
            SELECT
                m.id AS membership_id,
                mp.plan_name,
                m.start_date,
                m.end_date
            FROM
                users u
            JOIN
                transactions t ON u.id = t.user_id
            JOIN
                memberships m ON t.id = m.transaction_id
            JOIN
                membership_plans mp ON m.membership_plan_id = mp.id
            WHERE
                u.id = :user_id
                AND m.is_paid = 1
                AND m.status IN ('active', 'expiring')
                AND (m.start_date >= CURRENT_DATE() OR CURRENT_DATE() BETWEEN m.start_date AND m.end_date)"; // Corrected date condition

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching active memberships: ' . $e->getMessage());
            return [];
        }
    }

     /**
     * Get pending walk-ins for a user
     *
     * @param int $user_id - The ID of the current user
     * @return array - Array of pending walk-ins
     */
    private function getPendingWalkins($user_id) {
        // No changes needed here for this feature
        $query = "
            SELECT
                w.id AS walk_in_record_id,
                w.date
                -- t.id as transaction_id -- Optionally fetch transaction ID if needed later
            FROM
                walk_in_records w
            JOIN
                transactions t ON w.transaction_id = t.id
            WHERE
                t.user_id = :user_id
                AND t.status = 'pending'
                AND w.is_paid = 0
                AND w.status = 'pending'
                AND w.date >= CURRENT_DATE()";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching pending walk-ins: ' . $e->getMessage());
            return [];
        }
    }

    // --- NEW FUNCTION ---
    /**
     * Delete a pending membership transaction.
     *
     * @param int $user_id The ID of the user.
     * @param int $transaction_id The ID of the transaction to delete.
     * @return bool True on success, false on failure.
     */
    public function deletePendingMembershipTransaction($user_id, $transaction_id) {
        // First, verify the transaction belongs to the user and is actually a pending membership
        $checkQuery = "
            SELECT t.id
            FROM transactions t
            JOIN memberships m ON t.id = m.transaction_id
            WHERE t.id = :transaction_id
              AND t.user_id = :user_id
              AND t.status = 'pending'
              AND m.is_paid = 0";
        try {
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() == 0) {
                error_log("Attempt to delete non-existent or non-pending/non-membership transaction ID: {$transaction_id} for user ID: {$user_id}");
                return false; // Transaction not found, doesn't belong to user, or isn't a pending membership
            }

            // Proceed with deletion (adjust based on your schema, might need to delete from memberships first or handle foreign keys)
            // This assumes ON DELETE CASCADE or similar is set up, or you delete related records manually.
            // Start a transaction for safety
            $this->pdo->beginTransaction();

            // Delete from memberships table first if no cascade delete
            $deleteMembershipQuery = "DELETE FROM memberships WHERE transaction_id = :transaction_id";
            $deleteMembershipStmt = $this->pdo->prepare($deleteMembershipQuery);
            $deleteMembershipStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $membershipDeleted = $deleteMembershipStmt->execute();

            // Then delete from transactions table
            $deleteTransactionQuery = "DELETE FROM transactions WHERE id = :transaction_id";
            $deleteTransactionStmt = $this->pdo->prepare($deleteTransactionQuery);
            $deleteTransactionStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $transactionDeleted = $deleteTransactionStmt->execute();


            if ($membershipDeleted && $transactionDeleted) {
                 $this->pdo->commit();
                 return true;
            } else {
                 $this->pdo->rollBack();
                 error_log("Failed to delete transaction ID: {$transaction_id}. Membership delete status: {$membershipDeleted}, Transaction delete status: {$transactionDeleted}");
                 return false;
            }

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Error deleting pending membership transaction: ' . $e->getMessage());
            return false;
        }
    }
}
?>