<?php
    session_start();
    require_once '../coach.class.php';
    require_once __DIR__ . '/../../config.php';

    // Set timezone to Asia/Manila
    date_default_timezone_set('Asia/Manila');

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    $members = $coach->getProgramMembers($_SESSION['user_id']);
    include('../coach.nav.php');

    // Get transaction history for the coach
    $transactions = $coach->getTransactionHistory($_SESSION['user_id']);

    // Get website content for receipt header
    $websiteContentQuery = "SELECT * FROM website_content WHERE section IN ('welcome', 'contact')";
    $websiteContentStmt = $database->connect()->prepare($websiteContentQuery);
    $websiteContentStmt->execute();
    $websiteContent = $websiteContentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize variables
    $companyName = "";
    $location = "";
    $phone = "";

    // Process the fetched content
    foreach ($websiteContent as $content) {
        if ($content['section'] === 'welcome') {
            $companyName = $content['company_name'];
        } else if ($content['section'] === 'contact') {
            $location = $content['location'];
            $phone = $content['phone'];
        }
    }

    $coachQuery = "SELECT CONCAT_WS(' ', pd.first_name, NULLIF(pd.middle_name, ''), pd.last_name) as coach_name 
                FROM users u 
                JOIN personal_details pd ON u.id = pd.user_id 
                WHERE u.id = ?";
    $coachStmt = $database->connect()->prepare($coachQuery);
    $coachStmt->execute([$_SESSION['user_id']]);
    $coachData = $coachStmt->fetch(PDO::FETCH_ASSOC);
    $coachName = $coachData ? $coachData['coach_name'] : 'N/A';
?>

<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<!-- Content Area -->
<div id="content">
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-money-bill-wave"></i> Transaction History</h4>
                <div>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-calendar-day"></i> <?php echo date('F d, Y l'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Transactions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-receipt"></i> Transaction Records
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transactionTable" class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Member Name</th>
                                <th>Program</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($transactions) && !empty($transactions)): ?>
                                <?php foreach($transactions as $transaction): 
                                ?>
                                    <tr class="<?php echo $statusClass; ?>">
                                        <td><?= $transaction['transaction_id'] ?? 'N/A' ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle">
                                                    <?php echo strtoupper(substr($transaction['member_name'], 0, 1)); ?>
                                                </div>
                                                <span class="ms-2"><?= htmlspecialchars($transaction['member_name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($transaction['program_name']) ?></td>
                                        <td>
                                            <span class="badge <?php echo $transaction['program_type'] === 'Personal' ? 'badge-personal' : 'badge-group'; ?>">
                                                <?= ucfirst(htmlspecialchars($transaction['program_type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($transaction['date'])) ?></td>
                                        <td><?= date('h:i A', strtotime($transaction['start_time'])) ?> - <?= date('h:i A', strtotime($transaction['end_time'])) ?></td>
                                        <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                        <td>
                                            <span class="badge <?php echo $transaction['is_paid'] ? 'badge-paid' : 'badge-unpaid'; ?>">
                                                <?php echo $transaction['is_paid'] ? 'Paid' : 'Unpaid'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info session-action-btn view-receipt" 
                                                data-id="<?= $transaction['transaction_id'] ?? $transaction['subscription_id'] ?>"
                                                data-member="<?= htmlspecialchars($transaction['member_name']) ?>"
                                                data-program="<?= htmlspecialchars($transaction['program_name']) ?>"
                                                data-type="<?= ucfirst(htmlspecialchars($transaction['program_type'])) ?>"
                                                data-date="<?= date('M d, Y', strtotime($transaction['date'])) ?>"
                                                data-time="<?= date('h:i A', strtotime($transaction['start_time'])) ?> - <?= date('h:i A', strtotime($transaction['end_time'])) ?>"
                                                data-amount="<?= number_format($transaction['amount'], 2) ?>"
                                                data-paid="<?= $transaction['is_paid'] ? 'Paid' : 'Unpaid' ?>">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="receiptModalLabel"><i class="fas fa-receipt"></i> Transaction Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receipt-content" class="receipt-container">
                    <div class="receipt-header">
                        <h3><?php echo htmlspecialchars($companyName); ?></h3>
                        <p><?php echo htmlspecialchars($location); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($phone); ?></p>
                        <hr>
                        <h4>RECEIPT</h4>
                    </div>
                        
                    <div class="receipt-details">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Receipt No:</strong> <span id="receipt-id"></span></p>
                                <p><strong>Date:</strong> <span id="receipt-date"></span></p>
                            </div>
                            <div class="col-6 text-end">
                                <p><strong>Member:</strong> <span id="receipt-member"></span></p>
                                <!-- Add the coach line right here, after the member line -->
                                <p><strong>Coach: </strong><?php echo htmlspecialchars($coachName); ?> <span id="receipt-coach"></span></p>
                                <p><strong>Status:</strong> <span id="receipt-payment-status"></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Program:</strong> <span id="receipt-program"></span><br>
                                    <strong>Type:</strong> <span id="receipt-type"></span><br>
                                    <strong>Time:</strong> <span id="receipt-time"></span><br>
                                </td>
                                <td class="text-end">₱<span id="receipt-amount"></span></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-end">₱<span id="receipt-total"></span></th>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="receipt-footer">
                        <p>Thank you for your business!</p>
                        <p>For questions, please contact your coach or gym management.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printReceipt"><i class="fas fa-print"></i> Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<style>
    .receipt-modal .modal-dialog {
        max-width: 600px;
    }
    .receipt-container {
        font-family: 'Arial', sans-serif;
        padding: 20px;
    }
    .receipt-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .receipt-details {
        margin-bottom: 20px;
    }
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .receipt-table th, .receipt-table td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }
    .receipt-footer {
        text-align: center;
        font-size: 14px;
        margin-top: 30px;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        #receipt-content, #receipt-content * {
            visibility: visible;
        }
        #receipt-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#transactionTable').DataTable({
        order: [[4, 'desc'], [5, 'asc']], // Sort by date (desc) and time (asc)
        responsive: true
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Responsive sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const burgerMenu = document.getElementById('burgerMenu');

    if (burgerMenu) {
        burgerMenu.addEventListener('click', function() {
            if (sidebar) sidebar.classList.toggle('active');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        });
    }
    
    // Make transaction link active
    const transactionLink = document.getElementById('transaction-link');
    if (transactionLink) {
        transactionLink.classList.add('active');
    }
    
    // View Receipt
    const viewReceiptBtns = document.querySelectorAll('.view-receipt');
    if (viewReceiptBtns && viewReceiptBtns.length > 0) {
        viewReceiptBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const member = this.getAttribute('data-member');
                const program = this.getAttribute('data-program');
                const type = this.getAttribute('data-type');
                const date = this.getAttribute('data-date');
                const time = this.getAttribute('data-time');
                const amount = this.getAttribute('data-amount');
                const paid = this.getAttribute('data-paid');
                const coach = this.getAttribute('data-coach');
                
                // Populate receipt modal
                document.getElementById('receipt-id').textContent = id;
                document.getElementById('receipt-date').textContent = date;
                document.getElementById('receipt-member').textContent = member;
                document.getElementById('receipt-coach').textContent = coach; // Add this line
                document.getElementById('receipt-program').textContent = program;
                document.getElementById('receipt-type').textContent = type;
                document.getElementById('receipt-time').textContent = time;
                document.getElementById('receipt-amount').textContent = amount;
                document.getElementById('receipt-total').textContent = amount;
                document.getElementById('receipt-payment-status').textContent = paid;
                
                // Apply styling to payment status
                const paymentStatusEl = document.getElementById('receipt-payment-status');
                if (paid === 'Paid') {
                    paymentStatusEl.className = 'badge badge-paid';
                } else {
                    paymentStatusEl.className = 'badge badge-unpaid';
                }
                
                // Show modal
                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            });
        });
    }
    
    // Print Receipt
    const printReceiptBtn = document.getElementById('printReceipt');
    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>