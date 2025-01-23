<?php
// Use your existing database connection
require_once 'config.php';
$database = new Database();
$pdo = $database->connect();

// Query to get member subscription details matching your table structure
$query = "
SELECT 
    CONCAT(pd.first_name, ' ', pd.last_name) as full_name,
    m.id as membership_id,
    m.start_date as membership_start,
    m.end_date as membership_end,
    CASE 
        WHEN m.end_date < CURDATE() THEN 'expired'
        WHEN m.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
        ELSE 'active'
    END as membership_status,
    ps.id as program_id,
    p.program_name,
    ps.start_date as program_start,
    ps.end_date as program_end,
    CASE 
        WHEN ps.end_date < CURDATE() THEN 'expired'
        WHEN ps.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
        WHEN ps.end_date IS NULL THEN NULL
        ELSE 'active'
    END as program_status,
    rs.id as rental_id,
    rs.start_date as rental_start,
    rs.end_date as rental_end,
    srv.service_name,
    CASE 
        WHEN rs.end_date < CURDATE() THEN 'expired'
        WHEN rs.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
        WHEN rs.end_date IS NULL THEN NULL
        ELSE 'active'
    END as rental_status
FROM users u
INNER JOIN personal_details pd ON u.id = pd.user_id
INNER JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
INNER JOIN memberships m ON t.id = m.transaction_id
LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
    AND (ps.end_date >= CURDATE() OR ps.end_date IS NULL)
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    AND (rs.end_date >= CURDATE() OR rs.end_date IS NULL)
LEFT JOIN rental_services srv ON rs.rental_service_id = srv.id
WHERE u.is_active = 1
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
                                <span class="badge <?php 
                                    echo $row['membership_status'] === 'active' ? 'bg-success' : 
                                        ($row['membership_status'] === 'expiring' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                    <?php echo ucfirst($row['membership_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo $row['program_status'] === 'active' ? 'bg-success' : 
                                        ($row['program_status'] === 'expiring' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                    <?php echo $row['program_status'] ? ucfirst($row['program_status']) : 'No Program'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo $row['rental_status'] === 'active' ? 'bg-success' : 
                                        ($row['rental_status'] === 'expiring' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                    <?php echo $row['rental_status'] ? ucfirst($row['rental_status']) : 'No Service'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-primary btn-sm view-details" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailsModal"
                                        data-member='<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Subscription Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Left Section -->
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Membership Details</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Start Date:</strong> <span id="membershipStart"></span></p>
                                            <p><strong>End Date:</strong> <span id="membershipEnd"></span></p>
                                            <p><strong>Status:</strong> <span id="membershipStatus"></span></p>
                                            <button class="btn btn-success mt-2">Renew Membership</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="fw-bold">Program Details</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Name:</strong> <span id="programName"></span></p>
                                            <p><strong>Start Date:</strong> <span id="programStart"></span></p>
                                            <p><strong>End Date:</strong> <span id="programEnd"></span></p>
                                            <p><strong>Status:</strong> <span id="programStatus"></span></p>
                                            <button class="btn btn-success mt-2">Renew Program</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Section -->
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Service Details</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Service Name:</strong> <span id="serviceName"></span></p>
                                            <p><strong>Start Date:</strong> <span id="serviceStart"></span></p>
                                            <p><strong>End Date:</strong> <span id="serviceEnd"></span></p>
                                            <p><strong>Status:</strong> <span id="serviceStatus"></span></p>
                                            <button class="btn btn-success mt-2">Renew Service</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                const data = $(this).data('member');
                
                // Format dates
                function formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleDateString();
                }

                // Update membership details
                $('#membershipStart').text(formatDate(data.membership_start));
                $('#membershipEnd').text(formatDate(data.membership_end));
                $('#membershipStatus').text(data.membership_status ? 
                    data.membership_status.charAt(0).toUpperCase() + data.membership_status.slice(1) : 'N/A');

                // Update program details
                $('#programName').text(data.program_name || 'No Program');
                $('#programStart').text(formatDate(data.program_start));
                $('#programEnd').text(formatDate(data.program_end));
                $('#programStatus').text(data.program_status ? 
                    data.program_status.charAt(0).toUpperCase() + data.program_status.slice(1) : 'N/A');

                // Update service details
                $('#serviceName').text(data.service_name || 'No Service');
                $('#serviceStart').text(formatDate(data.rental_start));
                $('#serviceEnd').text(formatDate(data.rental_end));
                $('#serviceStatus').text(data.rental_status ? 
                    data.rental_status.charAt(0).toUpperCase() + data.rental_status.slice(1) : 'N/A');
            });
        });
    </script>
