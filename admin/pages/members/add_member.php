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
    error_log("=== FORM SUBMISSION DEBUG ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    if ($_POST['action'] === 'add_member') {
        try {
            $result = $memberRegistration->addMember($_POST);
            error_log("Add member result: " . print_r($result, true));
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error in add_member.php: " . $e->getMessage());
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
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
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
        body {
            padding-bottom: 215px;
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Add New Member</h3>
                
                <!-- Phase Navigation -->
                <ul class="nav nav-pills nav-fill mb-4">
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
                            <div class="col-md-4 mb-4">
                                <div class="card membership-option h-100" onclick="selectMembershipPlan(this)" 
                                     data-plan-id="<?= $plan['id'] ?>"
                                     data-price="<?= $plan['price'] ?>"
                                     data-name="<?= htmlspecialchars($plan['plan_name']) ?>"
                                     data-duration="<?= htmlspecialchars($plan['duration']) ?>"
                                     data-duration-type="<?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($plan['plan_name']) ?></h5>
                                        <span class="duration-badge">
                                            <?= htmlspecialchars($plan['duration']) ?> <?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>
                                        </span>
                                        <div class="h4">₱<?= number_format($plan['price'], 2) ?></div>
                                        <p class="description"><?= htmlspecialchars($plan['description']) ?></p>
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
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn">Back to Programs</button>
                                <button type="submit" class="btn btn-primary">Complete Registration</button>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Navigation Buttons -->
                    <div class="fixed-bottom bg-white py-3 border-top" style="z-index: 1000;">
                        <div class="container">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">Previous</button>
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

    <!-- Add padding to body to account for fixed summary -->
    <style>
        body {
            padding-bottom: 215px; /* Adjust based on summary height */
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize variables
            const registrationFee = parseFloat('<?= $memberRegistration->getRegistrationFee() ?>');
            let selectedPrograms = [];
            let totalAmount = registrationFee;
            let totalProgramsFee = 0;

            // Calculate and update all totals
            function updateTotalAmount() {
                // Start with registration fee
                let total = registrationFee;

                // Add plan price if selected
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    total += parseFloat(selectedPlan.data('price')) || 0;
                }

                // Add program and rental costs
                total += totalProgramsFee;
                $('.rental-service-checkbox:checked').each(function() {
                    total += parseFloat($(this).data('price')) || 0;
                });

                // Update totals in both summary and review
                $('.totalAmount').text('₱' + total.toFixed(2));
                $('#review-total-amount').text('₱' + total.toFixed(2));
                $('.review-programs-fee').text('₱' + totalProgramsFee.toFixed(2));
                totalAmount = total;
            }

            // Handle plan selection
            $('input[name="membership_plan"]').change(function() {
                const selectedPlan = $(this);
                const planPrice = parseFloat(selectedPlan.data('price'));
                const planName = selectedPlan.data('name');
                const duration = selectedPlan.data('duration');
                const durationType = selectedPlan.data('duration-type');
                const startDate = $('#membership_start_date').val();

                // Update plan summary
                $('#selectedPlan').html(`
                    <p>Plan: ${planName}</p>
                    <p>Duration: ${duration} ${durationType}</p>
                    <p>Price: ₱${planPrice.toFixed(2)}</p>
                    <p>Start Date: ${startDate || 'Not selected'}</p>
                `);

                // Update totals
                updateTotalAmount();

                // Update review section if visible
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Initialize totals on page load
            updateTotalAmount();

            // Handle start date changes
            $('#membership_start_date').change(function() {
                const startDate = $(this).val();
                const selectedPlan = $('#selectedPlan');
                
                if (selectedPlan.length) {
                    const lines = selectedPlan.html().split('</p>');
                    lines[3] = `<p>Start Date: ${startDate || 'Not selected'}`;
                    selectedPlan.html(lines.join('</p>'));

                    // Update totals
                    updateTotalAmount();

                    // Update review section if visible
                    if ($('#phase4').is(':visible')) {
                        updateReviewInformation();
                    }
                }
            });

            // Handle program selection
            $('.program-card').click(function() {
                const card = $(this);
                const programId = card.data('program-id');
                const program = card.find('.card-title').text();
                const type = card.data('program-type');
                const coach = card.find('.program-coach option:selected').text();
                const schedule = card.find('.program-coach option:selected').data('schedule');
                const price = parseFloat(card.find('.program-coach option:selected').data('price'));

                if (card.hasClass('selected')) {
                    // Remove program
                    card.removeClass('selected');
                    selectedPrograms = selectedPrograms.filter(p => p.id !== programId);
                } else {
                    // Add program
                    card.addClass('selected');
                    selectedPrograms.push({
                        id: programId,
                        program: program,
                        type: type,
                        coach: coach,
                        schedule: schedule,
                        price: price
                    });
                }

                // Update programs summary
                updateProgramsSummary();

                // Update totals
                updateTotalAmount();

                // Update review section if visible
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Handle program coach selection
            $('.program-coach').on('change', function() {
                const coachProgramTypeId = $(this).val();
                if (!coachProgramTypeId) return;

                const programCard = $(this).closest('.program-card');
                const programName = programCard.find('.card-title').text().trim();
                const programType = programCard.data('program-type');

                // Reset dropdown to default immediately
                $(this).val('').find('option:first').prop('selected', true);

                // Store current program card for reference
                $('#scheduleModal').data('current-program-card', programCard);

                scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                
                // Pre-fetch data before showing modal
                $.ajax({
                    url: `${BASE_URL}/admin/pages/members/add_member.php`,
                    method: 'GET',
                    data: {
                        action: 'get_schedule',
                        coach_program_type_id: coachProgramTypeId
                    },
                    success: function(response) {
                        const tableBody = $('#scheduleTableBody');
                        const tableHead = $('#scheduleTable thead');
                        const programDesc = $('#programDesc');
                        
                        // Clear previous content
                        tableBody.empty();
                        programDesc.empty();
                        
                        if (response.success && response.data?.length > 0) {
                            // Different headers for personal and group schedules
                            if (response.program_type === 'personal') {
                                tableHead.html(`
                                    <tr>
                                        <th>Day</th>
                                        <th>Time Slot</th>
                                        <th>Duration (mins)</th>
                                        <th>Coach</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                `);
                            } else {
                                tableHead.html(`
                                    <tr>
                                        <th>Day</th>
                                        <th>Time Slot</th>
                                        <th>Capacity</th>
                                        <th>Coach</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                `);
                            }

                            // For personal training, split time slots based on duration
                            if (response.program_type === 'personal') {
                                const rows = [];
                                response.data.forEach(schedule => {
                                    // Calculate number of slots based on duration
                                    const startTime = new Date(`2000-01-01 ${schedule.start_time}`);
                                    const endTime = new Date(`2000-01-01 ${schedule.end_time}`);
                                    const totalMinutes = (endTime - startTime) / 1000 / 60;
                                    const numSlots = Math.floor(totalMinutes / schedule.duration_rate);
                                    
                                    // Create a slot for each duration period
                                    for (let i = 0; i < numSlots; i++) {
                                        const slotStart = new Date(startTime.getTime() + i * schedule.duration_rate * 60000);
                                        const slotEnd = new Date(slotStart.getTime() + schedule.duration_rate * 60000);
                                        
                                        rows.push(`
                                            <tr data-id='${schedule.id}' 
                                                data-type='personal' 
                                                data-coach-program-type-id='${coachProgramTypeId}' 
                                                data-program='${programName}' 
                                                data-coach='${schedule.coach_name}' 
                                                data-day='${schedule.day}' 
                                                data-starttime='${slotStart.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' })}' 
                                                data-endtime='${slotEnd.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' })}' 
                                                data-price='${schedule.price}'>
                                                <td>${schedule.day}</td>
                                                <td>${slotStart.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' })} - ${slotEnd.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' })}</td>
                                                <td>${schedule.duration_rate}</td>
                                                <td>${schedule.coach_name}</td>
                                                <td>₱${schedule.price}</td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm select-schedule">Select</button>
                                                </td>
                                            </tr>
                                        `);
                                    }
                                });
                                tableBody.html(rows.join(''));
                            } else {
                                // Original group schedule display
                                const rows = response.data.map(schedule => `
                                    <tr data-id='${schedule.id}' 
                                        data-type='group' 
                                        data-coach-program-type-id='${coachProgramTypeId}' 
                                        data-program='${programName}' 
                                        data-coach='${schedule.coach_name}' 
                                        data-day='${schedule.day}' 
                                        data-starttime='${schedule.start_time}' 
                                        data-endtime='${schedule.end_time}' 
                                        data-price='${schedule.price}'>
                                        <td>${schedule.day}</td>
                                        <td>${schedule.start_time} - ${schedule.end_time}</td>
                                        <td>${schedule.current_subscribers} / ${schedule.capacity}</td>
                                        <td>${schedule.coach_name}</td>
                                        <td>₱${schedule.price}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary select-schedule">Select</button>
                                        </td>
                                    </tr>
                                `).join('');
                                tableBody.html(rows);
                            }
                        } else {
                            tableBody.html('<tr><td colspan="6" class="text-center">No schedules found</td></tr>');
                            programDesc.hide();
                        }
                        
                        // Clear any previous selections
                        $('.schedule-row').removeClass('selected');
                        
                        // Reset modal title
                        $('#scheduleModalLabel').text('Select Schedule');
                        
                        scheduleModal.show();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching schedules:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        $('#scheduleTableBody').html('<tr><td colspan="6" class="text-center">Failed to load schedules</td></tr>');
                        $('#programDesc').hide();
                        scheduleModal.show();
                    }
                });
            });

            // Reset modal when hidden
            $('#scheduleModal').on('hidden.bs.modal', function() {
                // Clear modal content
                $('#scheduleTableBody').empty();
                $('#programDesc').empty().hide();
                
                // Reset any program-specific UI elements
                $('.program-specific-element').hide();
                
                // Update the modal title to default
                $('#scheduleModalLabel').text('Select Schedule');
                
                // Remove stored program card reference
                $(this).removeData('current-program-card');
                
                // Ensure all program coach dropdowns show default option
                $('.program-coach').each(function() {
                    $(this).val('').find('option:first').prop('selected', true);
                });
            });

            // Handle rental service selection
            $(document).on('change', 'input[name="rental_services[]"]', function() {
                updateRentalServicesSummary();
                updateTotalAmount();
            });

            // Handle rental service removal
            $(document).on('click', '.remove-rental', function() {
                const rentalId = $(this).closest('.summary-row').data('rental-id');
                $(`input[name="rental_services[]"][value="${rentalId}"]`).prop('checked', false);
                $(this).closest('.summary-row').remove();
                updateTotalAmount();
            });

            // Handle program removal using event delegation
            $(document).on('click', '.remove-program', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const index = $(this).closest('.summary-row').data('index');
                if (typeof index !== 'undefined') {
                    selectedPrograms.splice(index, 1);
                    updateProgramsSummary();
                    updateTotalAmount();
                    
                    // Update review section if visible
                    if ($('#phase4').is(':visible')) {
                        updateReviewInformation();
                    }
                }
            });

            // Helper function to convert time string to minutes for comparison
            function timeToMinutes(timeStr) {
                const [time, period] = timeStr.split(' ');
                let [hours, minutes] = time.split(':').map(Number);
                
                // Convert to 24-hour format
                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }
                
                return hours * 60 + minutes;
            }

            // Function to check if two time ranges overlap
            function checkTimeOverlap(start1, end1, start2, end2) {
                // Convert time strings to minutes for easier comparison
                function timeToMinutes(timeStr) {
                    const [time, period] = timeStr.split(' ');
                    let [hours, minutes] = time.split(':').map(Number);
                    
                    // Convert to 24-hour format
                    if (period === 'PM' && hours !== 12) {
                        hours += 12;
                    } else if (period === 'AM' && hours === 12) {
                        hours = 0;
                    }
                    
                    return hours * 60 + minutes;
                }
                
                const start1Mins = timeToMinutes(start1);
                const end1Mins = timeToMinutes(end1);
                const start2Mins = timeToMinutes(start2);
                const end2Mins = timeToMinutes(end2);
                
                // No overlap if one ends before or at the same time the other starts
                return !(end1Mins <= start2Mins || end2Mins <= start1Mins);
            }

            // Function to check if a schedule conflicts with existing selections
            function hasScheduleConflict(newSchedule) {
                return selectedPrograms.some(program => {
                    // Only check if it's the same day
                    if (program.day === newSchedule.day) {
                        return checkTimeOverlap(
                            newSchedule.startTime,
                            newSchedule.endTime,
                            program.startTime,
                            program.endTime
                        );
                    }
                    return false;
                });
            }

            // Handle schedule selection using event delegation
            $(document).on('click', '.select-schedule', function(e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const scheduleData = {
                    id: row.data('id'),
                    type: row.data('type'),
                    coach_program_type_id: row.data('coach-program-type-id'),
                    program: row.data('program'),
                    coach: row.data('coach'),
                    day: row.data('day'),
                    startTime: row.data('starttime'),
                    endTime: row.data('endtime'),
                    price: row.data('price')
                };

                console.log('Selected schedule:', scheduleData);

                // Validate all required schedule data
                if (!scheduleData.id || !scheduleData.program || !scheduleData.coach || 
                    !scheduleData.day || !scheduleData.startTime || !scheduleData.endTime || 
                    !scheduleData.price || isNaN(scheduleData.price)) {
                    alert('Invalid schedule data. Please try again.');
                    return;
                }

                // Check for schedule conflicts
                if (hasScheduleConflict(scheduleData)) {
                    alert('This schedule conflicts with another selected program. Please choose a different time slot.');
                    return;
                }

                if (selectedPrograms.some(p => p.id === scheduleData.id)) {
                    alert('This schedule has already been selected.');
                    return;
                }

                selectedPrograms.push(scheduleData);
                console.log('Updated selected programs:', selectedPrograms);
                updateProgramsSummary();
                updateTotalAmount();

                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }

                if (scheduleModal) {
                    scheduleModal.hide();
                }
            });

            // Helper function to format date nicely
            function formatDate(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric'
                });
            }

            // Function to update programs summary
            function updateProgramsSummary() {
                const programsContainer = $('#selectedProgramsContainer');
                let html = '';
                totalProgramsFee = 0; // Reset total

                selectedPrograms.forEach((program, index) => {
                    // Skip invalid program data
                    if (!program.id || !program.program || !program.coach || 
                        !program.day || !program.startTime || !program.endTime || 
                        !program.price || isNaN(program.price)) {
                        return;
                    }

                    const schedules = generateProgramDates(
                        $('#membership_start_date').val(),
                        calculateEndDate(
                            $('#membership_start_date').val(),
                            $('input[name="membership_plan"]:checked').data('duration'),
                            $('input[name="membership_plan"]:checked').data('duration-type')
                        ),
                        program.day,
                        program.startTime,
                        program.endTime,
                        program.price
                    );

                    totalProgramsFee += program.price * schedules.length;

                    html += `
                        <div class="summary-row" data-index="${index}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">${program.program}</h5>
                                <button type="button" class="btn btn-link text-danger remove-program p-0" style="font-size: 1.2rem;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="details">
                                <p class="mb-1"><strong>Type:</strong> ${program.type.charAt(0).toUpperCase() + program.type.slice(1)} Program</p>
                                <p class="mb-1"><strong>Coach:</strong> ${program.coach}</p>
                                <p class="mb-1"><strong>Schedule:</strong> ${program.day}, ${program.startTime} - ${program.endTime}</p>
                                <p class="mb-1"><strong>Price:</strong> ₱${program.price} × ${schedules.length} = ₱${(program.price * schedules.length).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                });

                programsContainer.html(html || '');
                updateTotalAmount();
            }

            // Update review information
            function updateReviewInformation() {
                // Account Information
                $('#review-username').text($('#username').val());
                
                // Personal Information
                const fullName = [
                    $('#first_name').val(),
                    $('#middle_name').val(),
                    $('#last_name').val()
                ].filter(Boolean).join(' ');
                
                $('#review-name').text(fullName);
                $('#review-sex').text($('input[name="sex"]:checked').val() || 'Not selected');
                $('#review-birthdate').text($('#birthdate').val() || 'Not selected');
                $('#review-contact').text($('#contact').val() || 'Not selected');

                // Membership Plan Review
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    const planName = selectedPlan.data('name');
                    const duration = selectedPlan.data('duration');
                    const durationType = selectedPlan.data('duration-type');
                    const price = parseFloat(selectedPlan.data('price'));
                    const startDate = $('#membership_start_date').val();

                    // Update plan summary
                    $('#selectedPlan').html(`
                        <p>Plan: ${planName}</p>
                        <p>Duration: ${duration} ${durationType}</p>
                        <p>Price: ₱${price.toFixed(2)}</p>
                        <p>Start Date: ${startDate || 'Not selected'}</p>
                    `);

                    // Update review section
                    $('#review-membership').show();
                    $('#review-plan').text(planName);
                    $('#review-duration').text(duration + ' ' + durationType);
                    $('#review-start-date').text(startDate);
                    $('#review-end-date').text(calculateEndDate(startDate, duration, durationType));
                    $('.review-price').text('₱ ' + price.toFixed(2));
                    $('#review-membership-fee').text('₱' + registrationFee.toFixed(2));
                } else {
                    $('#review-membership').hide();
                }

                // Programs Review
                const reviewProgramsContainer = $('#review-programs');
                let reviewHtml = '';
                let totalProgramsFee = 0;

                selectedPrograms.forEach(program => {
                    // Skip invalid program data
                    if (!program.id || !program.program || !program.coach || 
                        !program.day || !program.startTime || !program.endTime || 
                        !program.price || isNaN(program.price)) {
                        return;
                    }

                    const schedules = generateProgramDates(
                        $('#membership_start_date').val(),
                        calculateEndDate(
                            $('#membership_start_date').val(),
                            selectedPlan.data('duration'),
                            selectedPlan.data('duration-type')
                        ),
                        program.day,
                        program.startTime,
                        program.endTime,
                        program.price
                    );

                    reviewHtml += `
                        <div class="program-item">
                            <div class="program-title">${program.program}</div>
                            <div class="program-details">
                                Type: ${program.type.charAt(0).toUpperCase() + program.type.slice(1)} Program<br>
                                Coach: ${program.coach}<br>
                                Schedule: Every ${program.day}, ${program.startTime} - ${program.endTime}<br>
                                Dates: ${schedules.map(s => formatDate(s.date)).join(', ')}<br>
                                Price: ₱${parseFloat(program.price).toFixed(2)} × ${schedules.length} = ₱${(program.price * schedules.length).toFixed(2)}
                            </div>
                        </div>
                    `;
                    
                    totalProgramsFee += program.price * schedules.length;
                });

                reviewProgramsContainer.html(reviewHtml || '<p class="text-muted">No programs selected</p>');
                $('.review-programs-fee').text(' ₱ ' + totalProgramsFee.toFixed(2));
                $('#review-programs').toggle(selectedPrograms.length > 0);

                // Rental Services Review
                $('#review-rentals-list').empty();
                let totalRentalsFee = 0;
                $('input[name="rental_services[]"]:checked').each(function() {
                    const rental = {
                        name: $(this).data('name'),
                        duration: $(this).data('duration'),
                        durationType: $(this).data('duration-type'),
                        price: parseFloat($(this).data('price'))
                    };
                    const startDate = new Date().toISOString().split('T')[0];
                    const endDate = calculateEndDate(startDate, rental.duration, rental.durationType);
                    
                    const rentalHtml = `
                        <div class="rental-item">
                            <div class="program-title">${rental.name}</div>
                            <div class="program-details">
                                Duration: ${rental.duration} ${rental.durationType}<br>
                                Start Date: ${startDate}<br>
                                End Date: ${endDate}<br>
                                Price: ₱${rental.price.toFixed(2)}
                            </div>
                        </div>`;
                    $('#review-rentals-list').append(rentalHtml);
                    totalRentalsFee += rental.price;
                });
                $('.review-rentals-fee').text(' ₱ ' + totalRentalsFee.toFixed(2));
                $('#review-rentals').toggle($('input[name="rental_services[]"]:checked').length > 0);

                // Update registration fee and total
                $('.review-registration-fee').text('₱ ' + registrationFee.toFixed(2));
                updateTotalAmount();
            }

            // Function to update rental services summary
            function updateRentalServicesSummary() {
                $('.rental-services-summary').empty();
                
                $('input[name="rental_services[]"]:checked').each(function() {
                    const rentalId = $(this).val();
                    const rentalName = $(this).data('name');
                    const rentalPrice = parseFloat($(this).data('price'));
                    const rentalDuration = $(this).data('duration');
                    const rentalDurationType = $(this).data('duration-type');
                    
                    const rentalHtml = `
                        <div class="summary-row" data-type="rental" data-rental-id="${rentalId}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">${rentalName}</h5>
                                <button type="button" class="btn btn-link text-danger remove-rental p-0" style="font-size: 1.2rem;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="details">
                                <p class="mb-1"><strong>Duration:</strong> ${rentalDuration} ${rentalDurationType}</p>
                                <p class="mb-1"><strong>Amount:</strong> ₱${rentalPrice.toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                    
                    $('.rental-services-summary').append(rentalHtml);
                });
            }

            // Handle rental service card selection
            $(document).on('click', '.rental-option', function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
                $(this).toggleClass('selected');
                updateTotalAmount();
                updateRentalServicesSummary();
            });

            // Form submission handler
            $('#memberForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate username and password before submission
                const username = $('#username').val();
                const password = $('#password').val();
                
                if (!username || !password) {
                    alert('Please enter both username and password to complete registration');
                    return;
                }

                const formData = new FormData(this);
                formData.append('action', 'add_member');

                // Get membership dates
                const membershipStartDate = $('#membership_start_date').val();
                const selectedPlan = $('input[name="membership_plan"]:checked');
                const duration = selectedPlan.data('duration');
                const durationType = selectedPlan.data('duration-type');
                
                // Calculate end date
                const membershipEndDate = calculateEndDate(membershipStartDate, duration, durationType);

                // Generate program schedules
                const programsWithSchedules = selectedPrograms.map(program => {
                    // Get all dates between start and end date for this program's day
                    const schedules = generateProgramDates(
                        membershipStartDate,
                        membershipEndDate,
                        program.day,
                        program.startTime,
                        program.endTime,
                        program.price
                    );

                    return {
                        id: program.id,
                        type: program.type,
                        coach_program_type_id: program.coach_program_type_id,
                        schedules: schedules
                    };
                });

                formData.append('selected_programs', JSON.stringify(programsWithSchedules));
                formData.append('membership_start_date', membershipStartDate);
                formData.append('membership_end_date', membershipEndDate);

                // Log form data for debugging
                console.log('=== FORM SUBMISSION DEBUG ===');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ':', pair[0] === 'password' ? '[HIDDEN]' : pair[1]);
                }

                // Submit form
                $.ajax({
                    url: BASE_URL + '/admin/pages/members/add_member.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Raw server response:', response);
                        try {
                            // Parse response if it's a string
                            if (typeof response === 'string') {
                                response = JSON.parse(response);
                            }
                            
                            if (response.success) {
                                alert('Member registration successful!');
                                window.location.href = BASE_URL + '/admin/members_new';
                            } else {
                                alert('Error: ' + (response.message || 'Failed to register member'));
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            console.error('Raw response:', response);
                            alert('An error occurred while processing the server response. Check the console for details.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        console.error('URL used:', BASE_URL + '/admin/pages/members/add_member.php');
                        alert('An error occurred while processing your request. Check the console for details.');
                    }
                });
            });

            // Function to generate program dates
            function generateProgramDates(startDate, endDate, dayOfWeek, startTime, endTime, price) {
                const schedules = [];
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                // Map day names to numbers (0 = Sunday, 6 = Saturday)
                const dayMap = {
                    'Sunday': 0,
                    'Monday': 1,
                    'Tuesday': 2,
                    'Wednesday': 3,
                    'Thursday': 4,
                    'Friday': 5,
                    'Saturday': 6
                };
                
                const targetDay = dayMap[dayOfWeek];
                
                // Iterate through each day in the date range
                for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                    // Check if current day matches the target day of week
                    if (d.getDay() === targetDay) {
                        schedules.push({
                            date: d.toISOString().split('T')[0],
                            day: dayOfWeek,
                            start_time: startTime,
                            end_time: endTime,
                            amount: price
                        });
                    }
                }
                
                return schedules;
            }

            // Function to generate random numbers of specified length
            function generateRandomNumbers(length) {
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += Math.floor(Math.random() * 10);
                }
                return result;
            }

            // Function to generate default credentials
            function generateDefaultCredentials() {
                const firstName = $('#first_name').val().toLowerCase();
                if (firstName) {
                    // Generate username: firstname + 4 random numbers
                    const randomNum = generateRandomNumbers(4);
                    const username = firstName + randomNum;
                    
                    // Generate password: 6 random numbers
                    const password = generateRandomNumbers(6);
                    
                    // Set the values
                    $('#username').val(username);
                    $('#password').val(password);

                    // Update the review section
                    $('#review-username').text(username);
                    $('#review-password').text('******');
                }
            }

            // Function to calculate end date based on start date and duration
            function calculateEndDate(startDate, duration, durationType) {
                if (!startDate) return '';
                
                try {
                    const start = new Date(startDate);
                    if (isNaN(start.getTime())) {
                        console.error('Invalid start date:', startDate);
                        return '';
                    }

                    const durationNum = parseInt(duration);
                    if (isNaN(durationNum)) {
                        console.error('Invalid duration:', duration);
                        return '';
                    }

                    const end = new Date(start);
                    switch(durationType.toLowerCase()) {
                        case 'day':
                        case 'days':
                            end.setDate(end.getDate() + durationNum);
                            break;
                        case 'month':
                        case 'months':
                            end.setMonth(end.getMonth() + durationNum);
                            break;
                        case 'year':
                        case 'years':
                            end.setFullYear(end.getFullYear() + durationNum);
                            break;
                        default:
                            console.error('Invalid duration type:', durationType);
                            return '';
                    }

                    // Format date as YYYY-MM-DD
                    return end.toISOString().split('T')[0];
                } catch (error) {
                    console.error('Error calculating end date:', error);
                    return '';
                }
            }

            // Update membership plan selection with end date
            $('input[name="membership_plan"]').change(function() {
                if ($(this).is(':checked')) {
                    const planName = $(this).data('name');
                    const duration = $(this).data('duration');
                    const durationType = $(this).data('duration-type');
                    const price = parseFloat($(this).data('price'));
                    const startDate = $('#membership_start_date').val();

                    // Update summary section
                    $('.summary-row[data-type="membership"]').show();
                    $('.membership-plan-name').text(planName);
                    $('.membership-duration').text(duration + ' ' + durationType);
                    $('.membership-start-date').text(startDate);
                    $('.membership-end-date').text(calculateEndDate(startDate, duration, durationType));
                    $('.membership-amount').text(price.toFixed(2));

                    // Update review section
                    $('#review-membership').show();
                    $('#review-plan').text(planName);
                    $('#review-duration').text(duration + ' ' + durationType);
                    $('#review-start-date').text(startDate);
                    $('#review-end-date').text(calculateEndDate(startDate, duration, durationType));
                    $('.review-price').text('₱ ' + price.toFixed(2));
                    $('#review-membership-fee').text('₱' + registrationFee.toFixed(2));
                } else {
                    $('.summary-row[data-type="membership"]').hide();
                    $('#review-membership').hide();
                }

                updateTotalAmount();
            });

            // Update membership dates when start date changes
            $('#membership_start_date').change(function() {
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    const startDate = $(this).val();
                    const duration = selectedPlan.data('duration');
                    const durationType = selectedPlan.data('duration-type');
                    const endDate = calculateEndDate(startDate, duration, durationType);
                    
                    if (endDate) {
                        $('.membership-start-date').text(startDate);
                        $('.membership-end-date').text(endDate);
                        $('#review-start-date').text(startDate);
                        $('#review-end-date').text(endDate);
                    }
                }
            });

            // Handle phase navigation
            function showPhase(phaseNumber) {
                // Hide all phases
                $('.phase').hide();
                
                // Show the selected phase
                $(`#phase${phaseNumber}`).show();
                
                // Update navigation pills
                $('.nav-link').removeClass('active');
                $(`.nav-link[data-phase="${phaseNumber}"]`).addClass('active');
                
                // Update progress indicators
                $('.step').removeClass('active completed');
                $(`.step:lt(${phaseNumber})`).addClass('completed');
                $(`.step:eq(${phaseNumber - 1})`).addClass('active');
                
                // Generate default credentials when reaching phase 4
                if (phaseNumber === 4) {
                    generateDefaultCredentials();
                }
                
                // Update buttons
                updateButtons(phaseNumber);
                
                // Update current phase
                currentPhase = phaseNumber;
            }

            // Update button visibility based on phase
            function updateButtons(phaseNumber) {
                // Hide all buttons first
                $('#prevBtn, #nextBtn, #reviewBtn, #submitBtn').hide();
                
                // Show/hide buttons based on phase
                if (phaseNumber > 1) {
                    $('#prevBtn').show();
                }
                
                if (phaseNumber < 3) {
                    $('#nextBtn').show();
                } else if (phaseNumber === 3) {
                    $('#nextBtn').hide();
                    $('#reviewBtn').show();
                } else if (phaseNumber === 4) {
                    $('#prevBtn').show();
                    $('#submitBtn').show();
                }
            }

            // Initialize form
            $(document).ready(function() {
                // Listen for changes to first name and regenerate credentials if in phase 4
                $('#first_name').on('change', function() {
                    if (currentPhase === 4) {
                        generateDefaultCredentials();
                    }
                });

                // Show first phase initially
                showPhase(1);
                
                // Handle next button click for phase 1
                $('#nextBtn').click(function() {
                    const currentPhase = parseInt($('.phase:visible').attr('id').replace('phase', ''));
                    
                    // Phase 1 validation
                    if (currentPhase === 1) {
                        // Validate required fields
                        const firstName = $('#first_name').val();
                        const lastName = $('#last_name').val();
                        const sex = $('input[name="sex"]:checked').val();
                        const birthdate = $('#birthdate').val();
                        const contact = $('#contact').val();

                        // Remove any existing validation styles
                        $('.is-invalid').removeClass('is-invalid');
                        
                        let isValid = true;
                        let missingFields = [];

                        if (!firstName) {
                            $('#first_name').addClass('is-invalid');
                            missingFields.push('First Name');
                            isValid = false;
                        }

                        if (!lastName) {
                            $('#last_name').addClass('is-invalid');
                            missingFields.push('Last Name');
                            isValid = false;
                        }

                        if (!sex) {
                            $('input[name="sex"]').addClass('is-invalid');
                            missingFields.push('Sex');
                            isValid = false;
                        }

                        if (!birthdate) {
                            $('#birthdate').addClass('is-invalid');
                            missingFields.push('Birth Date');
                            isValid = false;
                        } else {
                            const selectedDate = new Date(birthdate);
                            const today = new Date();
                            today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison
                            
                            if (selectedDate >= today) {
                                $('#birthdate').addClass('is-invalid');
                                $('#birthdate').next('.invalid-feedback').text('Birth date cannot be today or in the future');
                                isValid = false;
                            } else {
                                $('#birthdate').removeClass('is-invalid');
                            }
                        }

                        if (!contact) {
                            $('#contact').addClass('is-invalid');
                            missingFields.push('Contact Number');
                            isValid = false;
                        } else {
                            const contactRegex = /^09\d{9}$/;
                            if (!contactRegex.test(contact)) {
                                $('#contact').addClass('is-invalid');
                                $('#contact').next('.invalid-feedback').text('Please enter a valid 11-digit contact number');
                                isValid = false;
                            } else {
                                $('#contact').removeClass('is-invalid');
                            }
                        }

                        if (!isValid) {
                            return;
                        }
                    }

                    // Phase 2 validation
                    if (currentPhase === 2) {
                        // Check if a membership plan is selected
                        if (!$('input[name="membership_plan"]:checked').length) {
                            alert('Please select a membership plan before proceeding.');
                            return;
                        }
                        
                        // Check if start date is selected
                        if (!$('#membership_start_date').val()) {
                            alert('Please select a start date for the membership plan.');
                            return;
                        }
                    }

                    showPhase(currentPhase + 1);
                });
                
                // Handle previous button
                $('#prevBtn').click(function() {
                    const currentPhase = parseInt($('.phase:visible').attr('id').replace('phase', ''));
                    showPhase(currentPhase - 1);
                });
                
                // Handle review button
                $('#reviewBtn').click(function() {
                    updateReviewInformation();
                    showPhase(4);
                });
            });
        });
    </script>
    <script>

        function selectMembershipPlan(card) {
            // Remove selected class from all cards
            document.querySelectorAll('.membership-option').forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            card.classList.add('selected');
            
            // Find and check the radio button inside the card
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Trigger any existing change handlers
            $(radio).trigger('change');
        }
    </script>
</body>
</html>
