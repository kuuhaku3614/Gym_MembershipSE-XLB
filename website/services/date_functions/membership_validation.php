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
     * @return array - Array of disabled dates with their status
     */
    public function getDisabledDates($user_id) {
        $disabledDates = [];
        
        // Add dates from pending unpaid memberships
        $pendingMemberships = $this->getPendingMemberships($user_id);
        foreach ($pendingMemberships as $membership) {
            $disabledDates = $this->addDateRange($disabledDates, $membership['start_date'], $membership['end_date'], 'pending-membership');
        }
        
        // Add dates from active paid memberships
        $activeMemberships = $this->getActiveMemberships($user_id);
        foreach ($activeMemberships as $membership) {
            $disabledDates = $this->addDateRange($disabledDates, $membership['start_date'], $membership['end_date'], 'active-membership');
        }
        
        // Add dates from pending walk-ins
        $pendingWalkins = $this->getPendingWalkins($user_id);
        foreach ($pendingWalkins as $walkin) {
            $disabledDates[$walkin['date']] = 'pending-walkin';
        }
        
        return $disabledDates;
    }
    
    /**
     * Add a range of dates to the disabled dates array
     * 
     * @param array $disabledDates - Existing disabled dates
     * @param string $startDate - Start date (Y-m-d)
     * @param string $endDate - End date (Y-m-d)
     * @param string $status - Status code for these dates
     * @return array - Updated disabled dates array
     */
    private function addDateRange($disabledDates, $startDate, $endDate, $status) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include the end date
        
        $interval = new DateInterval('P1D'); // 1 day interval
        $dateRange = new DatePeriod($start, $interval, $end);
        
        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $disabledDates[$dateStr] = $status;
        }
        
        return $disabledDates;
    }
    
    /**
     * Get pending, unpaid memberships for a user
     * 
     * @param int $user_id - The ID of the current user
     * @return array - Array of pending memberships
     */
    private function getPendingMemberships($user_id) {
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
                AND t.status = 'pending'
                AND m.is_paid = 0
                AND m.status = 'active'
                AND (m.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN m.start_date AND m.end_date)";
        
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
                AND (m.start_date >= CURRENT_DATE() OR CURRENT_DATE BETWEEN m.start_date AND m.end_date)";
        
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
        $query = "
            SELECT 
                w.id AS walk_in_record_id,
                w.date
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
}
?>