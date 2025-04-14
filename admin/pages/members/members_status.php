<?php
date_default_timezone_set('Asia/Manila');
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();

// Query to get member subscription details - avoiding GROUP_CONCAT
$query = "SELECT 
    u.id AS user_id,
    CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
    m.id as membership_id,
    mp.plan_name as membership_plan,
    m.start_date as membership_start,
    m.end_date as membership_end,
    m.status as membership_status,
    CASE 
        WHEN m.status = 'expired' THEN 'Expired'
        ELSE CONCAT(DATEDIFF(m.end_date, CURDATE()), ' days remaining')
    END as days_remaining,
    t.created_at as transaction_date,
    t.id as transaction_id
FROM users u
INNER JOIN personal_details pd ON u.id = pd.user_id
INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
INNER JOIN memberships m ON t.id = m.transaction_id
INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
WHERE u.is_active = 1
AND m.status IN ('active', 'expiring')
AND m.is_paid = 1
ORDER BY 
    CASE m.status
        WHEN 'expiring' THEN 1
        WHEN 'active' THEN 2
        ELSE 3
    END,
    m.end_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch program details separately
function getProgramDetailsForMember($pdo, $transactionId) {
    $query = "SELECT 
        p.program_name,
        CONCAT(COALESCE(coach.first_name, ''), ' ', COALESCE(coach.last_name, '')) as coach_name,
        MIN(pss.date) as start_date,
        MAX(pss.date) as end_date,
        CASE 
            WHEN MAX(pss.date) IS NULL THEN NULL
            WHEN MAX(pss.date) < CURDATE() THEN 'Expired'
            ELSE CONCAT(DATEDIFF(MAX(pss.date), CURDATE()), ' days remaining')
        END as status
    FROM program_subscriptions ps
    LEFT JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
    LEFT JOIN programs p ON cpt.program_id = p.id
    LEFT JOIN program_subscription_schedule pss ON ps.id = pss.program_subscription_id
    LEFT JOIN users coach_user ON ps.coach_id = coach_user.id
    LEFT JOIN personal_details coach ON coach_user.id = coach.user_id
    WHERE ps.transaction_id = :transaction_id
    GROUP BY p.program_name, coach.first_name, coach.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch service details separately
function getServiceDetailsForMember($pdo, $transactionId) {
    $query = "SELECT 
        srv.service_name,
        rs.start_date,
        rs.end_date,
        CASE 
            WHEN rs.end_date IS NULL THEN NULL
            WHEN rs.end_date < CURDATE() THEN 'Expired'
            ELSE CONCAT(DATEDIFF(rs.end_date, CURDATE()), ' days remaining')
        END as status
    FROM rental_subscriptions rs
    LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
    WHERE rs.transaction_id = :transaction_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Enhance the members array with program and service details
foreach ($members as &$member) {
    $member['programs'] = getProgramDetailsForMember($pdo, $member['transaction_id']);
    $member['services'] = getServiceDetailsForMember($pdo, $member['transaction_id']);
}

// Similar approach for expired memberships
$historyQuery = "SELECT 
    u.id AS user_id,
    CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
    m.id as membership_id,
    mp.plan_name as membership_plan,
    m.start_date as membership_start,
    m.end_date as membership_end,
    m.status as membership_status,
    'Expired' as days_remaining,
    t.created_at as transaction_date,
    t.id as transaction_id
FROM users u
INNER JOIN personal_details pd ON u.id = pd.user_id
INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
INNER JOIN memberships m ON t.id = m.transaction_id
INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
WHERE u.is_active = 1
AND m.status = 'expired'
AND m.is_paid = 1
ORDER BY m.end_date DESC";

$historyStmt = $pdo->prepare($historyQuery);
$historyStmt->execute();
$expiredMemberships = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Enhance the expired memberships array with program and service details
foreach ($expiredMemberships as &$membership) {
    $membership['programs'] = getProgramDetailsForMember($pdo, $membership['transaction_id']);
    $membership['services'] = getServiceDetailsForMember($pdo, $membership['transaction_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Status</title>
    <style>
        /* Ensure table headers are visible */
        .table thead th {
            background-color: #f8f9fa;
            color: #212529;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        /* Fix DataTables header visibility */
        .dataTables_wrapper .dataTables_scroll div.dataTables_scrollHead table {
            margin-bottom: 0 !important;
        }
        
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* DataTables styling */
        .dataTables_wrapper .dataTables_scroll {
            margin-bottom: 0.5em;
        }
        
        .dataTables_wrapper .dataTables_scrollHead {
            overflow: hidden !important;
        }
        
        .dataTables_wrapper .dataTables_scrollBody {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }
        
        /* Fix header alignment */
        .dataTables_scrollHeadInner {
            width: 100% !important;
        }
        
        .dataTables_scrollHeadInner table {
            width: 100% !important;
            margin: 0 !important;
        }
        
        /* Ensure consistent column widths */
        #historyTable {
            width: 100% !important;
        }
        
        #historyTable th {
            white-space: nowrap;
            padding: 8px 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Membership Status</h2>
            <a href="../admin/membership_history" class="btn btn-info">
                <i class="fas fa-history"></i> Membership History
            </a>
        </div>
        
        <!-- Main Table -->
        <div class="card">
            <div class="card-body">
            <table id="subscriptionsTable" class="table table-striped table-bordered scrollable-table w-100">
                <thead>
                <tr>
                    <th>Member Name</th>
                    <th>Membership Status</th>
                    <th>Program Status</th>
                    <th>Service Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($members as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td>
                    <?php echo htmlspecialchars($row['membership_plan']); ?>
                    <br>
                    <span class="badge <?php 
                        echo $row['membership_status'] === 'expired' ? 'bg-danger' : 'bg-success'; 
                        ?>">
                        <?php echo $row['membership_status']; ?>
                    </span>
                    <br>
                    <span class="badge <?php 
                        echo strpos($row['days_remaining'], 'Expired') !== false ? 'bg-danger' : 'bg-success'; 
                        ?>">
                        <?php echo $row['days_remaining']; ?>
                    </span>
                    </td>
                    <td>
                    <?php
                    if (!empty($row['programs'])) {
                        foreach ($row['programs'] as $program) {
                            echo htmlspecialchars($program['program_name']);
                            echo '<br>';
                            echo '<span class="badge ' . 
                                (strpos($program['status'], 'Expired') !== false ? 'bg-danger' : 'bg-success') . 
                                '">' . (isset($program['status']) ? htmlspecialchars($program['status']) : 'N/A') . '</span>';
                            echo '<br><br>';
                        }
                    } else {
                        echo '<span class="text-muted">No program</span>';
                    }
                    ?>
                    </td>
                    <td>
                    <?php
                    if (!empty($row['services'])) {
                        foreach ($row['services'] as $service) {
                            echo htmlspecialchars($service['service_name']);
                            echo '<br>';
                            echo '<span class="badge ' . 
                                (strpos($service['status'], 'Expired') !== false ? 'bg-danger' : 'bg-success') . 
                                '">' . (isset($service['status']) ? htmlspecialchars($service['status']) : 'N/A') . '</span>';
                            echo '<br><br>';
                        }
                    } else {
                        echo '<span class="text-muted">No service</span>';
                    }
                    ?>
                    </td>
                    <td>
                    <button type="button" 
                        class="btn btn-primary btn-sm view-details" 
                        data-bs-toggle="modal" 
                        data-bs-target="#detailsModal"
                        data-details='<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                        <i class="bi bi-eye"></i> View Details
                    </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Member Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <h6 class="fw-bold">Membership Details</h6>
                            <p class="mb-1"><strong>Member Name:</strong> <span id="memberName"></span></p>
                            <p class="mb-1"><strong>Plan:</strong> <span id="membershipPlan"></span></p>
                            <p class="mb-1"><strong>Start Date:</strong> <span id="membershipStart"></span></p>
                            <p class="mb-1"><strong>End Date:</strong> <span id="membershipEnd"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="membershipStatus"></span></p>
                            <p class="mb-1"><strong>Days Remaining:</strong> <span id="membershipDaysRemaining"></span></p>
                        </div>

                        <div id="programDetails"></div>

                        <div id="serviceDetails"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Details Modal -->
        <div class="modal fade" id="membershipDetailsModal" tabindex="-1" aria-labelledby="membershipDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="membershipDetailsModalLabel">Member Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <h6 class="fw-bold">Membership Details</h6>
                            <p class="mb-1"><strong>Member Name:</strong> <span id="historyMemberName"></span></p>
                            <p class="mb-1"><strong>Plan:</strong> <span id="historyMembershipPlan"></span></p>
                            <p class="mb-1"><strong>Start Date:</strong> <span id="historyMembershipStart"></span></p>
                            <p class="mb-1"><strong>End Date:</strong> <span id="historyMembershipEnd"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="historyMembershipStatus"></span></p>
                            <p class="mb-1"><strong>Days Remaining:</strong> <span id="historyMembershipDaysRemaining"></span></p>
                        </div>

                        <div id="historyProgramDetails"></div>

                        <div id="historyServiceDetails"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Scripts at the end -->
    <script src="../vendor/jQuery-3.7.1/jquery-3.7.1.min.js"></script>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/datatable-2.1.8/datatables.min.js"></script>
    <script src="../vendor/datatable-2.1.8/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with specific options
            $('#subscriptionsTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[0, 'asc']], // Sort by member name by default
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip', // Custom layout
                language: {
                    search: "Search members:",
                    lengthMenu: "Show _MENU_ members per page"
                }
            });

            // Format dates
            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                return new Date(dateString).toLocaleDateString();
            }

            // Handle details button click for main table
            $('.view-details[data-details]').on('click', function() {
                const data = JSON.parse($(this).attr('data-details'));
                
                // Populate details modal
                $('#memberName').text(data.full_name);
                $('#membershipPlan').text(data.membership_plan);
                $('#membershipStart').text(formatDate(data.membership_start));
                $('#membershipEnd').text(formatDate(data.membership_end));
                $('#membershipStatus').text(data.membership_status);
                $('#membershipDaysRemaining').text(data.days_remaining);

                // Update program details
                var programContainer = $('#programDetails');
                programContainer.empty();
                
                if (data.programs && data.programs.length > 0) {
                    programContainer.append('<div class="mb-4"><h6 class="fw-bold">Program Details</h6></div>');
                    data.programs.forEach(function(program) {
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Program:</strong> ${program.program_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Coach:</strong> ${program.coach_name.trim() || 'No Coach Assigned'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(program.start_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(program.end_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${program.status || 'N/A'}</p>
                            </div>
                        `;
                        programContainer.append(html);
                    });
                }

                // Update service details
                var serviceContainer = $('#serviceDetails');
                serviceContainer.empty();
                
                if (data.services && data.services.length > 0) {
                    serviceContainer.append('<div class="mb-4"><h6 class="fw-bold">Service Details</h6></div>');
                    data.services.forEach(function(service) {
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Service:</strong> ${service.service_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(service.start_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(service.end_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${service.status || 'N/A'}</p>
                            </div>
                        `;
                        serviceContainer.append(html);
                    });
                }
            });

            // Handle details button click for history items
            $('.view-details[data-membership]').on('click', function() {
                const membership = JSON.parse($(this).attr('data-membership'));
                
                // Populate details modal
                $('#historyMemberName').text(membership.full_name);
                $('#historyMembershipPlan').text(membership.membership_plan);
                $('#historyMembershipStart').text(formatDate(membership.membership_start));
                $('#historyMembershipEnd').text(formatDate(membership.membership_end));
                $('#historyMembershipStatus').text(membership.membership_status);
                $('#historyMembershipDaysRemaining').text(membership.days_remaining);

                // Update program details
                var programContainer = $('#historyProgramDetails');
                programContainer.empty();
                
                if (membership.programs && membership.programs.length > 0) {
                    programContainer.append('<div class="mb-4"><h6 class="fw-bold">Program Details</h6></div>');
                    membership.programs.forEach(function(program) {
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Program:</strong> ${program.program_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Coach:</strong> ${program.coach_name.trim() || 'No Coach Assigned'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(program.start_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(program.end_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${program.status || 'N/A'}</p>
                            </div>
                        `;
                        programContainer.append(html);
                    });
                }

                // Update service details
                var serviceContainer = $('#historyServiceDetails');
                serviceContainer.empty();
                
                if (membership.services && membership.services.length > 0) {
                    serviceContainer.append('<div class="mb-4"><h6 class="fw-bold">Service Details</h6></div>');
                    membership.services.forEach(function(service) {
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Service:</strong> ${service.service_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(service.start_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(service.end_date) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${service.status || 'N/A'}</p>
                            </div>
                        `;
                        serviceContainer.append(html);
                    });
                }
            });

            // Handle membership details modal hidden event
            $('#membershipDetailsModal').on('hidden.bs.modal', function() {
                // Show the history modal again
                $('#historyModal').modal('show');
            });
        });
    </script>
</body>
</html>