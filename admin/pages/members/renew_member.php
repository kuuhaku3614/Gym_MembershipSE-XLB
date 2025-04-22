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

require_once(__DIR__ . "/functions/renew_member.class.php");
$renewMember = new RenewMember();

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

// Handle AJAX form submission for renewal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    

    

    
    if ($_POST['action'] === 'renew_member') {
        try {
            
//"=== RENEW MEMBER AJAX SUBMISSION ===");
            

            if (isset($_POST['selected_programs'])) {
                

                $decoded = json_decode($_POST['selected_programs'], true);
                

            }
            if (isset($_POST['selected_rentals'])) {
                
//'selected_rentals (raw): ' . $_POST['selected_rentals']);
                $decoded = json_decode($_POST['selected_rentals'], true);
                
//'selected_rentals (decoded): ' . print_r($decoded, true));
            }
            $result = $renewMember->renewMember($_POST);
            
//"Renew member result: " . print_r($result, true));
            echo json_encode($result);
        } catch (Exception $e) {
            
//"Error in renew_member.php: " . $e->getMessage());
            
//"Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Get initial data for the form
require_once(__DIR__ . "/functions/member_registration.class.php");
$memberRegistration = new MemberRegistration();
$programs = $memberRegistration->getPrograms();
$rentalServices = $memberRegistration->getRentalServices();
$registrationFee = $memberRegistration->getRegistrationFee();

// Get member ID from GET or fallback
$memberId = isset($_GET['member_id']) ? intval($_GET['member_id']) : '';

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
    <title>Renew Membership</title>
    <?php include '../../includes/header.php'; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .html{
            height: auto;
        }
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
        #phase2, #phase3{
            overflow-y: auto;
            max-height: calc(100vh - 400px); /* Adjust height accounting for headers and footer */
            padding: 0 20px 20px 20px;
        }
        #phase4{
            height: 100vh;
                
        }
        .review-container{
            margin-bottom: 20px;
        }
        .phases{
        border-bottom: 1px solid #ccc;
        }
        h3.mb-4{
            font-weight: 600;
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
            margin: 30px 0;
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
            margin-bottom: 15px;
        }
        .rental-option {
            border: 1px solid #e9ecef;
            padding: 15px;
            margin-bottom: 15px;
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
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Renew Membership</h3>
                <!-- Phase Navigation (skip Phase 1) -->
                <ul class="phases nav nav-pills nav-fill mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-phase="2" href="#">Membership Plan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="3" href="#">Select Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="4" href="#">Review & Renew</a>
                    </li>
                </ul>
                <form id="renewMemberForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" id="member_id" name="member_id" value="<?= htmlspecialchars($memberId) ?>">
                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="phase">
                        <h4 class="mb-4">Select Your Membership Plan</h4>
                        <div class="row">
                            <?php
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
                    <!-- Phase 4: Review & Renew -->
                    <div id="phase4" class="phase" style="display: none;">
                        <div class="review-container">
                            <h4 class="review-title">Review Information</h4>
                            
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
                                <button type="button" class="btn btn-success" id="renewSubmitBtn">Renew Membership</button>
                            </div>
                        </div>
                    </div>
                    <!-- Fixed Navigation Buttons -->
                    <div class="fixed-bottom bg-white py-3 border-top" style="z-index: 1000;">
                        <div class="container">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" id="prevBtn">Previous</button>
                                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                                <button type="button" class="btn btn-success" id="reviewBtn" style="display: none;">Review & Renew</button>
                            </div>
                        </div>
                    </div>
                    <!-- Hidden inputs for selected items -->
                    <input type="hidden" name="action" value="renew_member">
                    <input type="hidden" name="selected_schedule_id" value="">
                    <input type="hidden" id="selected_programs" name="selected_programs" value="">
                </form>
            </div>
        </div>
    </div>
    <!-- Membership Summary Section -->
    <div class="membership-summary">
        <div class="container">
            
            <!-- Selected Plan -->
            <div class="summary-row" data-type="membership" style="display: none;">
                <h5>Membership Plan</h5>
                <div id="selectedPlan" class="plan-summary-box"></div>
            </div>
            <!-- Selected Programs -->
            <div id="selectedProgramsContainer"></div>
            <!-- Selected Rental Services -->
            <div class="rental-services-summary"></div>
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
        window.BASE_URL = '<?= BASE_URL ?>';
        window.registrationFee = <?= number_format($memberRegistration->getRegistrationFee(), 2, '.', '') ?>;
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
    </script>
    <script src="<?= BASE_URL ?>/admin/pages/members/functions/renew_member.js"></script>
</body>
</html>
