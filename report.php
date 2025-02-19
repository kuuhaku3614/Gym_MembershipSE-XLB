<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .dashboard-card {
            transition: transform 0.2s;
            border-radius: 10px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(13, 110, 253, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d6efd;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid px-4 py-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Analytics Dashboard</h1>
            <button class="btn btn-primary" onclick="exportReport()">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <?php
            // Calculate summary statistics
            $total_members_sql = "SELECT COUNT(DISTINCT user_id) as total FROM users";
            $attendance_rate_sql = "SELECT 
                (COUNT(CASE WHEN status = 'checked_in' THEN 1 END) * 100.0 / COUNT(*)) as rate 
                FROM attendance_history";
            $total_revenue_sql = "SELECT SUM(amount) as total FROM memberships WHERE MONTH(created_at) = MONTH(CURRENT_DATE)";
            $active_programs_sql = "SELECT COUNT(*) as total FROM program_subscriptions WHERE status = 'active'";
            
            $stats = [
                [
                    'title' => 'Total Members',
                    'value' => number_format($pdo->query($total_members_sql)->fetch()['total']),
                    'icon' => 'fas fa-users',
                    'color' => 'primary'
                ],
                [
                    'title' => 'Attendance Rate',
                    'value' => number_format($pdo->query($attendance_rate_sql)->fetch()['rate'], 1) . '%',
                    'icon' => 'fas fa-chart-line',
                    'color' => 'success'
                ],
                [
                    'title' => 'Monthly Revenue',
                    'value' => '$' . number_format($pdo->query($total_revenue_sql)->fetch()['total'], 2),
                    'icon' => 'fas fa-dollar-sign',
                    'color' => 'info'
                ],
                [
                    'title' => 'Active Programs',
                    'value' => number_format($pdo->query($active_programs_sql)->fetch()['total']),
                    'icon' => 'fas fa-dumbbell',
                    'color' => 'warning'
                ]
            ];

            foreach ($stats as $stat) {
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2"><?= $stat['title'] ?></h6>
                                <h3 class="mb-0"><?= $stat['value'] ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="<?= $stat['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <!-- Main Content -->
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="attendance-tab" data-bs-toggle="tab" href="#attendance">Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="revenue-tab" data-bs-toggle="tab" href="#revenue">Revenue</a>
                    </li>
                </ul>

                <div class="tab-content mt-4" id="myTabContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Revenue Trends</h5>
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Top Members</h5>
                                        <div class="table-responsive">
                                            <table class="table" id="topMembersTable">
                                                <thead>
                                                    <tr>
                                                        <th>Member</th>
                                                        <th>Check-ins</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $top_members_sql = "
                                                        SELECT 
                                                            u.username,
                                                            COUNT(ah.id) as check_ins
                                                        FROM users u
                                                        JOIN attendance_history ah ON u.id = ah.user_id
                                                        WHERE ah.status = 'checked_in'
                                                        GROUP BY u.id
                                                        ORDER BY check_ins DESC
                                                        LIMIT 5
                                                    ";
                                                    $top_members = $pdo->query($top_members_sql)->fetchAll();
                                                    foreach ($top_members as $member) {
                                                    ?>
                                                    <tr>
                                                        <td><?= $member['username'] ?></td>
                                                        <td><?= $member['check_ins'] ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Tab -->
                    <div class="tab-pane fade" id="attendance">
                        <div class="table-responsive">
                            <table class="table" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Total Check-ins</th>
                                        <th>Total Missed</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <!-- Revenue Tab -->
                    <div class="tab-pane fade" id="revenue">
                        <div class="table-responsive">
                            <table class="table" id="revenueTable">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Year</th>
                                        <th>Total Memberships</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#attendanceTable').DataTable({
                ajax: {
                    url: 'report_api.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'username' },
                    { data: 'first_name' },
                    { data: 'last_name' },
                    { data: 'total_check_ins' },
                    { data: 'total_missed' }
                ]
            });

            $('#revenueTable').DataTable({
                ajax: {
                    url: 'report_api.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'month' },
                    { data: 'year' },
                    { data: 'total_memberships' },
                    { 
                        data: 'total_amount',
                        render: function(data) {
                            return '$' + parseFloat(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                ]
            });

            // Initialize Revenue Chart
            const ctx = document.getElementById('revenueChart').getContext('2d');
            fetch('report_api.php')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(item => item.month),
                            datasets: [{
                                label: 'Revenue',
                                data: data.map(item => item.amount),
                                borderColor: '#0d6efd',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                });
        });

        // Export Report Function
        function exportReport() {
            // Implementation for report export
            alert('Generating report...');
        }
    </script>
</body>
</html>