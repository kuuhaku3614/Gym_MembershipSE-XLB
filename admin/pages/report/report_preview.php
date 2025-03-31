<?php
require_once 'config.php';
require '../../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
$database = new Database();
$pdo = $database->connect();

// Check if this is a PDF export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_pdf'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $selected_sections = isset($_POST['report_sections']) ? $_POST['report_sections'] : [];
    
    // Get filtered data
    $data = getFilteredData($pdo, $start_date, $end_date);
    
    // Generate the PDF from the HTML preview
    generatePDF($data, $start_date, $end_date, $selected_sections);
    
    // Stop execution after PDF is created and sent to the browser
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_report'])) {
    // This is the original preview functionality
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $selected_sections = isset($_POST['report_sections']) ? $_POST['report_sections'] : [];
    
    // Get filtered data
    $data = getFilteredData($pdo, $start_date, $end_date);
    
    // Generate the HTML preview
    echo generatePreviewHTML($data, $start_date, $end_date, $selected_sections);
}

/**
 * Get data with date range filter (copied from report.php)
 */
function getFilteredData($pdo, $start_date, $end_date) {
    // This function should be exactly the same as in report.php
    // Copy the full function from there to ensure consistency
    
    $data = [];
    
    // Calculate total members within date range
    $total_members_sql = "SELECT COALESCE(COUNT(*), 0) AS total
FROM memberships
WHERE status IN ('active', 'expired', 'expiring') and created_at BETWEEN :start_date AND :end_date";
    $total_members_stmt = $pdo->prepare($total_members_sql);
    $total_members_stmt->bindParam(':start_date', $start_date);
    $total_members_stmt->bindParam(':end_date', $end_date);
    $total_members_stmt->execute();
    $data['total_members'] = $total_members_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate total revenue within date range
    $total_revenue_sql = "
        SELECT 
            (SELECT COALESCE(SUM(amount), 0) FROM memberships WHERE created_at BETWEEN :start_date1 AND :end_date1) +
            (SELECT COALESCE(SUM(amount), 0) FROM program_subscription_schedule WHERE created_at BETWEEN :start_date2 AND :end_date2) +
            (SELECT COALESCE(SUM(amount), 0) FROM rental_subscriptions WHERE created_at BETWEEN :start_date3 AND :end_date3) as total_revenue";
    $total_revenue_stmt = $pdo->prepare($total_revenue_sql);
    $total_revenue_stmt->bindParam(':start_date1', $start_date);
    $total_revenue_stmt->bindParam(':end_date1', $end_date);
    $total_revenue_stmt->bindParam(':start_date2', $start_date);
    $total_revenue_stmt->bindParam(':end_date2', $end_date);
    $total_revenue_stmt->bindParam(':start_date3', $start_date);
    $total_revenue_stmt->bindParam(':end_date3', $end_date);
    $total_revenue_stmt->execute();
    $data['total_revenue'] = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

    // Calculate average check-ins within date range
    $avg_checkins_sql = "
        SELECT AVG(total_checkins) as avg_checkins FROM (
            SELECT COUNT(CASE WHEN ah.status = 'checked_in' THEN 1 END) as total_checkins
            FROM attendance_history ah
            JOIN attendance a ON ah.attendance_id = a.id
            WHERE ah.created_at BETWEEN :start_date AND :end_date
            GROUP BY a.user_id
        ) as user_checkins";
    $avg_checkins_stmt = $pdo->prepare($avg_checkins_sql);
    $avg_checkins_stmt->bindParam(':start_date', $start_date);
    $avg_checkins_stmt->bindParam(':end_date', $end_date);
    $avg_checkins_stmt->execute();
    $data['avg_checkins'] = $avg_checkins_stmt->fetch(PDO::FETCH_ASSOC)['avg_checkins'];

    // Attendance data with date range
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
        WHERE ah.created_at BETWEEN :start_date AND :end_date
        GROUP BY u.id
        ORDER BY total_check_ins DESC";
    $attendance_stmt = $pdo->prepare($attendance_sql);
    $attendance_stmt->bindParam(':start_date', $start_date);
    $attendance_stmt->bindParam(':end_date', $end_date);
    $attendance_stmt->execute();
    $data['attendance'] = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Earnings data with date range
    $earnings_sql = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_memberships,
            SUM(amount) as total_amount
        FROM memberships
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY month, year
        ORDER BY year, month";
    $earnings_stmt = $pdo->prepare($earnings_sql);
    $earnings_stmt->bindParam(':start_date', $start_date);
    $earnings_stmt->bindParam(':end_date', $end_date);
    $earnings_stmt->execute();
    $data['earnings'] = $earnings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format earnings data for chart
    $data['formatted_earnings'] = array_map(function($row) {
        return [
            'month' => date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])),
            'total_amount' => $row['total_amount']
        ];
    }, $data['earnings']);

    // Member Service Utilization with date range
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
        LEFT JOIN memberships m ON t.id = m.transaction_id AND m.created_at BETWEEN :start_date1 AND :end_date1
        LEFT JOIN program_subscriptions ps ON t.id = ps.id AND ps.created_at BETWEEN :start_date2 AND :end_date2
        LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id AND rs.created_at BETWEEN :start_date3 AND :end_date3
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'member'
        GROUP BY u.id
        ORDER BY membership_count DESC";
    $utilization_stmt = $pdo->prepare($utilization_sql);
    $utilization_stmt->bindParam(':start_date1', $start_date);
    $utilization_stmt->bindParam(':end_date1', $end_date);
    $utilization_stmt->bindParam(':start_date2', $start_date);
    $utilization_stmt->bindParam(':end_date2', $end_date);
    $utilization_stmt->bindParam(':start_date3', $start_date);
    $utilization_stmt->bindParam(':end_date3', $end_date);
    $utilization_stmt->execute();
    $data['utilization'] = $utilization_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Programs data with date range
    $programs_sql = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_subscriptions,
            SUM(amount) as total_amount
        FROM program_subscription_schedule
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY month, year
        ORDER BY year, month";
    $programs_stmt = $pdo->prepare($programs_sql);
    $programs_stmt->bindParam(':start_date', $start_date);
    $programs_stmt->bindParam(':end_date', $end_date);
    $programs_stmt->execute();
    $data['programs'] = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rentals data with date range
    $rentals_sql = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_rentals,
            SUM(amount) as total_amount
        FROM rental_subscriptions
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY month, year
        ORDER BY year, month";
    $rentals_stmt = $pdo->prepare($rentals_sql);
    $rentals_stmt->bindParam(':start_date', $start_date);
    $rentals_stmt->bindParam(':end_date', $end_date);
    $rentals_stmt->execute();
    $data['rentals'] = $rentals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

/**
 * Generate HTML preview for report
 */
function generatePreviewHTML($data, $start_date, $end_date, $selected_sections) {
    $html = '
    <div class="report-preview">
        <div class="report-header text-center mb-4">
            <h2 class="mb-1">Analytics Report</h2>
            <div class="text-muted">Report Period: '.date('F d, Y', strtotime($start_date)).' - '.date('F d, Y', strtotime($end_date)).'</div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Members</h6>
                        <h3 class="text-primary">'.number_format($data['total_members']).'</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h3 class="text-success">Php'.number_format($data['total_revenue'], 2).'</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Average Check-ins</h6>
                        <h3 class="text-info">'.number_format($data['avg_checkins'], 1).'</h3>
                    </div>
                </div>
            </div>
        </div>';
    
    // Add selected sections
    if (empty($selected_sections) || in_array('attendance', $selected_sections)) {
        $html .= '
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Member Attendance Analysis</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Check-ins</th>
                                <th>Missed</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($data['attendance'] as $row) {
            $attendance_rate = 0;
            if (($row['total_check_ins'] + $row['total_missed']) > 0) {
                $attendance_rate = ($row['total_check_ins'] / ($row['total_check_ins'] + $row['total_missed'])) * 100;
            }
            $html .= '
                <tr>
                    <td>'.htmlspecialchars($row['username']).'</td>
                    <td>'.htmlspecialchars($row['first_name']).' '.htmlspecialchars($row['last_name']).'</td>
                    <td>'.number_format($row['total_check_ins']).'</td>
                    <td>'.number_format($row['total_missed']).'</td>
                    <td>'.number_format($attendance_rate, 1).'%</td>
                </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }
    
    // Monthly Earnings
    if (empty($selected_sections) || in_array('earnings', $selected_sections)) {
        $html .= '
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Monthly Earnings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Total Memberships</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($data['earnings'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td>'.number_format($row['total_memberships']).'</td>
                    <td>Php'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }
    
    // Member Service Utilization
    if (empty($selected_sections) || in_array('utilization', $selected_sections)) {
        $html .= '
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Member Service Utilization</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Membership Count</th>
                                <th>Program Subscriptions</th>
                                <th>Rental Subscriptions</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($data['utilization'] as $row) {
            $html .= '
                <tr>
                    <td>'.htmlspecialchars($row['username']).'</td>
                    <td>'.htmlspecialchars($row['first_name']).' '.htmlspecialchars($row['last_name']).'</td>
                    <td>'.number_format($row['membership_count']).'</td>
                    <td>'.number_format($row['program_subscriptions']).'</td>
                    <td>'.number_format($row['rental_subscriptions']).'</td>
                </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }
    
    // Program Subscriptions
    if (empty($selected_sections) || in_array('programs', $selected_sections)) {
        $html .= '
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Program Subscriptions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Total Subscriptions</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($data['programs'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td>'.number_format($row['total_subscriptions']).'</td>
                    <td>Php'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }
    
    // Rental Subscriptions
    if (empty($selected_sections) || in_array('rentals', $selected_sections)) {
        $html .= '
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Rental Subscriptions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Total Rentals</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($data['rentals'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td>'.number_format($row['total_rentals']).'</td>
                    <td>Php'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }
    
    // Close HTML
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate and download a PDF file from the report data
 */
function generatePDF($data, $start_date, $end_date, $selected_sections) {
    // Generate the HTML content for the PDF
    $html = generatePDFHTML($data, $start_date, $end_date, $selected_sections);
    
    // Set up DomPDF options
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    // Create new DomPDF instance
    $dompdf = new Dompdf($options);
    
    // Load HTML into DomPDF
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the PDF
    $dompdf->render();
    
    // Create a filename with the date range
    $pdf_filename = 'analytics_report_' . 
    date('M', strtotime($start_date)) . date('j', strtotime($start_date)) . ',' . date('Y', strtotime($start_date)) . 
    '-' . 
    date('M', strtotime($end_date)) . date('j', strtotime($end_date)) . ',' . date('Y', strtotime($end_date)) . 
    '.pdf';  
    // Stream the PDF to the browser for download
    $dompdf->stream($pdf_filename, array('Attachment' => true));
}

/**
 * Generate HTML specifically formatted for PDF output
 */
function generatePDFHTML($data, $start_date, $end_date, $selected_sections) {
    // PDF-optimized CSS with more formal styling
    $styles = '
<style>
    body {
        font-family: "DejaVu Sans", "Helvetica", sans-serif;
        font-size: 9pt;
        line-height: 1.3;
        color: #000000;
        margin: 0;
        padding: 15px;
    }
    .report-header {
        text-align: center;
        border-bottom: 1px solid #000000;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .report-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        text-align: center;
        font-size: 8pt;
        color: #666;
        border-top: 1px solid #000000;
        padding-top: 5px;
    }
    .page-number:after {
        content: counter(page);
    }
    h1, h2, h3, h4, h5, h6 {
        margin-top: 0;
        margin-bottom: 8px;
        font-weight: bold;
    }
    h2 {
        font-size: 14pt;
    }
    h5 {
        font-size: 10pt;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .summary-section {
        margin-bottom: 20px;
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .summary-table td {
        padding: 5px;
        border: 1px solid #000000;
    }
    .summary-table .header {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    .summary-table .value {
        text-align: center;
        font-weight: bold;
        font-size: 12pt;
    }
    .section {
        margin-bottom: 20px;
        page-break-inside: avoid;
    }
    .section-header {
        background-color: #f2f2f2;
        padding: 5px;
        font-weight: bold;
        border: 1px solid #000000;
        border-bottom: none;
    }
    .section-content {
        border: 1px solid #000000;
        padding: 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    th, td {
        padding: 4px;
        border: 1px solid #000000;
    }
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: left;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .page-break {
        page-break-before: always;
    }
    .report-meta {
        font-size: 8pt;
        color: #666;
        margin-bottom: 10px;
    }
</style>';

    // Start building the HTML document
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Analytics Report</title>
        ' . $styles . '
    </head>
    <body>
        <div class="report-header">
            <h2>FITNESS CLUB ANALYTICS REPORT</h2>
            <div>Reporting Period: '.date('F d, Y', strtotime($start_date)).' - '.date('F d, Y', strtotime($end_date)).'</div>
            <div class="report-meta">Report ID: '.uniqid('RPT-').' | Generated: '.date('F d, Y H:i:s').'</div>
        </div>
        
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="header">TOTAL MEMBERS</td>
                    <td class="header">TOTAL REVENUE</td>
                    <td class="header">AVG. CHECK-INS</td>
                </tr>
                <tr>
                    <td class="value">'.number_format($data['total_members']).'</td>
                    <td class="value">₱'.number_format($data['total_revenue'], 2).'</td>
                    <td class="value">'.number_format($data['avg_checkins'], 1).'</td>
                </tr>
            </table>
        </div>';
    
    // Add selected sections
    if (empty($selected_sections) || in_array('attendance', $selected_sections)) {
        $html .= '
        <div class="section">
            <div class="section-header">MEMBER ATTENDANCE ANALYSIS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Check-ins</th>
                            <th>Missed</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['attendance'] as $row) {
            $attendance_rate = 0;
            if (($row['total_check_ins'] + $row['total_missed']) > 0) {
                $attendance_rate = ($row['total_check_ins'] / ($row['total_check_ins'] + $row['total_missed'])) * 100;
            }
            $html .= '
                <tr>
                    <td>'.htmlspecialchars($row['username']).'</td>
                    <td>'.htmlspecialchars($row['first_name']).' '.htmlspecialchars($row['last_name']).'</td>
                    <td class="text-right">'.number_format($row['total_check_ins']).'</td>
                    <td class="text-right">'.number_format($row['total_missed']).'</td>
                    <td class="text-right">'.number_format($attendance_rate, 1).'%</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    // Add earnings section
    if (empty($selected_sections) || in_array('earnings', $selected_sections)) {
        // Add page break if necessary
        if (count($data['earnings']) > 5) {
            $html .= '<div class="page-break"></div>';
        }
        
        $html .= '
        <div class="section">
            <div class="section-header">MONTHLY EARNINGS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Total Memberships</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['earnings'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td class="text-right">'.number_format($row['total_memberships']).'</td>
                    <td class="text-right">₱'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    // Continue with the other sections following the same pattern...
    // ...
    
    // Member Service Utilization
    if (empty($selected_sections) || in_array('utilization', $selected_sections)) {
        if (count($data['utilization']) > 8) {
            $html .= '<div class="page-break"></div>';
        }
        
        $html .= '
        <div class="section">
            <div class="section-header">MEMBER SERVICE UTILIZATION</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Membership Count</th>
                            <th>Program Subscriptions</th>
                            <th>Rental Subscriptions</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['utilization'] as $row) {
            $html .= '
                <tr>
                    <td>'.htmlspecialchars($row['username']).'</td>
                    <td>'.htmlspecialchars($row['first_name']).' '.htmlspecialchars($row['last_name']).'</td>
                    <td class="text-right">'.number_format($row['membership_count']).'</td>
                    <td class="text-right">'.number_format($row['program_subscriptions']).'</td>
                    <td class="text-right">'.number_format($row['rental_subscriptions']).'</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    // Program Subscriptions
    if (empty($selected_sections) || in_array('programs', $selected_sections)) {
        if ((count($data['programs']) > 5) && (count($selected_sections) > 3)) {
            $html .= '<div class="page-break"></div>';
        }
        
        $html .= '
        <div class="section">
            <div class="section-header">PROGRAM SUBSCRIPTIONS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Total Subscriptions</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['programs'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td class="text-right">'.number_format($row['total_subscriptions']).'</td>
                    <td class="text-right">₱'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    // Rental Subscriptions
    if (empty($selected_sections) || in_array('rentals', $selected_sections)) {
        if ((count($data['rentals']) > 5) && (count($selected_sections) > 3)) {
            $html .= '<div class="page-break"></div>';
        }
        
        $html .= '
        <div class="section">
            <div class="section-header">RENTAL SUBSCRIPTIONS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Total Rentals</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['rentals'] as $row) {
            $html .= '
                <tr>
                    <td>'.date('F', mktime(0, 0, 0, $row['month'], 10)).'</td>
                    <td>'.$row['year'].'</td>
                    <td class="text-right">'.number_format($row['total_rentals']).'</td>
                    <td class="text-right">₱'.number_format($row['total_amount'], 2).'</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    // Add footer with page numbers
    $html .= '
        <div class="report-footer">
            Fitness Club Management System | CONFIDENTIAL | Page <span class="page-number"></span>
        </div>
    </body>
    </html>';
    
    return $html;
}