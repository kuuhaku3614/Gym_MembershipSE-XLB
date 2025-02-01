<?php
require_once '../../../config.php';

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
<style>
        /* Previous styles remain the same until graph-container */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #ff0000;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --background-color: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--primary-color);
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            gap: 24px;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .main-section {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            min-height: 400px; /* Set minimum height for main section */
        }

        @media (max-width: 1024px) {
            .main-section {
                grid-template-columns: 1fr;
            }
        }

        .tables-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .stats-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--secondary-color);
        }

        .stats-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stats-subtitle {
            font-size: 0.875rem;
            color: var(--success-color);
            margin-top: 4px;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-top: auto;
        }

        .notification-card {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Updated graph container styles */
        .graph-container {
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            height: 700px; /* Fixed height */
            display: flex;
            flex-direction: column;
        }

        .graph-container h2 {
            font-size: 1.25rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .graph-wrapper {
            flex: 1;
            position: relative;
            width: 100%;
            min-height: 300px; /* Minimum height for the graph */
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .table-header {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary-color);
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 2px solid var(--background-color);
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 1rem;
        }

        table.dataTable tbody tr:hover {
            background-color: var(--background-color);
        }
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        .stats-value.empty {
            color: #6c757d;
            font-size: 1.5rem;
        }
    </style>
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

        <?php
        // Pending Notifications
        $notifications_sql = "
        SELECT COALESCE(COUNT(*), 0) AS total_notifications 
        FROM transactions
        WHERE status = 'pending'
        ";
        $notifications_stmt = $pdo->prepare($notifications_sql);
        $notifications_stmt->execute();
        $notifications = $notifications_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card notification-card">
            <div class="notification-badge"><?= $notifications['total_notifications'] ?: '0' ?></div>
            <i class="fas fa-bell fa-2x" style="color: var(--secondary-color)"></i>
        </div>

        <?php
        // Total Earnings with COALESCE
        $total_earnings_sql = "
        SELECT COALESCE(
            (SELECT COALESCE(SUM(amount), 0) FROM memberships) +
            (SELECT COALESCE(SUM(amount), 0) FROM program_subscriptions) +
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
                        const ctx = document.getElementById('membershipGraph').getContext('2d');
                        const chart = new Chart(ctx, {
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
                    <div class="stats-title">Members Checked In</div>
                    <div class="stats-subtitle">Date: <?= $current_date ?></div>
                </div>
                <div class="stats-value <?= $member_checkin['member_checkin'] == 0 ? 'empty' : '' ?>">
                    <?= $member_checkin['member_checkin'] ?: '0' ?>
                </div>
            </div>

            <?php
            // Expiring and Expired Memberships
            $expiring_memberships_sql = "
            SELECT COALESCE(COUNT(*), 0) AS expiring_memberships 
            FROM memberships 
            WHERE status = 'expiring'
            ";
            $expiring_memberships_stmt = $pdo->prepare($expiring_memberships_sql);
            $expiring_memberships_stmt->execute();
            $expiring_memberships = $expiring_memberships_stmt->fetch(PDO::FETCH_ASSOC);

            $expired_memberships_sql = "
            SELECT COALESCE(COUNT(*), 0) AS expired_memberships 
            FROM memberships 
            WHERE status = 'expired'
            ";
            $expired_memberships_stmt = $pdo->prepare($expired_memberships_sql);
            $expired_memberships_stmt->execute();
            $expired_memberships = $expired_memberships_stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Expiring Memberships</div>
                </div>
                <div class="stats-value <?= $expiring_memberships['expiring_memberships'] == 0 ? 'empty' : '' ?>">
                    <?= $expiring_memberships['expiring_memberships'] ?: '0' ?>
                </div>
            </div>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Expired Memberships</div>
                </div>
                <div class="stats-value <?= $expired_memberships['expired_memberships'] == 0 ? 'empty' : '' ?>">
                    <?= $expired_memberships['expired_memberships'] ?: '0' ?>
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