<?php
    session_start();
    require_once '../coach.class.php';
    require_once __DIR__ . '/../../config.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    include('../coach.nav.php');
    require_once 'dashboard.class.php';
    require_once 'functions/profile.class.php';

    $profile = new Profile_class();

    // Fetch user details and store in session
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);
    if ($userDetails !== false && !empty($userDetails)) {
        $_SESSION['personal_details'] = $userDetails;
        $_SESSION['is_admin'] = ($userDetails['role_name'] === 'admin' || $userDetails['role_name'] === 'administrator');
    } else {
        // Handle the error case - user not found in DB despite session
        // This might indicate a data inconsistency or require logging out the user
        // For now, set default guest details
        $_SESSION['personal_details'] = array('name' => 'Guest', 'role_name' => '', 'role_id' => '');
        $_SESSION['is_admin'] = false;
        // Optionally, destroy session and redirect to login
        // session_destroy();
        // header('Location: ../login/login.php');
        // exit();
    }

    // Handle AJAX request for expired services
    if(isset($_GET['fetch_expired'])) {
        header('Content-Type: application/json');
        $expired_services = $profile->fetchExpiredServices();
        echo json_encode($expired_services);
        exit; // Stop further PHP execution for AJAX requests
    }

    // --- Non-AJAX request: Render the main profile page ---

    // Fetch initial data for active services and attendance log
    $active_services = $profile->fetchAvailedServices();
    $searchDate = isset($_GET['search_date']) ? $_GET['search_date'] : null;
    $attendance_log = $profile->fetchAttendanceLog($searchDate);
    $program_schedules = $profile->fetchProgramSchedules($_SESSION['user_id']); 
?>

<link rel="stylesheet" href="../css/browse_services.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="profile.css">
<!-- Add this link to include the new CSS -->
<div class="content-wrapper">
<section class="main-content container py-4">
    <header class="profile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="profile-name"><?= isset($_SESSION['personal_details']['name']) && !empty($_SESSION['personal_details']['name']) ? htmlspecialchars($_SESSION['personal_details']['name']) : 'Guest' ?></h1>
                <?php if (isset($_SESSION['personal_details']['role_name'])): ?>
                    <h5 class="profile-role">
                        <?php
                            if ($_SESSION['personal_details']['role_name'] === 'member') echo 'Member';
                            elseif ($_SESSION['personal_details']['role_name'] === 'coach') echo 'Coach';
                            elseif ($_SESSION['personal_details']['role_name'] === 'admin' || $_SESSION['personal_details']['role_name'] === 'administrator') echo 'Administrator';
                        ?>
                    </h5>
                <?php endif; ?>
            </div>
             <?php if (isset($_SESSION['personal_details']['role_id']) && $_SESSION['personal_details']['role_id'] === '3'): // Assuming role_id 3 is for Coach ?>
                <a href="coach_profile.php" class="btn btn-primary">
                    <i class="fas fa-user-tie me-2"></i> Coach Profile
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="card availed-services-card">
            <div class="card-body">
                <h4 class="card-title">Availed Services</h4>

                <ul class="nav nav-tabs" id="serviceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="memberships-tab" data-bs-toggle="tab" data-bs-target="#memberships" type="button" role="tab" aria-controls="memberships" aria-selected="true">Memberships</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rentals-tab" data-bs-toggle="tab" data-bs-target="#rentals" type="button" role="tab" aria-controls="rentals" aria-selected="false">Rentals</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="programs-tab" data-bs-toggle="tab" data-bs-target="#programs" type="button" role="tab" aria-controls="programs" aria-selected="false">Programs</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="walkins-tab" data-bs-toggle="tab" data-bs-target="#walkins" type="button" role="tab" aria-controls="walkins" aria-selected="false">Walk-ins</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired" type="button" role="tab" aria-controls="expired" aria-selected="false">Expired Services</button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="serviceTabsContent">
                    <div class="tab-pane fade show active" id="memberships" role="tabpanel" aria-labelledby="memberships-tab">
                        <div class="service-list">
                            <?php if (!empty($active_services['memberships'])) { ?>
                                <?php foreach ($active_services['memberships'] as $service) { ?>
                                    <div class="service-item card mb-2 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($service['name']) ?></h6>
                                                <span class="badge bg-success">Active</span>
                                            </div>
                                            <div class="d-flex justify-content-between mt-2">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-alt me-1"></i> Expires: <?= htmlspecialchars($service['end_date']) ?>
                                                    </small>
                                                </div>
                                                <?php if (isset($service['amount'])) { ?>
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-tag me-1"></i> ₱<?= number_format($service['amount'], 2) ?>
                                                        </small>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="alert alert-info" role="alert">
                                    No active memberships found.
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="rentals" role="tabpanel" aria-labelledby="rentals-tab">
                        <div class="service-list">
                            <?php if (!empty($active_services['rentals'])) { ?>
                                <?php foreach ($active_services['rentals'] as $service) { ?>
                                    <div class="service-item card mb-2 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($service['name']) ?></h6>
                                                <span class="badge bg-success">Active</span>
                                            </div>
                                            <?php if (!empty($service['description'])) { ?>
                                                <p class="text-muted small mb-1"><?= htmlspecialchars($service['description']) ?></p>
                                            <?php } ?>
                                            <div class="d-flex justify-content-between mt-2">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-alt me-1"></i> Expires: <?= htmlspecialchars($service['end_date']) ?>
                                                    </small>
                                                </div>
                                                <?php if (isset($service['amount'])) { ?>
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-tag me-1"></i> ₱<?= number_format($service['amount'], 2) ?>
                                                        </small>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="alert alert-info" role="alert">
                                    No active rentals found.
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="programs" role="tabpanel" aria-labelledby="programs-tab">
                        <div class="service-list">
                            <?php if (!empty($program_schedules)) { ?>
                                <?php foreach ($program_schedules as $schedule) { ?>
                                    <div class="service-item card mb-3 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($schedule['program_name']) ?></h6>
                                                <span class="badge <?= $schedule['status'] === 'completed' ? 'bg-success' : ($schedule['status'] === 'scheduled' ? 'bg-primary' : 'bg-warning') ?>">
                                                    <?= ucfirst(htmlspecialchars($schedule['status'])) ?>
                                                </span>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="text-muted mb-1"><small><i class="fas fa-calendar-alt me-1"></i> <?= htmlspecialchars($schedule['formatted_date']) ?></small></p>
                                                    <p class="text-muted mb-1"><small><i class="fas fa-clock me-1"></i> <?= htmlspecialchars($schedule['formatted_start_time']) ?> - <?= htmlspecialchars($schedule['formatted_end_time']) ?></small></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="text-muted mb-1"><small><i class="fas fa-user-tie me-1"></i> Coach: <?= htmlspecialchars($schedule['coach_name']) ?></small></p>
                                                    <p class="text-muted mb-1">
                                                        <small><i class="fas fa-tag me-1"></i> <?= htmlspecialchars($schedule['session_type']) ?> Session</small>
                                                        <span class="badge <?= $schedule['is_paid'] ? 'bg-success' : 'bg-danger' ?> ms-2"><?= $schedule['payment_status'] ?></span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="alert alert-info" role="alert">
                                    No program schedules found.
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="walkins" role="tabpanel" aria-labelledby="walkins-tab">
                        <div class="service-list">
                            <?php if (!empty($active_services['walkins'])) { ?>
                                <?php foreach ($active_services['walkins'] as $service) { ?>
                                    <div class="service-item card mb-2 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <h6 class="mb-0">Walk-in</h6>
                                                </div>
                                                <span class="badge <?= $service['status'] === 'walked-in' ? 'bg-success' : ($service['status'] === 'pending' ? 'bg-primary' : 'bg-warning') ?>">
                                                    <?= htmlspecialchars($service['status']) ?></span>
                                            </div>
                                            <div>
                                                <p class="text-muted mb-1"><small><i class="fas fa-calendar-alt me-1"></i> Date: <?= htmlspecialchars($service['date']) ?></small></p>
                                                <p class="text-muted mb-1"><small><i class="fas fa-clock me-1"></i> Time: <?= htmlspecialchars($service['time']) ?></small></p>
                                                <?php if (isset($service['amount'])) { ?>
                                                    <p class="text-muted mb-1"><small><i class="fas fa-tag me-1"></i> ₱<?= number_format($service['amount'], 2) ?></small></p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="alert alert-info" role="alert">
                                    No upcoming walk-ins found.
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="expired" role="tabpanel" aria-labelledby="expired-tab">
                        <div class="service-list">
                            <div id="expired_services_content">
                                <div class="text-center text-muted">Loading expired services...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card attendance-log-card">
            <div class="card-body">
                <h4 class="card-title">Attendance Log</h4>
                <form method="GET" class="log-filter">
                    <div class="input-group">
                        <input type="date" name="search_date" class="form-control" value="<?= isset($_GET['search_date']) ? htmlspecialchars($_GET['search_date']) : '' ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (isset($_GET['search_date'])): ?>
                            <a href="profile.php" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="attendance-list">
                    <?php if (!empty($attendance_log)) { ?>
                        <?php foreach ($attendance_log as $log) { ?>
                            <div class="attendance-item card mb-2 shadow-sm p-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <small class="text-muted">Logged in:</small><br>
                                        <strong><?= htmlspecialchars((string)$log['time_in']) ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted">Logged out:</small><br>
                                        <strong><?= htmlspecialchars((string)$log['time_out']) ?></strong>
                                    </div>
                                </div>
                                <div class="text-end text-muted mt-2"><small><?= htmlspecialchars((string)$log['created_at']) ?></small></div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="alert alert-info" role="alert">
                            No attendance records found for the selected date.
                            <?php if (isset($_GET['search_date'])): ?>
                                <a href="profile.php" class="alert-link">Show all logs.</a>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- profile -->
        <div class="card profile-settings-card">
            <div class="card-body">
                <h4 class="card-title">Profile Settings</h4>
                <!-- Add notification containers outside the card to ensure they're always visible -->
                <div class="profile-edit-container">
                    <div class="profile-success-message" style="display:none;"></div>
                    <div class="profile-error-message" style="display:none;"></div>
                </div>
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-info-tab" data-bs-toggle="tab" data-bs-target="#personal-info" type="button" role="tab" aria-controls="personal-info" aria-selected="true">
                        <i class="fas fa-user-circle me-2"></i>Personal Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-content" type="button" role="tab" aria-controls="security-content" aria-selected="false">
                        <i class="fas fa-shield-alt me-2"></i>Security
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="profileTabsContent">
                    <!-- Personal Information Tab -->
                    <div class="tab-pane fade show active" id="personal-info" role="tabpanel" aria-labelledby="personal-info-tab">
                        <div class="row">
                            <div class="col-md-3 text-center mb-4">
                                <div class="position-relative mb-3 mx-auto" style="width: 120px; height: 120px;">
                                    <!-- Database-driven profile photo -->
                                    <img src="../<?php echo isset($_SESSION['user_photo']) ? $_SESSION['user_photo'] : '../cms_img/user.png'; ?>" alt="Profile Photo" id="profilePhoto" class="img-fluid rounded-circle">
                                    <label for="photoInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer;">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="photoInput" name="profile_photo" class="d-none" accept="image/jpeg,image/png,image/gif">
                                </div>
                                <!-- Database-driven full name -->
                                <h5 class="mb-1" id="profile-full-name"><?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'User Name'; ?></h5>
                                <!-- Database-driven username -->
                                <small class="mb-1" id="profile-username">@<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'username'; ?></small>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <label for="usernameInput" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="usernameInput" value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="first-name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first-name" value="<?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last-name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last-name" value="<?php echo isset($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone_number" value="<?php echo isset($_SESSION['phone_number']) ? $_SESSION['phone_number'] : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="birthdate" class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" id="birthdate" value="<?php echo isset($_SESSION['birthdate']) ? $_SESSION['birthdate'] : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sex" class="form-label">Sex</label>
                                    <select class="form-select" id="sex">
                                        <option value="" <?php echo (isset($_SESSION['sex']) && $_SESSION['sex'] == '') ? 'selected' : ''; ?>>Prefer not to say</option>
                                        <option value="Male" <?php echo (isset($_SESSION['sex']) && $_SESSION['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($_SESSION['sex']) && $_SESSION['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="button" id="saveChanges" class="btn btn-primary px-4">Save Changes</button>
                                    <button type="button" id="cancelChanges" class="btn btn-outline-secondary px-4 ms-2">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security-content" role="tabpanel" aria-labelledby="security-tab">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="current-password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new-password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="button" id="updateSecurity" class="btn btn-primary px-4">Update Security Settings</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            </div>
        </section>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Include the edit_profile.js file -->
<script src="functions/edit_profile.js"></script>

<script>
$(document).ready(function() {
    // Function to load expired services via AJAX
    function loadExpiredServices() {
        $.ajax({
            url: 'profile.php',
            method: 'GET',
            data: { fetch_expired: true },
            dataType: 'json',
            success: function(response) {
                console.log("Received expired services data:", response); // Debug output
                var expiredHtml = '';
                var hasExpiredServices = false;

                // Helper function to generate HTML for a service item
                function generateServiceHtml(service, type) {
                    hasExpiredServices = true;
                    let title = '';
                    let details = [];
                    let badgeClass = 'bg-secondary';
                    let badgeText = 'Expired';

                    switch(type) {
                        case 'memberships':
                            title = service.plan_name || 'Membership Plan';
                            if (service.duration_name) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-clock me-1"></i> Duration: ${service.duration_name}</small></p>`);
                            if (service.formatted_start_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-calendar-plus me-1"></i> Started: ${service.formatted_start_date}</small></p>`);
                            if (service.formatted_end_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-calendar-times me-1"></i> Expired: ${service.formatted_end_date}</small></p>`);
                            if (service.amount) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-tag me-1"></i> Amount: ₱${parseFloat(service.amount).toFixed(2)}</small></p>`);
                            break;
                        case 'rentals':
                            title = service.service_name || 'Rental Service';
                            if (service.description) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-info-circle me-1"></i> ${service.description}</small></p>`);
                            if (service.duration_name) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-clock me-1"></i> Duration: ${service.duration_name}</small></p>`);
                            if (service.formatted_start_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-calendar-plus me-1"></i> Started: ${service.formatted_start_date}</small></p>`);
                            if (service.formatted_end_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-calendar-times me-1"></i> Expired: ${service.formatted_end_date}</small></p>`);
                            if (service.amount) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-tag me-1"></i> Amount: ₱${parseFloat(service.amount).toFixed(2)}</small></p>`);
                            break;
                        case 'walkins':
                            title = 'Walk-in';
                            if (service.formatted_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-calendar-alt me-1"></i> Date: ${service.formatted_date}</small></p>`);
                            if (service.price) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-tag me-1"></i> Amount: ₱${parseFloat(service.price).toFixed(2)}</small></p>`);
                            if (service.transaction_date) details.push(`<p class="text-muted mb-1"><small><i class="fas fa-receipt me-1"></i> Transaction: ${service.transaction_date}</small></p>`);
                            break;
                    }

                    let detailsHtml = details.join('');

                    return `
                        <div class="service-item card mb-2 shadow-sm expired-card">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">${title}</h6>
                                    <span class="badge ${badgeClass}">${badgeText}</span>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        ${detailsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Append expired services by type
                if (response.memberships && response.memberships.length > 0) {
                    expiredHtml += '<h5>Memberships</h5>';
                    response.memberships.forEach(service => {
                        expiredHtml += generateServiceHtml(service, 'memberships');
                    });
                }
                
                if (response.rentals && response.rentals.length > 0) {
                    expiredHtml += '<h5 class="mt-4">Rentals</h5>';
                    response.rentals.forEach(service => {
                        expiredHtml += generateServiceHtml(service, 'rentals');
                    });
                }
                
                if (response.programs && response.programs.length > 0) {
                    expiredHtml += '<h5 class="mt-4">Programs</h5>';
                    response.programs.forEach(service => {
                        expiredHtml += generateServiceHtml(service, 'programs');
                    });
                }
                
                if (response.walkins && response.walkins.length > 0) {
                    expiredHtml += '<h5 class="mt-4">Walk-ins</h5>';
                    response.walkins.forEach(service => {
                        expiredHtml += generateServiceHtml(service, 'walkins');
                    });
                }

                if (!hasExpiredServices) {
                    expiredHtml = '<div class="alert alert-info" role="alert">No expired services found.</div>';
                }

                $('#expired_services_content').html(expiredHtml);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading expired services:', error);
                $('#expired_services_content').html('<div class="alert alert-danger" role="alert">Error loading expired services: ' + error + '</div>');
            }
        });
    }

    // Load expired services when the 'Expired Services' tab is shown
    $('#expired-tab').on('shown.bs.tab', function (e) {
        // Check if content is already loaded to avoid unnecessary calls
        if ($('#expired_services_content').find('.alert').length === 0 && $('#expired_services_content').find('.service-item').length === 0) {
             loadExpiredServices();
        }
    });
});
</script>