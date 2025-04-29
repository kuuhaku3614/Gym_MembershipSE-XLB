<?php
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/expiry-notifications.class.php'); // Include the expiry notifications class
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; 
    $expiryNotificationsObj = new ExpiryNotifications(); // Create an instance of ExpiryNotifications
    
    // Get all expiry notifications with read status
    $allExpiryNotifications = $expiryNotificationsObj->getExpiryNotifications($current_user_id);
    
    // Split notifications by type
    $expiringNotifications = [];
    $expiredNotifications = [];
    $overduePendingNotifications = [];
    
    // Count for unread notifications
    $unreadExpiring = 0;
    $unreadExpired = 0;
    $unreadOverdue = 0;
    
    // Separate notifications by type (expired, expiring, overdue pending)
    foreach ($allExpiryNotifications as $notification) {
        if (strpos($notification['type'], 'overdue_pending') !== false) {
            $overduePendingNotifications[] = $notification;
            if (!$notification['is_read']) {
                $unreadOverdue++;
            }
        } elseif (strpos($notification['type'], 'expiring') !== false) {
            // Fix messages with "-0 days" or "0 days" to say "today" instead
            if (strpos($notification['message'], 'expires in -0 days') !== false) {
                $notification['message'] = str_replace('expires in -0 days', 'expires today', $notification['message']);
            } else if (strpos($notification['message'], 'expires in 0 days') !== false) {
                $notification['message'] = str_replace('expires in 0 days', 'expires today', $notification['message']);
            }
            $expiringNotifications[] = $notification;
            
            // Count unread
            if (!$notification['is_read']) {
                $unreadExpiring++;
            }
        } else {
            $expiredNotifications[] = $notification;
            
            // Count unread
            if (!$notification['is_read']) {
                $unreadExpired++;
            }
        }
    }
    
    // Count notifications by type
    $expiringCount = count($expiringNotifications);
    $expiredCount = count($expiredNotifications);
    $overdueCount = count($overduePendingNotifications);
    $totalCount = $expiringCount + $expiredCount + $overdueCount;
    $unreadTotal = $unreadExpiring + $unreadExpired + $unreadOverdue;
    
    // Combine lists: Overdue Pending first, then expired, then expiring
    $combinedNotifications = array_merge($overduePendingNotifications, $expiredNotifications, $expiringNotifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Notifications</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/notification.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Expiry Notifications</h2>
            <button id="markAllReadBtn" class="btn btn-outline-primary" <?php echo ($unreadTotal > 0) ? '' : 'disabled'; ?>>
                <i class="fas fa-check-double"></i> Mark All as Read
            </button>
        </div>

        <!-- Notification counts summary -->
        <div class="notification-counts">
            <div class="count-box count-overdue">
                <strong>Overdue Pending:</strong>
                <span><?php echo $overdueCount; ?></span>
                <?php if ($unreadOverdue > 0): ?>
                    <span class="unread-badge"><?php echo $unreadOverdue; ?></span>
                <?php endif; ?>
            </div>
            <div class="count-box count-expired">
                <strong>Expired:</strong>
                <span><?php echo $expiredCount; ?></span>
                <?php if ($unreadExpired > 0): ?>
                    <span class="unread-badge"><?php echo $unreadExpired; ?></span>
                <?php endif; ?>
            </div>
            <div class="count-box count-expiring">
                <strong>Expiring Soon:</strong>
                <span><?php echo $expiringCount; ?></span>
                <?php if ($unreadExpiring > 0): ?>
                    <span class="unread-badge"><?php echo $unreadExpiring; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Combined notifications list -->
        <div class="notification-container">
            <?php if (empty($combinedNotifications)): ?>
                <div class="alert alert-info">No notifications found</div>
            <?php else: ?>
                <?php foreach ($combinedNotifications as $notification): ?>
                    <?php 
                        if (strpos($notification['type'], 'overdue_pending') !== false) {
                            $statusClass = 'notification-overdue';
                            $statusLabel = 'Overdue Pending';
                            $statusIndicatorClass = 'overdue-indicator';
                        } elseif (strpos($notification['type'], 'expired') !== false) {
                            $statusClass = 'notification-expired';
                            $statusLabel = 'Expired';
                            $statusIndicatorClass = 'expired-indicator';
                        } else {
                            $statusClass = 'notification-expiring';
                            $statusLabel = 'Expiring Soon';
                            $statusIndicatorClass = 'expiring-indicator';
                        }
                        $unreadClass = !$notification['is_read'] ? 'notification-unread' : '';
                        
                        // For animation, add new notification class if needed
                        $newNotificationClass = '';
                        // Check if notification is very recent (within 5 minutes)
                        // This is just a placeholder - you would need to implement the logic
                        // based on your timestamp format and current time
                    ?>
                    <div class="notification-card <?php echo $statusClass; ?> <?php echo $unreadClass; ?> <?php echo $newNotificationClass; ?>" data-id="<?php echo htmlspecialchars($notification['id'] ?? $notification['transaction_id'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($notification['type']); ?>"
                         data-id="<?php echo htmlspecialchars($notification['id']); ?>"
                         data-type="<?php echo htmlspecialchars($notification['type']); ?>"
                         onclick="showExpiryDetails(<?php echo htmlspecialchars(json_encode($notification['details'])); ?>, 
                                   '<?php echo htmlspecialchars($notification['id']); ?>', 
                                   '<?php echo htmlspecialchars($notification['type']); ?>')">
                        <?php if (!$notification['is_read']): ?>
                            <span class="unread-dot" title="Unread notification"></span>
                        <?php endif; ?>
                        <div class="notification-header">
                            <h5 class="notification-title">
                                <span class="status-indicator <?php echo $statusIndicatorClass; ?>"><?php echo $statusLabel; ?></span>
                                <?php echo htmlspecialchars($notification['title']); ?>
                            </h5>
                            <span class="notification-time"><?php echo htmlspecialchars($notification['timestamp']); ?></span>
                        </div>
                        <div class="notification-body">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expiry Details Modal -->
    <div class="modal fade" id="expiryModal" tabindex="-1" aria-labelledby="expiryModalLabel" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expiryModalLabel">Expiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden fields for notification data -->
                    <input type="hidden" id="notificationId" name="notificationId">
                    <input type="hidden" id="notificationType" name="notificationType">
                    
                    <!-- Member Information -->
                    <div class="section-card mb-4">
                        <h6 class="section-title">Member Information</h6>
                        <p><strong>Name:</strong> <span id="expiryMemberName"></span></p>
                        <p><strong>Username:</strong> <span id="expiryUsername"></span></p>
                    </div>
                    
                    <!-- Membership/Rental Information -->
                    <div class="section-card mb-4">
                        <h6 class="section-title" id="expiryTypeTitle">Subscription Information</h6>
                        <p><strong>Type:</strong> <span id="expiryPlanName"></span></p>
                        <p><strong>Start Date:</strong> <span id="expiryStartDate"></span></p>
                        <p><strong>End Date:</strong> <span id="expiryEndDate"></span></p>
                        <p><strong>Amount:</strong> ₱ <span id="expiryAmount"></span></p>
                        <p id="expiryRemainingRow"><strong>Days Remaining:</strong> <span id="expiryDaysRemaining"></span></p>
                        <p id="expirySinceRow"><strong>Days Since Expiry:</strong> <span id="expiryDaysSince"></span></p>
                        <p id="expiryOverdueRow" style="display:none;"><strong>Days Overdue:</strong> <span id="expiryDaysOverdue"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelOverdueBtn" style="display:none;">Cancel</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show expiry details function
        function showExpiryDetails(details, notificationId, notificationType) {
            // Store notification data in hidden fields
            document.getElementById('notificationId').value = notificationId;
            document.getElementById('notificationType').value = notificationType;
            
            // Set member information
            document.getElementById('expiryMemberName').textContent = details.member_name;
            document.getElementById('expiryUsername').textContent = details.username;
            
            // Set plan/service information
            if (notificationType.includes('membership')) {
                document.getElementById('expiryTypeTitle').textContent = 'Membership Information';
                document.getElementById('expiryPlanName').textContent = details.plan_name;
            } else {
                document.getElementById('expiryTypeTitle').textContent = 'Rental Information';
                document.getElementById('expiryPlanName').textContent = details.service_name;
            }
            
            document.getElementById('expiryStartDate').textContent = details.start_date;
            document.getElementById('expiryAmount').textContent = details.amount;
            // Hide end date for walk-in
            var endDateRow = document.getElementById('expiryEndDate').parentElement;
            if ((details.plan_name && details.plan_name === 'Walk-in') || (details.service_name && details.service_name === 'Walk-in')) {
                endDateRow.style.display = 'none';
            } else {
                document.getElementById('expiryEndDate').textContent = details.end_date;
                endDateRow.style.display = '';
            }
            // Show/hide Cancel button for overdue pending
            var cancelBtn = document.getElementById('cancelOverdueBtn');
            if (notificationType.includes('overdue_pending')) {
                cancelBtn.style.display = '';
                cancelBtn.onclick = function() {
                    if (confirm('Are you sure you want to cancel and delete this overdue pending transaction?')) {
                        fetch('pages/notification/functions/delete-overdue-pending.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'transaction_id=' + encodeURIComponent(details.transaction_id) + '&type=' + encodeURIComponent(notificationType)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove notification card from UI
                                var card = document.querySelector('.notification-card[data-id="' + notificationId + '"][data-type="' + notificationType + '"]');
                                if (card) card.remove();
                                // Hide modal
                                const modal = bootstrap.Modal.getInstance(document.getElementById('expiryModal'));
                                if (modal) modal.hide();
                            } else {
                                alert('Failed to delete: ' + data.error);
                            }
                        })
                        .catch(err => alert('Error: ' + err));
                    }
                };
            } else {
                cancelBtn.style.display = 'none';
                cancelBtn.onclick = null;
            }
            // Show days remaining, days since expiry, or days overdue based on notification type
            if (notificationType.includes('overdue_pending')) {
                document.getElementById('expiryRemainingRow').style.display = 'none';
                document.getElementById('expirySinceRow').style.display = 'none';
                document.getElementById('expiryOverdueRow').style.display = '';
                document.getElementById('expiryDaysOverdue').textContent = details.days_overdue;
            } else if (notificationType.includes('expiring')) {
                document.getElementById('expiryRemainingRow').style.display = '';
                document.getElementById('expirySinceRow').style.display = 'none';
                document.getElementById('expiryOverdueRow').style.display = 'none';
                // Update the display for 0 days remaining
                if (details.days_remaining == 0) {
                    document.getElementById('expiryDaysRemaining').textContent = 'Today';
                } else {
                    document.getElementById('expiryDaysRemaining').textContent = details.days_remaining;
                }
            } else {
                document.getElementById('expiryRemainingRow').style.display = 'none';
                document.getElementById('expirySinceRow').style.display = '';
                document.getElementById('expiryOverdueRow').style.display = 'none';
                document.getElementById('expiryDaysSince').textContent = details.days_since;
            }
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('expiryModal'));
            modal.show();
            
            // Mark notification as read
            markNotificationAsRead(notificationId, notificationType);
        }
        
        // Function to mark a notification as read
        function markNotificationAsRead(notificationId, notificationType) {
            // Don't proceed if we don't have valid ID or type
            if (!notificationId || !notificationType) return;
            
            // Create form data
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            formData.append('notification_type', notificationType);
            
            // Send AJAX request to mark as read
            fetch('pages/notification/mark-notification-read.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI to show the notification as read
                    const notificationCard = document.querySelector(`.notification-card[data-id="${notificationId}"][data-type="${notificationType}"]`);
                    if (notificationCard) {
                        notificationCard.classList.remove('notification-unread');
                        const unreadDot = notificationCard.querySelector('.unread-dot');
                        if (unreadDot) {
                            unreadDot.remove();
                        }
                        
                        // Update notification counters
                        updateNotificationCounters();
                        
                        // Update navbar badges if the updateNotificationBadges function exists
                        if (typeof window.updateNotificationBadges === 'function') {
                            window.updateNotificationBadges();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        // Function to update notification counters
        function updateNotificationCounters() {
            // Count unread notifications
            const unreadNotifications = document.querySelectorAll('.notification-unread');
            const unreadExpiring = document.querySelectorAll('.notification-unread.notification-expiring').length;
            const unreadExpired = document.querySelectorAll('.notification-unread.notification-expired').length;
            const totalUnread = unreadExpiring + unreadExpired;
            
            // Update the unread badges
            const expiringBadge = document.querySelector('.count-box.count-expiring .unread-badge');
            const expiredBadge = document.querySelector('.count-box.count-expired .unread-badge');
            const totalBadge = document.querySelector('.count-box:not(.count-expired):not(.count-expiring) .unread-badge');
            
            // Update or remove badges based on counts
            updateBadge(expiringBadge, unreadExpiring, '.count-box.count-expiring');
            updateBadge(expiredBadge, unreadExpired, '.count-box.count-expired');
            updateBadge(totalBadge, totalUnread, '.count-box:not(.count-expired):not(.count-expiring)');
            
            // Update Mark All as Read button state
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) {
                markAllReadBtn.disabled = totalUnread === 0;
            }
        }
        
        // Helper function to update or create badge
        function updateBadge(badge, count, containerSelector) {
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const container = document.querySelector(containerSelector);
                    if (container) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'unread-badge';
                        newBadge.textContent = count;
                        container.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.remove();
            }
        }
        
        $(document).ready(function() {
    // Initialize tooltips if needed
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Add event listener to the "Mark All as Read" button
    $('#markAllReadBtn').on('click', function(e) {
        e.preventDefault();
        console.log("Mark All Read button clicked"); // Debug
        
        // Add loading state to button
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        // Send AJAX request to mark all as read
        $.ajax({
            url: 'pages/notification/mark-all-notifications-read.php',
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    // Update UI to show all notifications as read
                    $('.notification-unread').removeClass('notification-unread')
                        .find('.unread-dot').remove();
                    
                    // Remove all unread badges
                    $('.unread-badge').remove();
                    
                    // Reset button state
                    $btn.html(originalHtml).prop('disabled', true);
                    
                    // Update navbar badges if the updateNotificationBadges function exists
                    if (typeof window.updateNotificationBadges === 'function') {
                        window.updateNotificationBadges();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking all notifications as read:', error);
                // Reset button state
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
        
        document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Add event listener to the "Mark All as Read" button
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent any default action
                console.log("Mark All Read button clicked"); // Debug line
                markAllAsRead();
            });
        }
    });
    </script>
</body>
</html>