<?php
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    $notificationsObj = new Notifications();
    $notifications = $notificationsObj->getAllNotifications();
?>


    <div class="container mt-4">
        <h1 class="nav-title">Notification</h1>

        <div class="notification-container">
            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">No new notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card" onclick="showNotificationDetails(<?php echo htmlspecialchars(json_encode($notification['details'])); ?>)">
                        <div class="notification-header">
                            <h5 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h5>
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

    <!-- Notification Details Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Transaction Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Member Information -->
                    <div class="section-card mb-4">
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

                    <!-- Membership Details -->
                    <div id="membershipSection" class="section-card mb-4" style="display: none;">
                        <h6 class="section-title">Membership Plan</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Plan:</strong> <span id="planName"></span></p>
                                <p><strong>Amount:</strong> ₱<span id="membershipAmount"></span></p>
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
                                <p><strong>Amount:</strong> ₱<span id="programAmount"></span></p>
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
                        <h6 class="section-title">Rental Service</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Service:</strong> <span id="serviceName"></span></p>
                                <p><strong>Amount:</strong> ₱<span id="rentalAmount"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Start Date:</strong> <span id="rentalStart"></span></p>
                                <p><strong>End Date:</strong> <span id="rentalEnd"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Fee Section -->
                    <div class="section-card mb-4" id="registrationSection" style="display: none;">
                        <h6 class="section-title">Registration</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>Registration Fee:</strong> ₱<span id="registrationFee">0.00</span></p>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<style>
    .notification-container {
        margin: 0 auto;
        padding: 20px;
    }

    .notification-card {
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
        cursor: pointer;
    }

    .notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        background-color: #f8f9fa;
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .notification-title {
        margin: 0;
        color: #dc3545;
        font-weight: bold;
    }

    .notification-time {
        color: #6c757d;
        font-size: 0.9em;
    }

    .notification-body {
        color: #333;
        line-height: 1.5;
        font-size: 1.1em;
    }

    .alert {
        text-align: center;
        padding: 20px;
        border-radius: 8px;
    }

    .section-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .section-title {
        color: #dc3545;
        font-weight: bold;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #dc3545;
    }

    .modal-body strong {
        color: #495057;
    }

    .modal-body p {
        margin-bottom: 0.5rem;
    }

    .modal-dialog {
        max-width: 800px;
    }
</style>

<script>
function showNotificationDetails(details) {
        // Calculate total amount
        let totalAmount = 0;
        
        if (details.membership) {
            totalAmount += parseFloat(details.membership.amount) || 0;
        }
        if (details.program) {
            totalAmount += parseFloat(details.program.amount) || 0;
        }
        if (details.rental) {
            totalAmount += parseFloat(details.rental.amount) || 0;
        }
        if (details.registration_fee) {
            totalAmount += parseFloat(details.registration_fee) || 0;
        }
    
        // Display total amount with 2 decimal places
        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    
        // Handle registration fee section
        const registrationSection = document.getElementById('registrationSection');
        if (details.registration_fee) {
            registrationSection.style.display = 'block';
            document.getElementById('registrationFee').textContent = details.registration_fee;
        } else {
            registrationSection.style.display = 'none';
        }
    
        // Rest of the existing code...


    // Update member information
    document.getElementById('memberName').textContent = details.member_name;
    document.getElementById('phoneNumber').textContent = details.phone_number;
    document.getElementById('sex').textContent = details.sex;
    document.getElementById('age').textContent = details.age;
    document.getElementById('requestDate').textContent = details.request_date;

    // Set profile picture
    const profilePic = document.getElementById('memberProfilePic');
    if (details.profile_picture) {
        profilePic.src = '/Gym_MembershipSE-XLB/' + details.profile_picture;
    } else {
        profilePic.src = '/Gym_MembershipSE-XLB/assets/images/default-profile.png';
    }

    // Handle membership details
    const membershipSection = document.getElementById('membershipSection');
    if (details.membership) {
        membershipSection.style.display = 'block';
        document.getElementById('planName').textContent = details.membership.plan_name;
        document.getElementById('membershipAmount').textContent = details.membership.amount;
        document.getElementById('membershipStart').textContent = details.membership.start_date;
        document.getElementById('membershipEnd').textContent = details.membership.end_date;
    } else {
        membershipSection.style.display = 'none';
    }

    // Handle program details
    const programSection = document.getElementById('programSection');
    if (details.program) {
        programSection.style.display = 'block';
        document.getElementById('programName').textContent = details.program.name;
        document.getElementById('programType').textContent = details.program.type;
        document.getElementById('programAmount').textContent = details.program.amount;
        document.getElementById('coachName').textContent = details.program.coach;
        document.getElementById('programStart').textContent = details.program.start_date;
        document.getElementById('programEnd').textContent = details.program.end_date;
    } else {
        programSection.style.display = 'none';
    }

    // Handle rental details
    const rentalSection = document.getElementById('rentalSection');
    if (details.rental) {
        rentalSection.style.display = 'block';
        document.getElementById('serviceName').textContent = details.rental.service_name;
        document.getElementById('rentalAmount').textContent = details.rental.amount;
        document.getElementById('rentalStart').textContent = details.rental.start_date;
        document.getElementById('rentalEnd').textContent = details.rental.end_date;
    } else {
        rentalSection.style.display = 'none';
    }

    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();
}
</script>