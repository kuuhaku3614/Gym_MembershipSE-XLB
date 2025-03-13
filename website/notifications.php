<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/notification_queries.php';

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
        height: 70vh;
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
    .notification-unread {
        border-left: 4px solid #0d6efd;
        position: relative;
    }
    .notification-unread::before {
        content: "NEW";
        position: absolute;
        top: 10px;
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
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Notifications</h2>
            </div>
            <div class="col-md-4 text-end">
                <button id="markAllRead" class="btn btn-outline-secondary">Mark all as read</button>
            </div>
        </div>
        <div class="list-group">
            <?php if (empty($all_notifications)): ?>
                <div class="list-group-item text-center">
                    <p>You have no notifications at this time.</p>
                </div>
            <?php else: ?>
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

<!-- Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog justify-content-center">
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
    // Individual notification click handler
    $('.list-group-item').click(function() {
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
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>