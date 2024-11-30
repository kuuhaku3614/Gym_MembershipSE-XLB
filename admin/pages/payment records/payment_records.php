<?php
require_once 'config.php';

// Execute the query for transaction records
$query = "
    SELECT 
    t.id AS transaction_id,
    (
        COALESCE(mp.price, 0) + 
        COALESCE(p.price, 0) + 
        COALESCE(r.price, 0) + 
        COALESCE(reg.membership_fee, 0)
    ) AS total_amount,
    t.created_at AS payment_date,
    staff_u.username AS staff_name,
    CONCAT(member_pd.first_name, ' ', member_pd.last_name) AS member_name,
    pp.photo_path AS member_photo,
    m.start_date AS membership_start_date,
    m.end_date AS membership_end_date,
    m.status AS membership_status,
    mp.plan_name AS plan_name,
    p.program_name,
    ps.start_date AS program_start_date,
    ps.end_date AS program_end_date,
    p.price AS program_price,
    rs.start_date AS rental_start_date,
    rs.end_date AS rental_end_date,
    r.price AS rental_price,
    r.service_name AS rental_service_name,
    p.duration AS program_duration,
    dt_p.type_name AS program_duration_type,
    r.duration AS rental_duration,
    dt_r.type_name AS rental_duration_type,
    mp.price AS membership_price,
    reg.membership_fee AS registration_fee
FROM transactions t
LEFT JOIN users staff_u ON t.staff_id = staff_u.id
LEFT JOIN memberships m ON t.id = m.transaction_id
LEFT JOIN users member_u ON t.user_id = member_u.id
LEFT JOIN personal_details member_pd ON member_u.id = member_pd.user_id
LEFT JOIN profile_photos pp ON member_u.id = pp.user_id AND pp.is_active = 1
LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN duration_types dt_p ON p.duration_type_id = dt_p.id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
LEFT JOIN rental_services r ON rs.rental_service_id = r.id
LEFT JOIN duration_types dt_r ON r.duration_type_id = dt_r.id
CROSS JOIN registration reg
ORDER BY t.created_at DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
} catch(PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .receipt-header, .receipt-footer {
        text-align: center;
        padding: 20px 0;
        border: 2px dashed #ddd;
    }
    .receipt-item-label {
        font-weight: bold;
        color: #666;
    }
    .member-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
    }
    .total-amount {
        font-size: 1.5rem;
        font-weight: bold;
        color: #0d6efd;
    }
</style>

<div class="container my-5">
    <h2 class="text-center mb-4">Transaction Records</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Transaction ID</th>
                    <th>Member</th>
                    <th>Total Amount</th>
                    <th>Payment Date</th>
                    <th>Processed By</th>
                    <th>Services</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['transaction_id']; ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if ($row['member_photo']): ?>
                                <img src="../../../<?php echo htmlspecialchars($row['member_photo']); ?>" 
                                    class="member-photo me-2" 
                                    alt="Profile Photo">
                            <?php else: ?>
                                <span class="badge bg-secondary text-light">No Photo</span>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($row['member_name']); ?></span>
                        </div>
                    </td>
                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($row['payment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                    <td>
                        <?php
                        $services = [];
                        if ($row['plan_name']) $services[] = htmlspecialchars($row['plan_name']);
                        if ($row['program_name']) $services[] = htmlspecialchars($row['program_name']);
                        if ($row['rental_service_name']) $services[] = htmlspecialchars($row['rental_service_name']);
                        echo implode(', ', $services);
                        ?>
                    </td>
                    <td>
                        <?php 
                        $statusClass = match ($row['membership_status']) {
                            'active' => 'success',
                            'expiring' => 'warning',
                            default => 'danger'
                        };
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                            <?php echo ucfirst($row['membership_status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#receiptModal-<?php echo $row['transaction_id']; ?>">
                            <i class="bi bi-receipt"></i> View Receipt
                        </button>
                    </td>
                </tr>

                <!-- Modal for Receipt -->
                    <div class="modal fade" id="receiptModal-<?php echo $row['transaction_id']; ?>" tabindex="-1" aria-labelledby="receiptModalLabel-<?php echo $row['transaction_id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Transaction Receipt</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="receiptContent-<?php echo $row['transaction_id']; ?>">
                                    <div class="receipt-header">
                                        <div class="member-photo-container mb-3">
                                            <img src="../../../<?php echo $row['member_photo'] ?: 'path/to/default/image.png'; ?>" 
                                                alt="Member Photo" 
                                                class="member-photo">
                                        </div>
                                        <h4><?php echo htmlspecialchars($row['member_name']); ?></h4>
                                        <p class="text-muted mb-0">Transaction ID: <?php echo $row['transaction_id']; ?></p>
                                        <p class="text-muted">Payment Date: <?php echo date('M d, Y H:i', strtotime($row['payment_date'])); ?></p>
                                    </div>
                                    <div class="receipt-body">
                                        <!-- Membership Details -->
                                        <h5 class="text-primary">Membership Details</h5>
                                        <p>Plan Name: <?php echo $row['plan_name'] ?: 'N/A'; ?></p>
                                        <p>Duration: <?php echo $row['membership_start_date'] . ' - ' . $row['membership_end_date']; ?></p>
                                        <p>Status: <?php echo ucfirst($row['membership_status']); ?></p>
                                        <p>Price: ₱<?php echo number_format($row['membership_price'], 2); ?></p>

                                        <!-- Program Subscription -->
                                        <h5 class="text-primary">Program Subscription</h5>
                                        <p>Program Name: <?php echo $row['program_name'] ?: 'N/A'; ?></p>
                                        <p>Duration: <?php echo $row['program_duration'] . ' ' . $row['program_duration_type']; ?></p>
                                        <p>Period: <?php echo $row['program_start_date'] . ' - ' . $row['program_end_date']; ?></p>
                                        <p>Price: ₱<?php echo number_format($row['program_price'], 2); ?></p>

                                        <!-- Rental Service -->
                                        <h5 class="text-primary">Rental Service</h5>
                                        <p>Service Name: <?php echo $row['rental_service_name'] ?: 'N/A'; ?></p>
                                        <p>Duration: <?php echo $row['rental_duration'] . ' ' . $row['rental_duration_type']; ?></p>
                                        <p>Period: <?php echo $row['rental_start_date'] . ' - ' . $row['rental_end_date']; ?></p>
                                        <p>Price: ₱<?php echo number_format($row['rental_price'], 2); ?></p>

                                        <!-- Total Amount -->
                                        <h5 class="text-primary">Total Amount</h5>
                                        <p class="total-amount">₱<?php echo number_format($row['total_amount'], 2); ?></p>

                                        <!-- Staff -->
                                        <h5 class="text-primary">Processed by</h5>
                                        <p><?php echo htmlspecialchars($row['staff_name']); ?></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="printReceipt('receiptContent-<?php echo $row['transaction_id']; ?>')">
                                        Print Receipt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function printReceipt(contentId) {
        // Get the content of the receipt
        const content = document.getElementById(contentId).innerHTML;
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        // Write the receipt content to the new window
        printWindow.document.open();
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Receipt</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        .receipt-header, .receipt-footer {
                            text-align: center;
                            padding: 20px 0;
                            border: 2px dashed #ddd;
                        }
                        .receipt-item-label {
                            font-weight: bold;
                            color: #666;
                        }
                        .member-photo {
                            width: 100px;
                            height: 100px;
                            border-radius: 50%;
                            object-fit: cover;
                        }
                        .total-amount {
                            font-size: 1.5rem;
                            font-weight: bold;
                            color: #0d6efd;
                        }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    ${content}
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>