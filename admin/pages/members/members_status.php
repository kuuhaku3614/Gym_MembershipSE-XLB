<?php
date_default_timezone_set('Asia/Manila');
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();

// Query to get member subscription details matching your table structure
$query = "SELECT 
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
    GROUP_CONCAT(
        DISTINCT
        CONCAT(
            p.program_name, '|',
            COALESCE(coach.first_name, ''), ' ', COALESCE(coach.last_name, ''), '|',
            ps.start_date, '|',
            ps.end_date, '|',
            CASE 
                WHEN ps.end_date IS NULL THEN NULL
                WHEN ps.end_date < CURDATE() THEN 'Expired'
                ELSE CONCAT(DATEDIFF(ps.end_date, CURDATE()), ' days remaining')
            END
        ) SEPARATOR ';'
    ) as program_details,
    GROUP_CONCAT(
        DISTINCT
        CONCAT(
            srv.service_name, '|',
            rs.start_date, '|',
            rs.end_date, '|',
            CASE 
                WHEN rs.end_date IS NULL THEN NULL
                WHEN rs.end_date < CURDATE() THEN 'Expired'
                ELSE CONCAT(DATEDIFF(rs.end_date, CURDATE()), ' days remaining')
            END
        ) SEPARATOR ';'
    ) as service_details
FROM users u
INNER JOIN personal_details pd ON u.id = pd.user_id
INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
INNER JOIN memberships m ON t.id = m.transaction_id
INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN users coach_user ON ps.coach_id = coach_user.id
LEFT JOIN personal_details coach ON coach_user.id = coach.user_id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
WHERE u.is_active = 1
AND m.status IN ('active', 'expiring')
AND m.is_paid = 1
GROUP BY u.id, pd.first_name, pd.last_name, m.id, mp.plan_name, m.start_date, m.end_date, m.status
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

// Query to get expired memberships
$historyQuery = "SELECT 
    CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
    m.id as membership_id,
    mp.plan_name as membership_plan,
    m.start_date as membership_start,
    m.end_date as membership_end,
    m.status as membership_status,
    'Expired' as days_remaining,
    t.created_at as transaction_date,
    GROUP_CONCAT(
        DISTINCT
        CONCAT(
            p.program_name, '|',
            COALESCE(coach.first_name, ''), ' ', COALESCE(coach.last_name, ''), '|',
            ps.start_date, '|',
            ps.end_date, '|',
            CASE 
                WHEN ps.end_date IS NULL THEN NULL
                WHEN ps.end_date < CURDATE() THEN 'Expired'
                ELSE CONCAT(DATEDIFF(ps.end_date, CURDATE()), ' days remaining')
            END
        ) SEPARATOR ';'
    ) as program_details,
    GROUP_CONCAT(
        DISTINCT
        CONCAT(
            srv.service_name, '|',
            rs.start_date, '|',
            rs.end_date, '|',
            CASE 
                WHEN rs.end_date IS NULL THEN NULL
                WHEN rs.end_date < CURDATE() THEN 'Expired'
                ELSE CONCAT(DATEDIFF(rs.end_date, CURDATE()), ' days remaining')
            END
        ) SEPARATOR ';'
    ) as service_details
FROM users u
INNER JOIN personal_details pd ON u.id = pd.user_id
INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
INNER JOIN memberships m ON t.id = m.transaction_id
INNER JOIN membership_plans mp ON m.membership_plan_id = mp.id
LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN users coach_user ON ps.coach_id = coach_user.id
LEFT JOIN personal_details coach ON coach_user.id = coach.user_id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
WHERE u.is_active = 1
AND m.status = 'expired'
AND m.is_paid = 1
GROUP BY u.id, pd.first_name, pd.last_name, m.id, mp.plan_name, m.start_date, m.end_date, m.status
ORDER BY m.end_date DESC";

$historyStmt = $pdo->prepare($historyQuery);
$historyStmt->execute();
$expiredMemberships = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

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
                <table id="subscriptionsTable" class="table table-striped table-bordered w-100">
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
                                    echo strpos($row['membership_status'], 'Expired') !== false ? 'bg-danger' : 'bg-success'; 
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
                                if ($row['program_details']) {
                                    $programs = explode(';', $row['program_details']);
                                    foreach ($programs as $program) {
                                        $details = explode('|', $program);
                                        echo htmlspecialchars($details[0]); // Program name
                                        echo '<br>';
                                        echo '<span class="badge ' . 
                                            (strpos($details[4], 'Expired') !== false ? 'bg-danger' : 'bg-success') . 
                                            '">' . htmlspecialchars($details[4]) . '</span>';
                                        echo '<br><br>';
                                    }
                                } else {
                                    echo '<span class="text-muted">No program</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($row['service_details']) {
                                    $services = explode(';', $row['service_details']);
                                    foreach ($services as $service) {
                                        $details = explode('|', $service);
                                        echo htmlspecialchars($details[0]); // Service name
                                        echo '<br>';
                                        echo '<span class="badge ' . 
                                            (strpos($details[3], 'Expired') !== false ? 'bg-danger' : 'bg-success') . 
                                            '">' . htmlspecialchars($details[3]) . '</span>';
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
                const data = $(this).data('details');
                
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
                
                if (data.program_details) {
                    programContainer.append('<div class="mb-4"><h6 class="fw-bold">Program Details</h6></div>');
                    var programs = data.program_details.split(';');
                    programs.forEach(function(program) {
                        var details = program.split('|');
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Program:</strong> ${details[0] || 'N/A'}</p>
                                <p class="mb-1"><strong>Coach:</strong> ${details[1].trim() || 'No Coach Assigned'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(details[2]) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(details[3]) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${details[4] || 'N/A'}</p>
                            </div>
                        `;
                        programContainer.append(html);
                    });
                }

                // Update service details
                var serviceContainer = $('#serviceDetails');
                serviceContainer.empty();
                
                if (data.service_details) {
                    serviceContainer.append('<div class="mb-4"><h6 class="fw-bold">Service Details</h6></div>');
                    var services = data.service_details.split(';');
                    services.forEach(function(service) {
                        var details = service.split('|');
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Service:</strong> ${details[0] || 'N/A'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(details[1]) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(details[2]) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${details[3] || 'N/A'}</p>
                            </div>
                        `;
                        serviceContainer.append(html);
                    });
                }
            });

            // Handle details button click for history items
            $('.view-details[data-membership]').on('click', function() {
                const membership = $(this).data('membership');
                
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
                
                if (membership.program_details) {
                    programContainer.append('<div class="mb-4"><h6 class="fw-bold">Program Details</h6></div>');
                    var programs = membership.program_details.split(';');
                    programs.forEach(function(program) {
                        var details = program.split('|');
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Program:</strong> ${details[0] || 'N/A'}</p>
                                <p class="mb-1"><strong>Coach:</strong> ${details[1].trim() || 'No Coach Assigned'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(details[2]) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(details[3]) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${details[4] || 'N/A'}</p>
                            </div>
                        `;
                        programContainer.append(html);
                    });
                }

                // Update service details
                var serviceContainer = $('#historyServiceDetails');
                serviceContainer.empty();
                
                if (membership.service_details) {
                    serviceContainer.append('<div class="mb-4"><h6 class="fw-bold">Service Details</h6></div>');
                    var services = membership.service_details.split(';');
                    services.forEach(function(service) {
                        var details = service.split('|');
                        var html = `
                            <div class="mb-3">
                                <p class="mb-1"><strong>Service:</strong> ${details[0] || 'N/A'}</p>
                                <p class="mb-1"><strong>Start Date:</strong> ${formatDate(details[1]) || 'N/A'}</p>
                                <p class="mb-1"><strong>End Date:</strong> ${formatDate(details[2]) || 'N/A'}</p>
                                <p class="mb-1"><strong>Status:</strong> ${details[3] || 'N/A'}</p>
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
