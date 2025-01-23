<?php
date_default_timezone_set('Asia/Manila');
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();

// Query to get member subscription details matching your table structure
$query = "
SELECT 
    CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
    m.id as membership_id,
    mp.plan_name as membership_plan,
    m.start_date as membership_start,
    m.end_date as membership_end,
    CASE 
        WHEN m.end_date < CURDATE() THEN 'Expired'
        ELSE CONCAT(
            DATEDIFF(m.end_date, CURDATE()),
            ' days remaining'
        )
    END as membership_status,
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
    AND (ps.end_date >= CURDATE() OR ps.end_date IS NULL)
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN users coach_user ON ps.coach_id = coach_user.id
LEFT JOIN personal_details coach ON coach_user.id = coach.user_id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    AND (rs.end_date >= CURDATE() OR rs.end_date IS NULL)
LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
WHERE u.is_active = 1
AND m.end_date >= CURDATE()
GROUP BY u.id, pd.first_name, pd.last_name, m.id, mp.plan_name, m.start_date, m.end_date
ORDER BY pd.last_name, pd.first_name
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    </style>

    <div class="container-fluid py-4">
        <h2 class="mb-4">Membership Status</h2>
        
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
                            <p class="mb-1"><strong>Plan:</strong> <span id="membershipPlan"></span></p>
                            <p class="mb-1"><strong>Start Date:</strong> <span id="membershipStart"></span></p>
                            <p class="mb-1"><strong>End Date:</strong> <span id="membershipEnd"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="membershipStatus"></span></p>
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

            // Handle modal detail display
            $('.view-details').on('click', function() {
                const data = $(this).data('details');
                
                // Format dates
                function formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleDateString();
                }

                // Update membership details
                $('#membershipPlan').text(data.membership_plan);
                $('#membershipStart').text(formatDate(data.membership_start));
                $('#membershipEnd').text(formatDate(data.membership_end));
                $('#membershipStatus').text(data.membership_status);

                // Update program details
                var programContainer = $('#programDetails');
                programContainer.empty();
                
                if (data.program_details) {
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
                } else {
                    programContainer.html('<p>No programs</p>');
                }

                // Update service details
                var serviceContainer = $('#serviceDetails');
                serviceContainer.empty();
                
                if (data.service_details) {
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
                } else {
                    serviceContainer.html('<p>No services</p>');
                }

                $('#detailsModal').modal('show');
            });
        });
    </script>
