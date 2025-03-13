<?php
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    require_once(__DIR__ . '/functions/expiry-notifications.class.php');
    
    $notificationsObj = new Notifications();
    $expiryNotificationsObj = new ExpiryNotifications();
    
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
                    if ($notificationsObj->confirmTransaction($data['transactionId'], $userId)) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to process');
                    
                case 'cancel':
                    if (empty($data['transactionId'])) {
                        throw new Exception('Invalid transaction');
                    }
                    
                    if ($notificationsObj->cancelTransaction($data['transactionId'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to process');
                
                case 'markAsRead':
                    if (empty($data['userId']) || empty($data['type']) || empty($data['notificationId'])) {
                        throw new Exception('Invalid notification data');
                    }
                    
                    if ($expiryNotificationsObj->markAsRead($data['userId'], $data['type'], $data['notificationId'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to mark notification as read');
                
                case 'updateMembershipExpired':
                    if (empty($data['membershipId'])) {
                        throw new Exception('Invalid membership');
                    }
                    
                    if ($expiryNotificationsObj->updateMembershipExpired($data['membershipId'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to update membership status');
                
                case 'updateRentalExpired':
                    if (empty($data['rentalId'])) {
                        throw new Exception('Invalid rental');
                    }
                    
                    if ($expiryNotificationsObj->updateRentalExpired($data['rentalId'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to update rental status');
                
                case 'renewMembership':
                    if (empty($data['membershipId']) || empty($data['newEndDate'])) {
                        throw new Exception('Invalid membership renewal data');
                    }
                    
                    if ($expiryNotificationsObj->renewMembership($data['membershipId'], $data['newEndDate'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to renew membership');
                
                case 'renewRental':
                    if (empty($data['rentalId']) || empty($data['newEndDate'])) {
                        throw new Exception('Invalid rental renewal data');
                    }
                    
                    if ($expiryNotificationsObj->renewRental($data['rentalId'], $data['newEndDate'])) {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    throw new Exception('Failed to renew rental');

                default:
                    throw new Exception('Invalid request');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    $transactionNotifications = $notificationsObj->getAllNotifications();
    $expiryNotifications = $expiryNotificationsObj->getExpiryNotifications();
    
    // Get current user ID from session (assuming it's stored in $_SESSION['user_id'])
    $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>

<div class="container mt-4">
    <h2>Notifications</h2>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="notificationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                Transaction Requests
                <?php if (count($transactionNotifications) > 0): ?>
                <span class="badge bg-danger"><?php echo count($transactionNotifications); ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="expiry-tab" data-bs-toggle="tab" data-bs-target="#expiry" type="button" role="tab" aria-controls="expiry" aria-selected="false">
                Expiry Notifications
                <?php if (count($expiryNotifications) > 0): ?>
                <span class="badge bg-danger"><?php echo count($expiryNotifications); ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="notificationsTabContent">
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
        
        <!-- Expiry Notifications Tab -->
        <div class="tab-pane fade" id="expiry" role="tabpanel" aria-labelledby="expiry-tab">
            <div class="notification-container">
                <?php if (empty($expiryNotifications)): ?>
                    <div class="alert alert-info">No expiry notifications</div>
                <?php else: ?>
                    <?php foreach ($expiryNotifications as $notification): ?>
                        <?php 
                            $isRead = $expiryNotificationsObj->isNotificationRead($currentUserId, $notification['type'], $notification['id']);
                            $cardClass = $isRead ? 'notification-card read' : 'notification-card unread';
                        ?>
                        <div class="<?php echo $cardClass; ?>" 
                             onclick="showExpiryDetails(<?php echo htmlspecialchars(json_encode($notification)); ?>, <?php echo $currentUserId; ?>)">
                            <div class="notification-header">
                                <h5 class="notification-title">
                                    <?php if (!$isRead): ?>
                                    <span class="new-badge">New</span>
                                    <?php endif; ?>
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

                <!-- Program Details -->
                <div id="programSection" class="section-card mb-4" style="display: none;">
                    <h6 class="section-title">Program Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Program:</strong> <span id="programName"></span></p>
                            <p><strong>Type:</strong> <span id="programType"></span></p>
                            <p><strong>Amount:</strong> ₱ <span id="programAmount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Coach:</strong> <span id="coachName"></span></p>
                            <p><strong>Start Date:</strong> <span id="programStart"></span></p>
                            <p><strong>End Date:</strong> <span id="programEnd"></span></p>
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

<!-- Improved Expiry Details Modal -->
<div class="modal fade" id="expiryModal" tabindex="-1" aria-labelledby="expiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="expiryModalLabel">
                    <i class="fas fa-bell me-2"></i>Expiry Notification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Hidden fields for IDs -->
                <input type="hidden" id="notificationId" name="notificationId">
                <input type="hidden" id="notificationType" name="notificationType">
                <input type="hidden" id="currentUserId" name="currentUserId">
                <input type="hidden" id="recordId" name="recordId">
                
                <div class="notification-card mb-4">
                    <div class="notification-header">
                        <h6 class="notification-title mb-1" id="expiryTitle"></h6>
                        <span class="notification-badge" id="expiryBadge"></span>
                    </div>
                    <p class="notification-message" id="expiryMessage"></p>
                    <div class="notification-details" id="expiryDetails">
                        <!-- Details will be filled by JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
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
        document.getElementById('programSection').style.display = 'none';
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
            document.getElementById('memberProfilePic').src = '/Gym_MembershipSE-XLB/' + details.profile_picture || '/Gym_MembershipSE-XLB/assets/images/default-profile.png';
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
    
        // Show program details if present
        if (details.program) {
            document.getElementById('programSection').style.display = 'block';
            document.getElementById('programName').textContent = details.program.name;
            document.getElementById('programType').textContent = details.program.type;
            document.getElementById('coachName').textContent = details.program.coach;
            document.getElementById('programAmount').textContent = details.program.amount;
            document.getElementById('programStart').textContent = details.program.start_date;
            document.getElementById('programEnd').textContent = details.program.end_date;
            // Add program amount to total
            totalAmount += parseFloat(details.program.amount.replace(/,/g, '')) || 0;
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

    function showExpiryDetails(notification, userId) {
        // Store notification IDs and user ID
        document.getElementById('notificationId').value = notification.id;
        document.getElementById('notificationType').value = notification.type;
        document.getElementById('currentUserId').value = userId;
        document.getElementById('recordId').value = notification.type.includes('membership') ? 
            notification.details.membership_id : notification.details.rental_id;
        
        // Set title and message
        document.getElementById('expiryTitle').textContent = notification.title;
        document.getElementById('expiryMessage').textContent = notification.message;
        
        // Set appropriate badge
        const badgeElement = document.getElementById('expiryBadge');
        if (notification.type.includes('expiring')) {
            badgeElement.className = 'notification-badge warning';
            badgeElement.textContent = 'Expiring Soon';
        } else {
            badgeElement.className = 'notification-badge danger';
            badgeElement.textContent = 'Expired';
        }
        
        const detailsContainer = document.getElementById('expiryDetails');
        
        // Clear previous content
        detailsContainer.innerHTML = '';
        
        // Create formatted details based on notification type
        if (notification.type === 'expiring_membership' || notification.type === 'expired_membership') {
            // Add membership details
            const detailsHtml = `
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-user me-2"></i>Member:</span>
                    <span class="details-value">${notification.details.member_name}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-id-card me-2"></i>Plan:</span>
                    <span class="details-value">${notification.details.plan_name}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-calendar-day me-2"></i>Start Date:</span>
                    <span class="details-value">${notification.details.start_date}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-calendar-times me-2"></i>End Date:</span>
                    <span class="details-value">${notification.details.end_date}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-tags me-2"></i>Amount:</span>
                    <span class="details-value">₱${notification.details.amount}</span>
                </div>
            `;
            detailsContainer.innerHTML = detailsHtml;
        } else if (notification.type === 'expiring_rental' || notification.type === 'expired_rental') {
            // Add rental details
            const detailsHtml = `
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-user me-2"></i>Member:</span>
                    <span class="details-value">${notification.details.member_name}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-concierge-bell me-2"></i>Service:</span>
                    <span class="details-value">${notification.details.service_name}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-calendar-day me-2"></i>Start Date:</span>
                    <span class="details-value">${notification.details.start_date}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-calendar-times me-2"></i>End Date:</span>
                    <span class="details-value">${notification.details.end_date}</span>
                </div>
                <div class="details-item">
                    <span class="details-label"><i class="fas fa-tags me-2"></i>Amount:</span>
                    <span class="details-value">₱${notification.details.amount}</span>
                </div>
            `;
            detailsContainer.innerHTML = detailsHtml;
        }
        
        // Mark as read when viewing
        markAsRead(userId, notification.type, notification.id);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('expiryModal'));
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

        fetch('../admin/pages/notification/notification.php', {
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

        fetch('../admin/pages/notification/notification.php', {
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
    
    function markAsRead(userId, type, notificationId) {
        // Mark notification as read via AJAX
        fetch('../admin/pages/notification/notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'markAsRead',
                userId: userId,
                type: type,
                notificationId: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to show notification as read
                const notificationCards = document.querySelectorAll(`.notification-card[data-notification-id="${notificationId}"][data-notification-type="${type}"]`);
                notificationCards.forEach(card => {
                    card.classList.remove('unread');
                    card.classList.add('read');
                    
                    // Remove the "New" badge if present
                    const badge = card.querySelector('.new-badge');
                    if (badge) {
                        badge.remove();
                    }
                    
                    // Update notification counter
                    updateNotificationCounter();
                });
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

function updateNotificationCount() {
    // Count unread expiry notifications
    const unreadExpiryNotifications = document.querySelectorAll('#expiry .notification-card.unread').length;
    const expiryBadge = document.querySelector('#expiry-tab .badge');
    
    if (expiryBadge) {
        if (unreadExpiryNotifications > 0) {
            expiryBadge.textContent = unreadExpiryNotifications;
            expiryBadge.style.display = 'inline';
        } else {
            expiryBadge.style.display = 'none';
        }
    }
    
    // Count transaction notifications
    const transactionNotifications = document.querySelectorAll('#transactions .notification-card').length;
    const transactionBadge = document.querySelector('#transactions-tab .badge');
    
    if (transactionBadge) {
        if (transactionNotifications > 0) {
            transactionBadge.textContent = transactionNotifications;
            transactionBadge.style.display = 'inline';
        } else {
            transactionBadge.style.display = 'none';
        }
    }
}

// Fix for accessibility issue with aria-hidden
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal focus management
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function(event) {
            // When modal is closed, ensure focus is returned to an appropriate element
            // This prevents focus being trapped on an element that becomes aria-hidden
            const activeElement = document.activeElement;
            if (activeElement && activeElement.tagName === 'BUTTON') {
                // Move focus to the first focusable element that's not aria-hidden
                const focusableElements = document.querySelectorAll('button:not([aria-hidden="true"]), a:not([aria-hidden="true"]), input:not([aria-hidden="true"])');
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                } else {
                    // Fallback to body if no suitable element found
                    document.body.focus();
                }
            }
        });
    });
});
</script>

<style>
.notification-container {
    max-height: 600px;
    overflow-y: auto;
}

.notification-card {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.notification-card:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.notification-card.read {
    border-left-color: #28a745;
    opacity: 0.8;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-title {
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.notification-time {
    font-size: 0.85rem;
    color: #6c757d;
}

.notification-body {
    color: #495057;
}

.new-badge {
    background-color: #dc3545;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-right: 8px;
}

.section-card {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-title {
    padding-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 15px;
    color: #495057;
}

.profile-pic {
    border: 3px solid #dee2e6;
}

.notification-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.notification-badge.warning {
    background-color: #ffc107;
    color: #212529;
}

.notification-badge.danger {
    background-color: #dc3545;
    color: white;
}

.details-item {
    display: flex;
    margin-bottom: 8px;
    padding: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
}

.details-label {
    width: 120px;
    font-weight: 600;
    color: #495057;
}

.details-value {
    flex: 1;
}

.notification-message {
    background-color: #e9ecef;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}
</style>