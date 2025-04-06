<?php
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();

// Calculate total members (using COUNT of users)
$total_members_sql = "SELECT COALESCE(COUNT(*), 0) AS total
FROM memberships
WHERE status IN ('active', 'expired', 'expiring');
";
$total_members_stmt = $pdo->prepare($total_members_sql);
$total_members_stmt->execute();
$total_members = $total_members_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total revenue (sum from all sources using your original queries)
$total_revenue_sql = "
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM memberships) +
        (SELECT COALESCE(SUM(amount), 0) FROM program_subscription_schedule) +
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
    LEFT JOIN program_subscriptions ps ON t.id = ps.id
    LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'member'
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
    FROM program_subscription_schedule
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

function getIncomeExtremes($pdo) {
    $sql = "
    WITH monthly_revenue AS (
        SELECT 
            CONCAT(MONTH(created_at), '-', YEAR(created_at)) as month_key,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            SUM(amount) as total_amount
        FROM (
            SELECT created_at, amount FROM memberships
            UNION ALL
            SELECT created_at, amount FROM program_subscription_schedule
            UNION ALL
            SELECT created_at, amount FROM rental_subscriptions
        ) as all_revenue
        GROUP BY month_key, month_name
    )
    SELECT * FROM (
        (SELECT month_name, total_amount, 'highest' as type
        FROM monthly_revenue
        ORDER BY total_amount DESC
        LIMIT 1)
        UNION ALL
        (SELECT month_name, total_amount, 'lowest' as type
        FROM monthly_revenue
        WHERE total_amount > 0
        ORDER BY total_amount ASC
        LIMIT 1)
    ) as results
    ORDER BY type";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getServiceExtremes($pdo) {
    $sql = "
    WITH monthly_services AS (
        SELECT 
            CONCAT(MONTH(created_at), '-', YEAR(created_at)) as month_key,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COUNT(*) as total_services
        FROM (
            SELECT created_at FROM program_subscription_schedule
            UNION ALL
            SELECT created_at FROM rental_subscriptions
        ) as all_services
        GROUP BY month_key, month_name
    )
    SELECT * FROM (
        (SELECT month_name, total_services, 'highest' as type
        FROM monthly_services
        ORDER BY total_services DESC
        LIMIT 1)
        UNION ALL
        (SELECT month_name, total_services, 'lowest' as type
        FROM monthly_services
        WHERE total_services > 0
        ORDER BY total_services ASC
        LIMIT 1)
    ) as results
    ORDER BY type";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMembershipExtremes($pdo) {
    $sql = "
    WITH monthly_memberships AS (
        SELECT 
            CONCAT(MONTH(created_at), '-', YEAR(created_at)) as month_key,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COUNT(*) as total_memberships
        FROM memberships
        GROUP BY month_key, month_name
    )
    SELECT * FROM (
        (SELECT month_name, total_memberships, 'highest' as type
        FROM monthly_memberships
        ORDER BY total_memberships DESC
        LIMIT 1)
        UNION ALL
        (SELECT month_name, total_memberships, 'lowest' as type
        FROM monthly_memberships
        WHERE total_memberships > 0
        ORDER BY total_memberships ASC
        LIMIT 1)
    ) as results
    ORDER BY type";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Run the queries to get the extreme values
$income_extremes = getIncomeExtremes($pdo);
$service_extremes = getServiceExtremes($pdo);
$membership_extremes = getMembershipExtremes($pdo);

// Format the results for display
function formatExtremes($extremes) {
    $result = [
        'highest' => ['month' => 'N/A', 'value' => 0],
        'lowest' => ['month' => 'N/A', 'value' => 0]
    ];
    
    foreach ($extremes as $row) {
        if ($row['type'] == 'highest') {
            $result['highest']['month'] = $row['month_name'];
            $result['highest']['value'] = isset($row['total_amount']) ? $row['total_amount'] : 
                                        (isset($row['total_services']) ? $row['total_services'] : 
                                        $row['total_memberships']);
        } else {
            $result['lowest']['month'] = $row['month_name'];
            $result['lowest']['value'] = isset($row['total_amount']) ? $row['total_amount'] : 
                                       (isset($row['total_services']) ? $row['total_services'] : 
                                       $row['total_memberships']);
        }
    }
    
    return $result;
}

$income_data = formatExtremes($income_extremes);
$service_data = formatExtremes($service_extremes);
$membership_data = formatExtremes($membership_extremes);

// Gender distribution of memberships
$gender_distribution_sql = "
    SELECT 
        pd.sex,
        COUNT(DISTINCT m.id) as total_memberships,
        COUNT(DISTINCT u.id) as unique_members
    FROM memberships m
    JOIN transactions t ON m.transaction_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY pd.sex
    ORDER BY total_memberships DESC";
$gender_stmt = $pdo->prepare($gender_distribution_sql);
$gender_stmt->execute();
$gender_distribution = $gender_stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly gender distribution of memberships
$monthly_gender_sql = "
    SELECT 
        MONTH(m.created_at) as month,
        YEAR(m.created_at) as year,
        DATE_FORMAT(m.created_at, '%M %Y') as month_name,
        pd.sex,
        COUNT(m.id) as total_memberships
    FROM memberships m
    JOIN transactions t ON m.transaction_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY year, month, month_name, pd.sex
    ORDER BY year, month, pd.sex";
$monthly_gender_stmt = $pdo->prepare($monthly_gender_sql);
$monthly_gender_stmt->execute();
$monthly_gender_distribution = $monthly_gender_stmt->fetchAll(PDO::FETCH_ASSOC);

// Age demographics of memberships
$age_distribution_sql = "
    SELECT 
        CASE
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
            ELSE '65+'
        END as age_group,
        COUNT(DISTINCT m.id) as total_memberships,
        COUNT(DISTINCT u.id) as unique_members
    FROM memberships m
    JOIN transactions t ON m.transaction_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY age_group
    ORDER BY 
        CASE age_group
            WHEN 'Under 18' THEN 1
            WHEN '18-24' THEN 2
            WHEN '25-34' THEN 3
            WHEN '35-44' THEN 4
            WHEN '45-54' THEN 5
            WHEN '55-64' THEN 6
            WHEN '65+' THEN 7
        END";
$age_stmt = $pdo->prepare($age_distribution_sql);
$age_stmt->execute();
$age_distribution = $age_stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly age demographics of memberships
$monthly_age_sql = "
    SELECT 
        MONTH(m.created_at) as month,
        YEAR(m.created_at) as year,
        DATE_FORMAT(m.created_at, '%M %Y') as month_name,
        CASE
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
            WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
            ELSE '65+'
        END as age_group,
        COUNT(m.id) as total_memberships
    FROM memberships m
    JOIN transactions t ON m.transaction_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY year, month, month_name, age_group
    ORDER BY year, month, 
        CASE age_group
            WHEN 'Under 18' THEN 1
            WHEN '18-24' THEN 2
            WHEN '25-34' THEN 3
            WHEN '35-44' THEN 4
            WHEN '45-54' THEN 5
            WHEN '55-64' THEN 6
            WHEN '65+' THEN 7
        END";
$monthly_age_stmt = $pdo->prepare($monthly_age_sql);
$monthly_age_stmt->execute();
$monthly_age_distribution = $monthly_age_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get peak months by gender
function getPeakMonthsByGender($pdo) {
    $sql = "
    WITH gender_monthly AS (
        SELECT 
            pd.sex,
            DATE_FORMAT(m.created_at, '%M %Y') as month_name,
            COUNT(m.id) as membership_count,
            RANK() OVER (PARTITION BY pd.sex ORDER BY COUNT(m.id) DESC) as rnk
        FROM memberships m
        JOIN transactions t ON m.transaction_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        GROUP BY pd.sex, month_name
    )
    SELECT sex, month_name, membership_count
    FROM gender_monthly
    WHERE rnk = 1
    ORDER BY sex";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get peak months by age group
function getPeakMonthsByAge($pdo) {
    $sql = "
    WITH age_monthly AS (
        SELECT 
            CASE
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18'
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
                ELSE '65+'
            END as age_group,
            DATE_FORMAT(m.created_at, '%M %Y') as month_name,
            COUNT(m.id) as membership_count,
            RANK() OVER (PARTITION BY 
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
                    ELSE '65+'
                END
                ORDER BY COUNT(m.id) DESC) as rnk
        FROM memberships m
        JOIN transactions t ON m.transaction_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        GROUP BY age_group, month_name
    )
    SELECT age_group, month_name, membership_count
    FROM age_monthly
    WHERE rnk = 1
    ORDER BY 
        CASE age_group
            WHEN 'Under 18' THEN 1
            WHEN '18-24' THEN 2
            WHEN '25-34' THEN 3
            WHEN '35-44' THEN 4
            WHEN '45-54' THEN 5
            WHEN '55-64' THEN 6
            WHEN '65+' THEN 7
        END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get the peak months data
$peak_months_gender = getPeakMonthsByGender($pdo);
$peak_months_age = getPeakMonthsByAge($pdo);

date_default_timezone_set('Asia/Manila');
?>
<link rel="stylesheet" href="css/report.css">
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2> Analytics Report</h2>
                        <p class="text-muted">Generated on <?= date('F d, Y') ?></p>
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
                        <h2 class="mb-0">₱<?= number_format($total_revenue, 2) ?></h2>
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

        <!-- New section for performance extremes -->
     <div class="report-section">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Performance Highlights</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Income Extremes -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="h5 mb-0 font-weight-bold text-primary mb-3">Income</div>
                                            
                                            <div class="mb-3">
                                                <div class="text-success font-weight-bold">Highest: <?= $income_data['highest']['month'] ?></div>
                                                <div class="h4">₱<?= number_format($income_data['highest']['value'], 2) ?></div>
                                            </div>
                                            
                                            <div>
                                                <div class="text-danger font-weight-bold">Lowest: <?= $income_data['lowest']['month'] ?></div>
                                                <div class="h4">₱<?= number_format($income_data['lowest']['value'], 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Services Extremes -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="h5 mb-0 font-weight-bold text-primary mb-3">Services Availed</div>
                                            
                                            <div class="mb-3">
                                                <div class="text-success font-weight-bold">Most: <?= $service_data['highest']['month'] ?></div>
                                                <div class="h4"><?= number_format($service_data['highest']['value']) ?> services</div>
                                            </div>
                                            
                                            <div>
                                                <div class="text-danger font-weight-bold">Least: <?= $service_data['lowest']['month'] ?></div>
                                                <div class="h4"><?= number_format($service_data['lowest']['value']) ?> services</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Memberships Extremes -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="h5 mb-0 font-weight-bold text-primary mb-3">Memberships</div>
                                            
                                            <div class="mb-3">
                                                <div class="text-success font-weight-bold">Most: <?= $membership_data['highest']['month'] ?></div>
                                                <div class="h4"><?= number_format($membership_data['highest']['value']) ?> memberships</div>
                                            </div>
                                            
                                            <div>
                                                <div class="text-danger font-weight-bold">Least: <?= $membership_data['lowest']['month'] ?></div>
                                                <div class="h4"><?= number_format($membership_data['lowest']['value']) ?> memberships</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <!-- Gender Demographics -->
<div class="report-section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Membership by Gender</h4>
            <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('genderTable', 'gender_memberships.csv')">
                Export Data
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <canvas id="genderChart"></canvas>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Peak Months by Gender</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="peakGenderTable">
                            <thead>
                                <tr>
                                    <th>Gender</th>
                                    <th>Peak Month</th>
                                    <th>Memberships</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peak_months_gender as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['sex']) ?></td>
                                    <td><?= htmlspecialchars($row['month_name']) ?></td>
                                    <td><?= number_format($row['membership_count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="genderTable">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Total Memberships</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_gender_distribution as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['month_name']) ?></td>
                            <td><?= $row['year'] ?></td>
                            <td><?= htmlspecialchars($row['sex']) ?></td>
                            <td><?= number_format($row['total_memberships']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Age Demographics -->
<div class="report-section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Membership by Age Demographics</h4>
            <button class="btn btn-sm btn-outline-primary" onclick="exportTableToCSV('ageTable', 'age_memberships.csv')">
                Export Data
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <canvas id="ageChart"></canvas>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Peak Months by Age Group</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="peakAgeTable">
                            <thead>
                                <tr>
                                    <th>Age Group</th>
                                    <th>Peak Month</th>
                                    <th>Memberships</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peak_months_age as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['age_group']) ?></td>
                                    <td><?= htmlspecialchars($row['month_name']) ?></td>
                                    <td><?= number_format($row['membership_count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="ageTable">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Age Group</th>
                            <th>Total Memberships</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_age_distribution as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['month_name']) ?></td>
                            <td><?= $row['year'] ?></td>
                            <td><?= htmlspecialchars($row['age_group']) ?></td>
                            <td><?= number_format($row['total_memberships']) ?></td>
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
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
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
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
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
    // Initialize DataTables with proper configuration
    // Use try-catch to handle errors gracefully
    try {
        // Initialize tables individually with specific configurations
        $('#attendanceTable').DataTable({
            pageLength: 25,
            order: [[3, 'desc']] // Sort by check-ins column
        });
        
        $('#earningsTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc'], [1, 'desc']] // Sort by month and year
        });
        
        $('#utilizationTable').DataTable({
            pageLength: 25,
            order: [[3, 'desc']] // Sort by membership count
        });
        
        $('#programsTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc'], [1, 'desc']] // Sort by month and year
        });
        
        $('#rentalsTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc'], [1, 'desc']] // Sort by month and year
        });
        
        // Gender and age tables
        $('#genderTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc'], [1, 'desc']] // Sort by month and year
        });
        
        $('#ageTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc'], [1, 'desc']] // Sort by month and year
        });
        
        // Small tables without pagination
        $('#peakGenderTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: true
        });
        
        $('#peakAgeTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: true
        });
    } catch(e) {
        console.error("DataTables initialization error:", e);
    }

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
    try {
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
        
        // Initialize gender chart
        try {
            const genderData = <?= json_encode($gender_distribution) ?>;
            const genderCtx = document.getElementById('genderChart');
            
            // Check if the data has the expected structure and properties
            const labels = [];
            const values = [];
            
            // Handle different possible property names for gender
            genderData.forEach(item => {
                // Use the property that actually exists (sex or gender)
                const genderLabel = item.gender || item.sex || "Unknown";
                labels.push(genderLabel);
                values.push(item.total_memberships);
            });
            
            new Chart(genderCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',  // Male (blue)
                            'rgba(255, 99, 132, 0.7)',  // Female (pink)
                            'rgba(75, 192, 192, 0.7)'   // Other or Unknown (teal)
                        ],
                        borderWidth: 1
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
                            text: 'Membership Distribution by Gender'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch(e) {
            console.error("Gender chart initialization error:", e);
            console.log("Gender data:", genderData);
        }
        
        // Initialize age chart
        const ageData = <?= json_encode($age_distribution) ?>;
        const ageCtx = document.getElementById('ageChart');
        
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageData.map(item => item.age_group),
                datasets: [{
                    label: 'Memberships by Age Group',
                    data: ageData.map(item => item.total_memberships),
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Membership Distribution by Age Group'
                    }
                }
            }
        });
    } catch(e) {
        console.error("Chart initialization error:", e);
    }
});
    </script>