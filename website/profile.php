<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'user_account/profile.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/login.php');
        exit();
    }

    $profile = new Profile_class();
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);
    $searchDate = isset($_GET['search_date']) ? $_GET['search_date'] : null;
    $array = $profile->fetchAttendanceLog($searchDate);
    $avail_array = $profile->fetchAvailedServices();

    $_SESSION['personal_details'] = $userDetails;

    // Handle AJAX request for expired services
    if(isset($_GET['fetch_expired'])) {
        header('Content-Type: application/json');
        $expired_services = $profile->fetchExpiredServices();
        echo json_encode($expired_services);
        exit;
    }

    // Handle AJAX request for service details
    if(isset($_GET['fetch_service_details'])) {
        header('Content-Type: application/json');
        $serviceId = isset($_GET['service_id']) ? $_GET['service_id'] : null;
        $serviceType = isset($_GET['service_type']) ? $_GET['service_type'] : null;
        
        if (!$serviceId || !$serviceType) {
            echo json_encode(['error' => 'Missing service ID or type']);
            exit;
        }
        
        $serviceDetails = $profile->fetchServiceDetails($serviceId, $serviceType);
        echo json_encode($serviceDetails);
        exit;
    }

    include('includes/header.php');
?>


    <header class="container-fluid" id="title">
        <div class="container-xl" id="name">
            <h1><?= $_SESSION['personal_details']['name'] ?></h1>
            <?php if (isset($_SESSION['personal_details']['role_name'])): ?>
                <?php if ($_SESSION['personal_details']['role_name'] === 'member'): ?>
                    <h5>Member</h5>
                <?php elseif ($_SESSION['personal_details']['role_name'] === 'coach'): ?>
                    <h5>Coach</h5>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>

    <section>
        <div id="availed_services">
            <div class="section-header">
                <h4>Current Membership Plan</h4>
                <button type="button" class="icon-button" id="history-btn" onclick="toggleHistory()">
                    <i class="fas fa-history"></i>
                </button>
            </div>
            <div id="current_services" style="display: block;">
                <?php if (empty($avail_array['memberships']) && empty($avail_array['programs']) && empty($avail_array['rentals']) && empty($avail_array['walkins'])) { ?>
                    <div class="no-services-message">
                        <p>You haven't availed any services yet</p>
                    </div>
                <?php } else { ?>
                    <?php if (!empty($avail_array['memberships'])) { ?>
                        <div class="subscription-cards">
                            <?php foreach ($avail_array['memberships'] as $membership) { ?>
                                <div class="subscription-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                        <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value"><?= $membership['name'] ?></span>
                                        </div>
                                        <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value"><?= $membership['duration'] ?></span>
                                        </div>
                                            <div class="info-row">
                                                <span class="label">Expiry Date:</span>
                                                <span class="value"><?= $membership['end_date'] ?></span>
                                            </div>
                                    </div>
                                    <div class="card-icon">
                                        <?php 
                                        $button_attrs = array(
                                            'type' => 'button',
                                            'class' => 'icon-button view-receipt-btn',
                                            'data-service-type' => 'membership',
                                            'data-service-id' => isset($membership['id']) ? $membership['id'] : '',
                                            'data-bs-toggle' => 'modal',
                                            'data-bs-target' => '#receiptModal'
                                        );
                                        echo '<button ';
                                        foreach($button_attrs as $key => $value) {
                                            echo $key . '="' . htmlspecialchars($value) . '" ';
                                        }
                                        echo '><i class="fas fa-receipt"></i></button>';
                                        ?>
                                    </div>
                                </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($avail_array['programs'])) { ?>
                        <div class="subscription-cards">
                            <?php foreach ($avail_array['programs'] as $program) { ?>
                                <div class="subscription-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                        <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value"><?= $program['name'] ?></span>
                                        </div>
                                        <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value"><?= $program['duration'] ?></span>
                                        </div>
                                            <div class="info-row">
                                                <span class="label">Expiry Date:</span>
                                                <span class="value"><?= $program['end_date'] ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Coach:</span>
                                                <span class="value"><?= $program['coach'] ?></span>
                                            </div>
                                    </div>
                                    <div class="card-icon">
                                        <?php 
                                        $button_attrs = array(
                                            'type' => 'button',
                                            'class' => 'icon-button view-receipt-btn',
                                            'data-service-type' => 'program',
                                            'data-service-id' => $program['id'],
                                            'data-bs-toggle' => 'modal',
                                            'data-bs-target' => '#receiptModal'
                                        );
                                        echo '<button ';
                                        foreach($button_attrs as $key => $value) {
                                            echo $key . '="' . htmlspecialchars($value) . '" ';
                                        }
                                        echo '><i class="fas fa-receipt"></i></button>';
                                        ?>
                                    </div>
                                </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($avail_array['rentals'])) { ?>
                        <div class="subscription-cards">
                            <?php foreach ($avail_array['rentals'] as $rental) { ?>
                                <div class="subscription-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                        <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value"><?= $rental['name'] ?></span>
                                        </div>
                                        <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value"><?= $rental['duration'] ?></span>
                                        </div>
                                            <div class="info-row">
                                                <span class="label">Expiry Date:</span>
                                                <span class="value"><?= $rental['end_date'] ?></span>
                                            </div>
                                    </div>
                                    <div class="card-icon">
                                        <?php 
                                        $button_attrs = array(
                                            'type' => 'button',
                                            'class' => 'icon-button view-receipt-btn',
                                            'data-service-type' => 'rental',
                                            'data-service-id' => $rental['id'],
                                            'data-bs-toggle' => 'modal',
                                            'data-bs-target' => '#receiptModal'
                                        );
                                        echo '<button ';
                                        foreach($button_attrs as $key => $value) {
                                            echo $key . '="' . htmlspecialchars($value) . '" ';
                                        }
                                        echo '><i class="fas fa-receipt"></i></button>';
                                        ?>
                                    </div>
                                </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($avail_array['walkins'])) { ?>
                        <div class="subscription-cards">
                            <?php foreach ($avail_array['walkins'] as $walkin) { ?>
                                <div class="subscription-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                            <div class="info-row">
                                                <span class="label">Service:</span>
                                                <span class="value">Walk-in</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Date:</span>
                                                <span class="value"><?= $walkin['date'] ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Amount:</span>
                                                <span class="value">₱<?= number_format($walkin['price'], 2) ?></span>
                                            </div>
                                        </div>
                                        <div class="card-icon">
                                            <?php 
                                            $button_attrs = array(
                                                'type' => 'button',
                                                'class' => 'icon-button view-receipt-btn',
                                                'data-service-type' => 'walkin',
                                                'data-service-id' => $walkin['id'],
                                                'data-bs-toggle' => 'modal',
                                                'data-bs-target' => '#receiptModal'
                                            );
                                            echo '<button ';
                                            foreach($button_attrs as $key => $value) {
                                                echo $key . '="' . htmlspecialchars($value) . '" ';
                                            }
                                            echo '><i class="fas fa-receipt"></i></button>';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <div id="expired_services" style="display: none;">
                <div id="expired_services_content"></div>
            </div>
        </div>

        <div id="log">
            <div class="log-header">
                <h2 class="attendance-title">Attendance Log</h2>
                <div class="search-container">
                    <form method="GET" class="log-filter">
                        <div class="search-wrapper">
                            <div class="date-input-wrapper">
                                <input type="date" name="search_date" class="form-control date-input" value="<?= isset($_GET['search_date']) ? htmlspecialchars($_GET['search_date']) : '' ?>">
                            </div>
                            <div class="button-group">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (isset($_GET['search_date'])): ?>
                                    <a href="profile.php" class="clear-btn">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="attendance-cards">
                <?php foreach ($array as $arr) { ?>
                    <div class="attendance-card">
                        <div class="log-item">
                            <span class="log-label">Logged in</span>
                            <span class="log-time"><?= $arr['time_in'] ?></span>
                        </div>
                        <div class="log-item">
                            <span class="log-label">Logged out</span>
                            <span class="log-time"><?= $arr['time_out'] ?></span>
                        </div>
                        <div class="log-time-ago"><?= $arr['created_at'] ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    

    <!-- Service Details Modal -->
    <div class="modal fade" id="serviceDetailsModal" tabindex="-1" aria-labelledby="serviceDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceDetailsModalLabel">Service Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Membership Details -->
                    <div id="membershipDetails" style="display: none;">
                        <h6 class="service-name mb-3"></h6>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Plan Type:</span>
                                <span class="plan-type"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Duration:</span>
                                <span class="duration"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Start Date:</span>
                                <span class="start-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">End Date:</span>
                                <span class="end-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Status:</span>
                                <span class="status"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Amount:</span>
                                <span class="amount"></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="label">Description:</span>
                            <p class="description mt-2"></p>
                        </div>
                    </div>

                    <!-- Program Details -->
                    <div id="programDetails" style="display: none;">
                        <h6 class="service-name mb-3"></h6>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Program Type:</span>
                                <span class="program-type"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Coach:</span>
                                <span class="coach-name"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Duration:</span>
                                <span class="duration"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Start Date:</span>
                                <span class="start-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">End Date:</span>
                                <span class="end-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Status:</span>
                                <span class="status"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Amount:</span>
                                <span class="amount"></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="label">Description:</span>
                            <p class="description mt-2"></p>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div id="rentalDetails" style="display: none;">
                        <h6 class="service-name mb-3"></h6>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Duration:</span>
                                <span class="duration"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Start Date:</span>
                                <span class="start-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">End Date:</span>
                                <span class="end-date"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Status:</span>
                                <span class="status"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Amount:</span>
                                <span class="amount"></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="label">Description:</span>
                            <p class="description mt-2"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Receipt Details</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div id="receipt-details">
                        <!-- Receipt details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --red: #ff0000 !important;
            --white: #ffffff !important;
            --black: #000000 !important;
            --dark-gray: #333333 !important;
        }

        body {
            background-color: whitesmoke !important;
        }

        header {
            margin-bottom: 2rem !important;
        }

        #title {
            background-color: <?php echo (isset($_SESSION['personal_details']['role_name']) && $_SESSION['personal_details']['role_name'] === 'coach') ? '#000000' : 'var(--red)'; ?> !important;
            color: white !important;
            padding: 2rem 0 !important;
            box-shadow: 0px 5px 10px gray !important;
            display: flex !important;
            justify-content: center !important;
        }
        #name h1 {
            font-size: 2.5rem !important;
            font-weight: 900 !important;
            font-style: italic !important;
            font-family: arial !important;
            margin: 0 !important;
        }
        #name h5 {
            font-size: 1.25rem!important;
            font-family: arial!important;
        }
        #name {
            display: flex!important;
            flex-direction: column!important;
            justify-content: center!important;
        }
        section {
            width: 100%!important;
            padding: 0 5%!important;
            display: flex!important;
            flex-wrap: wrap!important;
        }

        section #availed_services {
            width: 30%!important;
            margin-right: 3%!important;
            box-shadow: 0px 1px 3px gray!important;
            border-radius: 10px!important;
            background-color: white!important;
            display: flex!important;
            flex-direction: column!important;
            padding: 1rem!important;
            height: auto!important;
            max-height: 70vh!important;
            overflow: auto!important;
            scrollbar-width: none!important;
            -ms-overflow-style: none!important;
        }

        section #availed_services::-webkit-scrollbar {
            display: none!important;
        }

        section #log {
            padding-top: 5px!important;
            width: 67%!important;
            background-color: whitesmoke!important;
            height: 70vh!important;
            overflow: auto!important;
            scrollbar-width: none!important;
            -ms-overflow-style: none!important;
        }

        section #log::-webkit-scrollbar {
            display: none!important;
        }

        .subscription-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .subscription-table tr {
            border-bottom: 1px solid #eee;
        }

        .subscription-table tr:last-child {
            border-bottom: none;
        }

        .subscription-table td {
            padding: 12px;
            color: #666;
        }

        .subscription-table span {
            color: #333;
            font-weight: 500;
            margin-left: 5px;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .attendance-title {
            color: #ff3b30;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .search-container {
            position: relative;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-input-wrapper {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 200px;
        }

        .date-input {
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            outline: none;
            background: transparent;
            width: 100%;
        }

        .button-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-btn, .clear-btn {
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn {
            background: #ff3b30;
        }

        .search-btn:hover {
            background: #ff1a1a;
            transform: translateY(-2px);
        }

        .clear-btn {
            background: #6c757d;
            text-decoration: none;
        }

        .clear-btn:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        .attendance-cards {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .attendance-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .log-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .log-label {
            color: #666;
            font-style: italic;
            font-size: 14px;
        }

        .log-time {
            color: #333;
            font-weight: 500;
        }

        .log-time-ago {
            color: #666;
            font-size: 14px;
            text-align: right;
            min-width: 120px;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .attendance-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .log-time-ago {
                align-self: flex-end;
            }
        }

        @media (max-width: 768px) {
            #title {
                padding: 1.5rem 0!important;
            }
            #name h1 {
                font-size: 1.75rem!important;
            }
            #name h5 {
                font-size: 1rem!important;
            }
            section {
                flex-direction: column!important;
            }
            section #availed_services, section #log {
                width: 100%!important;
                margin-right: 0!important;
                margin-bottom: 2rem!important;
            }
            section #availed_services {
                max-height: none!important;
                height: auto!important;
            }
        }

        #availed_services {
            padding: 20px;
        }
        
        #availed_services h4 {
            color: #333;
            margin-bottom: 15px;
        }

        .subscription-cards {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .subscription-card {
            background-color: #ff3b30;
            border-radius: 10px;
            padding: 15px;
            color: white;
            margin-bottom: 10px;
        }

        .card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-info {
            flex-grow: 1;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-row .label {
            font-weight: normal;
            margin-right: 10px;
        }

        .info-row .value {
            font-weight: bold;
        }

        .card-icon {
            font-size: 24px;
            margin-left: 15px;
        }

        @media (max-width: 768px) {
            .subscription-card {
                width: 100%;
            }
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-header h4 {
            color: #333;
            margin: 0;
        }

        .icon-button {
            min-width: 35px;
            max-width: 35px;
            min-height: 35px;
            max-height: 35px;
            border-radius: 50%;
            background-color: #ff3b30;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 0;
            flex-shrink: 0;
        }

        .icon-button i {
            font-size: 16px;
        }

        #history-btn{
            background-color: #00000070;
            transition: all 0.3s ease!important;
        }

        #history-btn:hover{
            transform: translateY(-2px);
        }
        
        #history-btn.active {
            background-color: #ff3b30 !important;
        }
        
        #history-btn {
            background-color: #00000070;
            transition: background-color 0.3s ease;
        }

        .expired-card {
            background-color: #00000060 !important;
        }

        .transaction-group {
            margin-bottom: 15px;
        }
        .transaction-group h6 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .transaction-group .subscription-cards {
            margin: 0;
            gap: 10px;
        }
        .transaction-group .subscription-cards .subscription-card {
            margin: 0;
        }

        .no-services-message {
            padding: 20px;
            text-align: center;
            color: #666;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .label {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .service-name {
            color: #ff0000;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .status {
            text-transform: capitalize;
        }
        .amount {
            font-weight: 600;
            color: #28a745;
        }
        .view-details-btn {
            background: none;
            border: none;
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
            font-size: 0.9rem;
        }
        .view-details-btn:hover {
            color: #0056b3;
        }

        .view-receipt-btn {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .view-receipt-btn:hover {
            transform: scale(1.1);
        }

        .view-receipt-btn i {
            font-size: 1.2em;
        }

        .receipt-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .receipt-item p {
            margin-bottom: 8px;
        }

        /* Simple Modal Styling */
        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .modal-header {
            background-color: var(--red);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .receipt-item {
            padding: 1rem;
        }

        .receipt-item p {
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
        }

        .receipt-item p:last-child {
            margin-bottom: 0;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        .modal-footer .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
    </style>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    let showingHistory = false;

    function toggleHistory() {
        var currentServices = document.getElementById('current_services');
        var expiredServices = document.getElementById('expired_services');
        var historyBtn = document.getElementById('history-btn');
        
        if (currentServices.style.display === 'none') {
            currentServices.style.display = 'block';
            expiredServices.style.display = 'none';
            historyBtn.classList.remove('active');
        } else {
            // Load expired services via AJAX
            $.ajax({
                url: 'profile.php',
                method: 'GET',
                data: { fetch_expired: true },
                success: function(response) {
                    var expiredHtml = '';
                    
                    // Memberships
                    if (response.memberships && response.memberships.length > 0) {
                        expiredHtml += '<div class="subscription-cards">';
                        response.memberships.forEach(function(membership) {
                            expiredHtml += `
                                <div class="subscription-card expired-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                            <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value">${membership.plan_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value">${membership.duration_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Start Date:</span>
                                                <span class="value">${membership.formatted_start_date}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">End Date:</span>
                                                <span class="value">${membership.formatted_end_date}</span>
                                            </div>
                                        </div>
                                        <div class="card-icon">
                                            <button type="button" 
                                                class="icon-button view-receipt-btn"
                                                data-service-type="membership"
                                                data-service-id="${membership.id}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#receiptModal">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        expiredHtml += '</div>';
                    }
                    
                    // Programs
                    if (response.programs && response.programs.length > 0) {
                        expiredHtml += '<div class="subscription-cards">';
                        response.programs.forEach(function(program) {
                            expiredHtml += `
                                <div class="subscription-card expired-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                            <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value">${program.program_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value">${program.duration_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Start Date:</span>
                                                <span class="value">${program.formatted_start_date}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">End Date:</span>
                                                <span class="value">${program.formatted_end_date}</span>
                                            </div>
                                        </div>
                                        <div class="card-icon">
                                            <button type="button" 
                                                class="icon-button view-receipt-btn"
                                                data-service-type="program"
                                                data-service-id="${program.id}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#receiptModal">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        expiredHtml += '</div>';
                    }
                    
                    // Rentals
                    if (response.rentals && response.rentals.length > 0) {
                        expiredHtml += '<div class="subscription-cards">';
                        response.rentals.forEach(function(rental) {
                            expiredHtml += `
                                <div class="subscription-card expired-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                            <div class="info-row">
                                                <span class="label">Type:</span>
                                                <span class="value">${rental.rental_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Duration:</span>
                                                <span class="value">${rental.duration_name}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Start Date:</span>
                                                <span class="value">${rental.formatted_start_date}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">End Date:</span>
                                                <span class="value">${rental.formatted_end_date}</span>
                                            </div>
                                        </div>
                                        <div class="card-icon">
                                            <button type="button" 
                                                class="icon-button view-receipt-btn"
                                                data-service-type="rental"
                                                data-service-id="${rental.id}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#receiptModal">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        expiredHtml += '</div>';
                    }
                    
                    // Walk-ins
                    if (response.walkins && response.walkins.length > 0) {
                        expiredHtml += '<div class="subscription-cards">';
                        response.walkins.forEach(function(walkin) {
                            expiredHtml += `
                                <div class="subscription-card expired-card">
                                    <div class="card-content">
                                        <div class="card-info">
                                            <div class="info-row">
                                                <span class="label">Service:</span>
                                                <span class="value">Walk-in</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Date:</span>
                                                <span class="value">${walkin.formatted_date}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Amount:</span>
                                                <span class="value">₱${walkin.formatted_price || walkin.price}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Transaction Date:</span>
                                                <span class="value">${walkin.transaction_date}</span>
                                            </div>
                                        </div>
                                        <div class="card-icon">
                                            <button type="button" 
                                                class="icon-button view-receipt-btn"
                                                data-service-type="walkin"
                                                data-service-id="${walkin.id}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#receiptModal">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        expiredHtml += '</div>';
                    }
                    
                    if (expiredHtml === '') {
                        expiredHtml = '<div class="no-services-message"><p>No expired services found</p></div>';
                    }
                    
                    $('#expired_services').html(expiredHtml);
                    
                    // Show expired services and update button state
                    currentServices.style.display = 'none';
                    expiredServices.style.display = 'block';
                    historyBtn.classList.add('active');
                    
                    // Rebind click event for new receipt buttons
                    $('.view-receipt-btn').off('click').on('click', function() {
                        var serviceId = $(this).data('service-id');
                        var serviceType = $(this).data('service-type');
                        
                        // Make AJAX call to fetch receipt details
                        $.ajax({
                            url: 'profile.php',
                            method: 'GET',
                            data: {
                                fetch_service_details: true,
                                service_id: serviceId,
                                service_type: serviceType
                            },
                            success: function(response) {
                                var details = '';
                                if (response && Object.keys(response).length > 0) {
                                    details += '<div class="receipt-item">';
                                    
                                    if (serviceType === 'membership') {
                                        details += `
                                            <p><strong>Plan Name:</strong> ${response.plan_name}</p>
                                            <p><strong>Plan Type:</strong> ${response.plan_type}</p>
                                            <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                                    } else if (serviceType === 'program') {
                                        details += `
                                            <p><strong>Program Name:</strong> ${response.program_name}</p>
                                            <p><strong>Program Type:</strong> ${response.program_type}</p>
                                            <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                                        if (response.coach_fname) {
                                            details += `<p><strong>Coach:</strong> ${response.coach_fname} ${response.coach_lname}</p>`;
                                        }
                                    } else if (serviceType === 'rental') {
                                        details += `
                                            <p><strong>Service Name:</strong> ${response.service_name}</p>
                                            <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                                    } else if (serviceType === 'walkin') {
                                        details += `
                                            <p><strong>Service:</strong> Walk-in</p>
                                            <p><strong>Date:</strong> ${response.formatted_date}</p>
                                            <p><strong>Amount:</strong> ₱${response.formatted_amount || response.amount}</p>
                                            <p><strong>Payment Status:</strong> ${response.payment_status}</p>
                                            <p><strong>Transaction Date:</strong> ${response.formatted_transaction_date}</p>`;
                                    }
                                    
                                    details += `
                                        <p><strong>Status:</strong> ${response.status || 'Active'}</p>
                                    </div>`;
                                } else {
                                    details = '<div class="receipt-item"><p>No receipt details available.</p></div>';
                                }
                                $('#receipt-details').html(details);
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                console.error('Status:', status);
                                console.error('Response:', xhr.responseText);
                                $('#receipt-details').html('<p>Error loading receipt details.</p>');
                            }
                        });
                    });
                },
                error: function() {
                    $('#expired_services').html('<div class="error-message"><p>Error loading expired services</p></div>');
                }
            });
        }
    }

    function viewServiceDetails(serviceId, serviceType) {
        // Hide all detail sections
        document.getElementById('membershipDetails').style.display = 'none';
        document.getElementById('programDetails').style.display = 'none';
        document.getElementById('rentalDetails').style.display = 'none';

        // Fetch service details
        fetch(`profile.php?fetch_service_details=true&service_id=${serviceId}&service_type=${serviceType}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    const detailsSection = document.getElementById(`${serviceType}Details`);
                    
                    // Show the appropriate section
                    detailsSection.style.display = 'block';
                    
                    // Common fields
                    detailsSection.querySelector('.start-date').textContent = formatDate(data.start_date);
                    detailsSection.querySelector('.end-date').textContent = formatDate(data.end_date);
                    detailsSection.querySelector('.status').textContent = data.status;
                    detailsSection.querySelector('.amount').textContent = formatCurrency(data.amount);
                    detailsSection.querySelector('.duration').textContent = `${data.duration} ${data.duration_type}`;
                    
                    if (data.description) {
                        detailsSection.querySelector('.description').textContent = data.description;
                    }

                    // Service-specific fields
                    switch(serviceType) {
                        case 'membership':
                            detailsSection.querySelector('.service-name').textContent = data.plan_name;
                            detailsSection.querySelector('.plan-type').textContent = data.plan_type;
                            break;
                        case 'program':
                            detailsSection.querySelector('.service-name').textContent = data.program_name;
                            detailsSection.querySelector('.program-type').textContent = data.program_type;
                            detailsSection.querySelector('.coach-name').textContent = 
                                `${data.coach_fname} ${data.coach_lname}`;
                            break;
                        case 'rental':
                            detailsSection.querySelector('.service-name').textContent = data.service_name;
                            break;
                        case 'walkin':
                            detailsSection.querySelector('.service-name').textContent = 'Walk-in';
                            detailsSection.querySelector('.start-date').textContent = data.formatted_date;
                            detailsSection.querySelector('.amount').textContent = `₱${data.formatted_amount || data.amount}`;
                            break;
                    }

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('serviceDetailsModal'));
                    modal.show();
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    $(document).ready(function() {
        $('.view-receipt-btn').click(function() {
            var serviceId = $(this).data('service-id');
            var serviceType = $(this).data('service-type');
            
            console.log('Service ID:', serviceId);
            console.log('Service Type:', serviceType);
            
            // Make AJAX call to fetch receipt details
            $.ajax({
                url: 'profile.php',
                method: 'GET',
                data: {
                    fetch_service_details: true,
                    service_id: serviceId,
                    service_type: serviceType
                },
                success: function(response) {
                    console.log('Response:', response);
                    // Populate modal with receipt details
                    var details = '';
                    if (response && Object.keys(response).length > 0) {
                        details += '<div class="receipt-item">';
                        
                        if (serviceType === 'membership') {
                            details += `
                                <p><strong>Plan Name:</strong> ${response.plan_name}</p>
                                <p><strong>Plan Type:</strong> ${response.plan_type}</p>
                                <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                        } else if (serviceType === 'program') {
                            details += `
                                <p><strong>Program Name:</strong> ${response.program_name}</p>
                                <p><strong>Program Type:</strong> ${response.program_type}</p>
                                <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                            if (response.coach_fname) {
                                details += `<p><strong>Coach:</strong> ${response.coach_fname} ${response.coach_lname}</p>`;
                            }
                        } else if (serviceType === 'rental') {
                            details += `
                                <p><strong>Service Name:</strong> ${response.service_name}</p>
                                <p><strong>Duration:</strong> ${response.duration_type}</p>`;
                        } else if (serviceType === 'walkin') {
                            details += `
                                <p><strong>Service:</strong> Walk-in</p>
                                <p><strong>Date:</strong> ${response.formatted_date}</p>
                                <p><strong>Amount:</strong> ₱${response.formatted_amount || response.amount}</p>
                                <p><strong>Payment Status:</strong> ${response.payment_status}</p>
                                <p><strong>Transaction Date:</strong> ${response.formatted_transaction_date}</p>`;
                        }
                        
                        details += `
                            <p><strong>Status:</strong> ${response.status || 'Active'}</p>
                        </div>`;
                    } else {
                        details = '<div class="receipt-item"><p>No receipt details available.</p></div>';
                        console.log('Empty or invalid response');
                    }
                    $('#receipt-details').html(details);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    $('#receipt-details').html('<p>Error loading receipt details.</p>');
                }
            });
        });
    });
    </script>
    
</body>
</html>