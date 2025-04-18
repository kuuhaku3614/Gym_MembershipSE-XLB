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


// Get coach program requests if user is a coach
$coach_requests = [];
if ($user && $user['role_id'] == 4) { // Role 4 for coaches
    $coachRequests = new CoachRequests($database);
    $coach_requests = $coachRequests->getPendingRequests($user_id);
}

// Merge all notifications into a single array for sorting
$all_notifications = [];

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
            $notification['title'] = 'Program Request Confirmed (Coach)';
            $notification['message'] = 'You have confirmed the <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ') </strong> program request for <strong>' . htmlspecialchars($notification['member_name']) . '</strong>.';
        } elseif ($user_id == $notification['user_id']) {
            $notification['title'] = 'Program Confirmed';
            $notification['message'] = 'You are now enrolled in the <strong>' . htmlspecialchars($notification['program_name']) . '</strong> (' . htmlspecialchars($notification['program_type']) . ') program of <strong>' . htmlspecialchars($notification['coach_name']) . '</strong>.';
        } else {
            $notification['title'] = 'Program Confirmed';
            $notification['message'] = 'A program <strong>' . htmlspecialchars($notification['program_name']) . '</strong> (' . htmlspecialchars($notification['program_type']) . ') was confirmed.';
        }
    } else {
        $notification['title'] = 'Program Confirmed';
        $notification['message'] = 'Your program <strong>' . htmlspecialchars($notification['program_name']) . '</strong> (' . htmlspecialchars($notification['program_type']) . ') has been <strong>confirmed</strong>.';
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
            $notification['title'] = 'Program Request Cancelled (Coach)';
            $notification['message'] = 'You have cancelled the <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ')</strong> program request for <strong>' . htmlspecialchars($notification['member_name']) . '</strong>.';
        } elseif ($user_id == $notification['user_id']) {
            $notification['title'] = 'Program Cancelled';
            $notification['message'] = 'Your request to avail the <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ')</strong> program of <strong>' . htmlspecialchars($notification['coach_name']) . '</strong> has been <strong>declined</strong>.';
        } else {
            $notification['title'] = 'Program Cancelled';
            $notification['message'] = 'A program request, <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ')</strong> was cancelled.';
        }
    } else {
        $notification['title'] = 'Program Cancelled';
        $notification['message'] = 'A program request, <strong>' . htmlspecialchars($notification['program_name']) . ' (' . htmlspecialchars($notification['program_type']) . ')</strong> was cancelled.';
    }
    $notification['class'] = 'list-group-item-danger';
    $all_notifications[] = $notification;
}

// No longer need to add coach requests to notifications array

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
<link rel="stylesheet" href="../css/browse_services.css">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<section class="main-content">
    <div class="container mt-4">
        <!-- Single Notifications Container -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bolder">Notifications</h2>
            </div>
            <div class="col-md-4 text-end">
                <button id="markAllRead" class="btn btn-outline-secondary">Mark all as read</button>
            </div>
        </div>
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
                    <div class="list-group-item <?= $notification['class'] ?> list-group-item-action flex-column align-items-start <?= !$notification['is_read'] ? 'notification-unread' : '' ?>"
                        data-notification-type="<?= $notification['type'] ?>" 
                        data-notification-id="<?= htmlspecialchars($notification['id']) ?>">
                        
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
            </div>
        </div>
    </div>
</div>

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
        
        $('#notificationModal').modal('show');
    });
    
    // Mark all as read button handler
    $('#markAllRead').click(function() {
        window.location.href = 'notifications.php?mark_read=1';
    });

    // Handle confirm button click
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
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>