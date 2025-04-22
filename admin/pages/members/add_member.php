<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use absolute path for includes
require_once(__DIR__ . '/../../../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Additional includes
require_once(__DIR__ . "/functions/member_registration.class.php");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set CORS headers
header("Access-Control-Allow-Origin: *"); // Allow all origins (or specify your frontend URL)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allowed request methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allowed headers

$memberRegistration = new MemberRegistration();

// Handle AJAX requests
if (isset($_GET['action'])) {
    $memberRegistration = new MemberRegistration($pdo);
    
    if ($_GET['action'] === 'get_schedule') {
        header('Content-Type: application/json');
        
        if (!isset($_GET['coach_program_type_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing coach program type ID']);
            exit;
        }

        $coachProgramTypeId = $_GET['coach_program_type_id'];
        $programType = $memberRegistration->getCoachProgramType($coachProgramTypeId);
        
        if (!$programType) {
            echo json_encode(['success' => false, 'error' => 'Invalid coach program type']);
            exit;
        }

        $schedules = $programType['type'] === 'personal' 
            ? $memberRegistration->getCoachPersonalSchedule($coachProgramTypeId)
            : $memberRegistration->getCoachGroupSchedule($coachProgramTypeId);

        if (isset($schedules['error'])) {
            echo json_encode(['success' => false, 'error' => $schedules['error']]);
            exit;
        }

        if (isset($schedules['message'])) {
            echo json_encode([
                'success' => true, 
                'data' => [], 
                'message' => $schedules['message'], 
                'program_type' => $programType['type'],
                'program_type_description' => $programType['description']
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $schedules,
            'program_type' => $programType['type'],
            'program_type_description' => $programType['description']
        ]);
        exit;
    }
}

// Handle AJAX form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    

    

    
    if ($_POST['action'] === 'add_member') {
        try {
            $result = $memberRegistration->addMember($_POST);
            

            echo json_encode($result);
        } catch (Exception $e) {
            

            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Get initial data for the form
$programs = $memberRegistration->getPrograms();
$rentalServices = $memberRegistration->getRentalServices();
$registrationFee = $memberRegistration->getRegistrationFee();

// Helper function to generate coach options
function generateCoachOptions($coaches) {
    $options = '';
    foreach ($coaches as $coach) {
        $options .= sprintf(
            '<option value="%d">%s</option>',
            $coach['coach_program_type_id'],
            htmlspecialchars($coach['coach_name'])
        );
    }
    return $options;
}

// Helper function to generate program card
function generateProgramCard($program) {
    if (!isset($program['coaches']) || empty($program['coaches'])) {
        return ''; // Skip if no coaches available
    }

    $programType = strtolower($program['program_type']);

    return sprintf(
        '<div class="card program-card mb-3" data-program-type="%s" data-program-id="%d">
            <div class="card-body">
                <h5 class="card-title">%s (%s)</h5>
                <p class="card-text">%s</p>
                <div class="form-group">
                    <label class="form-label">Select Coach:</label>
                    <select class="form-select program-coach" name="program_coaches[%d]" data-coaches=\'%s\'>
                        <option value="" selected>Choose a coach</option>
                        %s
                    </select>
                </div>
            </div>
        </div>',
        $programType,
        $program['program_id'],
        htmlspecialchars($program['program_name']),
        ucfirst($programType),
        htmlspecialchars($program['program_description']),
        $program['program_id'],
        json_encode($program['coaches']), // Store coach data as JSON
        generateCoachOptions($program['coaches'])
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member</title>
    <?php include '../../includes/header.php'; ?>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <style>
        body {
            font-family: "Inter", sans-serif;
        }
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
        }
        .phase {
            overflow-y: auto;
            max-height: calc(100vh - 400px); /* Adjust height accounting for headers and footer */
            padding: 0 20px 20px 20px;
        }
        #phase4{
            max-height: 100vh;
                }
        .phases{
        border-bottom: 1px solid #ccc;
        }
        .membership-summary {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            padding: 15px;
            padding-bottom: 70px;
            border-top: 1px solid #ddd;
            height: 200px;
            overflow-y: auto;
        }
        .membership-summary h5 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .membership-summary p {
            margin-bottom: 3px;
            font-size: 13px;
        }
        .membership-summary .btn {
            margin-top: 5px;
        }

        .summary-row {
            border: 1px solid #ddd;
            padding: 8px;
            margin-bottom: 8px;
            background: #fff;
            position: relative;
        }
        .summary-row .remove-program {
            position: absolute;
            right: 5px;
            top: 5px;
            cursor: pointer;
            color: #dc3545;
        }
        .membership-summary h5 {
            font-size: 14px;
            margin-bottom: 8px;
        }
        .membership-summary .details {
            font-size: 13px;
        }
        
        /* Review Phase Styles */
        .review-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .review-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .review-section {
            margin-bottom: 30px;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .review-section h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .review-info {
            display: grid;
            gap: 10px;
        }
        .info-row {
            display: flex;
            align-items: baseline;
            padding: 5px 0;
        }
        .info-label {
            flex: 0 0 100px;
            color: #6c757d;
            font-weight: 500;
        }
        .info-value {
            flex: 1;
        }
        .review-programs {
            display: grid;
            gap: 10px;
        }
        .program-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .program-item .program-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .program-item .program-details {
            color: #6c757d;
            font-size: 0.9em;
        }
        .review-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .review-actions .btn {
            min-width: 150px;
            padding: 10px 20px;
        }
        
        .review-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-section h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }

        .review-info p {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .review-info p strong {
            color: #34495e;
            min-width: 150px;
        }

        .program-item, .rental-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .program-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .program-details {
            color: #666;
            line-height: 1.6;
        }

        .review-total-amount {
            font-size: 1.5em;
            color: #2c3e50;
        }

        .review-title {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }

        /* Hide empty sections */
        #review-programs:empty,
        #review-rentals:empty {
            display: none;
        }

        /* Total amount section */
        .review-section:last-child {
            background-color: #f8f9fa;
            border: 2px solid #3498db;
        }

        .review-section:last-child h4 {
            color: #2c3e50;
            text-align: right;
        }
        
        /* Membership plan card styles */
        .membership-option {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .membership-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .membership-option.selected {
            border: 2px solid #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }
        .membership-option .form-check {
            display: none;
        }
        
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .summary-row {
            border: 1px solid #e9ecef;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background-color: #fff;
        }

        .remove-program,
        .remove-rental {
            cursor: pointer;
            color: #dc3545;
            transition: color 0.2s;
            padding: 5px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .remove-program:hover,
        .remove-rental:hover {
            color: #c82333;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .details {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .details p {
            margin-bottom: 8px;
        }

        .details p:last-child {
            margin-bottom: 0;
        }
        
        /* Rental services styles */
        .rental-option {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .rental-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rental-option.selected {
            border: 2px solid #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }
        h3.mb-4{
            font-weight: 600;
            color: #212529;
        }
    /* Subtle badge indicator styles */
.plan-badge-indicator {
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0.96;
    padding: 0.22em 0.65em;
    z-index: 10;
    background-color: #e0e0e0 !important;
    color: #333 !important;
    border: none;
    letter-spacing: 0.08em;
    box-shadow: none;
}
.bg-success.plan-badge-indicator {
    background-color: #e7f7ea !important;
    color: #22713a !important;
}
.bg-primary.plan-badge-indicator {
    background-color: #fff3cd !important;
    color: #856404 !important;
    border: 1px solid #ffe066 !important;
}
.card-header {
    position: relative;
}
.card.membership-option:hover, .card.membership-option.selected {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 6px 24px 0 rgba(0,0,0,0.15);
    border: 2px solid #c92f2f;
}
</style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Add New Member</h3>
                
                <!-- Phase Navigation -->
                <ul class="phases nav nav-pills nav-fill mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-phase="1" href="#">Personal Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="2" href="#">Membership Plan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="3" href="#">Select Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="4" href="#">Account Setup</a>
                    </li>
                </ul>

                <form id="memberForm" method="POST" enctype="multipart/form-data">
                   <!-- Phase 1: Personal Details -->
                    <div id="phase1" class="phase">
                        <h4 class="mb-4">Personal Information</h4>
                        <div class="card">
                            <div class="card-body">
                                <!-- Walk-in Member Selector -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="walk_in_selector" class="form-label">Select from Walk-in Records</label>
                                        <select class="form-select" id="walk_in_selector" name="walk_in_selector">
                                            <option value="">-- Select Walk-in Member --</option>
                                            <?php
                                            // Fetch walk-in records from the database
                                            $walkInSql = "SELECT id, first_name, middle_name, last_name, phone_number FROM walk_in_records ORDER BY last_name, first_name";
                                            $walkInStmt = $pdo->prepare($walkInSql);
                                            $walkInStmt->execute();
                                            $walkInRecords = $walkInStmt->fetchAll();
                                            
                                            foreach ($walkInRecords as $record) {
                                                $middleName = !empty($record['middle_name']) ? " " . $record['middle_name'] : "";
                                                $displayName = $record['last_name'] . ", " . $record['first_name'] . $middleName;
                                                echo '<option value="' . $record['id'] . '" 
                                                    data-first="' . htmlspecialchars($record['first_name']) . '" 
                                                    data-middle="' . htmlspecialchars($record['middle_name']) . '" 
                                                    data-last="' . htmlspecialchars($record['last_name']) . '"
                                                    data-phone="' . htmlspecialchars($record['phone_number']) . '">
                                                    ' . htmlspecialchars($displayName) . '
                                                </option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Personal Information -->
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name">
                                        <div class="invalid-feedback">Please enter your first name</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name">
                                        <div class="invalid-feedback">Please enter your last name</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Sex</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sex" id="male" value="Male">
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sex" id="female" value="Female">
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                        <div class="invalid-feedback">Please select your sex</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="birthdate" class="form-label">Birth Date</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate">
                                        <div class="invalid-feedback">Please enter your birth date</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{11}">
                                        <div class="invalid-feedback">Please enter your contact number</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="phase" style="display: none;">
                        <h4 class="mb-4">Select Your Membership Plan</h4>
                        <!-- Filter & Search Controls -->
<div class="row mb-3 align-items-center g-2">
    <div class="col-auto d-flex gap-2 align-items-center">
        <span class="fw-semibold me-2">Filter:</span>
        <button type="button" class="btn btn-outline-secondary btn-sm plan-filter active" data-filter="all">All</button>
        <button type="button" class="btn btn-outline-primary btn-sm plan-filter" data-filter="special">Special</button>
        <button type="button" class="btn btn-outline-success btn-sm plan-filter" data-filter="regular">Regular</button>
    </div>
    <div class="col-auto">
        <input type="text" class="form-control form-control-sm" id="planSearch" placeholder="Search plan name...">
    </div>
    <div class="col-auto">
    <select class="form-select form-select-sm" id="planSortDuration">
        <option value="none">Sort by Duration</option>
        <option value="duration-asc">Duration Low-High</option>
        <option value="duration-desc">Duration High-Low</option>
    </select>
</div>
<div class="col-auto">
    <select class="form-select form-select-sm" id="planSortPrice">
        <option value="none">Sort by Price</option>
        <option value="price-asc">Price Low-High</option>
        <option value="price-desc">Price High-Low</option>
    </select>
</div>
</div>
                        <div class="row">
                            <?php
                            // Fetch membership plans from database with duration type
                            $sql = "SELECT mp.*, dt.type_name as duration_type 
                                   FROM membership_plans mp 
                                   LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id 
                                   WHERE mp.status = 'active'";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();
                            $membershipPlans = $stmt->fetchAll();

                            foreach ($membershipPlans as $plan): ?>
                            <div class="col-sm-6 col-md-6 col-lg-3 mb-4">
                                <?php
    $planTypeRaw = isset($plan['plan_type']) ? strtolower($plan['plan_type']) : 'regular';
    $planTypeUi = ($planTypeRaw === 'standard') ? 'regular' : $planTypeRaw;
?>
<div class="card membership-option h-100 plan-type-<?= $planTypeUi ?>" onclick="selectMembershipPlan(this)"
     data-plan-id="<?= $plan['id'] ?>"
     data-price="<?= $plan['price'] ?>"
     data-name="<?= htmlspecialchars($plan['plan_name']) ?>"
     data-duration="<?= htmlspecialchars($plan['duration']) ?>"
     data-duration-type="<?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>"
     data-plan-type="<?= $planTypeUi ?>">
                                    <?php
                                        $defaultImage = '../cms_img/default/membership.jpeg';
                                        $imagePath = $defaultImage;
                                        if (!empty($plan['image']) && file_exists(__DIR__ . "/../../../cms_img/gym_rates/" . $plan['image'])) {
                                            $imagePath = '../cms_img/gym_rates/' . htmlspecialchars($plan['image']);
                                        }
                                    ?>
                                    <div class="card-header text-white text-center" style="background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center; position: relative; min-height: 120px;">
    <?php
        $planType = isset($plan['plan_type']) ? strtolower($plan['plan_type']) : 'regular';
        $planTypeUi = ($planType === 'standard') ? 'regular' : $planType;
        $badgeClass = $planTypeUi === 'special' ? 'bg-primary' : 'bg-success';
        $badgeText = ucfirst($planTypeUi);
    ?>
    <span class="badge rounded-pill plan-badge-indicator <?= $badgeClass ?>"> <?= $badgeText ?> </span>
    <div class="overlay-header">
        <h2 class="fw-bold mb-0 service-name"><?= htmlspecialchars($plan['plan_name']) ?></h2>
    </div>
    <div class="overlay-darker"></div>
</div>
                                    <div class="card-body">
                                        <p class="card-text mb-1">Price: ₱<?= number_format($plan['price'], 2) ?></p>
                                        <?php
                                            $duration = (int)$plan['duration'];
                                            $durationType = isset($plan['duration_type']) ? $plan['duration_type'] : 'months';
                                            if ($duration === 1) {
                                                // Remove only a trailing 's' for common duration types
                                                $durationType = preg_replace('/s$/', '', $durationType);
                                            }
                                        ?>
                                        <p class="card-text mb-1">Duration: <?= htmlspecialchars($plan['duration']) ?> <?= htmlspecialchars($durationType) ?></p>
                                        <input type="radio" name="membership_plan" class="d-none"
                                               value="<?= $plan['id'] ?>"
                                               data-price="<?= $plan['price'] ?>"
                                               data-name="<?= htmlspecialchars($plan['plan_name']) ?>"
                                               data-duration="<?= htmlspecialchars($plan['duration']) ?>"
                                               data-duration-type="<?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

<style>
.overlay-header {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    text-align: center;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.overlay-darker{
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.5);
    z-index: 1;
}
.card-header .service-name {
    color: #fff;
    text-shadow: 1px 1px 10px rgba(0,0,0,0.85);
    font-size: 2.1rem;
    font-weight: 800;
    letter-spacing: 1px;
    margin: 0;
    line-height: 1.1;
}
.card.membership-option {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card.membership-option:hover, .card.membership-option.selected {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 6px 24px 0 rgba(0,0,0,0.15);
    border: 2px solid #c92f2f;
}
/* Subtle badge indicator styles */
.plan-badge-indicator {
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0.96;
    padding: 0.22em 0.65em;
    z-index: 10;
    background-color: #e0e0e0 !important;
    color: #333 !important;
    border: none;
    letter-spacing: 0.08em;
    box-shadow: none;
}
.bg-success.plan-badge-indicator {
    background-color: #e7f7ea !important;
    color: #22713a !important;
}
.bg-primary.plan-badge-indicator {
    background-color: #fff3cd !important;
    color: #856404 !important;
    border: 1px solid #ffe066 !important;
}
</style>

<div class="form-group mt-4">
                            <label for="membership_start_date">Start Date:</label>
                            <input type="date" class="form-control" id="membership_start_date" name="membership_start_date" 
                                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Phase 3: Select Programs -->
                    <div id="phase3" class="phase" style="display: none;">
                        <h4 class="mb-4">Select Your Programs</h4>
                        
                        <!-- Programs Section -->
                        <div class="programs-container mb-5">
                            <?php foreach ($programs as $program): ?>
                                <?= generateProgramCard($program) ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Schedule Modal -->
                        <div class="modal fade" id="scheduleModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Available Schedules</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="modal-body">
                                            <p id="programDesc"></p>
                                        </div>
                                        <div id="scheduleTableContainer">
                                            <table id="scheduleTable" class="table table-striped">
                                                <thead id="scheduleTableHead"></thead>
                                                <tbody id="scheduleTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rental Services Section -->
                        <h4 class="mb-4 mt-5">Additional Rental Services</h4>
                        <div class="row">
                            <?php foreach ($rentalServices as $service): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card rental-option h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($service['rental_name']) ?></h5>
                                        <p class="card-text">
                                            Duration: <?= htmlspecialchars($service['duration']) ?> <?= htmlspecialchars($service['duration_type']) ?><br>
                                            Price: ₱<?= number_format($service['price'], 2) ?>
                                        </p>
                                        <input type="checkbox" 
                                               name="rental_services[]" 
                                               value="<?= $service['id']; ?>"
                                               class="rental-service-checkbox"
                                               data-name="<?= htmlspecialchars($service['rental_name']) ?>"
                                               data-price="<?= $service['price'] ?>"
                                               data-duration="<?= htmlspecialchars($service['duration']) ?>"
                                               data-duration-type="<?= htmlspecialchars($service['duration_type']) ?>"
                                               style="display: none;">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Phase 4: Review & Register -->
                    <div id="phase4" class="phase" style="display: none;">
                        <div class="review-container">
                            <h4 class="review-title">Review Information</h4>

                            <!-- Account Credentials -->
                            <div class="review-section">
                                <h5>Account Credentials</h5>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                        <div class="invalid-feedback">Please enter a username</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="text" class="form-control" id="password" name="password" required>
                                        <div class="invalid-feedback">Please enter a password</div>
                                    </div>
                                </div>
                            </div>

                            <div class="review-section">
                                <h5>Personal Information</h5>
                                <div class="review-info">
                                    <p><strong>Name:</strong> <span id="review-name"></span></p>
                                    <p><strong>Sex:</strong> <span id="review-sex"></span></p>
                                    <p><strong>Birthdate:</strong> <span id="review-birthdate"></span></p>
                                    <p><strong>Contact Number:</strong> <span id="review-contact"></span></p>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Membership Plan</h5>
                                <div id="review-membership" class="review-info">
                                    <p><strong>Plan:</strong> <span id="review-plan"></span></p>
                                    <p><strong>Duration:</strong> <span id="review-duration"></span></p>
                                    <p><strong>Start Date:</strong> <span id="review-start-date"></span></p>
                                    <p><strong>End Date:</strong> <span id="review-end-date"></span></p>
                                    <p><strong>Price:</strong><span class="review-price">0.00</span></p>
                                    <p><strong>Registration Fee:</strong><span class="review-registration-fee">0.00</span></p>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Selected Programs</h5>
                                <div id="review-programs" class="review-programs">
                                    <div id="review-programs-list"></div>
                                    <p class="mt-2"><strong>Total Programs Fee:</strong><span class="review-programs-fee">0.00</span></p>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Rental Services</h5>
                                <div id="review-rentals" class="review-rentals">
                                    <div id="review-rentals-list"></div>
                                    <p class="mt-2"><strong>Total Rentals Fee:</strong><span class="review-rentals-fee">0.00</span></p>
                                </div>
                            </div>

                            <div class="review-section">
                                <h5>Total Amount</h5>
                                <div class="review-info">
                                    <p><strong>Registration Fee:</strong><span class="review-registration-fee">₱500.00</span></p>
                                    <p><strong>Plan Amount:</strong><span class="review-price">0.00</span></p>
                                    <p><strong>Programs Fee:</strong><span class="review-programs-fee">0.00</span></p>
                                    <p><strong>Rentals Fee:</strong><span class="review-rentals-fee">0.00</span></p>
                                    <h4 class="mt-3"><strong>Total:</strong><span id="review-total-amount">0.00</span></h4>
                                </div>
                            </div>
                            
                            <div class="review-actions">
                                <button type="button" class="btn btn-outline-secondary" id="backToProgramsBtn">Back to Programs</button>
                                <button type="submit" class="btn btn-primary">Complete Registration</button>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Navigation Buttons -->
                    <div class="fixed-bottom bg-white py-3 border-top" style="z-index: 1000;">
                        <div class="container">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" id="prevBtn">Previous</button>
                                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                                <button type="button" class="btn btn-success" id="reviewBtn" style="display: none;">Review & Register</button>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs for selected items -->
                    <input type="hidden" name="action" value="add_member">
                    <input type="hidden" name="selected_schedule_id" value="">
                    <input type="hidden" id="selected_programs" name="selected_programs" value="">
                </form>

            </div>
        </div>
    </div>

    <!-- Membership Summary Section -->
    <div class="membership-summary">
        <div class="container">

            <div class="summary-row" data-type="registration">
                <div class="details">
                    <p><strong>Registration Fee:</strong> ₱<?= $memberRegistration->getRegistrationFee() ?></p>
                </div>
            </div>

            <!-- Selected Plan -->
            <div class="summary-row" data-type="membership" style="display: none;">
                <h5>Membership Plan</h5>
                <div class="details">
                    <p><strong>Plan:</strong> <span class="membership-plan-name"></span></p>
                    <p><strong>Duration:</strong> <span class="membership-duration"></span></p>
                    <p><strong>Start Date:</strong> <span class="membership-start-date"></span></p>
                    <p><strong>End Date:</strong> <span class="membership-end-date"></span></p>
                    <p><strong>Price:</strong> ₱<span class="membership-amount">0.00</span></p>
                </div>
            </div>

            <!-- Selected Programs -->
            <div id="selectedProgramsContainer">
                <!-- Programs will be dynamically added here -->
            </div>
            
            <!-- Selected Rental Services -->
            <div class="rental-services-summary">
                <!-- Rental services will be dynamically added here -->
            </div>

            <!-- Total Amount -->
            <div class="summary-row mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Total Amount:</h5>
                    <h5 class="mb-0"><span class="totalAmount">0.00</span></h5>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize PHP variables for JavaScript
        window.BASE_URL = '<?= BASE_URL ?>';
        window.registrationFee = <?= number_format($memberRegistration->getRegistrationFee(), 2, '.', '') ?>;
        
        // Function to calculate end date
        function calculateEndDate(startDate, duration, durationType = 'months') {
            if (!startDate || !duration) return '';
            
            const date = new Date(startDate);
            
            if (durationType === 'months') {
                date.setMonth(date.getMonth() + parseInt(duration));
            } else if (durationType === 'days') {
                date.setDate(date.getDate() + parseInt(duration));
            }
            
            return date.toISOString().split('T')[0];
        }
        // Walk-in member selector functionality
        $(document).ready(function() {
            $('#walk_in_selector').change(function() {
                var selectedOption = $(this).find('option:selected');
                
                if (selectedOption.val() !== '') {
                    // Populate the form fields with the selected walk-in member data
                    $('#first_name').val(selectedOption.data('first'));
                    $('#middle_name').val(selectedOption.data('middle'));
                    $('#last_name').val(selectedOption.data('last'));
                    $('#contact').val(selectedOption.data('phone')); // Add this line to populate phone number
                } else {
                    // Clear the form fields if "Select Walk-in Member" is chosen
                    $('#first_name').val('');
                    $('#middle_name').val('');
                    $('#last_name').val('');
                    $('#contact').val(''); // Clear phone number field
                }
            });
        });
    </script>
    <script src="<?= BASE_URL ?>/admin/pages/members/functions/add_member.js"></script>
<script>
// Membership Plan Filter, Search, and Sort Logic
$(document).ready(function() {
    function filterAndSortPlans() {
        var filter = $('.plan-filter.active').data('filter');
        var search = $('#planSearch').val().toLowerCase();
        var sortDuration = $('#planSortDuration').val();
        var sortPrice = $('#planSortPrice').val();

        // Duration type multipliers (days as base unit)
        var durationTypeToDays = {
            'day': 1, 'days': 1,
            'week': 7, 'weeks': 7,
            'month': 30, 'months': 30,
            'year': 365, 'years': 365
        };

        // Gather all columns/cards into an array for sorting
        var $cols = $('.membership-option').closest('.col-sm-6, .col-md-6, .col-lg-3');
        var $cards = $cols.map(function() {
            var $card = $(this).find('.membership-option');
            var name = $card.data('name').toLowerCase();
            var type = $card.data('plan-type');
            if (type === 'standard') type = 'regular';
            var price = parseFloat($card.data('price'));
            var duration = parseFloat($card.data('duration'));
            var durationType = ($card.data('duration-type') || '').toLowerCase();
            var durationDays = duration * (durationTypeToDays[durationType] || 1);
            return {
                col: $(this),
                card: $card,
                name: name,
                type: type,
                price: price,
                duration: duration,
                durationType: durationType,
                durationDays: durationDays
            };
        }).get();

        // Filter
        $cards.forEach(function(obj) {
            var show = true;
            if (filter !== 'all' && obj.type !== filter) show = false;
            if (search && !obj.name.includes(search)) show = false;
            obj.col.toggle(show);
        });

        // Sort (only visible)
        var $row = $('.membership-option').closest('.row');
        var $visibleCols = $cards.filter(function(obj) { return obj.col.is(':visible'); });
        // Only one sort is applied at a time, priority: duration > price
        if (sortDuration !== 'none') {
            $visibleCols.sort(function(a, b) {
                if (sortDuration === 'duration-asc') return a.durationDays - b.durationDays;
                if (sortDuration === 'duration-desc') return b.durationDays - a.durationDays;
                return 0;
            });
        } else if (sortPrice !== 'none') {
            $visibleCols.sort(function(a, b) {
                if (sortPrice === 'price-asc') return a.price - b.price;
                if (sortPrice === 'price-desc') return b.price - a.price;
                return 0;
            });
        }
        // Re-append sorted visible columns
        $visibleCols.forEach(function(obj) { $row.append(obj.col); });
    }

    $('.plan-filter').on('click', function() {
        $('.plan-filter').removeClass('active');
        $(this).addClass('active');
        filterAndSortPlans();
    });
    $('#planSearch').on('input', filterAndSortPlans);
    $('#planSortDuration').on('change', filterAndSortPlans);
    $('#planSortPrice').on('change', filterAndSortPlans);
    // Initial sort/filter
    filterAndSortPlans();
});
</script>
</body>
</html>
