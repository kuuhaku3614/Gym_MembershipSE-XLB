<?php
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    require_once(__DIR__ . '/functions/activity_logger.php');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; 
    $notificationsObj = new Notifications();
    
    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['action'])) {
                throw new Exception('Invalid request');
            }
            
            switch ($data['action']) {
                case 'confirm':
                    if (empty($data['transactionId'])) {
                        throw new Exception('Invalid transaction');
                    }
                    
                    $userId = isset($data['userId']) ? $data['userId'] : null;
                    
                    // Get transaction details to include username in log
                    $transaction = $notificationsObj->getTransactionDetails($data['transactionId']);
                    $username = $transaction['requester_name'] ?? 'Admin';
                    
                    if ($notificationsObj->confirmTransaction($data['transactionId'], $userId)) {
                        // Log the staff activity with username
                        logStaffActivity('confirm_transaction', 'Confirmed transaction #' . $data['transactionId'] . ' - ' . $username);
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to process');
                    
                case 'cancel':
                    if (empty($data['transactionId'])) {
                        throw new Exception('Invalid transaction');
                    }
                    
                    // Get transaction details to include username in log
                    $transaction = $notificationsObj->getTransactionDetails($data['transactionId']);
                    $username = $transaction['requester_name'] ?? 'Unknown';
                    
                    if ($notificationsObj->cancelTransaction($data['transactionId'])) {
                        // Log the staff activity with username
                        logStaffActivity('cancel_transaction', 'Cancelled transaction #' . $data['transactionId'] . ' - ' . $username);
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to process');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    $transactionNotifications = $notificationsObj->getAllNotifications();
    
    // Get current user ID from session (assuming it's stored in $_SESSION['user_id'])
    $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>
<link rel="stylesheet" href="css/notification.css">
<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Transaction Requests</h2>
    <div>
    <span class="badge bg-danger me-2">
        <?php 
            // Count only unread notifications
            $unreadCount = 0;
            
            // Count unread transaction notifications
            $unreadCount += count($transactionNotifications);
            
            echo $unreadCount; 
        ?> Unread
    </span>
    </div>
</div>

        <!-- Transaction Notifications Tab -->
        <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
            <div class="notification-container">
                <?php if (empty($transactionNotifications)): ?>
                    <div class="alert alert-info">No new transaction requests</div>
                <?php else: ?>
                    <?php foreach ($transactionNotifications as $notification): ?>
                        <div class="notification-card" onclick="showNotificationDetails(<?php echo htmlspecialchars(json_encode($notification['details'])); ?>)">
                            <div class="notification-header">
                                <h5 class="notification-title">
                                    <span class="new-badge">New</span>
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </h5>
                                <span class="notification-time"><?php echo htmlspecialchars($notification['timestamp']); ?></span>
                            </div>
                            <div class="notification-body">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Transaction Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Hidden fields for IDs -->
                <input type="hidden" id="transactionId" name="transactionId">
                <input type="hidden" id="userId" name="userId">

                <!-- Member Information -->
                <div class="section-card mb-4" id="memberInfoSection">
                    <h6 class="section-title">Member Information</h6>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img id="memberProfilePic" src="" alt="Profile Picture" class="img-fluid rounded-circle profile-pic mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <span id="memberName"></span></p>
                                    <p><strong>Phone:</strong> <span id="phoneNumber"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Sex:</strong> <span id="sex"></span></p>
                                    <p><strong>Age:</strong> <span id="age"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Walk-in Information -->
                <div class="section-card mb-4" id="walkInInfoSection">
                    <h6 class="section-title">Walk-in Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span id="walkInName"></span></p>
                            <p><strong>Phone:</strong> <span id="walkInPhone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <span id="walkInDate"></span></p>
                            <p><strong>Amount:</strong> ₱ <span id="walkInAmount"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Membership Details -->
                <div id="membershipSection" class="section-card mb-4" style="display: none;">
                    <h6 class="section-title">Membership Plan</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Plan:</strong> <span id="planName"></span></p>
                            <p><strong>Amount:</strong> ₱ <span id="membershipAmount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <span id="membershipStart"></span></p>
                            <p><strong>End Date:</strong> <span id="membershipEnd"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Rental Details -->
                <div id="rentalSection" class="section-card mb-4" style="display: none;">
                    <h6 class="section-title">Rental Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Service:</strong> <span id="serviceName"></span></p>
                            <p><strong>Amount:</strong> ₱ <span id="rentalAmount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <span id="rentalStart"></span></p>
                            <p><strong>End Date:</strong> <span id="rentalEnd"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Registration Fee -->
                <div id="registrationSection" class="section-card mb-4" style="display: none;">
                    <h6 class="section-title">Registration</h6>
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>Registration Fee:</strong> ₱ <span id="registrationFee">0.00</span></p>
                        </div>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="section-card mb-4">
                    <h6 class="section-title">Total Amount</h6>
                    <div class="row">
                        <div class="col-12">
                            <h4>₱ <span id="totalAmount">0.00</span></h4>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-3"><small>Request received: <span id="requestDate"></span></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" onclick="cancelTransaction()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmTransaction()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
    function showNotificationDetails(details) {
        // Reset sections visibility
        document.getElementById('memberInfoSection').style.display = 'none';
        document.getElementById('walkInInfoSection').style.display = 'none';
        document.getElementById('membershipSection').style.display = 'none';
        document.getElementById('rentalSection').style.display = 'none';
        document.getElementById('registrationSection').style.display = 'none';

        // Initialize total amount
        let totalAmount = 0;
    
        // Store transaction and user IDs
        document.getElementById('transactionId').value = details.transaction_id;
        if (details.user_id) {
            document.getElementById('userId').value = details.user_id;
        }

        // Handle button states based on transaction status
        const confirmBtn = document.querySelector('.modal-footer .btn-danger');
        const cancelBtn = document.querySelector('.modal-footer .btn-outline-danger');
        
        if (details.transaction_status === 'confirmed' || details.transaction_status === 'cancelled') {
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
        } else {
            confirmBtn.disabled = false;
            cancelBtn.disabled = false;
        }
    
        // Show relevant sections based on transaction type
        if (details.transaction_type === 'membership') {
            document.getElementById('memberInfoSection').style.display = 'block';
            // Set member information
            document.getElementById('memberProfilePic').src = '/../' + details.profile_picture || '/Gym_MembershipSE-XLB/assets/images/default-profile.png';
            document.getElementById('memberName').textContent = details.member_name;
            document.getElementById('phoneNumber').textContent = details.phone_number;
            document.getElementById('sex').textContent = details.sex;
            document.getElementById('age').textContent = details.age;

            // Only show registration fee section for new members
            if (details.registration_fee && !details.is_member) {
                document.getElementById('registrationSection').style.display = 'block';
                document.getElementById('registrationFee').textContent = details.registration_fee;
                totalAmount += parseFloat(details.registration_fee.replace(/,/g, '')) || 0;
            }
        } else if (details.transaction_type === 'walk-in') {
            document.getElementById('walkInInfoSection').style.display = 'block';
            // Set walk-in information
            document.getElementById('walkInName').textContent = details.walk_in_name;
            document.getElementById('walkInPhone').textContent = details.walk_in_phone;
            document.getElementById('walkInDate').textContent = details.walk_in_date;
            document.getElementById('walkInAmount').textContent = details.walk_in_amount;
            // Add walk-in amount to total
            totalAmount += parseFloat(details.walk_in_amount.replace(/,/g, '')) || 0;
        }
    
        // Show membership details if present
        if (details.membership) {
            document.getElementById('membershipSection').style.display = 'block';
            document.getElementById('planName').textContent = details.membership.plan_name;
            document.getElementById('membershipAmount').textContent = details.membership.amount;
            document.getElementById('membershipStart').textContent = details.membership.start_date;
            document.getElementById('membershipEnd').textContent = details.membership.end_date;
            // Add membership amount to total
            totalAmount += parseFloat(details.membership.amount.replace(/,/g, '')) || 0;
        }
    
        // Show rental details if present
        if (details.rental) {
            document.getElementById('rentalSection').style.display = 'block';
            document.getElementById('serviceName').textContent = details.rental.service_name;
            document.getElementById('rentalAmount').textContent = details.rental.amount;
            document.getElementById('rentalStart').textContent = details.rental.start_date;
            document.getElementById('rentalEnd').textContent = details.rental.end_date;
            // Add rental amount to total
            totalAmount += parseFloat(details.rental.amount.replace(/,/g, '')) || 0;
        }

        // Update total amount display
        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        modal.show();
    }

    function confirmTransaction() {
        if (!confirm('Confirm this transaction?')) {
            return;
        }

        const transactionId = document.getElementById('transactionId').value;
        const userId = document.getElementById('userId').value;

        if (!transactionId) {
            alert('Transaction details not found.');
            return;
        }

        const data = {
            action: 'confirm',
            transactionId: transactionId
        };

        if (userId) {
            data.userId = userId;
        }

        fetch('../admin/pages/notification/transactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Transaction confirmed successfully!');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to confirm transaction');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'An error occurred while processing the transaction');
        });
    }

    function cancelTransaction() {
        if (!confirm('This request will be cancelled')) {
            return;
        }

        const transactionId = document.getElementById('transactionId').value;

        if (!transactionId) {
            alert('Transaction details not found.');
            return;
        }

        fetch('../admin/pages/notification/transactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                transactionId: transactionId
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Transaction cancelled successfully!');
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to cancel transaction');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'An error occurred while processing the transaction');
        });
    }

// Initialize tooltips and other UI elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>