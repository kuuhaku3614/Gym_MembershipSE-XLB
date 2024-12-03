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
                    p.program_name,
                    pt.type_name as program_type,
                    ps.start_date as program_start,
                    ps.end_date as program_end,
                    ps.amount as program_amount,
                    CONCAT(coach_pd.first_name, ' ', COALESCE(coach_pd.middle_name, ''), ' ', coach_pd.last_name) as coach_name,
                    rs.service_name,
                    rsub.start_date as rental_start,
                    rsub.end_date as rental_end,
                    rsub.amount as rental_amount,
                    reg.membership_fee as registration_fee
                    FROM transactions t
                    JOIN users u ON t.user_id = u.id
                    JOIN personal_details pd ON u.id = pd.user_id
                    LEFT JOIN profile_photos pp ON u.id = pp.user_id
                    LEFT JOIN memberships m ON t.id = m.transaction_id
                    LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
                    LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
                    LEFT JOIN programs p ON ps.program_id = p.id
                    LEFT JOIN program_types pt ON p.program_type_id = pt.id
                    LEFT JOIN users coach_u ON ps.coach_id = coach_u.id
                    LEFT JOIN personal_details coach_pd ON coach_u.id = coach_pd.user_id
                    LEFT JOIN rental_subscriptions rsub ON t.id = rsub.transaction_id
                    LEFT JOIN rental_services rs ON rsub.rental_service_id = rs.id
                    LEFT JOIN registration reg ON reg.id = (SELECT id FROM registration ORDER BY id DESC LIMIT 1)
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
        $programStart = !empty($request['program_start']) ? new DateTime($request['program_start']) : null;
        $programEnd = !empty($request['program_end']) ? new DateTime($request['program_end']) : null;
        $rentalStart = !empty($request['rental_start']) ? new DateTime($request['rental_start']) : null;
        $rentalEnd = !empty($request['rental_end']) ? new DateTime($request['rental_end']) : null;
        $birthDate = new DateTime($request['birthdate']);
        
        $details = [
            'member_name' => $request['full_name'],
            'phone_number' => $request['phone_number'],
            'sex' => $request['sex'],
            'birthdate' => $birthDate->format('F d, Y'),
            'age' => $request['age'],
            'request_date' => $requestDate->format('F d, Y, h:i a'),
            'profile_picture' => $request['profile_picture']
        ];

        // Add membership details if present
        if (!empty($request['plan_name'])) {
            $details['membership'] = [
                'plan_name' => $request['plan_name'],
                'start_date' => $membershipStart ? $membershipStart->format('F d, Y') : '',
                'end_date' => $membershipEnd ? $membershipEnd->format('F d, Y') : '',
                'amount' => number_format($request['membership_amount'], 2)
            ];
        }

        // Add program details if present
        if (!empty($request['program_name'])) {
            $details['program'] = [
                'name' => $request['program_name'],
                'type' => $request['program_type'],
                'coach' => $request['coach_name'],
                'amount' => number_format($request['program_amount'], 2),
                'start_date' => $programStart ? $programStart->format('F d, Y') : '',
                'end_date' => $programEnd ? $programEnd->format('F d, Y') : ''
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

        // Add registration fee if present
        if (!empty($request['registration_fee'])) {
            $details['registration_fee'] = number_format($request['registration_fee'], 2);
        }

        $message = sprintf(
            "%s wants to avail services starting %s",
            $request['full_name'],
            $membershipStart ? $membershipStart->format('F d, Y') : 
            ($programStart ? $programStart->format('F d, Y') : 
            ($rentalStart ? $rentalStart->format('F d, Y') : ''))
        );
        
        return [
            'id' => $request['transaction_id'],
            'title' => 'New Transaction Request',
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
}
