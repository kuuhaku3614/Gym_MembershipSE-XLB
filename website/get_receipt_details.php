<?php
// Set default timezone
date_default_timezone_set('Asia/Manila');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['transaction_id']) || empty($_POST['transaction_id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
    exit();
}

$transaction_id = $_POST['transaction_id'];

// Get database connection
global $pdo;

try {
    // Updated query with proper error handling
    $query = "
        SELECT 
            t.id as transaction_id,
            ps.id as subscription_id,
            CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
            pd.user_id as member_id,
            p.program_name,
            cpt.type as program_type,
            cpt.category as program_category,
            pss.date as session_date,
            pss.start_time,
            pss.end_time,
            pss.amount,
            pss.is_paid,
            DATE(pss.updated_at) as payment_date,
            COALESCE(pss.coach_personal_schedule_id, pss.coach_group_schedule_id) as schedule_id,
            CASE 
                WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'personal'
                WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'group'
                ELSE NULL
            END as schedule_type,
            CONCAT_WS(' ', coach_pd.first_name, NULLIF(coach_pd.middle_name, ''), coach_pd.last_name) as coach_name
        FROM transactions t
        LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
        LEFT JOIN program_subscription_schedule pss ON ps.id = pss.program_subscription_id
        LEFT JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
        LEFT JOIN programs p ON cpt.program_id = p.id
        LEFT JOIN users u ON ps.user_id = u.id
        LEFT JOIN personal_details pd ON u.id = pd.user_id
        LEFT JOIN users coach_u ON cpt.coach_id = coach_u.id
        LEFT JOIN personal_details coach_pd ON coach_u.id = coach_pd.user_id
        WHERE t.id = ?
        ORDER BY pss.date, pss.start_time
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$transaction_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no items found directly, try checking if it's a membership transaction
    if (empty($items)) {
        // Try to fetch membership transaction data
        $membershipQuery = "
            SELECT 
                t.id as transaction_id,
                t.amount,
                t.created_at as payment_date,
                m.id as membership_id,
                mp.name as program_name,
                'membership' as program_type,
                'membership' as schedule_type,
                CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
                pd.user_id as member_id,
                NULL as coach_name,
                t.created_at as session_date,
                NULL as start_time,
                NULL as end_time
            FROM transactions t
            JOIN memberships m ON t.id = m.transaction_id
            JOIN membership_plans mp ON m.plan_id = mp.id
            JOIN users u ON m.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE t.id = ?
        ";
        
        $membershipStmt = $pdo->prepare($membershipQuery);
        $membershipStmt->execute([$transaction_id]);
        $items = $membershipStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If still no items found
        if (empty($items)) {
            // Try a generic transaction lookup
            $genericQuery = "
                SELECT 
                    t.id as transaction_id,
                    t.amount,
                    t.created_at as payment_date,
                    t.description as program_name,
                    'service' as program_type,
                    'service' as schedule_type,
                    CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
                    pd.user_id as member_id,
                    NULL as coach_name,
                    t.created_at as session_date,
                    NULL as start_time,
                    NULL as end_time
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN personal_details pd ON u.id = pd.user_id
                WHERE t.id = ?
            ";
            
            $genericStmt = $pdo->prepare($genericQuery);
            $genericStmt->execute([$transaction_id]);
            $items = $genericStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    if (empty($items)) {
        // Return a valid response even if no items are found
        echo json_encode([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction_id,
                'items' => [],
                'total_amount' => 0
            ]
        ]);
    } else {
        // Format dates and times for display
        foreach ($items as &$item) {
            // Set defaults for any NULL values
            $item['start_time'] = $item['start_time'] ?? '00:00:00';
            $item['end_time'] = $item['end_time'] ?? '00:00:00';
            $item['payment_date'] = $item['payment_date'] ?? date('Y-m-d');
            $item['amount'] = $item['amount'] ?? 0;
            $item['program_name'] = $item['program_name'] ?? 'Unknown Service';
            $item['coach_name'] = $item['coach_name'] ?? 'N/A';
        }
        
        // Group all items by transaction
        $result = [
            'transaction_id' => $transaction_id,
            'items' => $items,
            'total_amount' => array_sum(array_column($items, 'amount'))
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
    }
} catch (PDOException $e) {
    error_log('Database error in get_receipt_details.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'error_details' => $e->getMessage()
    ]);
}
?>