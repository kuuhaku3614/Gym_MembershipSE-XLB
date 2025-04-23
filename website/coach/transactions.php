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

    $groupedTransactions = [];
    if(isset($transactions) && !empty($transactions)) {
        foreach($transactions as $transaction) {
            $transId = $transaction['transaction_id'] ?? 'N/A';
            if(!isset($groupedTransactions[$transId])) {
                $groupedTransactions[$transId] = [
                    'transaction_id' => $transId,
                    'member_name' => $transaction['member_name'],
                    'member_id' => $transaction['member_id'],
                    'program_name' => $transaction['program_name'],
                    'program_type' => $transaction['program_type'],
                    'session_date' => $transaction['session_date'],
                    'start_time' => $transaction['start_time'],
                    'end_time' => $transaction['end_time'],
                    'amount' => $transaction['amount'],
                    'is_paid' => $transaction['is_paid'],
                    'payment_date' => $transaction['payment_date'] ?? null,
                    'schedule_id' => $transaction['schedule_id'],
                    'schedule_type' => $transaction['schedule_type'],
                    'items' => [$transaction],
                    'total_amount' => $transaction['amount']
                ];
            } else {
                $groupedTransactions[$transId]['items'][] = $transaction;
                $groupedTransactions[$transId]['total_amount'] += $transaction['amount'];
            }
        }
    }
?>
<link rel="stylesheet" href="transaction.css">
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
                            <th>Sessions</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($groupedTransactions) && !empty($groupedTransactions)): ?>
                            <?php foreach($groupedTransactions as $transaction): ?>
                                <tr>
                                    <td><?= $transaction['transaction_id'] ?></td>
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
                                    <td><?= date('M d, Y', strtotime($transaction['session_date'])) ?></td>
                                    <td><?= count($transaction['items']) ?> session(s)</td>
                                    <td>₱<?= number_format($transaction['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaction['is_paid'] ? 'badge-paid' : 'badge-unpaid'; ?>">
                                            <?php echo $transaction['is_paid'] ? 'Paid' : 'Unpaid'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info session-action-btn view-receipt" 
                                            data-id="<?= $transaction['transaction_id'] ?>"
                                            data-member="<?= htmlspecialchars($transaction['member_name']) ?>"
                                            data-member-id="<?= $transaction['member_id'] ?>"
                                            data-program="<?= htmlspecialchars($transaction['program_name']) ?>"
                                            data-type="<?= ucfirst(htmlspecialchars($transaction['program_type'])) ?>"
                                            data-date="<?= date('M d, Y', strtotime($transaction['session_date'])) ?>"
                                            data-time="<?= date('h:i A', strtotime($transaction['start_time'])) ?> - <?= date('h:i A', strtotime($transaction['end_time'])) ?>"
                                            data-amount="<?= number_format($transaction['total_amount'], 2) ?>"
                                            data-schedule-id="<?= $transaction['schedule_id'] ?>"
                                            data-schedule-type="<?= $transaction['schedule_type'] ?>"
                                            data-payment-date="<?= $transaction['payment_date'] ? date('Y-m-d', strtotime($transaction['payment_date'])) : '' ?>">
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="modal-receipt">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="receiptModalLabel"><i class="fas fa-receipt"></i> Member Transaction Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receipt-content" class="receipt-container">
                    <!-- The receipt content remains the same -->
                    <div class="receipt-header">
                        <h3><?php echo htmlspecialchars($companyName); ?></h3>
                        <p><?php echo htmlspecialchars($location); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($phone); ?></p>
                        <hr>
                        <h4>OFFICIAL RECEIPT</h4>
                    </div>
                        
                    <div class="receipt-details">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Receipt No:</strong> <span id="receipt-id"></span></p>
                                <p><strong>Date Issued:</strong> <?php echo date('M d, Y'); ?></p>
                            </div>
                            <div class="col-6 text-end">
                                <p><strong>Member:</strong> <span id="receipt-member"></span></p>
                                <p><strong>Coach:</strong> <?php echo htmlspecialchars($coachName); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="receipt-items-container mt-4">
                        <h5>Services:</h5>
                        <div id="receipt-grouped-items">
                            <!-- Items will be added dynamically via JavaScript -->
                        </div>
                    </div>
                    
                    <div class="receipt-summary mt-4">
                        <div class="row">
                            <div class="col-8"></div>
                            <div class="col-4">
                                <div class="d-flex justify-content-between">
                                    <strong>Total Amount:</strong>
                                    <span>₱<span id="receipt-total">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Status:</strong>
                                    <span class="badge badge-paid">PAID</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="receipt-footer mt-5">
                        <p>Thank you for your business!</p>
                        <p>For questions, please contact your coach or gym management.</p>
                        <p class="small text-muted">This receipt serves as proof of payment.</p>
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

    // Store transactions by member and program
    const transactionsByMember = {};

    // Collect all transactions data from the table
    const transactionRows = document.querySelectorAll('#transactionTable tbody tr');
    transactionRows.forEach(row => {
        const id = row.querySelector('.view-receipt').getAttribute('data-id');
        const member = row.querySelector('.view-receipt').getAttribute('data-member');
        const memberId = row.querySelector('.view-receipt').getAttribute('data-member-id');
        const program = row.querySelector('.view-receipt').getAttribute('data-program');
        const type = row.querySelector('.view-receipt').getAttribute('data-type');
        const date = row.querySelector('.view-receipt').getAttribute('data-date');
        const time = row.querySelector('.view-receipt').getAttribute('data-time');
        const amount = row.querySelector('.view-receipt').getAttribute('data-amount');
        const scheduleId = row.querySelector('.view-receipt').getAttribute('data-schedule-id');
        const scheduleType = row.querySelector('.view-receipt').getAttribute('data-schedule-type');
        const paymentDate = row.querySelector('.view-receipt').getAttribute('data-payment-date');
        
        // Create unique key for each member
        const memberKey = `${memberId}`;
        
        // Initialize member entry if it doesn't exist
        if (!transactionsByMember[memberKey]) {
            transactionsByMember[memberKey] = {
                name: member,
                transactions: []
            };
        }
        
        // Add transaction to member's list with all sessions information
        <?php if(isset($groupedTransactions) && !empty($groupedTransactions)): ?>
            <?php foreach($groupedTransactions as $tid => $transaction): ?>
                if (id === '<?= $tid ?>') {
                    <?php foreach($transaction['items'] as $item): ?>
                    transactionsByMember[memberKey].transactions.push({
                        id: '<?= $tid ?>',
                        program: '<?= htmlspecialchars($item['program_name']) ?>',
                        type: '<?= ucfirst(htmlspecialchars($item['program_type'])) ?>',
                        sessionDate: '<?= date('M d, Y', strtotime($item['session_date'])) ?>',
                        time: '<?= date('h:i A', strtotime($item['start_time'])) ?> - <?= date('h:i A', strtotime($item['end_time'])) ?>',
                        amount: <?= $item['amount'] ?>,
                        scheduleId: '<?= $item['schedule_id'] ?>',
                        scheduleType: '<?= $item['schedule_type'] ?>',
                        paymentDate: '<?= $item['payment_date'] ? date('Y-m-d', strtotime($item['payment_date'])) : date('Y-m-d', strtotime($item['session_date'])) ?>'
                    });
                    <?php endforeach; ?>
                }
            <?php endforeach; ?>
        <?php endif; ?>
    });
    
    // View Receipt
    const viewReceiptBtns = document.querySelectorAll('.view-receipt');
    if (viewReceiptBtns && viewReceiptBtns.length > 0) {
        viewReceiptBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                const member = this.getAttribute('data-member');
                const id = this.getAttribute('data-id');
                
                // Get all transactions for this member
                const memberData = transactionsByMember[memberId];
                
                if (!memberData) return;
                
                // Populate receipt modal header
                document.getElementById('receipt-id').textContent = `MR-${memberId}-${Date.now().toString().slice(-6)}`;
                document.getElementById('receipt-member').textContent = member;
                
                // Clear previous items
                const receiptItems = document.getElementById('receipt-grouped-items');
                receiptItems.innerHTML = '';
                
                // Group transactions by payment date and program
                const groupedByPaymentDate = {};
                memberData.transactions.forEach(transaction => {
                    // Create unique key for payment date + program
                    const paymentKey = `${transaction.paymentDate}_${transaction.program}_${transaction.type}`;
                    
                    if (!groupedByPaymentDate[paymentKey]) {
                        groupedByPaymentDate[paymentKey] = {
                            program: transaction.program,
                            type: transaction.type,
                            paymentDate: transaction.paymentDate,
                            sessions: [],
                            totalAmount: 0
                        };
                    }
                    
                    groupedByPaymentDate[paymentKey].sessions.push({
                        sessionDate: transaction.sessionDate,
                        time: transaction.time,
                        amount: transaction.amount
                    });
                    
                    groupedByPaymentDate[paymentKey].totalAmount += transaction.amount;
                });
                
                // Add grouped items to receipt
                let overallTotal = 0;
                Object.values(groupedByPaymentDate).forEach(group => {
                    const itemGroup = document.createElement('div');
                    itemGroup.className = 'receipt-item-group';
                    
                    // Format the payment date
                    const formattedPaymentDate = new Date(group.paymentDate).toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    });
                    
                    itemGroup.innerHTML = `
                        <div class="receipt-item-date">Payment Date: ${formattedPaymentDate}</div>
                        <div class="receipt-item-title">
                            ${group.program} 
                            <span class="badge ${group.type === 'Personal' ? 'badge-personal' : 'badge-group'}">${group.type}</span>
                            <span class="ms-2">(${group.sessions.length}x sessions)</span>
                        </div>
                        <div class="receipt-item-details">
                            ${group.sessions.map(session => 
                                `<div class="small">• ${session.sessionDate}, ${session.time} - ₱${session.amount.toFixed(2)}</div>`
                            ).join('')}
                        </div>
                        <div class="receipt-item-subtotal">
                            Subtotal: ₱${group.totalAmount.toFixed(2)}
                        </div>
                    `;
                    
                    receiptItems.appendChild(itemGroup);
                    overallTotal += group.totalAmount;
                });
                
                // Update total
                document.getElementById('receipt-total').textContent = overallTotal.toFixed(2);
                
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
            // Create a new window for printing
            const printContent = document.getElementById('receipt-content').cloneNode(true);
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write('<html><head><title>Transaction Receipt</title>');
            
            // Add Bootstrap CSS for proper styling
            printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">');
            
            // Add your custom receipt styles
            printWindow.document.write(`
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                    }
                    .receipt-container {
                        max-width: 800px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                    }
                    .receipt-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .receipt-header h3 {
                        margin-bottom: 5px;
                    }
                    .receipt-header p {
                        margin: 2px 0;
                    }
                    .receipt-item-group {
                        margin-bottom: 15px;
                        padding-bottom: 15px;
                        border-bottom: 1px dashed #ccc;
                    }
                    .receipt-item-date {
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .receipt-item-title {
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .receipt-item-details {
                        margin-left: 15px;
                        margin-bottom: 10px;
                    }
                    .receipt-item-subtotal {
                        text-align: right;
                        font-weight: bold;
                    }
                    .receipt-summary {
                        margin-top: 20px;
                        text-align: right;
                    }
                    .receipt-footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 14px;
                    }
                    .badge {
                        display: inline-block;
                        padding: 0.25em 0.4em;
                        font-size: 75%;
                        font-weight: 700;
                        line-height: 1;
                        text-align: center;
                        white-space: nowrap;
                        vertical-align: baseline;
                        border-radius: 0.25rem;
                    }
                    .badge-personal {
                        background-color: #17a2b8;
                        color: white;
                    }
                    .badge-group {
                        background-color: #6c757d;
                        color: white;
                    }
                    .badge-paid {
                        background-color: #28a745;
                        color: white;
                    }
                </style>
            `);
            
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent.outerHTML);
            printWindow.document.write('</body></html>');
            
            printWindow.document.close();
            
            // Wait for content to load then print
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                // Close window after print or if print is canceled
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        });
    }
});
</script>