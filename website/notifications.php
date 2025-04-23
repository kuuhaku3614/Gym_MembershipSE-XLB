<?php
// Set default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/notification_queries.php';
require_once __DIR__ . '/coach_requests.php';

// Ensure user is logged in
if (!function_exists('requireLogin')) {
    // Function to ensure login
    function requireLogin() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: ../login/login.php');
            exit(); // Prevent further execution
        }
    }
}

// Ensure user is logged in
requireLogin();
$user_id = $_SESSION['user_id'];

// Get database instance from config.php
$database = new Database();

// Get user role for coach requests
global $pdo;
$query = "SELECT role_id FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle mark all as read
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 1) {
    markAllNotificationsAsRead($database, $user_id);
    header('Location: notifications.php');
    exit();
}
include('includes/header.php');
// Get notifications with read status - Use this single function to get all notifications
$notifications = getNotificationsWithReadStatus($database, $user_id);
$transaction_result = $notifications['transactions'];
$membership_result = $notifications['memberships'];
$announcement_result = $notifications['announcements'];
$program_confirmation_result = function_exists('getProgramConfirmationNotifications') && isset($notifications['program_confirmations']) ? $notifications['program_confirmations'] : [];
$program_cancellation_result = function_exists('getProgramCancellationNotifications') && isset($notifications['program_cancellations']) ? $notifications['program_cancellations'] : [];
$cancelled_sessions_result = isset($notifications['cancelled_sessions']) ? $notifications['cancelled_sessions'] : [];
$completed_sessions_result = isset($notifications['completed_sessions']) ? $notifications['completed_sessions'] : [];
$receipt_notifications = isset($notifications['transaction_receipts']) ? $notifications['transaction_receipts'] : [];

// Get coach program requests if user is a coach
$coach_requests = [];
if ($user && $user['role_id'] == 4) { // Role 4 for coaches
    $coachRequests = new CoachRequests($database);
    $coach_requests = $coachRequests->getPendingRequests($user_id);
}

// Merge all notifications into a single array for sorting
$all_notifications = [];

// Process transaction receipt notifications
foreach ($receipt_notifications as $notification) {
    $notification['type'] = 'transaction_receipts';
    $notification['id'] = $notification['transaction_id'];
    $notification['date'] = $notification['created_at'];
    $notification['title'] = 'Payment Receipt';
    
    // Format the date and time for better display
    $formatted_date = date('F j, Y', strtotime($notification['session_date']));
    $formatted_start = date('g:i A', strtotime($notification['start_time']));
    $formatted_end = date('g:i A', strtotime($notification['end_time']));
    $formatted_payment_date = date('F j, Y', strtotime($notification['payment_date']));
    
    // Check if this is a grouped notification (multiple transactions)
    $transaction_count = isset($notification['transaction_ids']) ? count($notification['transaction_ids']) : 1;
    $amount_text = isset($notification['total_amount']) ? 
        'Total amount paid: ₱' . number_format($notification['total_amount'], 2) : 
        'Amount paid: ₱' . number_format($notification['amount'], 2);
    
    // For the payment receipt notifications
    $notification['message'] = '';

    // Get programs and sessions count by type
    if (isset($notification['transaction_ids']) && count($notification['transaction_ids']) > 0) {
        // For all transactions, we need to fetch the details from DB
        $programs = getTransactionProgramDetails($database, $notification['transaction_ids_list']);
        
        // Group by program type and schedule type
        $programsByType = [];
        $coachNames = [];
        
        foreach ($programs as $program) {
            $coachNames[$program['coach_id']] = $program['coach_name'];
            
            $key = $program['program_name'] . '-' . $program['schedule_type'];
            if (!isset($programsByType[$key])) {
                $programsByType[$key] = [
                    'name' => $program['program_name'],
                    'type' => $program['schedule_type'],
                    'count' => 0
                ];
            }
            
            $programsByType[$key]['count'] += $program['session_count'];
        }
        
        // Build the program parts of the message
        $programParts = [];
        foreach ($programsByType as $program) {
            $programParts[] = htmlspecialchars($program['name']) . ' - ' . 
                ucfirst(htmlspecialchars($program['type'])) . 
                ' (' . $program['count'] . ' ' . ($program['count'] > 1 ? 'sessions' : 'session') . ' paid)';
        }
        
        // Build the coach part of the message
        $coachPart = '';
        if (count($coachNames) == 1) {
            $coachPart = 'with Coach ' . htmlspecialchars(reset($coachNames));
        } else {
            $coaches = [];
            foreach ($coachNames as $coach) {
                $coaches[] = 'Coach ' . htmlspecialchars($coach);
            }
            $coachPart = 'with ' . implode(' and ', $coaches);
        }
        
        // Combine into the final message
        $notification['message'] = 'Payment receipt for ' . implode(' and ', $programParts) . ' ' . 
            $coachPart . '. Total amount paid: ₱' . number_format($notification['total_amount'], 2) . 
            ' on ' . date('F j, Y', strtotime($notification['payment_date'])) . '.';
    } else {
        // Fallback for any issues
        $notification['message'] = 'Payment receipt for ' . htmlspecialchars($notification['program_name']) . ' - ' . 
            ucfirst(htmlspecialchars($notification['schedule_type'])) . ' (1 session paid) with Coach ' . 
            htmlspecialchars($notification['coach_name']) . '. Amount paid: ₱' . 
            number_format($notification['amount'], 2) . ' on ' . 
            date('F j, Y', strtotime($notification['payment_date'])) . '.';
    }
    
    // If multiple transactions, add a note
    if ($transaction_count > 1) {
        $notification['message'] .= ' <span class="text-muted">(' . $transaction_count . ' payments combined)</span>';
    }
    
    $notification['is_read'] = in_array($notification['transaction_id'], $_SESSION['read_notifications']['transaction_receipts']);
    
    $notification['class'] = 'list-group-item-success';
    $all_notifications[] = $notification;
}

foreach ($transaction_result as $notification) {
    $notification['type'] = 'transactions';
    $notification['date'] = $notification['created_at'];
    $notification['title'] = 'Transaction Update';
    $notification['message'] = 'Your transaction with ID <strong>' . htmlspecialchars($notification['id']) . '</strong> has been <strong>' . htmlspecialchars($notification['status']) . '</strong>.';
    $notification['class'] = '';
    $all_notifications[] = $notification;
}

foreach ($membership_result as $notification) {
    $notification['type'] = 'memberships';
    $notification['date'] = $notification['end_date']; // Use end_date for sorting
    $notification['title'] = 'Membership ' . ucfirst($notification['status'] == 'expired' ? 'expired' : 'expiring soon');
    $status_text = ($notification['status'] == 'expired') ? 'expired' : 'expiring soon';
    $notification['message'] = 'Your <strong>' . htmlspecialchars($notification['plan_name']) . '</strong> membership is ' . htmlspecialchars($status_text) . '. ' .
        ($notification['status'] == 'expiring' ? 'Please renew your membership to continue enjoying our services.' :
        'Please visit our front desk or select on the service page to renew your membership.');
    $notification['class'] = ($notification['status'] == 'expired') ? 'list-group-item-danger' : 'list-group-item-warning';
    $all_notifications[] = $notification;
}

foreach ($announcement_result as $notification) {
    $notification['type'] = 'announcements';
    $notification['date'] = $notification['created_at'];
    $notification['title'] = htmlspecialchars($notification['announcement_type']) . ' Announcement';
    $notification['message'] = nl2br(htmlspecialchars($notification['message']));
    $notification['class'] = 'list-group-item-info';
    $all_notifications[] = $notification;
}

// Add program confirmation notifications
foreach ($program_confirmation_result as $notification) {
    $notification['type'] = 'program_confirmations';
    $notification['id'] = $notification['notification_id']; // notification_id is transaction_id
    $notification['date'] = $notification['created_at'];
    // Determine if this user is coach or member for this notification
    if (isset($notification['coach_id']) && isset($notification['user_id'])) {
        if ($user_id == $notification['coach_id']) {
            $notification['title'] = 'Program Request Confirmed';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'You have confirmed the <strong>' . htmlspecialchars($programs_str) . '</strong> program request for <strong>' . htmlspecialchars($notification['member_name']) . '</strong>.';
        } elseif ($user_id == $notification['user_id']) {
            $notification['title'] = 'Program Confirmed';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'You are now enrolled in the <strong>' . htmlspecialchars($programs_str) . '</strong> program(s) of <strong>' . htmlspecialchars($notification['coach_name']) . '</strong>.';
        } else {
            $notification['title'] = 'Program Confirmed';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'A program <strong>' . htmlspecialchars($programs_str) . '</strong> was confirmed.';
        }
    } else {
        $notification['title'] = 'Program Confirmed';
        $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
        $programs_str = '';
        if (count($programs) > 1) {
            $last = array_pop($programs);
            $programs_str = implode(', ', $programs) . ' and ' . $last;
        } else {
            $programs_str = $programs[0];
        }
        $notification['message'] = 'Your program(s) <strong>' . htmlspecialchars($programs_str) . '</strong> has/have been <strong>confirmed</strong>.';
    }
    $notification['class'] = 'list-group-item-success';
    $all_notifications[] = $notification;
}

// Add program cancellation notifications
foreach ($program_cancellation_result as $notification) {
    $notification['type'] = 'program_cancellations';
    $notification['id'] = $notification['notification_id']; // notification_id is transaction_id
    $notification['date'] = isset($notification['updated_at']) ? $notification['updated_at'] : $notification['created_at'];
    // Determine if this user is coach or member for this notification
    if (isset($notification['coach_id']) && isset($notification['user_id'])) {
        if ($user_id == $notification['coach_id']) {
            $notification['title'] = 'Program Request Cancelled';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'You have cancelled the <strong>' . htmlspecialchars($programs_str) . '</strong> program request for <strong>' . htmlspecialchars($notification['member_name']) . '</strong>.';
        } elseif ($user_id == $notification['user_id']) {
            $notification['title'] = 'Program Cancelled';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'Your request to avail the <strong>' . htmlspecialchars($programs_str) . '</strong> program(s) of <strong>' . htmlspecialchars($notification['coach_name']) . '</strong> has been <strong>declined</strong>.';
        } else {
            $notification['title'] = 'Program Cancelled';
            $programs = array_unique(array_map('trim', explode("\n", $notification['programs'])));
            $programs_str = '';
            if (count($programs) > 1) {
                $last = array_pop($programs);
                $programs_str = implode(', ', $programs) . ' and ' . $last;
            } else {
                $programs_str = $programs[0];
            }
            $notification['message'] = 'A program request, <strong>' . htmlspecialchars($programs_str) . '</strong> was cancelled.';
        }
    } else {
        $notification['title'] = 'Program Cancelled';
        $notification['message'] = 'A program request, <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ')</strong> was cancelled.';
    }
    $notification['class'] = 'list-group-item-danger';
    $all_notifications[] = $notification;
}

// Process cancelled sessions
foreach ($cancelled_sessions_result as $notification) {
    $notification['type'] = 'cancelled_sessions';
    $notification['id'] = $notification['schedule_id'];
    $notification['date'] = $notification['created_at'] ?? $notification['date'];
    $notification['title'] = 'Session Cancelled';
    $formatted_date = date('F j, Y', strtotime($notification['date']));
    $formatted_start = date('g:i A', strtotime($notification['start_time']));
    $formatted_end = date('g:i A', strtotime($notification['end_time']));
    $notification['message'] = 'Your <strong>' . htmlspecialchars($notification['program_name']) . ' - ' . 
        ucfirst(htmlspecialchars($notification['session_type'])) . '</strong> session on ' . 
        $formatted_date . ' from ' . $formatted_start . ' to ' . $formatted_end . 
        ' with ' . htmlspecialchars($notification['coach_username']) . ' has been <strong>cancelled</strong>.';
    if (!empty($notification['cancellation_reason'])) {
        $notification['message'] .= ' Reason: ' . htmlspecialchars($notification['cancellation_reason']);
    }
    $notification['class'] = 'list-group-item-danger';
    $all_notifications[] = $notification;
}

// Process completed sessions
foreach ($completed_sessions_result as $notification) {
    $notification['type'] = 'completed_sessions';
    $notification['id'] = $notification['schedule_id'];
    $notification['date'] = $notification['created_at'] ?? $notification['date'];
    $notification['title'] = 'Session Completed';
    $formatted_date = date('F j, Y', strtotime($notification['date']));
    $formatted_start = date('g:i A', strtotime($notification['start_time']));
    $formatted_end = date('g:i A', strtotime($notification['end_time']));
    $notification['message'] = 'Your <strong>' . htmlspecialchars($notification['program_name']) . ' - ' . 
        ucfirst(htmlspecialchars($notification['session_type'])) . '</strong> session on ' . 
        $formatted_date . ' from ' . $formatted_start . ' to ' . $formatted_end . 
        ' with ' . htmlspecialchars($notification['coach_username']) . ' has been <strong>completed</strong>.';
    $notification['class'] = 'list-group-item-success';
    $all_notifications[] = $notification;
}

// Sort notifications by date (newest first)
usort($all_notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Check if user is logged in and has valid session data
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);

// Handle logout logic
if (isset($_GET['logout'])) {
    // Clear all session data
    session_unset();
    session_destroy();
    header('Location: ../website/website.php');
    exit();
}

function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}
?>

<style>
    html{
        background-color: transparent;
    }
    body{
        height: 100vh;
        background-color: #efefef!important;
    }
    .home-navbar{
        background-color: red;
        position: fixed;
        border-radius: 0;
    }
    .main-content{
       padding-top: 100px;
    }
    .list-group{
        overflow-y: auto;
    }
    .list-group-item-warning {
        background-color: #fff3cd;
    }
    .list-group-item-danger {
        background-color: #f8d7da;
    }
    .list-group-item-info {
        background-color: #d1ecf1;
    }
    .list-group-item-primary {
        background-color: #cfe2ff;
    }
    .notification-unread {
        border-left: 4px solid #0d6efd;
        position: relative;
    }
    .notification-unread::before {
        content: "NEW";
        position: absolute;
        top: 30px;
        right: 10px;
        background-color: #0d6efd;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 10px;
    }
    .btn-mark-all {
        margin-left: 10px;
    }
    .notification-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    .notification-timestamp {
        font-size: 0.85em;
        color: #6c757d;
        margin-top: 8px;
    }
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    @media screen and (max-width: 480px) {
        .list-group{
            height: 60vh;
        }
        h2{
            font-size: 2em!important;
            text-align: center;
        }
        #markAllRead{
            font-size: 0.7em;
            padding: 6px 10px;
        }
    }
        
</style>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/browse_services.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Main Content Section -->
    <section class="main-content">
        <div class="container mt-4">
            <!-- Header with Mark All Read Button -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2 class="fw-bolder">Notifications</h2>
                </div>
                <div class="col-md-4 text-end">
                    <button id="markAllRead" class="btn btn-outline-secondary">Mark all as read</button>
                </div>
            </div>
            
            <!-- Notifications List -->
            <div class="list-group">
                <?php if ($user && $user['role_id'] == 4): ?>
                    <?php foreach ($coach_requests as $request): ?>
                        <div class="list-group-item list-group-item-primary list-group-item-action flex-column align-items-start" 
                             data-bs-toggle="modal" 
                             data-bs-target="#scheduleModal" 
                             data-subscription-id="<?= htmlspecialchars($request['subscription_id']) ?>"
                             data-member-name="<?= htmlspecialchars($request['member_name']) ?>"
                             data-contact="<?= htmlspecialchars($request['contact']) ?>"
                             data-program="<?= htmlspecialchars($request['programs']) ?>">

                            <div class="notification-header">
                                <h5 class="mb-1">New Program Request</h5>
                                <small class="text-muted">
                                    <?php 
                                    $date_obj = new DateTime($request['created_at']);
                                    $now = new DateTime();
                                    $diff = $date_obj->diff($now);
                                    
                                    if ($diff->days == 0) {
                                        if ($diff->h == 0) {
                                            if ($diff->i == 0) {
                                                echo "Just now";
                                            } else {
                                                echo $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
                                            }
                                        } else {
                                            echo $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
                                        }
                                    } elseif ($diff->days == 1) {
                                        echo "Yesterday";
                                    } elseif ($diff->days < 7) {
                                        echo $diff->days . " days ago";
                                    } else {
                                        echo $date_obj->format('M j, Y');
                                    }
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1">
                            <strong><?= htmlspecialchars($request['member_name']) ?></strong> has requested to join your program
                                <strong><?php
        // Deduplicate by coach_program_type_id if available, else by name+type
        $unique_programs = [];
        if (isset($request['programs_details']) && is_array($request['programs_details'])) {
            foreach ($request['programs_details'] as $prog) {
                $key = isset($prog['coach_program_type_id']) ? $prog['coach_program_type_id'] : ($prog['program_name'] . '|' . $prog['schedule_type']);
                $unique_programs[$key] = $prog['program_name'] . ' (' . $prog['schedule_type'] . ')';
            }
        } else {
            // fallback: old logic
            $programs = explode("\n", $request['programs']);
            foreach ($programs as $p) {
                $unique_programs[trim($p)] = trim($p);
            }
        }
        $values = array_values($unique_programs);
        if (count($values) > 1) {
            $lastProgram = array_pop($values);
            echo implode(', ', $values) . ' and ' . $lastProgram;
        } else {
            echo $values[0];
        }
    ?></strong>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($all_notifications) && (!$user || $user['role_id'] != 4 || empty($coach_requests))): ?>
                    <div class="list-group-item text-center">
                        <p>You have no notifications at this time.</p>
                    </div>
                <?php elseif (!empty($all_notifications)): ?>
                    <?php foreach ($all_notifications as $notification): ?>
        <?php
            // Add subscription_id for program confirmations/cancellations if missing
            if ((($notification['type'] === 'program_confirmations' || $notification['type'] === 'program_cancellations')) && empty($notification['subscription_id'])) {
                if (!empty($notification['subscription_ids'])) {
                    $subs = explode(',', $notification['subscription_ids']);
                    $notification['subscription_id'] = $subs[0];
                } else {
                    $notification['subscription_id'] = isset($notification['id']) ? $notification['id'] : (isset($notification['notification_id']) ? $notification['notification_id'] : null);
                }
            }
        ?>
        <div class="list-group-item <?= $notification['class'] ?> list-group-item-action flex-column align-items-start <?= !$notification['is_read'] ? 'notification-unread' : '' ?>"
            data-notification-type="<?= $notification['type'] ?>" 
            data-notification-id="<?= htmlspecialchars($notification['id']) ?>"
            <?php if ($notification['type'] === 'transaction_receipts' && isset($notification['transaction_ids_list'])): ?>
                data-transaction-ids="<?= htmlspecialchars($notification['transaction_ids_list']) ?>"
            <?php endif; ?>
            <?php if (!empty($notification['subscription_id'])): ?>
                data-subscription-id="<?= htmlspecialchars($notification['subscription_id']) ?>"
            <?php endif; ?>
        >
                <div class="notification-header">
                    <h5 class="mb-1 <?= $notification['type'] === 'transactions' ? 'text-success' : ($notification['type'] === 'announcements' ? 'text-primary' : '') ?>"><?= $notification['title'] ?></h5>
                    <small class="text-muted">
                        <?php 
                        // Format date to be more human-readable
                        $date_obj = new DateTime($notification['date']);
                        $now = new DateTime();
                        $diff = $date_obj->diff($now);
                        
                        if ($diff->days == 0) {
                            if ($diff->h == 0) {
                                if ($diff->i == 0) {
                                    echo "Just now";
                                } else {
                                    echo $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
                                }
                            } else {
                                echo $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
                            }
                        } elseif ($diff->days == 1) {
                            echo "Yesterday";
                        } elseif ($diff->days < 7) {
                            echo $diff->days . " days ago";
                        } else {
                            echo $date_obj->format('M j, Y');
                        }
                        ?>
                    </small>
                </div>
                
                <p class="mb-1"><?= $notification['message'] ?></p>

                <?php if ($notification['type'] === 'memberships'): ?>
                    <div class="notification-timestamp">
                        Original start date: <?= htmlspecialchars($notification['start_date']) ?>
                    </div>
                <?php elseif ($notification['type'] === 'announcements'): ?>
                    <div class="notification-timestamp">
                        Event date: <?= htmlspecialchars($notification['applied_date']) ?> at <?= htmlspecialchars($notification['applied_time']) ?>
                    </div>
                <?php endif; ?>
            </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- MODALS -->
    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Program Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="memberDetails"></div>
                    <div id="scheduleDetails" class="list-group"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelRequest">Cancel Request</button>
                    <button type="button" class="btn btn-primary" id="confirmRequest">Confirm Request</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered justify-content-center">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                    <small id="modalDate" class="text-muted"></small>
                    <div id="modalReceiptDownloads" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Modal -->
    <div class="modal fade" id="sessionModal" tabindex="-1" aria-labelledby="sessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sessionModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="sessionDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Confirmation/Cancellation Notification Modal -->
    <div class="modal fade" id="programNotifModal" tabindex="-1" aria-labelledby="programNotifModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="programNotifModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="programNotifModalBody">
                    <div id="programNotifMessage"></div>
                    <div id="programNotifScheduleDetails" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Handle program request click
        $('#scheduleModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var subscriptionId = button.data('subscription-id');
            var memberName = button.data('member-name');
            var memberContact = button.data('contact');
            var programName = button.data('program');
            console.log('Subscription ID:', subscriptionId);
            var modal = $(this);

            // Update modal title and show member details
            // We'll deduplicate program names by coach_program_type_id after fetching schedules
            $('#memberDetails').html(`
                <p class="mb-3">${memberName} has requested to join your program(s):</p>
                <ul class="mb-3" id="programListHolder"></ul>
                <p class="text-muted small mb-4">Contact: ${memberContact}</p>
                <h6 class="mb-3">Schedule:</h6>
            `);

            // Clear previous content and show loading
            $('#scheduleDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading schedules...</p></div>');

            // Load schedules
            $.ajax({
                url: 'get_program_schedules.php',
                method: 'POST',
                data: { subscription_id: subscriptionId },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX Response:', response);
                    if (response && response.success && response.schedules && response.schedules.length > 0) {
                        // Deduplicate program names by coach_program_type_id for member details
                        var uniquePrograms = {};
                        response.schedules.forEach(function(sch) {
                            // Use a composite key: program_name + schedule_type + coach_program_type_id (if available)
                            // If coach_program_type_id is not present, fallback to program_name + schedule_type
                            var key = (sch.coach_program_type_id ? sch.coach_program_type_id : sch.program_name + '|' + sch.schedule_type);
                            if (!uniquePrograms[key]) {
                                uniquePrograms[key] = sch.program_name + ' (' + sch.schedule_type + ')';
                            }
                        });
                        var programListHtml = Object.values(uniquePrograms).map(p => `<li>${p}</li>`).join('');
                        $('#programListHolder').html('<strong>' + programListHtml + '</strong>');
                        // Group schedules by program_name, then by schedule_type, then by day/time
                        var grouped = {};
                        response.schedules.forEach(function(sch) {
                            if (!grouped[sch.program_name]) grouped[sch.program_name] = {};
                            if (!grouped[sch.program_name][sch.schedule_type]) grouped[sch.program_name][sch.schedule_type] = {};
                            var key = sch.day + ' at ' + sch.formatted_time;
                            if (!grouped[sch.program_name][sch.schedule_type][key]) grouped[sch.program_name][sch.schedule_type][key] = [];
                            grouped[sch.program_name][sch.schedule_type][key].push(sch);
                        });
                        var content = '';
                        for (const program in grouped) {
                            content += `<div class="mb-3"><strong class="text-primary">${program}</strong>`;
                            for (const type in grouped[program]) {
                                for (const scheduleKey in grouped[program][type]) {
                                    const schedules = grouped[program][type][scheduleKey];
                                    content += `<div class="border rounded p-2 mb-2">
                                        <div><strong>Schedule:</strong> ${scheduleKey}</div>
                                        <div><strong>Type:</strong> ${type}</div>
                                        <div><strong>Dates:</strong><ul class="mb-1">`;
                                    schedules.forEach(function(sch) {
                                        content += `<li>${sch.formatted_date}</li>`;
                                    });
                                    content += `</ul></div>`;
                                    content += `</div>`;
                                }
                            }
                            content += `</div>`;
                        }
                        $('#scheduleDetails').html(content);
                    } else {
                        $('#scheduleDetails').html('<div class="text-center p-3">No schedules available</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    $('#scheduleDetails').html('<div class="text-center p-3 text-danger">Error loading schedules</div>');
                }
            });
        });

        // Individual notification click handler
        $('.list-group-item[data-notification-type]').click(function() {
            const type = $(this).data('notification-type');
            // Prevent default notification modal for program confirmations/cancellations
            if (type === 'program_confirmations' || type === 'program_cancellations') {
                return; // Handled by the dedicated handler
            }
            const id = $(this).data('notification-id');
            const element = $(this);
            
            if (type && id) {
                // Make AJAX call to mark notification as read
                $.ajax({
                    url: 'mark_notification_read.php',
                    type: 'POST',
                    data: {
                        type: type,
                        id: id
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                // Remove unread styling
                                element.removeClass('notification-unread');
                                
                                // Update notification count in header (if available)
                                const notificationBadge = $('.notification-badge');
                                const notificationBadge2 = $('.notification-badge2');
                                
                                if (notificationBadge.length > 0) {
                                    let count = parseInt(notificationBadge.text());
                                    if (count > 0) {
                                        count--;
                                        
                                        if (count === 0) {
                                            notificationBadge.hide();
                                            notificationBadge2.hide();
                                        } else {
                                            notificationBadge.text(count);
                                            notificationBadge2.text(count);
                                        }
                                    }
                                }
                            }
                        } catch (e) {
                            console.error("Error parsing response:", e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                    }
                });
            }
            
            // Show the notification modal
            const title = $(this).find('h5').text();
            const message = $(this).find('p').text();
            const date = $(this).find('small:first').text();
            
            $('#notificationModalLabel').text(title);
            $('#modalMessage').text(message);
            $('#modalDate').text(date);
            
            // Clear previous receipt download options
            $('#modalReceiptDownloads').empty();
             // Add receipt download options if this is a receipt notification
            if (type === 'transaction_receipts') {
                const transactionIds = $(this).data('transaction-ids') ? $(this).data('transaction-ids').toString().split(',') : [id];
                
                if (transactionIds.length > 1) {
                    let downloadsHtml = '<div class="mt-3"><h6>Download Receipts:</h6><div class="list-group">';
                    
                    transactionIds.forEach(function(tid) {
                        downloadsHtml += `<a href="download_receipt.php?transaction_id=${tid}" class="list-group-item list-group-item-action" target="_blank">
                            <i class="fas fa-file-invoice"></i> Receipt #${tid}
                        </a>`;
                    });
                    
                    downloadsHtml += '</div></div>';
                    $('#modalReceiptDownloads').html(downloadsHtml);
                } else {
                    $('#modalReceiptDownloads').html(`
                        <div class="d-grid gap-2 mt-3">
                            <a href="download_receipt.php?transaction_id=${id}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i> Download Receipt
                            </a>
                        </div>
                    `);
                }
            } else {
                $('#modalReceiptDownloads').empty();
            }
            
            $('#notificationModal').modal('show');

            
            $('#notificationModal').modal('show');
        });
        
        // Mark all as read button handler
        $('#markAllRead').click(function() {
            window.location.href = 'notifications.php?mark_read=1';
        });

        // Handle confirm/cancel buttons for program requests
        var currentSubscriptionId = null;

        $('#scheduleModal').on('show.bs.modal', function(event) {
            currentSubscriptionId = $(event.relatedTarget).data('subscription-id');
        });

        $('#cancelRequest').on('click', function() {
            var button = $(this);
            var modal = $('#scheduleModal');
            if (confirm('Are you sure you want to cancel this program request?')) {
                // Disable buttons and show loading state
                modal.find('.modal-footer button').prop('disabled', true);
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelling...');
                // Send AJAX request
                $.ajax({
                    url: 'coach_requests.php',
                    method: 'POST',
                    data: {
                        action: 'cancel_request',
                        subscription_id: currentSubscriptionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            modal.modal('hide');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            modal.find('.modal-footer button').prop('disabled', false);
                            button.text('Cancel Request');
                        }
                    },
                    error: function() {
                        alert('An error occurred while cancelling the request');
                        modal.find('.modal-footer button').prop('disabled', false);
                        button.text('Cancel Request');
                    }
                });
            }
        });

        $('#confirmRequest').on('click', function() {
            var button = $(this);
            var modal = $('#scheduleModal');

            if (confirm('Are you sure you want to confirm this program request?')) {
                // Disable buttons and show loading state
                modal.find('.modal-footer button').prop('disabled', true);
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Confirming...');

                // Send AJAX request
                $.ajax({
                    url: 'coach_requests.php',
                    method: 'POST',
                    data: { 
                        action: 'confirm_request',
                        subscription_id: currentSubscriptionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            alert(response.message);
                            // Close the modal
                            modal.modal('hide');
                            // Reload the page to update the list
                            location.reload();
                        } else {
                            // Show error message
                            alert('Error: ' + response.message);
                            // Reset button states
                            modal.find('.modal-footer button').prop('disabled', false);
                            button.text('Confirm Request');
                        }
                    },
                    error: function() {
                        // Show error message
                        alert('An error occurred while confirming the request');
                        // Reset button states
                        modal.find('.modal-footer button').prop('disabled', false);
                        button.text('Confirm Request');
                    }
                });
            }
        });

        // Handler for program confirmation/cancellation notifications
        $('.list-group-item[data-notification-type="program_confirmations"], .list-group-item[data-notification-type="program_cancellations"]').click(function(e) {
            e.stopPropagation(); // Prevent double modal opening
            const title = $(this).find('h5').text();
            const message = $(this).find('p.mb-1').html();
            const date = $(this).find('small:first').text();
            const subscriptionId = $(this).data('subscription-id');
            const type = $(this).data('notification-type');
            const id = $(this).data('notification-id');
            const element = $(this);

            // Mark as read
            if (type && id) {
                $.ajax({
                    url: 'mark_notification_read.php',
                    type: 'POST',
                    data: {
                        type: type,
                        id: id
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                element.removeClass('notification-unread');
                                
                                // Update badge counts
                                const notificationBadge = $('.notification-badge');
                                const notificationBadge2 = $('.notification-badge2');
                                
                                if (notificationBadge.length > 0) {
                                    let count = parseInt(notificationBadge.text());
                                    if (count > 0) {
                                        count--;
                                        
                                        if (count === 0) {
                                            notificationBadge.hide();
                                            notificationBadge2.hide();
                                        } else {
                                            notificationBadge.text(count);
                                            notificationBadge2.text(count);
                                        }
                                    }
                                }
                            }
                        } catch (e) {
                            console.error("Error parsing response:", e);
                        }
                    }
                });
            }

            $('#programNotifModalLabel').text(title);
            $('#programNotifMessage').html(`<div>${message}</div><small class="text-muted">${date}</small>`);
            $('#programNotifScheduleDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading schedules...</p></div>');
            $('#programNotifModal').modal('show');

            // Fetch and display schedules
            if (subscriptionId) {
                $.ajax({
                    url: 'get_program_schedules.php',
                    method: 'POST',
                    data: { subscription_id: subscriptionId },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success && response.schedules && response.schedules.length > 0) {
                            // Group and format schedules
                            var grouped = {};
                            response.schedules.forEach(function(sch) {
                                if (!grouped[sch.program_name]) grouped[sch.program_name] = {};
                                if (!grouped[sch.program_name][sch.schedule_type]) grouped[sch.program_name][sch.schedule_type] = {};
                                var key = sch.day + ' at ' + sch.formatted_time;
                                if (!grouped[sch.program_name][sch.schedule_type][key]) grouped[sch.program_name][sch.schedule_type][key] = [];
                                grouped[sch.program_name][sch.schedule_type][key].push(sch);
                            });
                            var content = '';
                            for (const program in grouped) {
                                content += `<div class="mb-3"><strong class="text-primary">${program}</strong>`;
                                for (const type in grouped[program]) {
                                    for (const scheduleKey in grouped[program][type]) {
                                        const schedules = grouped[program][type][scheduleKey];
                                        content += `<div class="border rounded p-2 mb-2">
                                            <div><strong>Schedule:</strong> ${scheduleKey}</div>
                                            <div><strong>Type:</strong> ${type}</div>
                                            <div><strong>Dates:</strong><ul class="mb-1">`;
                                        schedules.forEach(function(sch) {
                                            content += `<li>${sch.formatted_date}</li>`;
                                        });
                                        content += `</ul></div>`;
                                        content += `</div>`;
                                    }
                                }
                                content += `</div>`;
                            }
                            $('#programNotifScheduleDetails').html(content);
                        } else {
                            $('#programNotifScheduleDetails').html('<div class="text-center p-3">No schedules available</div>');
                        }
                    },
                    error: function() {
                        $('#programNotifScheduleDetails').html('<div class="text-center p-3 text-danger">Error loading schedules</div>');
                    }
                });
            } else {
                $('#programNotifScheduleDetails').html('<div class="text-center p-3 text-danger">No subscription ID found for this notification.</div>');
            }
        });
    });
    </script>
</body>
</html>