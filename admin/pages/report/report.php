<?php
require_once 'config.php';
$database = new Database();
$pdo = $database->connect();

// Calculate total members (using COUNT of users)
$total_members_sql = "SELECT COUNT(DISTINCT id) as total FROM users";
$total_members_stmt = $pdo->prepare($total_members_sql);
$total_members_stmt->execute();
$total_members = $total_members_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total revenue (sum from all sources using your original queries)
$total_revenue_sql = "
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM memberships) +
        (SELECT COALESCE(SUM(amount), 0) FROM program_subscriptions) +
        (SELECT COALESCE(SUM(amount), 0) FROM rental_subscriptions) as total_revenue";
$total_revenue_stmt = $pdo->prepare($total_revenue_sql);
$total_revenue_stmt->execute();
$total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// Calculate average check-ins from attendance_history
$avg_checkins_sql = "
    SELECT AVG(total_checkins) as avg_checkins FROM (
        SELECT COUNT(CASE WHEN ah.status = 'checked_in' THEN 1 END) as total_checkins
        FROM attendance_history ah
        JOIN attendance a ON ah.attendance_id = a.id
        GROUP BY a.user_id
    ) as user_checkins";
$avg_checkins_stmt = $pdo->prepare($avg_checkins_sql);
$avg_checkins_stmt->execute();
$avg_checkins = $avg_checkins_stmt->fetch(PDO::FETCH_ASSOC)['avg_checkins'];

// Your original attendance query
$attendance_sql = "
    SELECT 
        u.username, 
        pd.first_name, 
        pd.last_name,
        COUNT(CASE WHEN ah.status = 'checked_in' THEN 1 END) as total_check_ins,
        COUNT(CASE WHEN ah.status = 'missed' THEN 1 END) as total_missed
    FROM attendance_history ah
    JOIN attendance a ON ah.attendance_id = a.id
    JOIN users u ON a.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY u.id
    ORDER BY total_check_ins DESC";
$attendance_stmt = $pdo->prepare($attendance_sql);
$attendance_stmt->execute();
$attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

// Your original earnings query
$earnings_sql = "
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as total_memberships,
        SUM(amount) as total_amount
    FROM memberships
    GROUP BY month, year
    ORDER BY year, month";
$earnings_stmt = $pdo->prepare($earnings_sql);
$earnings_stmt->execute();
$earnings = $earnings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Format earnings data for the chart
$formatted_earnings = array_map(function($row) {
    return [
        'month' => date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])),
        'total_amount' => $row['total_amount']
    ];
}, $earnings);

// Member Service Utilization query
$utilization_sql = "
    SELECT 
        u.username,
        pd.first_name,
        pd.last_name,
        COUNT(DISTINCT m.id) as membership_count,
        COUNT(DISTINCT ps.id) as program_subscriptions,
        COUNT(DISTINCT rs.id) as rental_subscriptions
    FROM users u
    JOIN personal_details pd ON u.id = pd.user_id
    LEFT JOIN transactions t ON u.id = t.user_id
    LEFT JOIN memberships m ON t.id = m.transaction_id
    LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
    LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    GROUP BY u.id
    ORDER BY membership_count DESC";
$utilization_stmt = $pdo->prepare($utilization_sql);
$utilization_stmt->execute();
$utilization = $utilization_stmt->fetchAll(PDO::FETCH_ASSOC);

// Programs query
$programs_sql = "
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as total_subscriptions,
        SUM(amount) as total_amount
    FROM program_subscriptions
    GROUP BY month, year
    ORDER BY year, month";
$programs_stmt = $pdo->prepare($programs_sql);
$programs_stmt->execute();
$programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Rentals query
$rentals_sql = "
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as total_rentals,
        SUM(amount) as total_amount
    FROM rental_subscriptions
    GROUP BY month, year
    ORDER BY year, month";
$rentals_stmt = $pdo->prepare($rentals_sql);
$rentals_stmt->execute();
$rentals = $rentals_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    
    <style>
        .report-section {
            margin-bottom: 2rem;
            break-inside: avoid;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: white;
            border-bottom: 2px solid #f8f9fa;
            padding: 1.5rem;
        }
        .stats-card {
            /* background: linear-gradient(45deg, #4158D0, #C850C0); */
            background: linear-gradient(45deg, #cc0000, #ff3333);
            color: white;
        }
        .export-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }
        @media print {
            .export-btn {
                display: none;
            }
            .card {
                break-inside: avoid;
            }
        }
    </style>
<body class="bg-light">
    <button class="btn btn-primary export-btn" onclick="window.print()">
        <i class="fas fa-download me-2"></i>Export Report
    </button>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="display-6 mb-0">Analytics Report</h1>
                        <p class="text-muted">Generated on <?= date('F j, Y') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Members</h5>
                        <h2 class="mb-0"><?= number_format($total_members) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="mb-0">$<?= number_format($total_revenue, 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Check-ins</h5>
                        <h2 class="mb-0"><?= number_format($avg_checkins, 1) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Member Attendance Analysis</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('attendanceTable', 'attendance_report.csv')">
                        Export Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Total Check-ins</th>
                                    <th>Total Missed</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= number_format($row['total_check_ins']) ?></td>
                                    <td><?= number_format($row['total_missed']) ?></td>
                                    <td><?= number_format(($row['total_check_ins'] / ($row['total_check_ins'] + $row['total_missed'])) * 100, 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Monthly Earnings</h4>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table table-striped" id="earningsTable">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Total Memberships</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($earnings as $row): ?>
                                <tr>
                                    <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
                                    <td><?= $row['year'] ?></td>
                                    <td><?= number_format($row['total_memberships']) ?></td>
                                    <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="report-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Member Service Utilization</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('utilizationTable', 'service_utilization.csv')">
                        Export Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="utilizationTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Membership Count</th>
                                    <th>Program Subscriptions</th>
                                    <th>Rental Subscriptions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utilization as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= number_format($row['membership_count']) ?></td>
                                    <td><?= number_format($row['program_subscriptions']) ?></td>
                                    <td><?= number_format($row['rental_subscriptions']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Program Subscriptions</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('programsTable', 'program_subscriptions.csv')">
                        Export Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="programsTable">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Total Subscriptions</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs as $row): ?>
                                <tr>
                                    <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
                                    <td><?= $row['year'] ?></td>
                                    <td><?= number_format($row['total_subscriptions']) ?></td>
                                    <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Rental Subscriptions</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('rentalsTable', 'rental_subscriptions.csv')">
                        Export Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="rentalsTable">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Total Rentals</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rentals as $row): ?>
                                <tr>
                                    <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
                                    <td><?= $row['year'] ?></td>
                                    <td><?= number_format($row['total_rentals']) ?></td>
                                    <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTables
        $('.table').DataTable({
            pageLength: 25,
            order: [[3, 'desc']]
        });

        // Export function
        window.exportTableToCSV = function(tableId, filename) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) 
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                
                csv.push(row.join(','));        
            }

            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
        };

        // Initialize Revenue Chart
        const chartData = <?= json_encode($formatted_earnings) ?>;
        const ctx = document.getElementById('revenueChart');
        
        // Check if chart instance exists and destroy it
        if (window.revenueChart instanceof Chart) {
            window.revenueChart.destroy();
        }
        
        // Create new chart instance
        window.revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => item.month),
                datasets: [{
                    label: 'Monthly Revenue',
                    data: chartData.map(item => item.total_amount),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Revenue Trend'
                    }
                }
            }
        });
    });
    </script>