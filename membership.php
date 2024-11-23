<?php
require_once 'config.php';

// Execute the query
$query = "SELECT 
    m.id AS membership_id,
    CONCAT(pd.first_name, ' ', pd.last_name) AS member_name,
    mp.plan_name,
    mp.plan_type,
    mp.price AS membership_price,
    mp.duration AS membership_duration,
    dt.type_name AS duration_type,
    ps.id AS program_subscription_id,
    p.program_name,
    ps.price AS program_price,
    rs.id AS rental_subscription_id,
    r.service_name,
    rs.price AS rental_price,
    CONCAT(staff_pd.first_name, ' ', staff_pd.last_name) AS processed_by,
    u.id AS staff_id,
    m.start_date AS membership_start_date,
    m.end_date AS membership_end_date,
    ps.start_date AS program_start_date,
    ps.end_date AS program_end_date,
    rs.start_date AS rental_start_date,
    rs.end_date AS rental_end_date
FROM memberships m
LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
LEFT JOIN program_subscriptions ps ON ps.membership_id = m.id
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN rental_subscriptions rs ON rs.membership_id = m.id
LEFT JOIN rental_services r ON rs.rental_service_id = r.id
JOIN users u ON m.staff_id = u.id
JOIN personal_details staff_pd ON u.id = staff_pd.user_id
JOIN personal_details pd ON m.user_id = pd.user_id
ORDER BY m.id";

$stmt = $pdo->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Records</title>
    <link rel="stylesheet" href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../vendor/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../vendor/datatable-2.1.8/datatables.min.css">
    <link rel="stylesheet" href="../vendor/fontawesome-free-5.15.4-web/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/navbar1.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-details {
            margin: 20px 0;
        }
        .receipt-table {
            width: 100%;
            margin: 20px 0;
        }
        .receipt-footer {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container mt-4">
        <h2>Membership Records</h2>
        <div class="table-responsive">
            <table id="membershipTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member Name</th>
                        <th>Plan Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['membership_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                        <td><?php echo $row['membership_start_date']; ?></td>
                        <td><?php echo $row['membership_end_date']; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm view-details" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#receiptModal"
                                    data-membership="<?php echo htmlspecialchars(json_encode($row)); ?>">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header no-print">
                    <h5 class="modal-title" id="receiptModalLabel">Membership Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="receiptContent">
                    <div class="receipt-header">
                        <h2>GYM NAME</h2>
                        <p>123 Fitness Street, Gym City</p>
                        <p>Phone: (123) 456-7890</p>
                    </div>
                    
                    <div class="receipt-details">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Receipt No:</strong> <span id="membershipId"></span></p>
                                <p><strong>Member Name:</strong> <span id="memberName"></span></p>
                                <p><strong>Date Issued:</strong> <span id="startDate"></span></p>
                            </div>
                            <div class="col-6 text-end">
                                <p><strong>Processed By:</strong> <span id="processedBy"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-table">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="membershipRow">
                                    <td id="planName"></td>
                                    <td id="duration"></td>
                                    <td id="membershipPrice"></td>
                                </tr>
                                <tr id="programRow">
                                    <td id="programName"></td>
                                    <td id="programDuration"></td>
                                    <td id="programPrice"></td>
                                </tr>
                                <tr id="rentalRow">
                                    <td id="rentalService"></td>
                                    <td id="rentalDuration"></td>
                                    <td id="rentalPrice"></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Total Amount:</strong></td>
                                    <td id="totalAmount"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="receipt-footer">
                        <p>Thank you for choosing our gym!</p>
                        <p>This is an official receipt of your transaction.</p>
                    </div>
                </div>
                <div class="modal-footer no-print">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../vendor/jQuery-3.7.1/jquery-3.7.1.min.js"></script>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/datatable-2.1.8/datatables.min.js"></script>
    <script src="../vendor/datatable-2.1.8/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#membershipTable').DataTable();

            // Handle view details button click
            $('.view-details').click(function() {
                const membershipData = JSON.parse($(this).data('membership'));
                
                // Populate modal with membership data
                $('#membershipId').text(membershipData.membership_id);
                $('#memberName').text(membershipData.member_name);
                $('#startDate').text(membershipData.membership_start_date);
                $('#processedBy').text(membershipData.processed_by);
                
                // Membership details
                $('#planName').text(membershipData.plan_name);
                $('#duration').text(membershipData.membership_duration + ' ' + membershipData.duration_type);
                $('#membershipPrice').text('$' + parseFloat(membershipData.membership_price).toFixed(2));
                
                // Program details
                if (membershipData.program_name) {
                    $('#programRow').show();
                    $('#programName').text(membershipData.program_name);
                    $('#programDuration').text(membershipData.program_start_date + ' to ' + membershipData.program_end_date);
                    $('#programPrice').text('$' + parseFloat(membershipData.program_price).toFixed(2));
                } else {
                    $('#programRow').hide();
                }
                
                // Rental details
                if (membershipData.service_name) {
                    $('#rentalRow').show();
                    $('#rentalService').text(membershipData.service_name);
                    $('#rentalDuration').text(membershipData.rental_start_date + ' to ' + membershipData.rental_end_date);
                    $('#rentalPrice').text('$' + parseFloat(membershipData.rental_price).toFixed(2));
                } else {
                    $('#rentalRow').hide();
                }
                
                // Calculate total
                let total = parseFloat(membershipData.membership_price) || 0;
                total += parseFloat(membershipData.program_price) || 0;
                total += parseFloat(membershipData.rental_price) || 0;
                $('#totalAmount').text('$' + total.toFixed(2));
            });
        });
    </script>
</body>
</html>