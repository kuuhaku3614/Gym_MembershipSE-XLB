<?php
// api/attendance.php
header('Content-Type: application/json');
require_once 'config.php';
$database = new Database();
$pdo = $database->connect();

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
    ORDER BY total_check_ins DESC
";

$attendance_stmt = $pdo->prepare($attendance_sql);
$attendance_stmt->execute();
echo json_encode($attendance_stmt->fetchAll(PDO::FETCH_ASSOC));

// api/revenue.php
header('Content-Type: application/json');
require_once 'config.php';
$database = new Database();
$pdo = $database->connect();

$revenue_sql = "
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as total_memberships,
        SUM(amount) as total_amount
    FROM memberships
    GROUP BY month, year
    ORDER BY year DESC, month DESC
";

$revenue_stmt = $pdo->prepare($revenue_sql);
$revenue_stmt->execute();
echo json_encode($revenue_stmt->fetchAll(PDO::FETCH_ASSOC));

// api/revenue-trends.php
header('Content-Type: application/json');
require_once 'config.php';
$database = new Database();
$pdo = $database->connect();

$trends_sql = "
    SELECT 
        DATE_FORMAT(created_at, '%b %Y') as month,
        SUM(amount) as amount
    FROM memberships
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at
";

$trends_stmt = $pdo->prepare($trends_sql);
$trends_stmt->execute();
echo json_encode($trends_stmt->fetchAll(PDO::FETCH_ASSOC));