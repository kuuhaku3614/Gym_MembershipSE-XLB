<?php
require_once '../../../config.php';
require_once('../notification/functions/expiry-notifications.class.php');

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Function to get notification counts - similar to the navbar
function getNotificationCounts() {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    $counts = [
        'pending_transactions' => 0,
        'expiring' => 0,
        'expired' => 0,
        'total' => 0
    ];
    
    // Get counts from database for pending transactions
    $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
    if ($conn->connect_error) {
        return $counts;
    }
    
    // Pending Transactions Query
    $pending_transactions_sql = "
        SELECT COALESCE(COUNT(*), 0) AS pending_transactions 
        FROM transactions 
        WHERE status = 'pending'
    ";
    
    $result = $conn->query($pending_transactions_sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['pending_transactions'] = (int)$row['pending_transactions'];
    }
    
    $conn->close();
    
    // Use ExpiryNotifications class to get unread notifications counts
    if ($userId > 0) {
        try {
            $expiryNotificationsObj = new ExpiryNotifications();
            $unreadCounts = $expiryNotificationsObj->countUnreadNotifications($userId);
            
            $counts['expiring'] = $unreadCounts['expiring'];
            $counts['expired'] = $unreadCounts['expired'];
            $counts['total'] = $counts['pending_transactions'] + $unreadCounts['total'];
            
        } catch (Exception $e) {
            // Log error but continue
            error_log('Error getting notification counts: ' . $e->getMessage());
        }
    }
    
    return $counts;
}

// Get notification counts
$notificationCounts = getNotificationCounts();

// Initialize the ExpiryNotifications class to get actual notifications
$expiryNotifications = new ExpiryNotifications();
$notifications = $expiryNotifications->getExpiryNotifications();

// Fetch administrative announcements
$admin_sql = "
SELECT id, applied_date, applied_time, message 
FROM announcements 
WHERE is_active = 1 AND announcement_type = 'administrative'
ORDER BY applied_date DESC, applied_time DESC
";
$admin_stmt = $pdo->prepare($admin_sql);
$admin_stmt->execute();
$administrative_announcements = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch activity announcements
$activity_sql = "
SELECT id, applied_date, applied_time, message 
FROM announcements 
WHERE is_active = 1 AND announcement_type = 'activity'
ORDER BY applied_date DESC, applied_time DESC
";
$activity_stmt = $pdo->prepare($activity_sql);
$activity_stmt->execute();
$activity_announcements = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="css/dashboard.css">
<div class="dashboard-container">
    <!-- Stats Section -->
    <div class="stats-section">
        <?php
        // Active Memberships
        $active_memberships_sql = "
        SELECT COALESCE(COUNT(*), 0) AS active_memberships 
        FROM memberships 
        WHERE status = 'active'
        ";
        $active_memberships_stmt = $pdo->prepare($active_memberships_sql);
        $active_memberships_stmt->execute();
        $active_memberships = $active_memberships_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card stats-card">
            <div>
                <div class="stats-title">Current Members</div>
                <div class="stats-subtitle">(Active)</div>
            </div>
            <div class="stats-value <?= $active_memberships['active_memberships'] == 0 ? 'empty' : '' ?>">
                <?= $active_memberships['active_memberships'] ?: '0' ?>
            </div>
        </div>

        <?php
        // New Memberships This Month
        $new_memberships_sql = "
        SELECT COALESCE(COUNT(*), 0) AS new_memberships 
        FROM memberships 
        WHERE MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())
        ";
        $new_memberships_stmt = $pdo->prepare($new_memberships_sql);
        $new_memberships_stmt->execute();
        $new_memberships = $new_memberships_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card stats-card">
            <div>
                <div class="stats-title">New Members</div>
                <div class="stats-subtitle">this month</div>
            </div>
            <div class="stats-value <?= $new_memberships['new_memberships'] == 0 ? 'empty' : '' ?>">
                <?= $new_memberships['new_memberships'] ?: '0' ?>
            </div>
        </div>

        <?php
        // Member Users Count
        $member_users_sql = "
        SELECT COALESCE(COUNT(*), 0) AS member_users 
        FROM users 
        JOIN roles ON users.role_id = roles.id 
        WHERE roles.role_name = 'member'
        ";
        $member_users_stmt = $pdo->prepare($member_users_sql);
        $member_users_stmt->execute();
        $member_users = $member_users_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card stats-card">
            <div>
                <div class="stats-title">Website Accounts</div>
            </div>
            <div class="stats-value <?= $member_users['member_users'] == 0 ? 'empty' : '' ?>">
                <?= $member_users['member_users'] ?: '0' ?>
            </div>
        </div>

        <!-- Notification Card using the notification counts from getNotificationCounts function -->
        <div class="card notification-card stats-card" id="notificationCard">
            <div class="notification-badge" id="total-notifications-badge"><?= $notificationCounts['total'] ?: '0' ?></div>
            <i class="fas fa-bell fa-2x" style="color: var(--secondary-color)"></i>
            
            <!-- Notifications dropdown -->
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="table-header">Notifications</div>
                
                <?php if ($notificationCounts['total'] == 0): ?>
                    <div class="empty-state">No notifications available</div>
                <?php else: ?>
                    <?php if ($notificationCounts['pending_transactions'] > 0): ?>
                        <div class="notification-item pending">
                            <div class="notification-title">Pending Transactions</div>
                            <div class="notification-message">You have <?= $notificationCounts['pending_transactions'] ?> pending transaction(s) that need approval</div>
                            <div class="notification-time">View in Transactions section</div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($notifications as $notification): ?>
                        <?php 
                        $notificationClass = '';
                        if (strpos($notification['type'], 'expiring') !== false) {
                            $notificationClass = 'expiring';
                        } elseif (strpos($notification['type'], 'expired') !== false) {
                            $notificationClass = 'expired';
                        }
                        ?>
                        <div class="notification-item <?= $notificationClass ?>" 
                             data-id="<?= $notification['id'] ?>"
                             data-type="<?= $notification['type'] ?>">
                            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                            <div class="notification-time">Now</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Total Earnings with COALESCE
        $total_earnings_sql = "
        SELECT COALESCE(
            (SELECT COALESCE(SUM(amount), 0) FROM memberships) +
            (SELECT COALESCE(SUM(amount), 0) FROM program_subscription_schedule) +
            (SELECT COALESCE(SUM(amount), 0) FROM rental_subscriptions),
            0
        ) AS total_earnings;
        ";
        $total_earnings_stmt = $pdo->prepare($total_earnings_sql);
        $total_earnings_stmt->execute();
        $total_earnings = $total_earnings_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card stats-card">
            <div>
                <div class="stats-title">Total Earnings</div>
            </div>
            <div class="stats-value <?= $total_earnings['total_earnings'] == 0 ? 'empty' : '' ?>">
                ₱<?= number_format($total_earnings['total_earnings'], 2) ?>
            </div>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section">
        <div class="graph-container">
            <h2>Monthly Membership Growth in <?= date('Y') ?></h2>
            <div class="graph-wrapper">
                <?php
                $membership_data_sql = "
                SELECT 
                    MONTHNAME(created_at) AS month,
                    COUNT(*) AS total_memberships
                FROM memberships
                WHERE YEAR(created_at) = YEAR(CURDATE())
                GROUP BY MONTH(created_at)
                ORDER BY MONTH(created_at)
                ";
                $membership_data_stmt = $pdo->prepare($membership_data_sql);
                $membership_data_stmt->execute();
                $membership_data = $membership_data_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($membership_data)) {
                    echo '<div class="empty-state">No membership data available for ' . date('Y') . '</div>';
                } else {
                    $labels = array_column($membership_data, 'month');
                    $data = array_map('intval', array_column($membership_data, 'total_memberships'));
                ?>
                    <canvas id="membershipGraph"></canvas>
                    <script>
                        // Create a unique ID for the chart context to avoid conflicts
                        const membershipChartCtx = document.getElementById('membershipGraph').getContext('2d');
                        const membershipChart = new Chart(membershipChartCtx, {
                            type: 'line',
                            data: {
                                labels: <?= json_encode($labels) ?>,
                                datasets: [{
                                    label: 'Total Memberships',
                                    data: <?= json_encode($data) ?>,
                                    backgroundColor: ['rgba(255, 99, 132, 0.2)'],
                                    borderColor: ['rgba(255, 99, 132, 1)'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                            callback: function(value) {
                                                return Math.floor(value);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php } ?>
            </div>
        </div>

        <div class="stats-section">
            <?php
            // Member Check-ins
            $current_date = date('Y-m-d');
            $member_checkin_sql = "
            SELECT COALESCE(COUNT(*), 0) AS member_checkin 
            FROM attendance 
            WHERE date = :current_date
            AND status = 'checked_in'
            ";
            $member_checkin_stmt = $pdo->prepare($member_checkin_sql);
            $member_checkin_stmt->execute(['current_date' => $current_date]);
            $member_checkin = $member_checkin_stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Members Attendance</div>
                    <div class="stats-subtitle">Date: <?= $current_date ?></div>
                </div>
                <div class="stats-value <?= $member_checkin['member_checkin'] == 0 ? 'empty' : '' ?>">
                    <?= $member_checkin['member_checkin'] ?: '0' ?>
                </div>
            </div>

            <!-- Display expiring and expired memberships from notification counts -->
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Expiring Memberships</div>
                </div>
                <div class="stats-value <?= $notificationCounts['expiring'] == 0 ? 'empty' : '' ?>">
                    <?= $notificationCounts['expiring'] ?: '0' ?>
                </div>
            </div>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Expired Memberships</div>
                </div>
                <div class="stats-value <?= $notificationCounts['expired'] == 0 ? 'empty' : '' ?>">
                    <?= $notificationCounts['expired'] ?: '0' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="tables-section">
        <div class="table-container">
            <div class="table-header">Administrative Announcements</div>
            <?php if (empty($administrative_announcements)): ?>
                <div class="empty-state">No administrative announcements available</div>
            <?php else: ?>
                <table id="administrativeAnnouncementsTable" class="display" width="100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($administrative_announcements as $announcement): ?>
                            <tr>
                                <td><?= date('F d, Y', strtotime($announcement['applied_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($announcement['applied_time'])) ?></td>
                                <td><?= htmlspecialchars($announcement['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <div class="table-header">Activity Announcements</div>
            <?php if (empty($activity_announcements)): ?>
                <div class="empty-state">No activity announcements available</div>
            <?php else: ?>
                <table id="activityAnnouncementsTable" class="display" width="100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity_announcements as $announcement): ?>
                            <tr>
                                <td><?= date('F d, Y', strtotime($announcement['applied_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($announcement['applied_time'])) ?></td>
                                <td><?= htmlspecialchars($announcement['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables if they exist and there's data
    if (document.getElementById('administrativeAnnouncementsTable') && document.querySelectorAll('#administrativeAnnouncementsTable tbody tr').length > 0) {
        $('#administrativeAnnouncementsTable').DataTable({
            responsive: true,
            order: [[0, 'desc'], [1, 'desc']]
        });
    }
    
    if (document.getElementById('activityAnnouncementsTable') && document.querySelectorAll('#activityAnnouncementsTable tbody tr').length > 0) {
        $('#activityAnnouncementsTable').DataTable({
            responsive: true,
            order: [[0, 'desc'], [1, 'desc']]
        });
    }

    // Add click event to notification card to toggle dropdown
    const notificationCard = document.getElementById('notificationCard');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    notificationCard.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('notification-show');
    });
    
    // Close notification dropdown when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (!notificationCard.contains(e.target)) {
            notificationDropdown.classList.remove('notification-show');
        }
    });
    
    // Notification items click event (for marking as read)
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-id');
            const notificationType = this.getAttribute('data-type');
            
            if (notificationId && (notificationType.includes('expiring') || notificationType.includes('expired'))) {
                // Mark notification as read via AJAX
                fetch('/admin/pages/notification/functions/mark-notification-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove this notification from the list
                        this.remove();
                        
                        // Update notification counts
                        updateNotificationBadges();
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            } else if (notificationType === 'pending') {
                // Redirect to transactions page
                window.location.href = '/admin/pages/transactions';
            }
        });
    });
    
    // Function to update notification badges
    window.updateNotificationBadges = function() {
        // Make an AJAX call to get updated notification counts
        fetch('/admin/pages/notification/functions/get-notification-counts.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update total notifications badge
            const totalBadge = document.getElementById('total-notifications-badge');
            if (data.total > 0) {
                if (totalBadge) {
                    totalBadge.textContent = data.total;
                }
            } else if (totalBadge) {
                totalBadge.textContent = '0';
            }   
            
            // If all notifications are read, show empty state
            if (data.total === 0) {
                const dropdownContent = document.getElementById('notificationDropdown');
                dropdownContent.innerHTML = '<div class="table-header">Notifications</div><div class="empty-state">No notifications available</div>';
            }
        })
        .catch(error => {
            console.error('Error updating notification badges:', error);
        });
    };
});
</script>