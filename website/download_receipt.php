<?php
// Set default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if transaction_id is provided
if (!isset($_GET['transaction_id']) || empty($_GET['transaction_id'])) {
    die('Transaction ID is required');
}

$transaction_id = (int)$_GET['transaction_id'];

// Get database instance
$database = new Database();
$pdo = $database->connect();

// First, get company information from website_content table
$query = "SELECT * FROM website_content WHERE section IN ('contact', 'welcome', 'schedule')";
$stmt = $pdo->prepare($query);
$stmt->execute();
$website_content = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process website content into a more usable format
$gym = [
    'gym_name' => '',
    'address' => '',
    'phone' => '',
    'email' => '',
    'days' => '',
    'hours' => ''
];

foreach ($website_content as $content) {
    switch ($content['section']) {
        case 'welcome':
            $gym['gym_name'] = $content['company_name'];
            break;
        case 'contact':
            $gym['address'] = $content['location'];
            $gym['phone'] = $content['phone'];
            $gym['email'] = $content['email'];
            break;
        case 'schedule':
            $gym['days'] = $content['days'];
            $gym['hours'] = $content['hours'];
            break;
    }
}

// First, get the base transaction details to identify the coach schedule ID
$query = "SELECT 
                t.id as transaction_id,
                ps.id as subscription_id,
                COALESCE(pss.coach_personal_schedule_id, pss.coach_group_schedule_id) as schedule_id,
                CASE 
                    WHEN pss.coach_personal_schedule_id IS NOT NULL THEN 'personal'
                    WHEN pss.coach_group_schedule_id IS NOT NULL THEN 'group'
                    ELSE NULL
                END as schedule_type
            FROM program_subscription_schedule pss
            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
            LEFT JOIN transactions t ON ps.transaction_id = t.id
            WHERE t.id = ? LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$transaction_id]);
$baseSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$baseSchedule) {
    die('Receipt not found');
}

// Now get all related items based on the schedule ID
$related_items = [];
$member_info = null;
$coach_info = null;
$total_amount = 0;

// Get all items with the same schedule ID (both personal and group)
$query = "SELECT 
                t.id as transaction_id,
                ps.id as subscription_id,
                CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as member_name,
                pd.user_id as member_id,
                p.program_name,
                cpt.type as program_type,
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
            FROM program_subscription_schedule pss
            JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
            JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
            JOIN programs p ON cpt.program_id = p.id
            JOIN users u ON ps.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            JOIN users coach_u ON cpt.coach_id = coach_u.id
            JOIN personal_details coach_pd ON coach_u.id = coach_pd.user_id
            LEFT JOIN transactions t ON ps.transaction_id = t.id
            WHERE (pss.coach_personal_schedule_id = ? OR pss.coach_group_schedule_id = ?)
            AND pss.is_paid = 1
            ORDER BY pss.updated_at, pss.date, pss.start_time";

$stmt = $pdo->prepare($query);
$scheduleId = $baseSchedule['schedule_id'];
$stmt->execute([$scheduleId, $scheduleId]);
$related_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($related_items)) {
    die('No related receipt items found');
}

// Group items by payment date
$grouped_items = [];
foreach ($related_items as $item) {
    $payment_date = $item['payment_date'];
    if (!isset($grouped_items[$payment_date])) {
        $grouped_items[$payment_date] = [
            'date' => $payment_date,
            'items' => [],
            'subtotal' => 0,
            'type' => $item['schedule_type']
        ];
    }
    
    $grouped_items[$payment_date]['items'][] = $item;
    $grouped_items[$payment_date]['subtotal'] += $item['amount'];
    $total_amount += $item['amount'];
    
    // Store member and coach info from the first item
    if (!$member_info) {
        $member_info = [
            'name' => $item['member_name'],
            'id' => $item['member_id']
        ];
    }
    
    if (!$coach_info) {
        $coach_info = [
            'name' => $item['coach_name']
        ];
    }
    
    // Mark notification as read
    if (!isset($_SESSION['read_notifications']['transaction_receipts'])) {
        $_SESSION['read_notifications']['transaction_receipts'] = [];
    }
    if (!in_array($item['transaction_id'], $_SESSION['read_notifications']['transaction_receipts'])) {
        $_SESSION['read_notifications']['transaction_receipts'][] = $item['transaction_id'];
    }
}

// Current date (for Receipt Date)
$current_date = date('M d, Y');

// Generate receipt number
$receipt_number = 'MR-' . rand(100, 999) . '-' . rand(10000, 99999);

// Require DOMPDF autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Use Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// Initialize dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

// Initialize dompdf
$dompdf = new Dompdf($options);

// HTML for the receipt
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .receipt {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .gym-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .gym-address, .gym-contact {
            font-size: 11px;
            margin: 2px 0;
        }
        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 8px 0;
        }
        .receipt-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .receipt-info, .member-info {
            width: 48%;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 100px;
        }
        .service-section {
            margin-bottom: 15px;
        }
        .service-header {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .service-date {
            margin-top: 10px;
            font-weight: bold;
        }
        .service-item {
            padding-left: 15px;
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .service-details {
            width: 85%;
        }
        .service-price {
            width: 15%;
            text-align: right;
        }
        .subtotal {
            text-align: right;
            margin: 5px 0;
            font-weight: bold;
        }
        .total-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
            font-weight: bold;
        }
        .payment-status {
            text-align: right;
            font-weight: bold;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }
        .thank-you {
            margin: 10px 0;
            font-weight: bold;
        }
        .contact-info {
            margin: 5px 0;
        }
        .proof-text {
            margin-top: 15px;
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="gym-name">' . htmlspecialchars($gym['gym_name']) . '</div>
            <div class="gym-address">' . htmlspecialchars($gym['address']) . '</div>
            <div class="gym-contact">Phone: ' . htmlspecialchars($gym['phone']) . '</div>
        </div>
        
        <div class="receipt-title">OFFICIAL RECEIPT</div>
        
        <div class="receipt-details">
            <div class="receipt-info">
                <div class="info-row">
                    <div class="info-label">Receipt No:</div>
                    <div>' . $receipt_number . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date Issued:</div>
                    <div>' . $current_date . '</div>
                </div>
            </div>
            
            <div class="member-info">
                <div class="info-row">
                    <div class="info-label">Member:</div>
                    <div>' . htmlspecialchars($member_info['name']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Coach:</div>
                    <div>' . htmlspecialchars($coach_info['name']) . '</div>
                </div>
            </div>
        </div>
        
        <div class="service-section">
            <div class="service-header">Services:</div>';

foreach ($grouped_items as $payment_date => $group) {
    $formatted_payment_date = date('M d, Y', strtotime($payment_date));
    
    $html .= '
            <div class="service-date">Payment Date: ' . $formatted_payment_date . '</div>
            <div class="service-header">Coachings <span style="font-weight: normal">(' . ucfirst($group['type']) . ')</span> (' . count($group['items']) . 'x sessions)</div>';
    
    foreach ($group['items'] as $item) {
        $formatted_session_date = date('M d, Y', strtotime($item['session_date']));
        $formatted_start = date('h:i A', strtotime($item['start_time']));
        $formatted_end = date('h:i A', strtotime($item['end_time']));
        
        $html .= '
            <div class="service-item">
                <div class="service-details">• ' . $formatted_session_date . ', ' . $formatted_start . ' - ' . $formatted_end . '</div>
                <div class="service-price">Php' . number_format($item['amount'], 2) . '</div>
            </div>';
    }
    
    $html .= '
            <div class="subtotal">Subtotal: Php' . number_format($group['subtotal'], 2) . '</div>';
}

$html .= '
        </div>
        
        <div class="total-section">
            <div>Total Amount:</div>
            <div>Php' . number_format($total_amount, 2) . '</div>
        </div>
        
        <div class="payment-status">Status: <span style="color: green;">Paid</span></div>
        
        <div class="footer">
            <div class="thank-you">Thank you for your business!</div>
            <div class="contact-info">For questions, please contact your coach or gym management.</div>
            <div class="proof-text">This receipt serves as proof of payment.</div>
        </div>
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size (A4)
$dompdf->setPaper('A4', 'portrait');

// Render PDF (generate)
$dompdf->render();

// Set filename
$filename = 'Receipt_' . $receipt_number . '.pdf';

// Output PDF to browser and force download
$dompdf->stream($filename, array('Attachment' => true));
exit;