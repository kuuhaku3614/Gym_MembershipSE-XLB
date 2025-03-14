<?php
// For AJAX requests, prevent any output before JSON response
if (isset($_GET['action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

session_start();
date_default_timezone_set('Asia/Manila');

// Use absolute path for includes
require_once($_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/admin/pages/members/functions/member_registration.class.php");

// For debugging
error_log("Initializing MemberRegistration class");
$memberRegistration = new MemberRegistration();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_member') {
        $result = $memberRegistration->addMember($_POST);
        echo json_encode($result);
        exit;
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    error_log("Received action: " . $_GET['action']); // Debug log
    
    if ($_GET['action'] === 'get_schedule') {
        error_log("Processing get_schedule request"); // Debug log
        
        // Ensure we're sending JSON
        header('Content-Type: application/json');
        
        try {
            if (!isset($_GET['coach_program_type_id'])) {
                error_log("Missing coach_program_type_id"); // Debug log
                echo json_encode(['success' => false, 'error' => 'Coach program type ID is required']);
                exit;
            }
            
            $coachProgramTypeId = intval($_GET['coach_program_type_id']);
            error_log("Fetching schedule for coach program type ID: " . $coachProgramTypeId); // Debug log
            
            if ($coachProgramTypeId <= 0) {
                error_log("Invalid coach program type ID: " . $coachProgramTypeId); // Debug log
                echo json_encode(['success' => false, 'error' => 'Invalid coach program type ID']);
                exit;
            }
            
            // Get the program type using the class method
            $programType = $memberRegistration->getCoachProgramType($coachProgramTypeId);
            if ($programType === null) {
                error_log("Failed to determine program type"); // Debug log
                echo json_encode(['success' => false, 'error' => 'Failed to determine program type']);
                exit;
            }
            
            $schedules = [];
            if ($programType === 'personal') {
                $schedules = $memberRegistration->getCoachPersonalSchedule($coachProgramTypeId);
            } else {
                $schedules = $memberRegistration->getCoachGroupSchedule($coachProgramTypeId);
            }
            
            error_log("Retrieved schedules: " . print_r($schedules, true)); // Debug log
            
            if (isset($schedules['error'])) {
                echo json_encode(['success' => false, 'error' => $schedules['error']]);
            } else if (isset($schedules['message'])) {
                echo json_encode(['success' => true, 'data' => [], 'message' => $schedules['message']]);
            } else {
                echo json_encode(['success' => true, 'data' => $schedules, 'program_type' => $programType]);
            }
            exit;
        } catch (Exception $e) {
            error_log("Error in get_schedule: " . $e->getMessage()); // Debug log
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    exit;
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
    <title>Add Member</title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/Gym_MembershipSE-XLB/admin/includes/header.php"; ?>
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
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
                </ul>

                <form id="memberForm" method="POST" enctype="multipart/form-data">
                    <!-- Phase 1: Personal Details -->
                    <div id="phase1" class="form-phase active">
                        <h4 class="mb-4">Personal Information</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Sex</label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sex" value="Male" id="sexMale">
                                                <label class="form-check-label" for="sexMale">Male</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sex" value="Female" id="sexFemale">
                                                <label class="form-check-label" for="sexFemale">Female</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Birth Date</label>
                                        <input type="date" class="form-control" name="birthdate">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="phone">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary nav-button" id="prevBtn1">Previous</button>
                            <button type="button" class="btn btn-primary nav-button" id="nextBtn1">Next</button>
                            <button type="submit" class="btn btn-success nav-button" id="submitBtn1" style="display: none;">Submit Registration</button>
                        </div>
                    </div>

                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="form-phase" style="display: none;">
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

                        <div class="form-group">
                            <label for="membership_start_date">When would you like to start your membership?</label>
                            <input type="date" class="form-control" id="membership_start_date" name="membership_start_date" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary nav-button" id="prevBtn2">Previous</button>
                            <button type="button" class="btn btn-primary nav-button" id="nextBtn2">Next</button>
                        </div>
                    </div>

                    <!-- Phase 3: Programs & Services -->
                    <div id="phase3" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Select Programs</h4>
                        
                        <!-- Programs Section -->
                        <div class="mb-4 program-cards">
                            <h5>Available Programs</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4 programs-container">
                                <?php 
                                foreach ($programs as $program): ?>
                                <?php echo generateProgramCard($program); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Rental Services Section -->
                        <div class="mb-4">
                            <h5>Rental Services</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4 rentals-container">
                                <?php 
                                if (empty($rentalServices)) {
                                    echo '<div class="col-12"><div class="alert alert-info">No rental services available.</div></div>';
                                } else {
                                    foreach ($rentalServices as $rental): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($rental['rental_name']); ?></h6>
                                                <p class="card-text"><?php echo htmlspecialchars($rental['description']); ?></p>
                                                <p class="card-text">
                                                    <small class="text-muted">Duration: <?php echo htmlspecialchars($rental['duration'] . ' ' . $rental['duration_type']); ?></small>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">Price: ₱<?php echo number_format($rental['price'], 2); ?></small>
                                                </p>
                                                <div class="form-check">
                                                    <input class="form-check-input rental-checkbox" type="checkbox" 
                                                           name="rental_services[]" 
                                                           value="<?php echo $rental['id']; ?>"
                                                           data-price="<?php echo $rental['price']; ?>"
                                                           data-duration="<?php echo $rental['duration']; ?>"
                                                           data-duration-type="<?php echo $rental['duration_type']; ?>">
                                                    <label class="form-check-label">Select Service</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach;
                                } ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary nav-button" id="prevBtn3">Previous</button>
                            <button type="button" class="btn btn-primary" id="reviewBtn">Review & Register</button>
                        </div>
                    </div>

                    <!-- Phase 4: Review & Register -->
                    <div id="phase4" class="form-phase" style="display: none;">
                        <div class="review-container">
                            <h4 class="review-title">Review Information</h4>
                            
                            <div class="review-section">
                                <h5>Personal Information</h5>
                                <div class="review-info">
                                    <div class="info-row">
                                        <span class="info-label">Name:</span>
                                        <span id="review-name" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Phone:</span>
                                        <span id="review-phone" class="info-value"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Membership Details</h5>
                                <div class="review-info">
                                    <div class="info-row">
                                        <span class="info-label">Plan:</span>
                                        <span id="review-plan" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Duration:</span>
                                        <span id="review-duration" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Price:</span>
                                        <span id="review-price" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Start Date:</span>
                                        <span id="review-start-date" class="info-value"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Selected Programs</h5>
                                <div id="review-programs" class="review-programs"></div>
                            </div>
                            
                            <div class="review-actions">
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn4">Back to Programs</button>
                                <button type="submit" class="btn btn-primary">Complete Registration</button>
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
            <!-- Selected Plan -->
            <div class="summary-row">
                <h5>Selected Plan: <span id="summary-plan-bottom">Not selected</span></h5>
                <div class="details">
                    Duration: <span id="summary-duration-bottom">-</span> | 
                    Start: <span id="summary-start-date-bottom">-</span> | 
                    Price: <span id="summary-price-bottom">₱0.00</span>
                </div>
            </div>

            <!-- Selected Programs -->
            <div id="selected-programs-bottom">
                <!-- Program cards will be dynamically added here -->
            </div>

            <div class="text-center">
                <button type="button" id="reviewBtn" class="btn btn-primary btn-sm">Review & Register</button>
            </div>
        </div>
    </div>

    <!-- Add padding to body to account for fixed summary -->
    <style>
        body {
            padding-bottom: 215px; /* Adjust based on summary height */
        }
    </style>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration/ Capacity</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody">
                                <!-- Schedules will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store selected programs
            let selectedPrograms = [];

            // Phase navigation function
            function showPhase(phaseNumber) {
                $('.form-phase').hide();
                $(`#phase${phaseNumber}`).show();
                
                // Update navigation pills
                $('.nav-link').removeClass('active');
                if (phaseNumber < 4) {
                    $(`.nav-link[data-phase="${phaseNumber}"]`).addClass('active');
                }
                
                // Update progress
                $('.nav-link').each(function() {
                    const phase = parseInt($(this).data('phase'));
                    if (phase < phaseNumber) {
                        $(this).addClass('bg-success text-white');
                    } else if (phase > phaseNumber) {
                        $(this).removeClass('bg-success text-white');
                    }
                });
                
                // Show/hide navigation buttons
                $('.nav-button').hide();
                if (phaseNumber > 1) {
                    $(`#prevBtn${phaseNumber}`).show();
                }
                if (phaseNumber < 3) {
                    $(`#nextBtn${phaseNumber}`).show();
                }

                // Handle review phase
                if (phaseNumber === 4) {
                    updateReviewInfo();
                    $('.membership-summary').hide();
                } else {
                    $('.membership-summary').show();
                }
            }

            // Update review information
            function updateReviewInfo() {
                // Copy basic information
                $('#review-name').text($('#first_name').val() + ' ' + $('#last_name').val());
                $('#review-phone').text($('#contact').val());
                
                // Copy membership details
                $('#review-plan').text($('#summary-plan-bottom').text());
                $('#review-duration').text($('#summary-duration-bottom').text());
                $('#review-price').text($('#summary-price-bottom').text());
                $('#review-start-date').text($('#membership_start_date').val() || 'Not selected');
                
                // Copy programs
                let programsHtml = '';
                if (selectedPrograms.length > 0) {
                    selectedPrograms.forEach(program => {
                        programsHtml += `
                            <div class="program-item">
                                <div class="program-title">${program.coach} - ${program.type}</div>
                                <div class="program-details">
                                    Schedule: ${program.schedule}<br>
                                    Price: ₱${program.price}
                                </div>
                            </div>
                        `;
                    });
                } else {
                    programsHtml = '<div class="text-muted">No programs selected</div>';
                }
                $('#review-programs').html(programsHtml);
            }

            // Handle review button
            $('#reviewBtn').click(function() {
                updateReviewInfo();
                showPhase(4);
            });

            // Navigation button click handlers
            $('.nav-button').click(function() {
                const action = $(this).attr('id').startsWith('prev') ? 'prev' : 'next';
                const currentPhase = parseInt($('.form-phase:visible').attr('id').replace('phase', ''));
                
                if (action === 'prev') {
                    if (currentPhase === 4) { // From review back to programs
                        showPhase(3);
                    } else if (currentPhase > 1) {
                        showPhase(currentPhase - 1);
                    }
                } else if (action === 'next' && currentPhase < 3) {
                    showPhase(currentPhase + 1);
                }
            });

            // Handle membership plan selection
            $('.membership-option').click(function() {
                const radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true);
                
                $('.membership-option').removeClass('selected');
                $(this).addClass('selected');

                // Update summary
                const planName = $(this).find('.card-title').text();
                const duration = $(this).find('.duration').text();
                const price = $(this).find('.price').text();
                
                $('#summary-plan-bottom').text(planName);
                $('#summary-duration-bottom').text(duration);
                $('#summary-price-bottom').text(price);
            });

            // Handle start date change
            $('#membership_start_date').change(function() {
                const startDate = $(this).val();
                $('#summary-start-date-bottom').text(startDate || '-');
            });

            // Add change event handler to coach selects
            $('.program-coach').change(function() {
                const coachProgramTypeId = $(this).val();
                if (!coachProgramTypeId) return;

                $('#scheduleTableBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
                $('#scheduleModal').modal('show');

                $.ajax({
                    url: BASE_URL + '/admin/pages/members/add_member.php',
                    method: 'GET',
                    data: {
                        action: 'get_schedule',
                        coach_program_type_id: coachProgramTypeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            // Update table headers based on program type
                            let headers = `
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>${response.program_type === 'personal' ? 'Duration (mins)' : 'Capacity'}</th>
                                    <th>Price</th>
                                </tr>
                            `;
                            $('#scheduleTable thead').html(headers);

                            let html = '';
                            response.data.forEach(schedule => {
                                html += `
                                    <tr data-id="${schedule.id}" 
                                        data-coach="${schedule.coach_name}"
                                        data-type="${response.program_type}"
                                        data-price="${schedule.price}">
                                        <td>${schedule.day}</td>
                                        <td>${schedule.start_time}</td>
                                        <td>${schedule.end_time}</td>
                                        <td>${response.program_type === 'personal' ? schedule.duration_rate : schedule.capacity}</td>
                                        <td>₱${schedule.price}</td>
                                    </tr>
                                `;
                            });
                            $('#scheduleTableBody').html(html);
                        } else {
                            $('#scheduleTableBody').html('<tr><td colspan="5" class="text-center">No schedules found</td></tr>');
                        }
                    },
                    error: function() {
                        $('#scheduleTableBody').html('<tr><td colspan="5" class="text-center">Failed to load schedules</td></tr>');
                    }
                });

                // Update coach in summary
                const selectedCoach = $(this).find('option:selected').text();
                $('#summary-coach').text(selectedCoach || 'Not selected');
            });

            // Handle schedule selection
            $('#scheduleTableBody').on('click', 'tr', function() {
                const $row = $(this);
                if (!$row.data('id')) return; // Skip if clicking on message row
                
                $('#scheduleTableBody tr').removeClass('selected');
                $row.addClass('selected');

                const programData = {
                    id: $row.data('id'),
                    coach: $row.data('coach'),
                    type: $row.data('type'),
                    schedule: $row.find('td:eq(0)').text() + ' ' + $row.find('td:eq(1)').text() + '-' + $row.find('td:eq(2)').text(),
                    price: $row.data('price')
                };

                // Add program to array
                selectedPrograms.push(programData);
                updateProgramsSummary();
                $('#scheduleModal').modal('hide');
            });

            // Update programs summary
            function updateProgramsSummary() {
                let programsHtml = '';
                selectedPrograms.forEach((program, index) => {
                    programsHtml += `
                        <div class="summary-row" data-index="${index}">
                            <span class="remove-program" onclick="removeProgram(${index})">×</span>
                            <div class="details">
                                <strong>${program.coach}</strong> (${program.type})
                                <div class="mt-1">
                                    <small>${program.schedule}</small>
                                    <span class="float-right">₱${program.price}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#selected-programs-bottom').html(programsHtml || '<div class="text-muted">No programs selected</div>');
                // Store programs in hidden input
                $('#selected_programs').val(JSON.stringify(selectedPrograms));
            }

            // Remove program
            window.removeProgram = function(index) {
                selectedPrograms.splice(index, 1);
                updateProgramsSummary();
            };

            // Handle form submission
            $('#memberForm').submit(function(e) {
                e.preventDefault();
                
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Processing...
                `);
                
                const formData = new FormData(this);
                formData.append('selected_programs', JSON.stringify(selectedPrograms));
                
                $.ajax({
                    url: 'functions/member_registration.class.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.status === 'success') {
                                alert('Registration successful! Redirecting to members list...');
                                window.location.href = 'index.php';
                            } else {
                                alert(result.message || 'Registration failed. Please try again.');
                                submitBtn.prop('disabled', false).text('Complete Registration');
                            }
                        } catch (e) {
                            alert('An error occurred while processing your request. Please try again.');
                            submitBtn.prop('disabled', false).text('Complete Registration');
                        }
                    },
                    error: function() {
                        alert('Failed to submit registration. Please check your connection and try again.');
                        submitBtn.prop('disabled', false).text('Complete Registration');
                    }
                });
            });

            // Initialize with first phase
            showPhase(1);
        });
    </script>
</body>
</html>
