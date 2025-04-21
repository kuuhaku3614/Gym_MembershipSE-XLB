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
require_once(__DIR__ . "/functions/renew_member.class.php");

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

        $schedules = $programType === 'personal' 
            ? $memberRegistration->getCoachPersonalSchedule($coachProgramTypeId)
            : $memberRegistration->getCoachGroupSchedule($coachProgramTypeId);

        if (isset($schedules['error'])) {
            echo json_encode(['success' => false, 'error' => $schedules['error']]);
            exit;
        }

        if (isset($schedules['message'])) {
            echo json_encode(['success' => true, 'data' => [], 'message' => $schedules['message'], 'program_type' => $programType]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $schedules,
            'program_type' => $programType
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
    } elseif ($_POST['action'] === 'renew_member') {
        try {
            $result = $memberRegistration->renewMember($_POST);
            error_log("Renew member result: " . print_r($result, true));
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error in renew_member.php: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Get initial data for the form
$programs = $memberRegistration->getPrograms();
$rentalServices = $memberRegistration->getRentalServices();
$registrationFee = $memberRegistration->getRegistrationFee();

// Get member details if member_id is provided
$memberDetails = null;
if (isset($_GET['member_id'])) {
    echo "<script>console.log('Received member_id:', " . json_encode($_GET['member_id']) . ");</script>";
    $memberDetails = $memberRegistration->getMemberDetails($_GET['member_id']);
    if ($memberDetails) {
        echo "<script>console.log('Member details:', " . json_encode($memberDetails) . ");</script>";
    } else {
        echo "<script>console.error('Failed to load member details');</script>";
    }
}

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

    $coachOptions = generateCoachOptions($program['coaches']);
    $programType = strtolower($program['program_type']);

    return sprintf(
        '<div class="card program-card mb-3" data-program-type="%s" data-program-id="%d">
            <div class="card-body">
                <h5 class="card-title">%s</h5>
                <p class="card-text">%s</p>
                <div class="form-group">
                    <label class="form-label">Select Coach:</label>
                    <select class="form-select program-coach" name="program_coaches[%d]">
                        <option value="">Choose a coach</option>
                        %s
                    </select>
                </div>
            </div>
        </div>',
        $programType,
        $program['program_id'],
        htmlspecialchars($program['program_name']),
        htmlspecialchars($program['program_description']),
        $program['program_id'],
        $coachOptions
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
        .phase {
            overflow-y: auto;
            max-height: calc(100vh - 400px); /* Adjust height accounting for headers and footer */
            padding: 0 20px 20px 20px;
        }
        .phases{
        border-bottom: 1px solid #ccc;
        }
        #phase4{
            min-height: 100vh;
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
            font-weight: bold;
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
        h3.mb-4{
            font-weight:600;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Renew Membership</h3>
                
                <!-- Phase Navigation -->
                <ul class="phases nav nav-pills nav-fill mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-phase="2" href="#">Membership Plan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="3" href="#">Select Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="4" href="#">Review & Confirm</a>
                    </li>
                </ul>

                <form id="renewalForm" method="POST">
                    <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($_GET['member_id']); ?>">
                    <input type="hidden" name="action" value="renew_member">
                    
                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="phase">
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
                                <div class="card membership-option h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($plan['plan_name']) ?></h5>
                                        <span class="duration-badge">
                                            <?= htmlspecialchars($plan['duration']) ?> <?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>
                                        </span>
                                        <div class="h4">₱<?= number_format($plan['price'], 2) ?></div>
                                        <p class="description"><?= htmlspecialchars($plan['description']) ?></p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="membership_plan" 
                                                   value="<?= $plan['id'] ?>" 
                                                   data-price="<?= $plan['price'] ?>"
                                                   data-name="<?= htmlspecialchars($plan['plan_name']) ?>"
                                                   data-duration="<?= htmlspecialchars($plan['duration']) ?>"
                                                   data-duration-type="<?= isset($plan['duration_type']) ? htmlspecialchars($plan['duration_type']) : 'months' ?>">
                                        </div>
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
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" onclick="showPhase(3)">Next</button>
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
                                        <div id="scheduleTableContainer">
                                            <table id="scheduleTable" class="table table-striped">
                                                <thead id="scheduleTableHead"></thead>
                                                <tbody id="scheduleTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="selectScheduleBtn">Select Schedule</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rental Services Section -->
                        <h4 class="mb-4 mt-5">Additional Rental Services</h4>
                        <div class="row">
                            <?php foreach ($rentalServices as $service): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card rental-service h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($service['rental_name']) ?></h5>
                                        <div class="h4">₱<?= number_format($service['price'], 2) ?></div>
                                        <p class="description"><?= htmlspecialchars($service['description']) ?></p>
                                        <p class="duration">Duration: <?= htmlspecialchars($service['duration']) ?> <?= htmlspecialchars($service['duration_type']) ?></p>
                                        <div class="form-check">
                                            <input class="form-check-input rental-service-checkbox" type="checkbox" 
                                                   name="rental_services[]" 
                                                   value="<?= $service['id'] ?>"
                                                   data-price="<?= $service['price'] ?>"
                                                   data-name="<?= htmlspecialchars($service['rental_name']) ?>"
                                                   data-duration="<?= htmlspecialchars($service['duration']) ?>"
                                                   data-duration-type="<?= htmlspecialchars($service['duration_type']) ?>">
                                            <label class="form-check-label">Select this service</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="showPhase(2)">Back</button>
                            <button type="button" class="btn btn-primary" onclick="showPhase(4)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 4: Review & Confirm -->
                    <div id="phase4" class="phase" style="display: none;">
                        <div class="review-container">
                            <h4 class="review-title">Review Your Renewal</h4>

                            <!-- Plan Review -->
                            <div id="review-plan" class="review-section">
                                <h5>Selected Plan</h5>
                                <div class="review-info">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Programs Review -->
                            <div id="review-programs" class="review-section">
                                <h5>Selected Programs</h5>
                                <div class="review-info">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Rental Services Review -->
                            <div id="review-rentals" class="review-section">
                                <h5>Selected Rentals</h5>
                                <div class="review-info">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Total Amount -->
                            <div class="review-section">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Total Amount</h5>
                                    <h4 class="mb-0" id="review-total">₱0.00</h4>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="review-actions mt-4">
                                <button type="button" class="btn btn-secondary me-2" onclick="showPhase(3)">Back</button>
                                <button type="submit" class="btn btn-primary" id="confirmRenewal">Confirm Renewal</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Membership Summary -->
        <div class="membership-summary">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h5>Summary</h5>
                        <div class="summary-content">
                            <!-- Membership Plan Summary -->
                            <div class="summary-row" data-type="membership" style="display: none;">
                                <h5>Selected Plan</h5>
                                <div class="details">
                                    <p><strong>Plan:</strong> <span class="membership-plan-name"></span></p>
                                    <p><strong>Duration:</strong> <span class="membership-duration"></span></p>
                                    <p><strong>Start Date:</strong> <span class="membership-start-date"></span></p>
                                    <p><strong>End Date:</strong> <span class="membership-end-date"></span></p>
                                    <p><strong>Amount:</strong> ₱<span class="membership-amount">0.00</span></p>
                                </div>
                            </div>

                            <!-- Programs Summary -->
                            <div id="selectedPrograms"></div>

                            <!-- Rental Services Summary -->
                            <div class="rental-services-summary"></div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <strong>Total Amount:</strong>
                                    <span class="totalAmount">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make showPhase function globally accessible
        function showPhase(phaseNumber) {
            // Hide all phases and show the requested one
            $('.phase').hide();
            $('#phase' + phaseNumber).show();

            // Update navigation pills if they exist
            $('.nav-link').removeClass('active');
            $('.nav-link[data-phase="' + phaseNumber + '"]').addClass('active');

            // Update review section if going to phase 4
            if (phaseNumber === 4) {
                updateReviewSection();
            }
        }

        $(document).ready(function() {
            let selectedPrograms = [];
            let selectedRentals = [];
            let totalAmount = 0;
            let registrationFee = <?php echo json_encode($registrationFee); ?>;
            let scheduleModal;

            // Calculate and update all totals
            function updateTotalAmount() {
                let total = 0;
                
                // Add plan price if selected
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    total += parseFloat(selectedPlan.data('price')) || 0;
                }

                // Add program costs
                selectedPrograms.forEach(program => {
                    total += parseFloat(program.price) || 0;
                });

                // Add rental costs
                $('.rental-service-checkbox:checked').each(function() {
                    total += parseFloat($(this).data('price')) || 0;
                });

                // Update all total amount displays
                $('.totalAmount').text('₱' + total.toFixed(2));
                $('#review-total').text('₱' + total.toFixed(2));
            }

            // Handle plan selection
            $('input[name="membership_plan"]').change(function() {
                const selectedPlan = $(this);
                const planPrice = parseFloat(selectedPlan.data('price'));
                const planName = selectedPlan.data('name');
                const duration = selectedPlan.data('duration');
                const durationType = selectedPlan.data('duration-type');
                const startDate = $('#membership_start_date').val();
                const endDate = calculateEndDate(startDate, duration, durationType);

                // Update membership summary section
                $('.summary-row[data-type="membership"]').show();
                $('.membership-plan-name').text(planName);
                $('.membership-duration').text(`${duration} ${durationType}`);
                $('.membership-start-date').text(startDate || 'Not selected');
                $('.membership-end-date').text(endDate || 'Not selected');
                $('.membership-amount').text(planPrice.toFixed(2));

                updateTotalAmount();
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Calculate end date based on start date and duration
            function calculateEndDate(startDate, duration, durationType) {
                if (!startDate) return '';
                
                const start = new Date(startDate);
                if (isNaN(start.getTime())) return '';

                const end = new Date(start);
                switch(durationType.toLowerCase()) {
                    case 'day':
                    case 'days':
                        end.setDate(end.getDate() + parseInt(duration));
                        break;
                    case 'month':
                    case 'months':
                        end.setMonth(end.getMonth() + parseInt(duration));
                        break;
                    case 'year':
                    case 'years':
                        end.setFullYear(end.getFullYear() + parseInt(duration));
                        break;
                }

                return end.toISOString().split('T')[0];
            }

            // Update membership dates when start date changes
            $('#membership_start_date').change(function() {
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    const startDate = $(this).val();
                    const duration = selectedPlan.data('duration');
                    const durationType = selectedPlan.data('duration-type');
                    const endDate = calculateEndDate(startDate, duration, durationType);

                    // Update membership summary
                    $('.membership-start-date').text(startDate);
                    $('.membership-end-date').text(endDate);

                    if ($('#phase4').is(':visible')) {
                        updateReviewInformation();
                    }
                }
            });

            // Handle program coach selection
            $(document).on('change', '.program-coach', function() {
                const coachProgramTypeId = $(this).val();
                if (!coachProgramTypeId) return;

                const programCard = $(this).closest('.program-card');
                const programId = programCard.data('program-id');
                const programName = programCard.find('.card-title').text().trim();

                scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                
                // Fetch schedules
                $.ajax({
                    url: `${BASE_URL}/admin/pages/members/add_member.php`,
                    method: 'GET',
                    data: {
                        action: 'get_schedule',
                        coach_program_type_id: coachProgramTypeId
                    },
                    success: function(response) {
                        const tableBody = $('#scheduleTableBody');
                        const tableHead = $('#scheduleTableHead');
                        
                        if (response.success && response.data?.length > 0) {
                            tableHead.html(`
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            `);

                            const rows = response.data.map(schedule => `
                                <tr data-id='${schedule.id}'
                                    data-program-id='${programId}'
                                    data-program='${programName}'
                                    data-coach='${schedule.coach_name}'
                                    data-day='${schedule.day}'
                                    data-start-time='${schedule.start_time}'
                                    data-end-time='${schedule.end_time}'
                                    data-price='${schedule.price}'>
                                    <td>${schedule.day}</td>
                                    <td>${schedule.start_time}</td>
                                    <td>${schedule.end_time}</td>
                                    <td>₱${schedule.price}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary select-schedule">Select</button>
                                    </td>
                                </tr>
                            `).join('');
                            
                            tableBody.html(rows);
                        } else {
                            tableBody.html('<tr><td colspan="5" class="text-center">No schedules found</td></tr>');
                        }
                        scheduleModal.show();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching schedules:', error);
                        alert('Failed to load schedules. Please try again.');
                    }
                });
            });

            // Handle schedule selection
            $(document).on('click', '.select-schedule', function() {
                const row = $(this).closest('tr');
                const scheduleData = {
                    id: row.data('id'),
                    programId: row.data('program-id'),
                    program: row.data('program'),
                    coach: row.data('coach'),
                    day: row.data('day'),
                    startTime: row.data('start-time'),
                    endTime: row.data('end-time'),
                    price: row.data('price')
                };

                // Add to selected programs
                selectedPrograms.push(scheduleData);
                updateProgramsSummary();
                updateTotalAmount();

                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }

                scheduleModal.hide();
            });

            // Update programs summary
            function updateProgramsSummary() {
                $('#selectedPrograms').empty();
                
                selectedPrograms.forEach((program, index) => {
                    const programHtml = `
                        <div class="summary-row">
                            <i class="fas fa-times remove-program" data-index="${index}"></i>
                            <h5>${program.program}</h5>
                            <div class="details">
                                <p><strong>Coach:</strong> ${program.coach}</p>
                                <p><strong>Schedule:</strong> ${program.day}, ${program.startTime} - ${program.endTime}</p>
                                <p><strong>Price:</strong> ₱${parseFloat(program.price).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                    
                    $('#selectedPrograms').append(programHtml);
                });
            }

            // Handle rental service selection
            $('.rental-service-checkbox').change(function() {
                const rental = $(this);
                const rentalId = rental.val();
                const isChecked = rental.is(':checked');
                
                if (isChecked) {
                    // Add rental to summary
                    const rentalHtml = `
                        <div class="summary-row" data-rental-id="${rentalId}">
                            <i class="fas fa-times remove-rental" data-id="${rentalId}"></i>
                            <h5>${rental.data('name')}</h5>
                            <div class="details">
                                <p><strong>Duration:</strong> ${rental.data('duration')} ${rental.data('duration-type')}</p>
                                <p><strong>Price:</strong> ₱${parseFloat(rental.data('price')).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                    $('.rental-services-summary').append(rentalHtml);
                } else {
                    // Remove rental from summary
                    $(`.summary-row[data-rental-id="${rentalId}"]`).remove();
                }

                updateTotalAmount();
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Remove rental service
            $(document).on('click', '.remove-rental', function() {
                const rentalId = $(this).data('id');
                // Uncheck the checkbox
                $(`input.rental-service-checkbox[value="${rentalId}"]`).prop('checked', false);
                // Remove from summary
                $(this).closest('.summary-row').remove();
                updateTotalAmount();
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Remove program
            $(document).on('click', '.remove-program', function() {
                const index = $(this).data('index');
                selectedPrograms.splice(index, 1);
                updateProgramsSummary();
                updateTotalAmount();
                
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Update review information
            function updateReviewInformation() {
                // Membership Plan Review
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    const startDate = $('#membership_start_date').val();
                    const duration = selectedPlan.data('duration');
                    const durationType = selectedPlan.data('duration-type');
                    const endDate = calculateEndDate(startDate, duration, durationType);

                    const planHtml = `
                        <div class="info-row">
                            <span class="info-label">Plan:</span>
                            <span class="info-value">${selectedPlan.data('name')}</span>
                            </div>
                        <div class="info-row">
                            <span class="info-label">Duration:</span>
                            <span class="info-value">${duration} ${durationType}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Start Date:</span>
                            <span class="info-value">${startDate}</span>
                                </div>
                        <div class="info-row">
                            <span class="info-label">End Date:</span>
                            <span class="info-value">${endDate}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Price:</span>
                            <span class="info-value">₱${parseFloat(selectedPlan.data('price')).toFixed(2)}</span>
                            </div>
                        `;
                    $('#review-plan .review-info').html(planHtml);
                }

                // Programs Review
                let programsHtml = '';
                if (selectedPrograms.length > 0) {
                    selectedPrograms.forEach(program => {
                        programsHtml += `
                        <div class="program-item">
                            <div class="program-title">${program.program}</div>
                            <div class="program-details">
                                    Coach: ${program.coach}<br>
                                    Schedule: ${program.day}, ${program.startTime} - ${program.endTime}<br>
                                    Price: ₱${parseFloat(program.price).toFixed(2)}
                                </div>
                            </div>
                        `;
                                });
                            } else {
                    programsHtml = '<p>No programs selected</p>';
                }
                $('#review-programs .review-info').html(programsHtml);

                // Rental Services Review
                let rentalsHtml = '';
                const selectedRentals = $('.rental-service-checkbox:checked');
                if (selectedRentals.length > 0) {
                    selectedRentals.each(function() {
                        const rental = $(this);
                        rentalsHtml += `
                            <div class="rental-item">
                                <div class="rental-title">${rental.data('name')}</div>
                                <div class="rental-details">
                                    Duration: ${rental.data('duration')} ${rental.data('duration-type')}<br>
                                    Price: ₱${parseFloat(rental.data('price')).toFixed(2)}
                                </div>
                            </div>
                        `;
                    });
                            } else {
                    rentalsHtml = '<p>No rentals selected</p>';
                        }
                $('#review-rentals .review-info').html(rentalsHtml);

            updateTotalAmount();
            }

            // Form submission
            $('#renewalForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('selected_programs', JSON.stringify(selectedPrograms));
                
                $.ajax({
                    url: BASE_URL + '/admin/pages/members/renew_member.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            if (typeof response === 'string') {
                                response = JSON.parse(response);
                            }
                            
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Membership renewal successful!',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = BASE_URL + '/admin/members_new';
                                });
                            } else {
                                alert('Error: ' + (response.message || 'Failed to renew membership'));
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('An error occurred while processing the server response.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('An error occurred while processing your request.');
                    }
                });
            });

            // Navigation functions
            function validateCurrentPhase() {
                const currentPhase = parseInt($('.nav-link.active').data('phase'));
                
                switch(currentPhase) {
                    case 2:
                        if (!$('input[name="membership_plan"]:checked').length) {
                            alert('Please select a membership plan');
                            return false;
                        }
                        if (!$('#membership_start_date').val()) {
                            alert('Please select a start date');
                            return false;
                        }
                        break;
                }
                
                return true;
            }

            // Navigation handlers
            $('.nav-link').click(function(e) {
                e.preventDefault();
                const phase = $(this).data('phase');
                if (validateCurrentPhase()) {
                    showPhase(phase);
                }
            });

            $('#nextBtn').click(function() {
                const currentPhase = parseInt($('.nav-link.active').data('phase'));
                if (validateCurrentPhase()) {
                    showPhase(currentPhase + 1);
                }
            });

            // Initialize
            showPhase(2);
            updateTotalAmount();
        });
    </script>
</body>
</html>
