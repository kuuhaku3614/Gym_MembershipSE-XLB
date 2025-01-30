<?php
require_once '../../../../config.php';

class MembershipHistory {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    public function getAllMemberships() {
        try {
            $query = "SELECT 
                CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
                m.id as membership_id,
                mp.plan_name as membership_plan,
                m.start_date as membership_start,
                m.end_date as membership_end,
                t.created_at as transaction_date
            FROM users u
            INNER JOIN personal_details pd ON u.id = pd.user_id
            INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
            INNER JOIN memberships m ON t.id = m.transaction_id
            INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
            WHERE m.status = 'expired'
            ORDER BY m.end_date DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the data for DataTables
            $formattedMemberships = [];
            foreach ($memberships as $membership) {
                $formattedMemberships[] = [
                    'member_name' => $membership['full_name'],
                    'membership_type' => $membership['membership_plan'],
                    'start_date' => date('M d, Y', strtotime($membership['membership_start'])),
                    'end_date' => date('M d, Y', strtotime($membership['membership_end'])),
                    'membership_id' => $membership['membership_id']
                ];
            }

            return $formattedMemberships;
        } catch (PDOException $e) {
            throw new Exception('Error fetching memberships');
        }
    }

    public function getMembershipDetails($membership_id) {
        try {
            $query = "SELECT 
                CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
                m.id as membership_id,
                mp.plan_name as membership_plan,
                m.start_date as membership_start,
                m.end_date as membership_end,
                t.created_at as transaction_date,
                GROUP_CONCAT(
                    DISTINCT
                    CONCAT(
                        p.program_name, '|',
                        COALESCE(coach.first_name, ''), ' ', COALESCE(coach.last_name, ''), '|',
                        ps.start_date, '|',
                        ps.end_date
                    ) SEPARATOR ';'
                ) as program_details,
                GROUP_CONCAT(
                    DISTINCT
                    CONCAT(
                        srv.service_name, '|',
                        rs.start_date, '|',
                        rs.end_date
                    ) SEPARATOR ';'
                ) as service_details
            FROM users u
            INNER JOIN personal_details pd ON u.id = pd.user_id
            INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
            INNER JOIN memberships m ON t.id = m.transaction_id
            INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
            LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
            LEFT JOIN programs p ON ps.program_id = p.id
            LEFT JOIN users coach_user ON ps.coach_id = coach_user.id
            LEFT JOIN personal_details coach ON coach_user.id = coach.user_id
            LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
            LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
            WHERE m.id = :membership_id
            GROUP BY u.id, pd.first_name, pd.last_name, m.id, mp.plan_name, m.start_date, m.end_date";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':membership_id', $membership_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$membership) {
                throw new Exception('Membership not found');
            }

            return $this->generateDetailsHTML($membership);
        } catch (PDOException $e) {
            throw new Exception('Database error while fetching membership details');
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function generateDetailsHTML($membership) {
        try {
            ob_start();
            ?>
            <div class="p-3">
                <h5 class="mb-3">Member Information</h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($membership['full_name']) ?></p>

                <h5 class="mt-4 mb-3">Membership Details</h5>
                <p><strong>Plan:</strong> <?= htmlspecialchars($membership['membership_plan']) ?></p>
                <p><strong>Start Date:</strong> <?= date('M d, Y', strtotime($membership['membership_start'])) ?></p>
                <p><strong>End Date:</strong> <?= date('M d, Y', strtotime($membership['membership_end'])) ?></p>

                <?php if (!empty($membership['program_details']) && $membership['program_details'] !== '|||'): ?>
                    <h5 class="mt-4 mb-3">Program Subscriptions</h5>
                    <?php
                    $programs = explode(';', $membership['program_details']);
                    foreach ($programs as $program) {
                        list($name, $coach, $start, $end) = explode('|', $program);
                        if (!empty(trim($name))): ?>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Program:</strong> <?= htmlspecialchars($name) ?></p>
                                <?php if (!empty(trim($coach))): ?>
                                    <p class="mb-1"><strong>Coach:</strong> <?= htmlspecialchars($coach) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($start) && !empty($end)): ?>
                                    <p class="mb-1"><strong>Duration:</strong> <?= date('M d, Y', strtotime($start)) ?> - <?= date('M d, Y', strtotime($end)) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif;
                    }
                endif;

                if (!empty($membership['service_details']) && $membership['service_details'] !== '||'): ?>
                    <h5 class="mt-4 mb-3">Service Rentals</h5>
                    <?php
                    $services = explode(';', $membership['service_details']);
                    foreach ($services as $service) {
                        list($name, $start, $end) = explode('|', $service);
                        if (!empty(trim($name))): ?>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($name) ?></p>
                                <?php if (!empty($start) && !empty($end)): ?>
                                    <p class="mb-1"><strong>Duration:</strong> <?= date('M d, Y', strtotime($start)) ?> - <?= date('M d, Y', strtotime($end)) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif;
                    }
                endif; ?>
            </div>
            <?php
            return ob_get_clean();
        } catch (Exception $e) {
            throw new Exception('Error generating membership details view');
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $membershipHistory = new MembershipHistory();
    header('Content-Type: application/json');

    try {
        switch ($_GET['action']) {
            case 'getAll':
                echo json_encode($membershipHistory->getAllMemberships());
                break;

            case 'getDetails':
                if (!isset($_GET['membership_id'])) {
                    throw new Exception('Membership ID is required');
                }
                echo json_encode([
                    'html' => $membershipHistory->getMembershipDetails($_GET['membership_id'])
                ]);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
