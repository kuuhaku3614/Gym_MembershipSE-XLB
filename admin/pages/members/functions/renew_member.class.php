<?php
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/member_registration.class.php');

class RenewMember {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    /**
     * Renew an existing member's membership.
     * @param array $data
     * @return array
     */
    public function renewMember($data) {
    

    

        try {
            $this->pdo->beginTransaction();
            // Validate required inputs
            if (!isset($data['member_id']) || !isset($data['membership_plan']) || !isset($data['membership_start_date'])) {
                throw new Exception('Missing required renewal data');
            }
            $userId = $data['member_id'];
            $planId = $data['membership_plan'];
            $startDate = $data['membership_start_date'];

            $memberReg = new MemberRegistration($this->pdo);
            $planDetails = $memberReg->getMembershipPlanDetails($planId);
            if (!$planDetails) {
                throw new Exception('Invalid membership plan');
            }
            $duration = $planDetails['duration'];
            $durationType = $planDetails['duration_type'];
            $endDate = $memberReg->calculateEndDate($startDate, $duration, $durationType);

            // Create a single transaction for all records
            $sql = "INSERT INTO transactions (user_id, status, created_at) VALUES (:user_id, 'confirmed', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':user_id' => $userId]);
            if (!$result) throw new Exception('Failed to create transaction record');
            $transactionId = $this->pdo->lastInsertId();

            if (!$result) throw new Exception('Failed to create registration record');

            // Set is_paid based on checkbox (default to 0 if not set)
            $isPaid = isset($data['is_paid']) && $data['is_paid'] == '1' ? 1 : 0;

            // Insert membership record
            $sql = "INSERT INTO memberships (transaction_id, membership_plan_id, start_date, end_date, amount, status, is_paid) VALUES (:transaction_id, :plan_id, :start_date, :end_date, :amount, 'active', :is_paid)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':transaction_id' => $transactionId,
                ':plan_id' => $planId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':amount' => $planDetails['price'],
                ':is_paid' => $isPaid
            ]);
            if (!$result) throw new Exception('Failed to process membership plan');

            // Handle rental services (legacy and new AJAX structure)
            $rentalIds = [];
            if (!empty($data['rental_services'])) {
                // Legacy support: array of rental IDs
                $rentalIds = is_array($data['rental_services']) ? $data['rental_services'] : [$data['rental_services']];
            }
            if (!empty($data['selected_rentals'])) {
                // New AJAX: JSON array of rental IDs
                $decoded = json_decode($data['selected_rentals'], true);
                if (is_array($decoded)) {
                    $rentalIds = array_merge($rentalIds, $decoded);
                }
            }
            $rentalIds = array_unique($rentalIds);
            

            foreach ($rentalIds as $rentalId) {
                

                $rentalDetails = $memberReg->getRentalServiceDetails($rentalId);
                

                if (!$rentalDetails) throw new Exception('Invalid rental service selected');
                $rentalStart = date('Y-m-d');
                $rentalEnd = $memberReg->calculateEndDate($rentalStart, $rentalDetails['duration'], $rentalDetails['duration_type']);
                $sql = "INSERT INTO rental_subscriptions (transaction_id, rental_service_id, start_date, end_date, amount, status, is_paid) VALUES (:transaction_id, :rental_id, :start_date, :end_date, :amount, 'active', :is_paid)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    ':transaction_id' => $transactionId,
                    ':rental_id' => $rentalId,
                    ':start_date' => $rentalStart,
                    ':end_date' => $rentalEnd,
                    ':amount' => $rentalDetails['price'],
                    ':is_paid' => $isPaid
                ]);
                

                if (!$result) throw new Exception('Failed to process rental service');
            }

            // Handle program subscriptions
            if (!empty($data['selected_programs'])) {
                $selectedPrograms = json_decode($data['selected_programs'], true);
                foreach ($selectedPrograms as $program) {
                    $sql = "INSERT INTO program_subscriptions (user_id, coach_program_type_id, status, transaction_id) VALUES (:user_id, :program_id, 'pending', :transaction_id)";
                    $stmt = $this->pdo->prepare($sql);
                    $result = $stmt->execute([
                        ':user_id' => $userId,
                        ':program_id' => $program['coach_program_type_id'],
                        ':transaction_id' => $transactionId
                    ]);
                    if (!$result) throw new Exception('Failed to process program subscription');
                    $programSubscriptionId = $this->pdo->lastInsertId();

                    // Insert program schedule(s) if provided
                    if (!empty($program['schedules']) && is_array($program['schedules'])) {
                        foreach ($program['schedules'] as $schedule) {
                            $sql = "INSERT INTO program_subscription_schedule (
    program_subscription_id, date, day, start_time, end_time, amount, coach_group_schedule_id, coach_personal_schedule_id
) VALUES (
    :program_subscription_id, :date, :day, :start_time, :end_time, :amount, :coach_group_schedule_id, :coach_personal_schedule_id
)";
                            $stmt = $this->pdo->prepare($sql);
                            $result = $stmt->execute([
                                ':program_subscription_id' => $programSubscriptionId,
                                ':date' => $schedule['date'],
                                ':day' => $schedule['day'],
                                ':start_time' => $schedule['start_time'],
                                ':end_time' => $schedule['end_time'],
                                ':amount' => $schedule['amount'],
                                ':coach_group_schedule_id' => isset($schedule['coach_group_schedule_id']) ? $schedule['coach_group_schedule_id'] : null,
                                ':coach_personal_schedule_id' => isset($schedule['coach_personal_schedule_id']) ? $schedule['coach_personal_schedule_id'] : null
                            ]);
                            

                            if (!$result) throw new Exception('Failed to insert program schedule');
                        }
                    } else {
                        

                    }
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Membership renewed successfully'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
