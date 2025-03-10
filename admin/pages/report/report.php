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

date_default_timezone_set('Asia/Manila');
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
        .stats-card h2{
            color: green;
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
       /* Add these styles to your existing CSS */
@media print {
    /* Hide the entire dashboard container */
    .container-fluid {
        display: none !important;
    }
    
    /* Hide specific sections */
    .report-section {
        display: none !important;
    }
    
    /* Hide all cards */
    .card {
        display: none !important;
    }
    
    /* Hide all buttons */
    .btn, 
    button {
        display: none !important;
    }
    
    /* Hide the canvas element (chart) */
    canvas {
        display: none !important;
    }
    
    /* Hide table responsive wrappers */
    .table-responsive {
        display: none !important;
    }
    
    /* Hide all rows */
    .row {
        display: none !important;
    }
    
    /* Hide specific elements */
    .stats-card,
    .card-header,
    .card-body {
        display: none !important;
    }
    
    /* Ensure the print-only template is visible */
    .print-only {
        display: block !important;
    }
    
    /* Reset any conflicting styles */
    body {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Ensure proper page breaks */
    .page-break {
        page-break-before: always;
    }
}
    </style>
<body class="bg-light">
    <button class="btn btn-primary export-btn">
        <i class="fas fa-download me-2"></i>Export Report
    </button>

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

    <!-- Add this modal right before the closing body tag in your report.php file -->
<div class="modal fade" id="exportReportModal" tabindex="-1" aria-labelledby="exportReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportReportModalLabel">Export Analytics Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" id="reportForm">
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Select Report Sections</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select_all" checked>
                                <label class="form-check-label" for="select_all">Select All</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input section-checkbox" type="checkbox" id="attendance_section" name="report_sections[]" value="attendance" checked>
                                        <label class="form-check-label" for="attendance_section">Member Attendance Analysis</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input section-checkbox" type="checkbox" id="earnings_section" name="report_sections[]" value="earnings" checked>
                                        <label class="form-check-label" for="earnings_section">Monthly Earnings</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input section-checkbox" type="checkbox" id="utilization_section" name="report_sections[]" value="utilization" checked>
                                        <label class="form-check-label" for="utilization_section">Member Service Utilization</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input section-checkbox" type="checkbox" id="programs_section" name="report_sections[]" value="programs" checked>
                                        <label class="form-check-label" for="programs_section">Program Subscriptions</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input section-checkbox" type="checkbox" id="rentals_section" name="report_sections[]" value="rentals" checked>
                                        <label class="form-check-label" for="rentals_section">Rental Subscriptions</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Click "Preview Report" to see how your report will look before exporting to PDF.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="generate_report" id="preview_report">Preview Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Preview Modal -->
<div class="modal fade" id="previewReportModal" tabindex="-1" aria-labelledby="previewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="previewReportModalLabel">Report Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="report-preview-container p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div id="reportPreviewContent">
                        <!-- Preview content will be loaded here via AJAX -->
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Generating preview...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="export_pdf_btn">
                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                </button>
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

     $('.export-btn').off('click').on('click', function(e) {
        e.preventDefault();
        $('#exportReportModal').modal('show');
    });
    
    // Handle Select All checkbox
    $('#select_all').on('change', function() {
        $('.section-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Update Select All when individual checkboxes change
    $('.section-checkbox').on('change', function() {
        if ($('.section-checkbox:checked').length === $('.section-checkbox').length) {
            $('#select_all').prop('checked', true);
        } else {
            $('#select_all').prop('checked', false);
        }
    });
    
    // Form submission for preview
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        
        // Close the export options modal
        $('#exportReportModal').modal('hide');
        
        // Show the preview modal
        $('#previewReportModal').modal('show');
        
        // Get form data
        const formData = new FormData(this);
        formData.append('preview_report', 'true');
        
        // AJAX request to get report preview
        $.ajax({
            url: './pages/report/report_preview.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#reportPreviewContent').html(response);
            },
            error: function() {
                $('#reportPreviewContent').html('<div class="alert alert-danger">Error generating report preview. Please try again.</div>');
            }
        });
    });
    
    // Export as PDF button handler
    $('#export_pdf_btn').on('click', function() {
    // Get form data from the original report form
    const reportForm = document.getElementById('reportForm');
    
    // Create a temporary form element for PDF export
    const pdfForm = document.createElement('form');
    pdfForm.method = 'POST';
    pdfForm.action = './pages/report/report_preview.php'; // Point to the report_preview.php file
    pdfForm.style.display = 'none';
    
    // Add export_pdf flag
    const exportFlag = document.createElement('input');
    exportFlag.type = 'hidden';
    exportFlag.name = 'export_pdf';
    exportFlag.value = 'true';
    pdfForm.appendChild(exportFlag);
    
    // Copy all form fields from the original form
    const formData = new FormData(reportForm);
    for (const [key, value] of formData.entries()) {
        // Handle array values (like report_sections[])
        if (key.includes('report_sections')) {
            // For checkboxes that might have multiple values
            const checkboxes = reportForm.querySelectorAll(`input[name="${key}"]:checked`);
            checkboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = checkbox.value;
                pdfForm.appendChild(input);
            });
        } else {
            // Regular form fields
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            pdfForm.appendChild(input);
        }
    }
    
    // Close the preview modal if it's open
    if ($('#previewReportModal').length) {
        $('#previewReportModal').modal('hide');
    }
    
    // Append the form to the body, submit it, then remove it
    document.body.appendChild(pdfForm);
    pdfForm.submit();
    document.body.removeChild(pdfForm);
});
    
    // Validate date range
    $('#end_date').on('change', function() {
        const startDate = new Date($('#start_date').val());
        const endDate = new Date($(this).val());
        const currentDate = new Date();
        
        if (endDate > currentDate) {
            alert('End date cannot be in the future.');
            $(this).val(formatDate(currentDate));
        }
        if (endDate < startDate) {
            alert('End date cannot be before start date.');
            $(this).val($('#start_date').val());
        }
    });
    
    $('#start_date').on('change', function() {
        const startDate = new Date($(this).val());
        const endDate = new Date($('#end_date').val());
        
        if (startDate > endDate) {
            alert('Start date cannot be after end date.');
            $(this).val($('#end_date').val());
        }
    });
    
    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    </script>