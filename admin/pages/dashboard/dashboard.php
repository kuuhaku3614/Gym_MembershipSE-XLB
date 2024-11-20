<?php
require_once '../../../config.php';

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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Current Members</div>
                    <div class="stats-subtitle">(Active)</div>
                </div>
                <div class="stats-value">150</div>
            </div>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">New Members</div>
                    <div class="stats-subtitle">this month</div>
                </div>
                <div class="stats-value">15</div>
            </div>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Website Accounts</div>
                </div>
                <div class="stats-value">200</div>
            </div>
            <div class="card notification-card">
                <div class="notification-badge">7</div>
                <i class="fas fa-bell fa-2x" style="color: var(--secondary-color)"></i>
            </div>
            <div class="card stats-card">
                <div>
                    <div class="stats-title">Total Earnings</div>
                </div>
                <div class="stats-value">₱10000</div>
            </div>
        </div>

        <!-- Main Section -->
        <div class="main-section">
            <div class="graph-container">
                <h2>Monthly Membership Growth in 2024</h2>
                <div class="graph-wrapper">
                    <canvas id="membershipGraph"></canvas>
                </div>
            </div>
            <div class="stats-section">
                <div class="card stats-card">
                    <div>
                        <div class="stats-title">Members Checked In</div>
                        <div class="stats-subtitle">Date: 20/10/2024</div>
                    </div>
                    <div class="stats-value">50</div>
                </div>
                <div class="card stats-card">
                    <div>
                        <div class="stats-title">Coaches Checked In</div>
                        <div class="stats-subtitle">Date: 20/10/2024</div>
                    </div>
                    <div class="stats-value">10</div>
                </div>
                <div class="card stats-card">
                    <div>
                        <div class="stats-title">Expiring Memberships</div>
                    </div>
                    <div class="stats-value">1</div>
                </div>
                <div class="card stats-card">
                    <div>
                        <div class="stats-title">Expired Memberships</div>
                    </div>
                    <div class="stats-value">2</div>
                </div>
            </div>
        </div>

<!-- Tables Section -->
<div class="tables-section">
    <div class="table-container">
        <div class="table-header">Administrative Announcements</div>
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
    </div>
    <div class="table-container">
        <div class="table-header">Activity Announcements</div>
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
    </div>
</div>

    <script src="js/dashboard.js"></script>
</body>