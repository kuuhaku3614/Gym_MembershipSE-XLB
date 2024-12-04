<?php
require_once 'config.php';

// Execute the query for transaction records
$query = "SELECT 
    t.id AS transaction_id,
    (
        COALESCE(m.amount, 0) + 
        COALESCE(rs.amount, 0) + 
        COALESCE(ps.amount, 0) + 
        COALESCE(w.amount, 0) +
        COALESCE(rr.amount, 0)
    ) AS total_amount,
    t.created_at AS transaction_date,
    CASE 
        WHEN m.id IS NOT NULL THEN CONCAT(pd.first_name, ' ', pd.last_name, ' - Membership Plan')
        WHEN rs.id IS NOT NULL THEN CONCAT(pd.first_name, ' ', pd.last_name, ' - Rental Service')
        WHEN ps.id IS NOT NULL THEN CONCAT(pd.first_name, ' ', pd.last_name, ' - Program')
        WHEN w.id IS NOT NULL THEN CONCAT(w.name, ' - Walk-in')
    END AS transaction_details,
    CASE 
        WHEN m.id IS NOT NULL THEN mp.plan_name
        WHEN rs.id IS NOT NULL THEN r.service_name
        WHEN ps.id IS NOT NULL THEN p.program_name
        WHEN w.id IS NOT NULL THEN 'Walk-in'
    END AS service_type,
    -- Additional fields for detailed receipt
    m.id AS membership_id,
    m.start_date AS membership_start,
    m.end_date AS membership_end,
    m.amount AS membership_amount,
    mp.plan_name AS membership_plan,
    rs.id AS rental_id,
    rs.start_date AS rental_start,
    rs.end_date AS rental_end,
    rs.amount AS rental_amount,
    r.service_name AS rental_service,
    ps.id AS program_id,
    ps.start_date AS program_start,
    ps.end_date AS program_end,
    ps.amount AS program_amount,
    p.program_name,
    w.id AS walk_in_id,
    w.date AS walk_in_date,
    w.amount AS walk_in_amount,
    -- Registration fee fields
    rr.id AS registration_id,
    rr.amount AS registration_amount,
    reg.membership_fee AS registration_fee
FROM transactions t
LEFT JOIN memberships m ON t.id = m.transaction_id AND m.is_paid = 1
LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id AND rs.is_paid = 1
LEFT JOIN rental_services r ON rs.rental_service_id = r.id
LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id AND ps.is_paid = 1
LEFT JOIN programs p ON ps.program_id = p.id
LEFT JOIN walk_in_records w ON t.id = w.transaction_id AND w.is_paid = 1
LEFT JOIN registration_records rr ON t.id = rr.transaction_id
LEFT JOIN registration reg ON rr.registration_id = reg.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN personal_details pd ON u.id = pd.user_id
WHERE t.status = 'confirmed'
AND (
    (m.id IS NOT NULL AND m.is_paid = 1) OR
    (rs.id IS NOT NULL AND rs.is_paid = 1) OR
    (ps.id IS NOT NULL AND ps.is_paid = 1) OR
    (w.id IS NOT NULL AND w.is_paid = 1)
)
ORDER BY t.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
} catch(PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}
?>

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
                    <th>Member/Client</th>
                    <th>Total Amount</th>
                    <th>Payment Date</th>
                    <th>Service Type</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['transaction_id']; ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php echo htmlspecialchars($row['transaction_details']); ?>
                        </div>
                    </td>
                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($row['transaction_date'])); ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td>
                        <span class="badge bg-success">Paid</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#receiptModal-<?php echo $row['transaction_id']; ?>">
                            View Receipt
                        </button>
                    </td>
                </tr>

                <!-- Modal for Receipt -->
                <div class="modal fade" id="receiptModal-<?php echo $row['transaction_id']; ?>" tabindex="-1" aria-labelledby="receiptModalLabel-<?php echo $row['transaction_id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="receiptModalLabel-<?php echo $row['transaction_id']; ?>">Receipt Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="receipt-header">
                                    <h4>Payment Receipt</h4>
                                    <p>Transaction ID: <?php echo $row['transaction_id']; ?></p>
                                    <p>Date: <?php echo date('M d, Y H:i', strtotime($row['transaction_date'])); ?></p>
                                </div>
                                <div class="container mt-4">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p class="receipt-item-label">Member/Client:</p>
                                            <p><?php echo htmlspecialchars($row['transaction_details']); ?></p>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <h5 class="receipt-item-label">Services Availed:</h5>
                                            
                                            <?php if ($row['registration_id']): ?>
                                            <div class="service-item mb-3">
                                                <h6 class="text-primary">Registration Fee</h6>
                                                <p class="mb-1">Amount: ₱<?php echo number_format($row['registration_amount'], 2); ?></p>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($row['membership_id']): ?>
                                            <div class="service-item mb-3">
                                                <h6 class="text-primary">Membership Plan</h6>
                                                <p class="mb-1">Plan: <?php echo htmlspecialchars($row['membership_plan']); ?></p>
                                                <p class="mb-1">Duration: <?php echo date('M d, Y', strtotime($row['membership_start'])) . ' to ' . date('M d, Y', strtotime($row['membership_end'])); ?></p>
                                                <p class="mb-1">Amount: ₱<?php echo number_format($row['membership_amount'], 2); ?></p>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($row['rental_id']): ?>
                                            <div class="service-item mb-3">
                                                <h6 class="text-primary">Rental Service</h6>
                                                <p class="mb-1">Service: <?php echo htmlspecialchars($row['rental_service']); ?></p>
                                                <p class="mb-1">Duration: <?php echo date('M d, Y', strtotime($row['rental_start'])) . ' to ' . date('M d, Y', strtotime($row['rental_end'])); ?></p>
                                                <p class="mb-1">Amount: ₱<?php echo number_format($row['rental_amount'], 2); ?></p>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($row['program_id']): ?>
                                            <div class="service-item mb-3">
                                                <h6 class="text-primary">Program Subscription</h6>
                                                <p class="mb-1">Program: <?php echo htmlspecialchars($row['program_name']); ?></p>
                                                <p class="mb-1">Duration: <?php echo date('M d, Y', strtotime($row['program_start'])) . ' to ' . date('M d, Y', strtotime($row['program_end'])); ?></p>
                                                <p class="mb-1">Amount: ₱<?php echo number_format($row['program_amount'], 2); ?></p>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($row['walk_in_id']): ?>
                                            <div class="service-item mb-3">
                                                <h6 class="text-primary">Walk-in Service</h6>
                                                <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($row['walk_in_date'])); ?></p>
                                                <p class="mb-1">Amount: ₱<?php echo number_format($row['walk_in_amount'], 2); ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <h5 class="receipt-item-label">Total Amount:</h5>
                                            <p class="total-amount">₱<?php echo number_format($row['total_amount'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="receipt-footer mt-4">
                                    <p>Thank you for your payment!</p>
                                    <small>This is your official receipt.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
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
        printWindow.document.write(``
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
        ``
        );
        printWindow.document.close();
    }
</script>