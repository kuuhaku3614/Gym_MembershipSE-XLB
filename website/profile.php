<?php
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
            <h4>Current Membership Plan</h4>
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
                                    <i class="fas fa-calendar-alt"></i>
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
                                    <i class="fas fa-calendar-alt"></i>
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
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --red: #ff0000 !important;
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
            gap: 15px;
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
    </style>
    
</body>
</html>