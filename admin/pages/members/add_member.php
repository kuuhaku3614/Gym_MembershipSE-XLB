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
                    <div id="phase1" class="phase">
                        <h4 class="mb-4">Personal Information</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        <div class="invalid-feedback">Please enter your first name</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                        <div class="invalid-feedback">Please enter your last name</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Sex</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sex" id="male" value="Male" required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sex" id="female" value="Female" required>
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                        <div class="invalid-feedback">Please select your sex</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="birthdate" class="form-label">Birth Date</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                                        <div class="invalid-feedback">Please enter your birth date</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{11}" required>
                                        <div class="invalid-feedback">Please enter a valid 11-digit contact number</div>
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
                                   min="<?= date('Y-m-d') ?>" required>
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
                                        <table id="scheduleTable" class="table">
                                            <thead></thead>
                                            <tbody id="scheduleTableBody"></tbody>
                                        </table>
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
                    </div>

                    <!-- Phase 4: Review & Register -->
                    <div id="phase4" class="phase" style="display: none;">
                        <div class="review-container">
                            <h4 class="review-title">Review Information</h4>

                            <div class="review-section">
                                <h5>Account Credentials</h5>
                                <div class="account-info">
                                    <div class="info-row">
                                        <span class="info-label">Username: <input type="text"></span>
                                        <span class="info-label">Password: <input type="password"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-section">
                                <h5>Personal Information</h5>
                                <div class="review-info">
                                    <div class="info-row">
                                        <span class="info-label">Name:</span>
                                        <span id="review-name" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Sex:</span>
                                        <span id="review-sex" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Birthdate:</span>
                                        <span id="review-birthdate" class="info-value"></span>
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
                            
                            <div class="review-section">
                                <h5>Rental Services</h5>
                                <div id="rentals-review" class="review-rentals"></div>
                            </div>

                            <div class="review-section">
                                <div class="review-info">
                                    <div class="info-row">
                                            <span class="info-label">Registration Fee:</span>
                                            <span class="info-value" id="review-registration-fee">₱<?= number_format($memberRegistration->getRegistrationFee(), 2) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total:</span>
                                            <span class="info-value" id="review-total">₱0.00</span>
                                    </div>
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
                                <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">Complete Registration</button>
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
                <h5>Membership Summary</h5>
                <div id="selectedPlan" class="details"></div>
                <div id="selectedPrograms" class="details"></div>
                <div id="selectedRentals" class="details"></div>
            </div>
            <div class="row">
                <div class="col-md-8">
                </div>
                <div class="col-md-4 text-end">
                    <h5>Total Amount</h5>
                    <div class="h4" id="totalAmount">₱0.00</div>
                    <small class="text-muted">Registration Fee: ₱<?= number_format($registrationFee, 2) ?></small>
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

            // Calculate and update all totals
            function updateTotalAmount() {
                // Start with registration fee
                let total = registrationFee;

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

                // Update membership summary total
                $('#totalAmount').text('₱' + total.toFixed(2));

                // Update review section total if visible
                if ($('#phase4').is(':visible')) {
                    $('#review-total').text('₱' + total.toFixed(2));
                }

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
            $(document).on('change', '.program-coach', function() {
                const coachProgramTypeId = $(this).val();
                if (!coachProgramTypeId) return;

                const programCard = $(this).closest('.program-card');
                const programName = programCard.find('.card-title').text().trim();
                const programType = programCard.data('program-type');

                scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                
                // Pre-fetch data before showing modal
                $.ajax({
                    url: `${BASE_URL}/admin/pages/members/add_member.php`,
                    method: 'GET',
                    data: {
                        action: 'get_schedule',
                        coach_program_type_id: coachProgramTypeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        const tableBody = $('#scheduleTableBody');
                        const tableHead = $('#scheduleTable thead');
                        
                        if (response.success && response.data?.length > 0) {
                            tableHead.html(`
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>${response.program_type === 'personal' ? 'Duration (mins)' : 'Capacity'}</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            `);

                            const rows = response.data.map(schedule => `
                                <tr data-schedule='${JSON.stringify({
                                    id: schedule.id,
                                    coach: schedule.coach_name || '',
                                    type: response.program_type,
                                    program: programName,
                                    price: schedule.price,
                                    day: schedule.day,
                                    startTime: schedule.start_time,
                                    endTime: schedule.end_time
                                })}'>
                                    <td>${schedule.day}</td>
                                    <td>${schedule.start_time}</td>
                                    <td>${schedule.end_time}</td>
                                    <td>${response.program_type === 'personal' ? schedule.duration_rate : schedule.capacity}</td>
                                    <td>₱${schedule.price}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary select-schedule">Select</button>
                                    </td>
                                </tr>
                            `).join('');
                            
                            tableBody.html(rows);
                        } else {
                            tableBody.html('<tr><td colspan="6" class="text-center">No schedules found</td></tr>');
                        }
                        scheduleModal.show();
                    },
                    error: function() {
                        $('#scheduleTableBody').html('<tr><td colspan="6" class="text-center">Failed to load schedules</td></tr>');
                        scheduleModal.show();
                    }
                });
            });

            // Handle rental service selection
            $('.rental-service-checkbox').change(function() {
                // Update rental summary
                let rentalHtml = '';
                $('.rental-service-checkbox:checked').each(function() {
                    const checkbox = $(this);
                    const name = checkbox.data('name');
                    const price = parseFloat(checkbox.data('price'));
                    const duration = checkbox.data('duration');
                    const durationType = checkbox.data('duration-type');
                    
                    rentalHtml += `
                        <div class="summary-row">
                            <p><strong>${name}</strong></p>
                            <p>Duration: ${duration} ${durationType}</p>
                            <p>Price: ₱${price.toFixed(2)}</p>
                        </div>
                    `;
                });
                $('#selectedRentals').html(rentalHtml);

                // Update totals
                updateTotalAmount();

                // Update review section if visible
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }
            });

            // Update programs summary
            function updateProgramsSummary() {
                let programsHtml = '';
                if (selectedPrograms.length > 0) {
                    selectedPrograms.forEach(program => {
                        programsHtml += `
                            <div class="summary-row">
                                <p><strong>${program.program}</strong> (${program.type})</p>
                                <p>Coach: ${program.coach}</p>
                                <p>Schedule: ${program.schedule}</p>
                                <p>Price: ₱${parseFloat(program.price).toFixed(2)}</p>
                            </div>
                        `;
                    });
                }
                $('#selectedPrograms').html(programsHtml);
            }

            // Update review information
            function updateReviewInformation() {
                // Get personal information values
                const firstName = $('#first_name').val();
                const middleName = $('#middle_name').val();
                const lastName = $('#last_name').val();
                const sex = $('input[name="sex"]:checked').val();
                const birthdate = $('#birthdate').val();
                const contact = $('#contact').val();

                // Build full name
                const nameParts = [firstName];
                if (middleName && middleName.trim() !== '') {
                    nameParts.push(middleName);
                }
                nameParts.push(lastName);
                const fullName = nameParts.join(' ');

                // Update personal information display
                $('#review-name').text(fullName || 'Not provided');
                $('#review-sex').text(sex || 'Not provided');
                $('#review-birthdate').text(birthdate || 'Not provided');
                $('#review-phone').text(contact || 'Not provided');
                
                // Update membership details
                const selectedPlan = $('#selectedPlan');
                const planName = selectedPlan.find('p:first').text().replace('Plan: ', '');
                const duration = selectedPlan.find('p:eq(1)').text().replace('Duration: ', '');
                const planPrice = parseFloat(selectedPlan.find('p:eq(2)').text().replace('Price: ₱', '')) || 0;
                const startDate = $('#membership_start_date').val();

                // Update plan details
                $('#review-plan').text(planName || 'Not selected');
                $('#review-duration').text(duration || 'Not selected');
                $('#review-price').text('₱' + planPrice.toFixed(2));
                $('#review-start-date').text(startDate || 'Not selected');
                $('#review-registration-fee').text('₱' + registrationFee.toFixed(2));

                // Update programs display
                let programsHtml = '';
                if (selectedPrograms.length > 0) {
                    selectedPrograms.forEach(program => {
                        programsHtml += `
                            <div class="program-item">
                                <div class="program-title">${program.program} (${program.type.charAt(0).toUpperCase() + program.type.slice(1)})</div>
                                <div class="program-details">
                                    Coach: ${program.coach}<br>
                                    Schedule: ${program.schedule}<br>
                                    Price: ₱${parseFloat(program.price).toFixed(2)}
                                </div>
                            </div>
                        `;
                    });
                } else {
                    programsHtml = '<div class="text-muted">No programs selected</div>';
                }
                $('#review-programs').html(programsHtml);

                // Update rental services review
                let rentalReviewHtml = '';
                $('.rental-service-checkbox:checked').each(function() {
                    const checkbox = $(this);
                    const name = checkbox.data('name');
                    const price = parseFloat(checkbox.data('price'));
                    const duration = checkbox.data('duration');
                    const durationType = checkbox.data('duration-type');
                    
                    rentalReviewHtml += `
                        <div class="program-item">
                            <div class="program-title">${name}</div>
                            <div class="program-details">
                                Duration: ${duration} ${durationType}<br>
                                Price: ₱${price.toFixed(2)}
                            </div>
                        </div>
                    `;
                });
                
                $('#rentals-review').html(rentalReviewHtml || '<p>No rental services selected</p>');

                // Update total amount
                updateTotalAmount();
            }

            // Phase navigation function
            function showPhase(phaseNumber) {
                // Hide all phases
                $('.phase').hide();
                
                // Show the selected phase
                $(`#phase${phaseNumber}`).show();
                
                // Update progress indicators
                $('.step').removeClass('active completed');
                $(`.step:lt(${phaseNumber})`).addClass('completed');
                $(`.step:eq(${phaseNumber - 1})`).addClass('active');
                
                // Show/hide navigation buttons
                $('#prevBtn, #nextBtn, #reviewBtn, #submitBtn').hide();
                
                if (phaseNumber > 1) {
                    $('#prevBtn').show();
                }
                
                if (phaseNumber < 3) {
                    $('#nextBtn').show();
                } else if (phaseNumber === 3) {
                    $('#nextBtn').hide();
                    $('#reviewBtn').show();
                } else if (phaseNumber === 4) {
                    $('#submitBtn').show();
                }
                
                // Update membership summary visibility
                if (phaseNumber === 4) {
                    $('.membership-summary').hide();
                } else {
                    $('.membership-summary').show();
                }
            }

            // Initialize form
            $(document).ready(function() {
                // Show first phase initially
                showPhase(1);
                
                // Handle next button
                $('#nextBtn').click(function() {
                    const currentPhase = parseInt($('.phase:visible').attr('id').replace('phase', ''));
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

            // Handle schedule selection using event delegation
            $(document).on('click', '.select-schedule', function(e) {
                e.preventDefault();
                const $row = $(this).closest('tr');
                const scheduleData = $row.data('schedule');

                if (!scheduleData) return;

                if (selectedPrograms.some(p => p.id === scheduleData.id)) {
                    alert('This schedule has already been selected.');
                    return;
                }

                // Store complete schedule information
                selectedPrograms.push({
                    id: scheduleData.id,
                    type: scheduleData.type,
                    coach_program_type_id: scheduleData.coach_program_type_id,
                    program: scheduleData.program,
                    coach: scheduleData.coach,
                    day: scheduleData.day,
                    startTime: scheduleData.startTime,
                    endTime: scheduleData.endTime,
                    price: scheduleData.price,
                    date: new Date().toISOString().split('T')[0]
                });

                // Update programs summary
                updateProgramsSummary();

                // Update totals
                updateTotalAmount();

                // Update review section if visible
                if ($('#phase4').is(':visible')) {
                    updateReviewInformation();
                }

                scheduleModal?.hide();
            });

            // Handle program removal using event delegation
            $(document).on('click', '.remove-program', function() {
                const index = $(this).data('index');
                if (index >= 0 && index < selectedPrograms.length) {
                    selectedPrograms.splice(index, 1);
                    
                    // Update programs summary
                    updateProgramsSummary();

                    // Update totals
                    updateTotalAmount();

                    // Update review section if visible
                    if ($('#phase4').is(':visible')) {
                        updateReviewInformation();
                    }
                }
            });

            // Handle form submission
            $('#memberForm').submit(function(e) {
                e.preventDefault();
                
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Processing...
                `);
                
                // Debug data collection
                const debugData = {
                    'personal_details': {
                        'first_name': $('#first_name').val(),
                        'middle_name': $('#middle_name').val() || 'NULL',
                        'last_name': $('#last_name').val(),
                        'sex': $('input[name="sex"]:checked').val(),
                        'birthdate': $('#birthdate').val(),
                        'phone_number': $('#contact').val()
                    },
                    'transactions': {
                        'status': 'pending'
                    },
                    'registration_records': {
                        'registration_id': 1,
                        'amount': parseFloat('<?= $memberRegistration->getRegistrationFee() ?>'),
                        'is_paid': 0
                    }
                };

                // Add membership plan data if selected
                const selectedPlan = $('input[name="membership_plan"]:checked');
                if (selectedPlan.length) {
                    const startDate = $('#membership_start_date').val() || new Date().toISOString().split('T')[0];
                    debugData['memberships'] = {
                        'membership_plan_id': selectedPlan.val(),
                        'start_date': startDate,
                        'end_date': 'auto-calculated based on plan duration',
                        'amount': parseFloat(selectedPlan.data('price')),
                        'status': 'pending',
                        'is_paid': 0
                    };
                }

                // Add program subscriptions data if selected
                if (selectedPrograms.length) {
                    debugData['program_subscriptions'] = selectedPrograms.map(program => ({
                        'coach_program_type_id': program.coach_program_type_id,
                        'status': 'pending'
                    }));

                    debugData['program_subscription_schedule'] = selectedPrograms.map(program => ({
                        'coach_group_schedule_id': program.type === 'group' ? program.id : 'NULL',
                        'coach_personal_schedule_id': program.type === 'personal' ? program.id : 'NULL',
                        'date': program.date,
                        'day': program.day,
                        'start_time': program.startTime,
                        'end_time': program.endTime,
                        'amount': parseFloat(program.price),
                        'is_paid': 0
                    }));
                }

                // Add rental services data if selected
                const selectedRentals = [];
                $('.rental-service-checkbox:checked').each(function() {
                    const startDate = new Date().toISOString().split('T')[0];
                    selectedRentals.push({
                        'rental_service_id': $(this).val(),
                        'start_date': startDate,
                        'end_date': 'auto-calculated based on rental duration',
                        'amount': parseFloat($(this).data('price')),
                        'status': 'pending',
                        'is_paid': 0
                    });
                });
                
                if (selectedRentals.length) {
                    debugData['rental_subscriptions'] = selectedRentals;
                }

                // Log the debug data in a formatted way
                console.log('=== FORM SUBMISSION DEBUG DATA ===');
                let counter = 1;
                Object.entries(debugData).forEach(([table, data]) => {
                    console.log(`\n${counter}. ${table} table:`);
                    if (Array.isArray(data)) {
                        data.forEach((item, index) => {
                            if (index > 0) console.log(''); // Add space between items
                            Object.entries(item).forEach(([column, value]) => {
                                console.log(`• ${column}: ${value}`);
                            });
                        });
                    } else {
                        Object.entries(data).forEach(([column, value]) => {
                            console.log(`• ${column}: ${value}`);
                        });
                    }
                    counter++;
                });
                console.log('\n=== END DEBUG DATA ===');

                // Collect form data for submission
                const formData = new FormData(this);
                formData.append('selected_programs', JSON.stringify(selectedPrograms));
                
                // Proceed with form submission
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
                            console.error('Parse error:', e);
                            console.error('Response:', response);
                            alert('An error occurred while processing your request. Please try again.');
                            submitBtn.prop('disabled', false).text('Complete Registration');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        alert('Failed to submit registration. Please check your connection and try again.');
                        submitBtn.prop('disabled', false).text('Complete Registration');
                    }
                });
            });

            // Handle next button click for phase 1
            $('#nextBtn1').click(function() {
                // Validate required fields
                const firstName = $('#first_name').val().trim();
                const lastName = $('#last_name').val().trim();
                const sex = $('input[name="sex"]:checked').val();
                const birthdate = $('#birthdate').val().trim();
                const contact = $('#contact').val().trim();

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
                }

                if (!contact) {
                    $('#contact').addClass('is-invalid');
                    missingFields.push('Contact Number');
                    isValid = false;
                }

                if (!isValid) {
                    alert('Please fill in the following required fields:\n' + missingFields.join('\n'));
                    return;
                }

                showPhase(2);
            });

            // Initialize with first phase
            showPhase(1);
        });
    </script>
</body>
</html>
