<?php
require_once '../../../config.php'; // Ensure path is correct
$database = new Database();
$pdo = $database->connect();

// =========================================================================
// == FILTER FUNCTION AND AJAX HANDLER =====================================
// =========================================================================

/**
 * Fetches report data filtered by a date range.
 *
 * @param PDO $pdo The database connection object.
 * @param string $startDate The start date in 'YYYY-MM-DD' format.
 * @param string $endDate The end date in 'YYYY-MM-DD' format.
 * @return array An array containing all filtered report datasets.
 */
function getFilteredReportData(PDO $pdo, string $startDate, string $endDate): array
{
    $filteredData = [];

    // Add time component to end date to include the whole day
    $endDateFull = $endDate . ' 23:59:59';
    $params = [':startDate' => $startDate, ':endDate' => $endDateFull];

    // --- Filtered Total Members ---
    // Assuming new members are counted based on their 'created_at' date in memberships
    // Adjust 'created_at' if a different column determines when a member counts for the period
    $total_members_sql_filtered = "SELECT COALESCE(COUNT(*), 0) AS total
        FROM memberships
        WHERE status IN ('active', 'expired', 'expiring')
        AND created_at BETWEEN :startDate AND :endDate"; // Adjust 'created_at' if needed
    $total_members_stmt_filtered = $pdo->prepare($total_members_sql_filtered);
    $total_members_stmt_filtered->execute($params);
    $filteredData['total_members'] = $total_members_stmt_filtered->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


    // --- Filtered Total Revenue ---
    $total_revenue_sql_filtered = "
        SELECT
            (SELECT COALESCE(SUM(amount), 0) FROM memberships WHERE created_at BETWEEN :startDate AND :endDate) +
            (SELECT COALESCE(SUM(amount), 0) FROM program_subscription_schedule WHERE created_at BETWEEN :startDate AND :endDate) +
            (SELECT COALESCE(SUM(amount), 0) FROM rental_subscriptions WHERE created_at BETWEEN :startDate AND :endDate) +
            (SELECT COALESCE(SUM(amount), 0) FROM walk_in_records WHERE is_paid = 1 AND date BETWEEN :startDate AND :endDate)
            as total_revenue";
    $total_revenue_stmt_filtered = $pdo->prepare($total_revenue_sql_filtered);
    $total_revenue_stmt_filtered->execute($params);
    $filteredData['total_revenue'] = $total_revenue_stmt_filtered->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // --- Filtered Average Check-ins ---
    // Assuming filtering based on the check-in time in attendance_history
    // Adjust 'ah.created_at' or use 'ah.check_in_time' if that exists and is more appropriate
    $avg_checkins_sql_filtered = "
        SELECT AVG(total_checkins) as avg_checkins FROM (
            SELECT COUNT(CASE WHEN ah.status = 'checked_in' THEN 1 END) as total_checkins
            FROM attendance_history ah
            JOIN attendance a ON ah.attendance_id = a.id
            WHERE ah.created_at BETWEEN :startDate AND :endDate -- Adjust date column if necessary
            GROUP BY a.user_id
        ) as user_checkins";
    $avg_checkins_stmt_filtered = $pdo->prepare($avg_checkins_sql_filtered);
    $avg_checkins_stmt_filtered->execute($params);
    $filteredData['avg_checkins'] = $avg_checkins_stmt_filtered->fetch(PDO::FETCH_ASSOC)['avg_checkins'] ?? 0;


    // --- Filtered Attendance ---
    $attendance_sql_filtered = "
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
        WHERE ah.created_at BETWEEN :startDate AND :endDate -- Adjust 'ah.created_at' if needed
        GROUP BY u.id, u.username, pd.first_name, pd.last_name
        ORDER BY total_check_ins DESC";
    $attendance_stmt_filtered = $pdo->prepare($attendance_sql_filtered);
    $attendance_stmt_filtered->execute($params);
    $filteredData['attendance'] = $attendance_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);

    // --- Filtered Monthly Earnings (Memberships) ---
     $earnings_sql_filtered = "
        SELECT
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_memberships,
            SUM(amount) as total_amount
        FROM memberships
        WHERE created_at BETWEEN :startDate AND :endDate
        GROUP BY month, year
        ORDER BY year, month";
    $earnings_stmt_filtered = $pdo->prepare($earnings_sql_filtered);
    $earnings_stmt_filtered->execute($params);
    $filteredData['earnings'] = $earnings_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);
    // Format earnings data for the chart
    $filteredData['formatted_earnings'] = array_map(function($row) {
        return [
            'month' => date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])),
            'total_amount' => $row['total_amount']
        ];
    }, $filteredData['earnings']);

    // --- Filtered Member Service Utilization ---
    // Needs date filtering on transactions or the individual service tables
    $utilization_sql_filtered = "
        SELECT
            u.username,
            pd.first_name,
            pd.last_name,
            COUNT(DISTINCT CASE WHEN m.created_at BETWEEN :startDate AND :endDate THEN m.id ELSE NULL END) as membership_count,
            COUNT(DISTINCT CASE WHEN ps.created_at BETWEEN :startDate AND :endDate THEN ps.id ELSE NULL END) as program_subscriptions,
            COUNT(DISTINCT CASE WHEN rs.created_at BETWEEN :startDate AND :endDate THEN rs.id ELSE NULL END) as rental_subscriptions
        FROM users u
        JOIN personal_details pd ON u.id = pd.user_id
        LEFT JOIN transactions t ON u.id = t.user_id -- Check if transactions.created_at is better filter
        LEFT JOIN memberships m ON t.id = m.transaction_id
        LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id -- Assuming transaction_id link
        LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'member'
           -- Add a general date filter if possible, e.g. on transactions or user registration
           -- AND u.created_at BETWEEN :startDate AND :endDate
        GROUP BY u.id, u.username, pd.first_name, pd.last_name
        ORDER BY membership_count DESC";
    $utilization_stmt_filtered = $pdo->prepare($utilization_sql_filtered);
    $utilization_stmt_filtered->execute($params); // Pass params here
    $filteredData['utilization'] = $utilization_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);

    // --- Filtered Programs ---
    $programs_sql_filtered = "
        SELECT
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_subscriptions,
            SUM(amount) as total_amount
        FROM program_subscription_schedule
        WHERE created_at BETWEEN :startDate AND :endDate
        GROUP BY month, year
        ORDER BY year, month";
    $programs_stmt_filtered = $pdo->prepare($programs_sql_filtered);
    $programs_stmt_filtered->execute($params);
    $filteredData['programs'] = $programs_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);

    // --- Filtered Rentals ---
    $rentals_sql_filtered = "
        SELECT
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_rentals,
            SUM(amount) as total_amount
        FROM rental_subscriptions
        WHERE created_at BETWEEN :startDate AND :endDate
        GROUP BY month, year
        ORDER BY year, month";
    $rentals_stmt_filtered = $pdo->prepare($rentals_sql_filtered);
    $rentals_stmt_filtered->execute($params);
    $filteredData['rentals'] = $rentals_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);

     // --- Filtered Walk-ins ---
    $walkins_sql_filtered = "
        SELECT
            MONTH(date) as month,
            YEAR(date) as year,
            COUNT(*) as total_walkins,
            SUM(amount) as total_amount
        FROM walk_in_records
        WHERE is_paid = 1 AND date BETWEEN :startDate AND :endDate
        GROUP BY year, month
        ORDER BY year, month";
    $walkins_stmt_filtered = $pdo->prepare($walkins_sql_filtered);
    $walkins_stmt_filtered->execute($params); // Use date only param here if 'date' column is DATE type
    // $walkins_stmt_filtered->execute([':startDate' => $startDate, ':endDate' => $endDate]);
    $filteredData['walkins'] = $walkins_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);

    // --- Filtered Performance Extremes ---
    // TODO: Modify the get...Extremes functions to accept date params OR rewrite logic here
    // Example (Conceptual - requires modifying the original functions):
    // include_once 'path/to/extreme_functions.php'; // If they are separate
    // $income_extremes_filtered = getIncomeExtremes($pdo, $startDate, $endDateFull); // Assuming modified function
    // $service_extremes_filtered = getServiceExtremes($pdo, $startDate, $endDateFull);
    // $membership_extremes_filtered = getMembershipExtremes($pdo, $startDate, $endDateFull);
    // $filteredData['income_data'] = formatExtremes($income_extremes_filtered);
    // $filteredData['service_data'] = formatExtremes($service_extremes_filtered);
    // $filteredData['membership_data'] = formatExtremes($membership_extremes_filtered);
    // --- Placeholder Data for Extremes ---
     $filteredData['income_data'] = ['highest' => ['month' => 'N/A', 'value' => 0], 'lowest' => ['month' => 'N/A', 'value' => 0]];
     $filteredData['service_data'] = ['highest' => ['month' => 'N/A', 'value' => 0], 'lowest' => ['month' => 'N/A', 'value' => 0]];
     $filteredData['membership_data'] = ['highest' => ['month' => 'N/A', 'value' => 0], 'lowest' => ['month' => 'N/A', 'value' => 0]];


    // --- Filtered Gender Distribution ---
    $gender_distribution_sql_filtered = "
        SELECT
            pd.sex,
            COUNT(DISTINCT m.id) as total_memberships,
            COUNT(DISTINCT u.id) as unique_members
        FROM memberships m
        JOIN transactions t ON m.transaction_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN personal_details pd ON u.id = pd.user_id
        WHERE m.created_at BETWEEN :startDate AND :endDate
        GROUP BY pd.sex
        ORDER BY total_memberships DESC";
    $gender_stmt_filtered = $pdo->prepare($gender_distribution_sql_filtered);
    $gender_stmt_filtered->execute($params);
    $filteredData['gender_distribution'] = $gender_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Add filtered monthly gender distribution query + data
     $filteredData['monthly_gender_distribution'] = []; // Placeholder
     // TODO: Add filtered peak months by gender query + data
     $filteredData['peak_months_gender'] = []; // Placeholder


    // --- Filtered Age Distribution ---
    $age_distribution_sql_filtered = "
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
        WHERE m.created_at BETWEEN :startDate AND :endDate
        GROUP BY age_group
        ORDER BY -- Keeping original order logic
            CASE age_group WHEN 'Under 18' THEN 1 WHEN '18-24' THEN 2 WHEN '25-34' THEN 3 WHEN '35-44' THEN 4 WHEN '45-54' THEN 5 WHEN '55-64' THEN 6 ELSE 7 END";
    $age_stmt_filtered = $pdo->prepare($age_distribution_sql_filtered);
    $age_stmt_filtered->execute($params);
    $filteredData['age_distribution'] = $age_stmt_filtered->fetchAll(PDO::FETCH_ASSOC);
     // TODO: Add filtered monthly age distribution query + data
     $filteredData['monthly_age_distribution'] = []; // Placeholder
      // TODO: Add filtered peak months by age query + data
     $filteredData['peak_months_age'] = []; // Placeholder


    return $filteredData;
}


// --- Handling AJAX Request ---
if (isset($_POST['action']) && $_POST['action'] == 'filter_report' && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    try {
        // Basic validation (add more robust validation)
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];

        // Basic date format check (improve as needed)
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $startDate) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $endDate) && $startDate <= $endDate) {

            // Ensure $pdo is available - Reconnect if necessary for AJAX context
            // If config.php doesn't create $pdo globally, you might need this:
            // require_once '../../../config.php';
            // $database = new Database();
            // $pdo = $database->connect();

            if (!$pdo) {
                 throw new Exception("Database connection failed.");
            }

            $filteredReportData = getFilteredReportData($pdo, $startDate, $endDate);

            // Return data as JSON
            header('Content-Type: application/json');
            echo json_encode($filteredReportData);
            exit; // Important to stop the rest of the HTML page from rendering
        } else {
            throw new Exception("Invalid date format or range. Use YYYY-MM-DD and ensure start date is not after end date.");
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400); // Bad Request
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}


// =========================================================================
// == ORIGINAL DATA FETCHING FOR INITIAL PAGE LOAD =========================
// =========================================================================
// These queries run when the page is first loaded, showing unfiltered data.

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
        (SELECT COALESCE(SUM(amount), 0) FROM rental_subscriptions) +
        (SELECT COALESCE(SUM(amount), 0) FROM walk_in_records WHERE is_paid = 1) as total_revenue";
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
    GROUP BY u.id, u.username, pd.first_name, pd.last_name
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
    LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id -- Assuming transaction_id link
    LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'member'
    GROUP BY u.id, u.username, pd.first_name, pd.last_name
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

// --- Original Performance Extreme Functions ---
// These need to be modified IF you want the filtered report to show extremes *for that specific period*
function getIncomeExtremes($pdo) {
    $sql = "
    WITH monthly_revenue AS (
        SELECT
            CONCAT(MONTH(created_at), '-', YEAR(created_at)) as month_key,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            SUM(amount) as total_amount
        FROM (
            SELECT created_at, amount FROM memberships
            UNION ALL SELECT created_at, amount FROM program_subscription_schedule
            UNION ALL SELECT created_at, amount FROM rental_subscriptions
            UNION ALL SELECT date as created_at, amount FROM walk_in_records WHERE is_paid = 1
        ) as all_revenue
        GROUP BY month_key, month_name
    )
    SELECT * FROM (
        (SELECT month_name, total_amount, 'highest' as type FROM monthly_revenue ORDER BY total_amount DESC LIMIT 1)
        UNION ALL
        (SELECT month_name, total_amount, 'lowest' as type FROM monthly_revenue WHERE total_amount > 0 ORDER BY total_amount ASC LIMIT 1)
    ) as results ORDER BY type";
    $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            UNION ALL SELECT created_at FROM rental_subscriptions
        ) as all_services
        GROUP BY month_key, month_name
    )
    SELECT * FROM (
        (SELECT month_name, total_services, 'highest' as type FROM monthly_services ORDER BY total_services DESC LIMIT 1)
        UNION ALL
        (SELECT month_name, total_services, 'lowest' as type FROM monthly_services WHERE total_services > 0 ORDER BY total_services ASC LIMIT 1)
    ) as results ORDER BY type";
     $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        (SELECT month_name, total_memberships, 'highest' as type FROM monthly_memberships ORDER BY total_memberships DESC LIMIT 1)
        UNION ALL
        (SELECT month_name, total_memberships, 'lowest' as type FROM monthly_memberships WHERE total_memberships > 0 ORDER BY total_memberships ASC LIMIT 1)
    ) as results ORDER BY type";
    $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function formatExtremes($extremes) {
    $result = ['highest' => ['month' => 'N/A', 'value' => 0], 'lowest' => ['month' => 'N/A', 'value' => 0]];
    foreach ($extremes as $row) {
        $value = $row['total_amount'] ?? ($row['total_services'] ?? $row['total_memberships'] ?? 0);
        if ($row['type'] == 'highest') { $result['highest']['month'] = $row['month_name']; $result['highest']['value'] = $value; }
        else { $result['lowest']['month'] = $row['month_name']; $result['lowest']['value'] = $value; }
    } return $result;
}

// Run the original queries to get the extreme values for initial load
$income_extremes = getIncomeExtremes($pdo);
$service_extremes = getServiceExtremes($pdo);
$membership_extremes = getMembershipExtremes($pdo);
$income_data = formatExtremes($income_extremes);
$service_data = formatExtremes($service_extremes);
$membership_data = formatExtremes($membership_extremes);

// Gender distribution of memberships
$gender_distribution_sql = "
    SELECT pd.sex, COUNT(DISTINCT m.id) as total_memberships, COUNT(DISTINCT u.id) as unique_members
    FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY pd.sex ORDER BY total_memberships DESC";
$gender_stmt = $pdo->prepare($gender_distribution_sql); $gender_stmt->execute(); $gender_distribution = $gender_stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly gender distribution
$monthly_gender_sql = "
    SELECT MONTH(m.created_at) as month, YEAR(m.created_at) as year, DATE_FORMAT(m.created_at, '%M %Y') as month_name, pd.sex, COUNT(m.id) as total_memberships
    FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY year, month, month_name, pd.sex ORDER BY year, month, pd.sex";
$monthly_gender_stmt = $pdo->prepare($monthly_gender_sql); $monthly_gender_stmt->execute(); $monthly_gender_distribution = $monthly_gender_stmt->fetchAll(PDO::FETCH_ASSOC);

// Age demographics of memberships
$age_distribution_sql = "
    SELECT CASE WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64' ELSE '65+' END as age_group, COUNT(DISTINCT m.id) as total_memberships, COUNT(DISTINCT u.id) as unique_members
    FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY age_group ORDER BY CASE age_group WHEN 'Under 18' THEN 1 WHEN '18-24' THEN 2 WHEN '25-34' THEN 3 WHEN '35-44' THEN 4 WHEN '45-54' THEN 5 WHEN '55-64' THEN 6 ELSE 7 END";
$age_stmt = $pdo->prepare($age_distribution_sql); $age_stmt->execute(); $age_distribution = $age_stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly age demographics
$monthly_age_sql = "
    SELECT MONTH(m.created_at) as month, YEAR(m.created_at) as year, DATE_FORMAT(m.created_at, '%M %Y') as month_name, CASE WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64' ELSE '65+' END as age_group, COUNT(m.id) as total_memberships
    FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id
    GROUP BY year, month, month_name, age_group ORDER BY year, month, CASE age_group WHEN 'Under 18' THEN 1 WHEN '18-24' THEN 2 WHEN '25-34' THEN 3 WHEN '35-44' THEN 4 WHEN '45-54' THEN 5 WHEN '55-64' THEN 6 ELSE 7 END";
$monthly_age_stmt = $pdo->prepare($monthly_age_sql); $monthly_age_stmt->execute(); $monthly_age_distribution = $monthly_age_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get peak months by gender
function getPeakMonthsByGender($pdo) {
    $sql = "
    WITH gender_monthly AS (
        SELECT pd.sex, DATE_FORMAT(m.created_at, '%M %Y') as month_name, COUNT(m.id) as membership_count, RANK() OVER (PARTITION BY pd.sex ORDER BY COUNT(m.id) DESC) as rnk
        FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id GROUP BY pd.sex, month_name
    ) SELECT sex, month_name, membership_count FROM gender_monthly WHERE rnk = 1 ORDER BY sex";
    $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Function to get peak months by age group
function getPeakMonthsByAge($pdo) {
    $sql = "
    WITH age_monthly AS (
        SELECT CASE WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64' ELSE '65+' END as age_group, DATE_FORMAT(m.created_at, '%M %Y') as month_name, COUNT(m.id) as membership_count, RANK() OVER (PARTITION BY CASE WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) < 18 THEN 'Under 18' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 45 AND 54 THEN '45-54' WHEN TIMESTAMPDIFF(YEAR, pd.birthdate, CURDATE()) BETWEEN 55 AND 64 THEN '55-64' ELSE '65+' END ORDER BY COUNT(m.id) DESC) as rnk
        FROM memberships m JOIN transactions t ON m.transaction_id = t.id JOIN users u ON t.user_id = u.id JOIN personal_details pd ON u.id = pd.user_id GROUP BY age_group, month_name
    ) SELECT age_group, month_name, membership_count FROM age_monthly WHERE rnk = 1 ORDER BY CASE age_group WHEN 'Under 18' THEN 1 WHEN '18-24' THEN 2 WHEN '25-34' THEN 3 WHEN '35-44' THEN 4 WHEN '45-54' THEN 5 WHEN '55-64' THEN 6 ELSE 7 END";
    $stmt = $pdo->prepare($sql); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Walk-ins query
$walkins_sql = "
    SELECT MONTH(date) as month, YEAR(date) as year, COUNT(*) as total_walkins, SUM(amount) as total_amount
    FROM walk_in_records WHERE is_paid = 1 GROUP BY year, month ORDER BY year, month";
$walkins_stmt = $pdo->prepare($walkins_sql); $walkins_stmt->execute(); $walkins = $walkins_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the peak months data
$peak_months_gender = getPeakMonthsByGender($pdo);
$peak_months_age = getPeakMonthsByAge($pdo);

date_default_timezone_set('Asia/Manila'); // Set timezone
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Report</title>
    <link rel="stylesheet" href="css/report.css"> <style>
        /* Basic styling for modal tables */
        #filteredReportModal .table { margin-bottom: 1rem; }
        #filteredReportModal h5 { margin-top: 1.5rem; border-bottom: 1px solid #dee2e6; padding-bottom: 0.5rem; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2> Analytics Report</h2>
                        <p class="text-muted mb-0">Generated on <?= date('F d, Y H:i:s T') ?></p> </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex justify-content-start align-items-center flex-wrap">
                <h5 class="me-3 mb-2 mb-md-0">Filter Report by Date:</h5>
                <div class="me-2 mb-2 mb-md-0">
                    <label for="startDate" class="form-label visually-hidden">Start Date</label>
                    <input type="date" id="startDate" class="form-control form-control-sm" placeholder="Start Date" aria-label="Start Date">
                </div>
                <div class="me-3 mb-2 mb-md-0">
                    <label for="endDate" class="form-label visually-hidden">End Date</label>
                    <input type="date" id="endDate" class="form-control form-control-sm" placeholder="End Date" aria-label="End Date">
                </div>
                <button id="filterButton" class="btn btn-primary btn-sm">Apply Filter</button>
                <span id="filterSpinner" class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                <span id="filterError" class="text-danger ms-2" style="display: none;"></span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Members</h5>
                        <h2 class="mb-0"><?= number_format($total_members) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="mb-0">₱<?= number_format($total_revenue, 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Average Check-ins</h5>
                        <h2 class="mb-0"><?= number_format((float)$avg_checkins, 1) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Performance Highlights (Overall)</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="h-100">
                                <div class="h5 mb-3 font-weight-bold text-primary">Income</div>
                                <div class="mb-3">
                                    <div class="text-success font-weight-bold">Highest: <?= htmlspecialchars($income_data['highest']['month']) ?> <i class="fas fa-arrow-up"></i></div>
                                    <div class="h4">₱<?= number_format($income_data['highest']['value'], 2) ?></div>
                                </div>
                                <div>
                                    <div class="text-danger font-weight-bold">Lowest: <?= htmlspecialchars($income_data['lowest']['month']) ?> <i class="fas fa-arrow-down"></i></div>
                                    <div class="h4">₱<?= number_format($income_data['lowest']['value'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                             <div class="h-100">
                                <div class="h5 mb-3 font-weight-bold text-primary">Services Availed</div>
                                <div class="mb-3">
                                    <div class="text-success font-weight-bold">Most: <?= htmlspecialchars($service_data['highest']['month']) ?> <i class="fas fa-arrow-up"></i></div>
                                    <div class="h4"><?= number_format($service_data['highest']['value']) ?> services</div>
                                </div>
                                <div>
                                    <div class="text-danger font-weight-bold">Least: <?= htmlspecialchars($service_data['lowest']['month']) ?> <i class="fas fa-arrow-down"></i></div>
                                    <div class="h4"><?= number_format($service_data['lowest']['value']) ?> services</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="h-100">
                                <div class="h5 mb-3 font-weight-bold text-primary">Memberships</div>
                                <div class="mb-3">
                                    <div class="text-success font-weight-bold">Most: <?= htmlspecialchars($membership_data['highest']['month']) ?> <i class="fas fa-arrow-up"></i></div>
                                    <div class="h4"><?= number_format($membership_data['highest']['value']) ?> memberships</div>
                                </div>
                                <div>
                                    <div class="text-danger font-weight-bold">Least: <?= htmlspecialchars($membership_data['lowest']['month']) ?> <i class="fas fa-arrow-down"></i></div>
                                    <div class="h4"><?= number_format($membership_data['lowest']['value']) ?> memberships</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section mb-4">
            <div class="card">
                 <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="mb-0 me-3">Member Attendance Analysis</h4>
                    <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('attendanceTable', 'attendance_report.csv')">
                        <i class="fas fa-download me-1"></i>Export Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="attendanceTable" style="width:100%;">
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
                                <?php foreach ($attendance as $row):
                                    $total_sessions = $row['total_check_ins'] + $row['total_missed'];
                                    $attendance_rate = ($total_sessions > 0) ? ($row['total_check_ins'] / $total_sessions) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= number_format($row['total_check_ins']) ?></td>
                                    <td><?= number_format($row['total_missed']) ?></td>
                                    <td><?= number_format($attendance_rate, 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($attendance)): ?>
                                    <tr><td colspan="6" class="text-center">No attendance data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-section mb-4">
            <div class="card">
                 <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="mb-0 me-3">Monthly Earnings (Memberships)</h4>
                     <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('earningsTable', 'monthly_earnings.csv')">
                        <i class="fas fa-download me-1"></i>Export Data
                    </button>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-hover" id="earningsTable" style="width:100%;">
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
                                 <?php if (empty($earnings)): ?>
                                    <tr><td colspan="4" class="text-center">No earnings data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

     <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Membership by Gender</h4>
                 <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('genderTable', 'gender_memberships.csv')">
                        <i class="fas fa-download me-1"></i>Export Data
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <canvas id="genderChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Peak Months by Gender (Overall)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="peakGenderTable" style="width:100%;">
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
                                     <?php if (empty($peak_months_gender)): ?>
                                        <tr><td colspan="3" class="text-center">No peak data available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <h5 class="mt-4">Monthly Gender Breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="genderTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Gender</th>
                                <th>Total Memberships</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_gender_distribution as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['month_name']) ?></td>
                                <td><?= htmlspecialchars($row['sex']) ?></td>
                                <td><?= number_format($row['total_memberships']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                             <?php if (empty($monthly_gender_distribution)): ?>
                                <tr><td colspan="3" class="text-center">No monthly gender data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Membership by Age Demographics</h4>
                 <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('ageTable', 'age_memberships.csv')">
                    <i class="fas fa-download me-1"></i>Export Data
                 </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <canvas id="ageChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Peak Months by Age Group (Overall)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="peakAgeTable" style="width:100%;">
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
                                    <?php if (empty($peak_months_age)): ?>
                                        <tr><td colspan="3" class="text-center">No peak data available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <h5 class="mt-4">Monthly Age Group Breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="ageTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Age Group</th>
                                <th>Total Memberships</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_age_distribution as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['month_name']) ?></td>
                                <td><?= htmlspecialchars($row['age_group']) ?></td>
                                <td><?= number_format($row['total_memberships']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                             <?php if (empty($monthly_age_distribution)): ?>
                                <tr><td colspan="3" class="text-center">No monthly age data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Member Service Utilization</h4>
                <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('utilizationTable', 'service_utilization.csv')">
                    <i class="fas fa-download me-1"></i>Export Data
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="utilizationTable" style="width:100%;">
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
                            <?php if (empty($utilization)): ?>
                                <tr><td colspan="6" class="text-center">No utilization data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Program Subscriptions</h4>
                <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('programsTable', 'program_subscriptions.csv')">
                   <i class="fas fa-download me-1"></i>Export Data
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="programsTable" style="width:100%;">
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
                            <?php if (empty($programs)): ?>
                                <tr><td colspan="4" class="text-center">No program subscription data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Rental Subscriptions</h4>
                <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('rentalsTable', 'rental_subscriptions.csv')">
                    <i class="fas fa-download me-1"></i>Export Data
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="rentalsTable" style="width:100%;">
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
                             <?php if (empty($rentals)): ?>
                                <tr><td colspan="4" class="text-center">No rental subscription data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-section mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 me-3">Walk-In Records</h4>
                <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" onclick="exportTableToCSV('walkinsTable', 'walkin_records.csv')">
                    <i class="fas fa-download me-1"></i>Export Data
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="walkinsTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Total Walk-ins</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($walkins as $row): ?>
                            <tr>
                                <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
                                <td><?= $row['year'] ?></td>
                                <td><?= number_format($row['total_walkins']) ?></td>
                                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($walkins)): ?>
                                <tr><td colspan="4" class="text-center">No walk-in data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    </div><div class="modal fade" id="filteredReportModal" tabindex="-1" aria-labelledby="filteredReportModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="filteredReportModalLabel">Filtered Report Results</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="filteredReportContent">
            <p>Loading filtered data...</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
      </div>
    </div>


    <script>
    // Function to escape HTML for safely inserting text content
    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Global variable to hold the filtered chart instance
    window.filteredRevenueChartInstance = null;
    window.filteredGenderChartInstance = null;
    window.filteredAgeChartInstance = null;

    // Global variables for filtered DataTables instances
    window.filteredDataTables = {};

     // Function to destroy existing DataTables in the modal
    function destroyFilteredDataTables() {
        for (const tableId in window.filteredDataTables) {
            if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                window.filteredDataTables[tableId].destroy();
                // console.log(`DataTable destroyed: ${tableId}`);
            }
             delete window.filteredDataTables[tableId]; // Remove reference
        }
        // Clear the container in case tables weren't properly removed
        $('#filteredReportContent').find('.dataTables_wrapper').remove();
    }

    // DataTables Initialization function for Modal Content
    function initializeModalDataTables() {
        // Ensure previous instances are destroyed before initializing new ones
        destroyFilteredDataTables();

        // Initialize tables inside the modal
         try {
            // Select only tables *inside* the modal body
            $('#filteredReportContent table.datatable-modal').each(function() {
                const tableId = $(this).attr('id');
                 if (tableId && !window.filteredDataTables[tableId]) { // Initialize only if ID exists and not already initialized
                    // Basic configuration - customize per table if needed
                    let config = {
                        pageLength: 10,
                        responsive: true,
                        destroy: true, // Ensure it can be re-initialized
                        language: {
                            emptyTable: "No data available for this period"
                        }
                        // Add specific ordering based on table ID if needed
                        // order: tableId === 'filteredAttendanceTable' ? [[3, 'desc']] : [[0, 'asc']]
                    };
                     window.filteredDataTables[tableId] = $(this).DataTable(config);
                     // console.log(`DataTable initialized: ${tableId}`);
                 }
            });
         } catch(e) {
              console.error("Error initializing modal DataTables:", e);
         }
    }

    // Export function (remains the same)
    window.exportTableToCSV = function(tableId, filename) {
        const table = document.getElementById(tableId);
         if (!table) {
            console.error("Table not found for export:", tableId);
            alert("Error: Could not find table data to export.");
            return;
        }
        const rows = table.querySelectorAll('tbody tr'); // Select only body rows for data
        let csv = [];

        // Header Row
        const headerCells = table.querySelectorAll('thead th');
        let headerRow = [];
        headerCells.forEach(cell => {
            headerRow.push('"' + cell.innerText.replace(/"/g, '""') + '"');
        });
         csv.push(headerRow.join(','));

        // Data Rows
        rows.forEach(row => {
            const rowData = [];
            const cols = row.querySelectorAll('td');
            if (cols.length > 0 && !row.classList.contains('dt-empty')) { // Skip DataTables empty row
                 cols.forEach(col => {
                     // Preserve formatting like currency symbols if possible, handle potential nulls
                     let cellText = col.innerText || '';
                    rowData.push('"' + cellText.replace(/"/g, '""') + '"');
                 });
                csv.push(rowData.join(','));
             }
        });

        if (csv.length <= 1) { // Only header row or empty
             alert("No data available in the table to export.");
             return;
         }

        const csvFile = new Blob([csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
        const downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink); // Clean up
    };


    $(document).ready(function() {

        // --- Initialize DataTables for the MAIN PAGE ---
        try {
            $('#attendanceTable').DataTable({ pageLength: 10, order: [[3, 'desc']], responsive: true });
            $('#earningsTable').DataTable({ pageLength: 10, order: [[1, 'desc'],[0, 'desc']], responsive: true }); // Year, Month
            $('#genderTable').DataTable({ pageLength: 10, order: [[0, 'desc']], responsive: true }); // Month Name desc
            $('#ageTable').DataTable({ pageLength: 10, order: [[0, 'desc']], responsive: true }); // Month Name desc
            $('#utilizationTable').DataTable({ pageLength: 10, order: [[3, 'desc']], responsive: true });
            $('#programsTable').DataTable({ pageLength: 10, order: [[1, 'desc'], [0, 'desc']], responsive: true });
            $('#rentalsTable').DataTable({ pageLength: 10, order: [[1, 'desc'], [0, 'desc']], responsive: true });
            $('#walkinsTable').DataTable({ pageLength: 10, order: [[1, 'desc'], [0, 'desc']], responsive: true });

            // Small tables without pagination/searching
            $('#peakGenderTable').DataTable({ paging: false, searching: false, info: false, ordering: true, order: [[0, 'asc']], responsive: true});
            $('#peakAgeTable').DataTable({ paging: false, searching: false, info: false, ordering: true, order: [[0, 'asc']], responsive: true});

        } catch(e) {
            console.error("Main page DataTables initialization error:", e);
        }

        // --- Initialize Charts for the MAIN PAGE ---
         try {
            // Revenue Chart (Main Page)
            const mainChartData = <?= json_encode($formatted_earnings) ?>;
            const mainCtx = document.getElementById('revenueChart');
            if (mainCtx) {
                 // Check if chart instance exists and destroy it (useful for potential page reloads/updates)
                if (window.mainRevenueChart instanceof Chart) { window.mainRevenueChart.destroy(); }
                window.mainRevenueChart = new Chart(mainCtx, {
                    type: 'line',
                    data: {
                        labels: mainChartData.map(item => item.month),
                        datasets: [{
                            label: 'Monthly Revenue (Memberships)', data: mainChartData.map(item => item.total_amount),
                            borderColor: 'rgb(75, 192, 192)', tension: 0.1
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'top' }, title: { display: true, text: 'Monthly Revenue Trend (Overall)' } } }
                });
            } else { console.warn("Canvas element #revenueChart not found."); }

            // Gender Chart (Main Page)
            const mainGenderData = <?= json_encode($gender_distribution) ?>;
            const mainGenderCtx = document.getElementById('genderChart');
             if (mainGenderCtx) {
                 if (window.mainGenderChart instanceof Chart) { window.mainGenderChart.destroy(); }
                const genderLabels = mainGenderData.map(item => item.sex || "Unknown");
                const genderValues = mainGenderData.map(item => item.total_memberships || 0);
                window.mainGenderChart = new Chart(mainGenderCtx, {
                    type: 'pie',
                    data: { labels: genderLabels, datasets: [{ data: genderValues, backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(75, 192, 192, 0.7)'], borderWidth: 1 }] },
                    options: { responsive: true, plugins: { legend: { position: 'top' }, title: { display: true, text: 'Membership Distribution by Gender (Overall)' }, tooltip: { callbacks: { label: function(context) { const label=context.label||''; const value=context.raw||0; const total=context.dataset.data.reduce((a,b)=>a+b,0); const percentage=total>0?((value/total)*100).toFixed(1):0; return `${label}: ${value} (${percentage}%)`; } } } } }
                });
             } else { console.warn("Canvas element #genderChart not found."); }

            // Age Chart (Main Page)
            const mainAgeData = <?= json_encode($age_distribution) ?>;
            const mainAgeCtx = document.getElementById('ageChart');
            if (mainAgeCtx) {
                if (window.mainAgeChart instanceof Chart) { window.mainAgeChart.destroy(); }
                window.mainAgeChart = new Chart(mainAgeCtx, {
                    type: 'bar',
                    data: {
                        labels: mainAgeData.map(item => item.age_group),
                        datasets: [{ label: 'Memberships by Age Group', data: mainAgeData.map(item => item.total_memberships), backgroundColor: 'rgba(153, 102, 255, 0.7)', borderColor: 'rgba(153, 102, 255, 1)', borderWidth: 1 }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Membership Distribution by Age Group (Overall)' } } }
                });
            } else { console.warn("Canvas element #ageChart not found."); }

        } catch(e) {
            console.error("Main page chart initialization error:", e);
        }


        // --- Filter Functionality ---
        const today = new Date().toISOString().split('T')[0];
        $('#endDate').val(today); // Default end date to today

        $('#filterButton').on('click', function() {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            const filterButton = $(this);
            const spinner = $('#filterSpinner');
            const errorSpan = $('#filterError');

            errorSpan.hide().text(''); // Clear previous errors

            // Validation
            if (!startDate || !endDate) { errorSpan.text('Please select both start and end dates.').show(); return; }
            if (startDate > endDate) { errorSpan.text('Start date cannot be after end date.').show(); return; }
            if (endDate > today) { errorSpan.text('End date cannot be in the future.').show(); return; }


            filterButton.prop('disabled', true);
            spinner.show();
            $('#filteredReportContent').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading filtered data...</p></div>'); // Show loading state in modal body

            // Show modal immediately with loading indicator
             var reportModal = new bootstrap.Modal(document.getElementById('filteredReportModal'));
             reportModal.show();


            $.ajax({
                url: 'pages/report/report.php', // Post back to self
                method: 'POST',
                data: { action: 'filter_report', start_date: startDate, end_date: endDate },
                dataType: 'json',
                success: function(response) {
                    if (response.error) { // Handle errors returned from PHP
                         errorSpan.text('Error: ' + response.error).show();
                         $('#filteredReportContent').html('<p class="text-danger p-4">Error loading data: ' + escapeHtml(response.error) + '</p>');
                    } else {
                         // --- Build Modal Content ---
                         let modalContent = `<h4 class="mb-3">Report from ${escapeHtml(startDate)} to ${escapeHtml(endDate)}</h4>`;

                         // Summary Stats
                         modalContent += `<div class="row mb-3">
                             <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h6 class="card-subtitle mb-2 text-muted">Total Members</h6><p class="h4 card-title">${escapeHtml(response.total_members ?? 0)}</p></div></div></div>
                             <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h6 class="card-subtitle mb-2 text-muted">Total Revenue</h6><p class="h4 card-title">₱${parseFloat(response.total_revenue ?? 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p></div></div></div>
                             <div class="col-md-4"><div class="card h-100"><div class="card-body text-center"><h6 class="card-subtitle mb-2 text-muted">Avg Check-ins</h6><p class="h4 card-title">${parseFloat(response.avg_checkins ?? 0).toFixed(1)}</p></div></div></div>
                         </div>`;

                          // Performance Highlights (Filtered - Placeholder)
                         // TODO: Populate this with response.income_data, response.service_data, response.membership_data if calculated in PHP
                         modalContent += `<h5>Performance Highlights (Filtered Period)</h5>`;
                         modalContent += `<p class="text-muted"><em>Extreme calculation for filtered period needs to be implemented in PHP.</em></p>`;
                         // Example Structure:
                         // modalContent += `<div class="row mb-3">... display highest/lowest income, services, memberships for the period ...</div>`;


                        // Attendance Table
                        modalContent += '<h5>Member Attendance</h5>';
                        if(response.attendance && response.attendance.length > 0) {
                            modalContent += '<table class="table table-sm table-striped table-hover datatable-modal" id="filteredAttendanceTable" style="width:100%;"><thead><tr><th>Username</th><th>Name</th><th>Check-ins</th><th>Missed</th><th>Rate</th></tr></thead><tbody>';
                            response.attendance.forEach(row => {
                                const total_sessions = parseInt(row.total_check_ins || 0) + parseInt(row.total_missed || 0);
                                const rate = total_sessions > 0 ? ((parseInt(row.total_check_ins || 0) / total_sessions) * 100).toFixed(1) + '%' : 'N/A';
                                modalContent += `<tr>
                                    <td>${escapeHtml(row.username)}</td>
                                    <td>${escapeHtml(row.first_name)} ${escapeHtml(row.last_name)}</td>
                                    <td>${escapeHtml(row.total_check_ins)}</td>
                                    <td>${escapeHtml(row.total_missed)}</td>
                                    <td>${escapeHtml(rate)}</td>
                                </tr>`;
                            });
                            modalContent += '</tbody></table>';
                        } else {
                            modalContent += '<p>No attendance data for this period.</p>';
                        }

                        // Earnings Chart & Table
                        modalContent += '<h5>Monthly Earnings (Memberships)</h5>';
                        modalContent += '<canvas id="filteredRevenueChart" height="100"></canvas>'; // Added height constraint
                        if(response.earnings && response.earnings.length > 0) {
                            modalContent += '<div class="table-responsive mt-3"><table class="table table-sm table-striped table-hover datatable-modal" id="filteredEarningsTable" style="width:100%;"><thead><tr><th>Month</th><th>Year</th><th>Memberships</th><th>Amount</th></tr></thead><tbody>';
                             response.earnings.forEach(row => {
                                 modalContent += `<tr>
                                     <td>${escapeHtml(new Date(row.year, row.month - 1).toLocaleString('default', { month: 'long' }))}</td>
                                     <td>${escapeHtml(row.year)}</td>
                                     <td>${escapeHtml(row.total_memberships)}</td>
                                     <td>₱${parseFloat(row.total_amount ?? 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                 </tr>`;
                             });
                            modalContent += '</tbody></table></div>';
                        } else {
                             modalContent += '<p class="mt-3">No membership earnings data for this period.</p>';
                        }

                        // Gender Demographics (Charts & Tables)
                        modalContent += '<h5>Membership by Gender</h5>';
                        modalContent += '<div class="row"><div class="col-md-6"><canvas id="filteredGenderChart" height="150"></canvas></div><div class="col-md-6">';
                        // TODO: Add filtered peak gender table (from response.peak_months_gender)
                         modalContent += '<h6>Peak Months (Filtered)</h6><p class="text-muted"><em>Peak calculation needs implementation.</em></p>';
                        modalContent += '</div></div>';
                         // TODO: Add filtered monthly gender table (from response.monthly_gender_distribution)
                         modalContent += '<h6 class="mt-3">Monthly Breakdown (Filtered)</h6><p class="text-muted"><em>Table needs implementation.</em></p>';

                         // Age Demographics (Charts & Tables)
                        modalContent += '<h5>Membership by Age</h5>';
                        modalContent += '<div class="row"><div class="col-md-6"><canvas id="filteredAgeChart" height="150"></canvas></div><div class="col-md-6">';
                        // TODO: Add filtered peak age table (from response.peak_months_age)
                        modalContent += '<h6>Peak Months (Filtered)</h6><p class="text-muted"><em>Peak calculation needs implementation.</em></p>';
                        modalContent += '</div></div>';
                         // TODO: Add filtered monthly age table (from response.monthly_age_distribution)
                         modalContent += '<h6 class="mt-3">Monthly Breakdown (Filtered)</h6><p class="text-muted"><em>Table needs implementation.</em></p>';


                        // Service Utilization Table
                        modalContent += '<h5>Member Service Utilization</h5>';
                        if(response.utilization && response.utilization.length > 0) {
                            modalContent += '<table class="table table-sm table-striped table-hover datatable-modal" id="filteredUtilizationTable" style="width:100%;"><thead><tr><th>Username</th><th>Name</th><th>Memberships</th><th>Programs</th><th>Rentals</th></tr></thead><tbody>';
                             response.utilization.forEach(row => {
                                 modalContent += `<tr>
                                     <td>${escapeHtml(row.username)}</td>
                                     <td>${escapeHtml(row.first_name)} ${escapeHtml(row.last_name)}</td>
                                     <td>${escapeHtml(row.membership_count)}</td>
                                     <td>${escapeHtml(row.program_subscriptions)}</td>
                                     <td>${escapeHtml(row.rental_subscriptions)}</td>
                                 </tr>`;
                             });
                             modalContent += '</tbody></table>';
                        } else {
                             modalContent += '<p>No service utilization data for this period.</p>';
                        }

                        // Programs Table
                         modalContent += '<h5>Program Subscriptions</h5>';
                         // TODO: Create table from response.programs

                         // Rentals Table
                         modalContent += '<h5>Rental Subscriptions</h5>';
                         // TODO: Create table from response.rentals

                         // Walkins Table
                         modalContent += '<h5>Walk-ins</h5>';
                         // TODO: Create table from response.walkins


                        // Update modal content
                        $('#filteredReportContent').html(modalContent);

                        // Initialize DataTables for the newly added tables in the modal
                        initializeModalDataTables();

                         // Initialize/Update Charts AFTER content is in DOM and modal is shown
                         // Handled by 'shown.bs.modal' event listener


                    } // end else (success)
                }, // end success callback
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error, xhr.responseText);
                    errorSpan.text('An error occurred while filtering. Check console.').show();
                    let errorMsg = "An error occurred while fetching data.";
                    if(xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = "Error: " + escapeHtml(xhr.responseJSON.error);
                    } else if (xhr.responseText) {
                         // Try to show response text if not JSON error provided
                         // errorMsg += "<br><small>" + escapeHtml(xhr.responseText.substring(0, 200)) + "...</small>";
                    }
                    $('#filteredReportContent').html('<p class="text-danger p-4">' + errorMsg + '</p>');
                },
                complete: function() {
                    filterButton.prop('disabled', false);
                    spinner.hide();
                }
            }); // end $.ajax
        }); // end filterButton click


        // --- Modal Event Listeners ---
        const reportModalElement = document.getElementById('filteredReportModal');

        reportModalElement.addEventListener('shown.bs.modal', function (event) {
            // Initialize/Reinitialize charts when modal is fully shown
             try {
                 const responseData = $('#filterButton').data('reportData'); // Retrieve data if stored
                 if (!responseData) {
                     // console.warn("No report data found for charts in modal.");
                     // Maybe try to fetch again or show message?
                      // Attempt to draw charts even if data wasn't stored on button, using current content state
                      // This might fail if AJAX hasn't completed yet, but it's a fallback.
                      // Better approach: Pass data directly to this event handler if possible.

                 }

                 // Destroy previous chart instances inside the modal
                  if (window.filteredRevenueChartInstance instanceof Chart) { window.filteredRevenueChartInstance.destroy(); }
                  if (window.filteredGenderChartInstance instanceof Chart) { window.filteredGenderChartInstance.destroy(); }
                  if (window.filteredAgeChartInstance instanceof Chart) { window.filteredAgeChartInstance.destroy(); }


                 // Revenue Chart (Filtered)
                 const filteredChartCtx = document.getElementById('filteredRevenueChart')?.getContext('2d');
                 const filteredEarnings = responseData?.formatted_earnings || []; // Use optional chaining
                 if (filteredChartCtx && filteredEarnings.length > 0) {
                     window.filteredRevenueChartInstance = new Chart(filteredChartCtx, {
                         type: 'line', data: { labels: filteredEarnings.map(item => item.month), datasets: [{ label: 'Filtered Monthly Revenue', data: filteredEarnings.map(item => item.total_amount), borderColor: 'rgb(255, 159, 64)', tension: 0.1 }] },
                         options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, title: { display: true, text: 'Filtered Revenue Trend' } } }
                     });
                 } else if (filteredChartCtx) {
                     // Optionally draw an empty state or message on the canvas
                     filteredChartCtx.clearRect(0, 0, filteredChartCtx.canvas.width, filteredChartCtx.canvas.height);
                     filteredChartCtx.textAlign = 'center'; filteredChartCtx.fillText('No revenue data for this period', filteredChartCtx.canvas.width/2, 50);
                 }

                 // Gender Chart (Filtered)
                 // TODO: Get filtered gender data (e.g., responseData.gender_distribution)
                 const filteredGenderCtx = document.getElementById('filteredGenderChart')?.getContext('2d');
                 const filteredGenderData = responseData?.gender_distribution || [];
                  if (filteredGenderCtx && filteredGenderData.length > 0) {
                     const genderLabels = filteredGenderData.map(item => item.sex || "Unknown");
                     const genderValues = filteredGenderData.map(item => item.total_memberships || 0);
                     window.filteredGenderChartInstance = new Chart(filteredGenderCtx, {
                        type: 'pie', data: { labels: genderLabels, datasets: [{ data: genderValues, backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(75, 192, 192, 0.7)'], borderWidth: 1 }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, title: { display: true, text: 'Gender Distribution (Filtered)' }, tooltip: { callbacks: { label: function(context){ /* same tooltip as main chart */ const label=context.label||'';const value=context.raw||0; const total=context.dataset.data.reduce((a,b)=>a+b,0); const percentage=total>0?((value/total)*100).toFixed(1):0; return `${label}: ${value} (${percentage}%)`; } } } } }
                     });
                  } else if (filteredGenderCtx) {
                      filteredGenderCtx.clearRect(0, 0, filteredGenderCtx.canvas.width, filteredGenderCtx.canvas.height);
                      filteredGenderCtx.textAlign = 'center'; filteredGenderCtx.fillText('No gender data for this period', filteredGenderCtx.canvas.width/2, 50);
                  }


                 // Age Chart (Filtered)
                 // TODO: Get filtered age data (e.g., responseData.age_distribution)
                  const filteredAgeCtx = document.getElementById('filteredAgeChart')?.getContext('2d');
                  const filteredAgeData = responseData?.age_distribution || [];
                   if (filteredAgeCtx && filteredAgeData.length > 0) {
                      window.filteredAgeChartInstance = new Chart(filteredAgeCtx, {
                         type: 'bar', data: { labels: filteredAgeData.map(item => item.age_group), datasets: [{ label: 'Memberships by Age Group', data: filteredAgeData.map(item => item.total_memberships), backgroundColor: 'rgba(153, 102, 255, 0.7)', borderColor: 'rgba(153, 102, 255, 1)', borderWidth: 1 }] },
                         options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Age Distribution (Filtered)' } } }
                      });
                   } else if (filteredAgeCtx) {
                       filteredAgeCtx.clearRect(0, 0, filteredAgeCtx.canvas.width, filteredAgeCtx.canvas.height);
                       filteredAgeCtx.textAlign = 'center'; filteredAgeCtx.fillText('No age data for this period', filteredAgeCtx.canvas.width/2, 50);
                   }


             } catch(e) {
                 console.error("Error initializing/updating modal charts:", e);
             }

             // Store the data used for the modal charts/tables on the button for potential re-use/export
              // Retrieve it from the AJAX success function if possible
              // This is a workaround as passing data directly to event listener is tricky
             $.ajax({ /* Re-fetch or use stored data */ }).done(function(data) {
                $('#filterButton').data('reportData', data);
             });

        });

        reportModalElement.addEventListener('hidden.bs.modal', function (event) {
            // Destroy charts when modal is hidden
            if (window.filteredRevenueChartInstance instanceof Chart) { window.filteredRevenueChartInstance.destroy(); window.filteredRevenueChartInstance = null;}
            if (window.filteredGenderChartInstance instanceof Chart) { window.filteredGenderChartInstance.destroy(); window.filteredGenderChartInstance = null; }
            if (window.filteredAgeChartInstance instanceof Chart) { window.filteredAgeChartInstance.destroy(); window.filteredAgeChartInstance = null; }

            // Destroy DataTables in the modal
             destroyFilteredDataTables();


            // Clear the modal content to prevent stale data flashing on next open
            $('#filteredReportContent').html('<p>Loading filtered data...</p>');
             $('#filterButton').removeData('reportData'); // Clear stored data
        });


    }); // end document ready
    </script>

</body>
</html>