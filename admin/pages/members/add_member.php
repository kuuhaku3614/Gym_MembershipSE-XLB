<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once "../../../config.php";
require_once 'functions/member_registration.class.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            
            $schedules = $memberRegistration->getCoachGroupSchedule($coachProgramTypeId);
            error_log("Retrieved schedules: " . print_r($schedules, true)); // Debug log
            
            if (isset($schedules['error'])) {
                echo json_encode(['success' => false, 'error' => $schedules['error']]);
            } else if (isset($schedules['message'])) {
                echo json_encode(['success' => true, 'data' => [], 'message' => $schedules['message']]);
            } else {
                echo json_encode(['success' => true, 'data' => $schedules]);
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
$membershipPlans = $memberRegistration->getMembershipPlans();
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
        '<div class="card program-card mb-3" data-program-type="%s">
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
    <?php include '../../includes/header.php'; ?>
    <style>
        .nav-pills .nav-link {
            cursor: pointer;
            position: relative;
            padding-right: 2rem;
        }
        
        .nav-pills .nav-link.completed::after {
            content: '✓';
            position: absolute;
            right: 0.75rem;
            color: #198754;
        }
        
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        
        .nav-pills .nav-link.completed {
            background-color: #e9ecef;
            color: #198754;
        }
        
        .form-phase {
            display: none;
        }
        
        #phase1 {
            display: block;
        }
        
        .membership-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .membership-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .membership-option.selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px #0d6efd;
        }
    </style>
</head>
<body>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Add New Member</h3>
                
                <!-- Phase Navigation -->
                <ul class="nav nav-pills nav-justified mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-phase="1">Personal Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="2">Membership Plan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="3">Select Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="4">Review & Submit</a>
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
                                        <input type="tel" class="form-control" name="phone">
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
                        <h4 class="mb-4">Membership Plan</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Select Membership Plan</h5>
                                <div class="row">
                                    <?php 
                                    foreach ($membershipPlans as $plan): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card membership-option">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($plan['plan_name']); ?></h6>
                                                <p class="card-text"><?php echo htmlspecialchars($plan['description']); ?></p>
                                                <p class="card-text">
                                                    Type: <?php echo htmlspecialchars($plan['plan_type']); ?><br>
                                                    Price: ₱<?php echo number_format($plan['price'], 2); ?><br>
                                                    Duration: <?php echo $plan['duration'] . ' ' . $plan['duration_type']; ?>
                                                </p>
                                                <div class="form-check">
                                                    <input class="form-check-input membership-plan-radio" type="radio" 
                                                           name="membership_plan" 
                                                           value="<?php echo $plan['id']; ?>"
                                                           data-price="<?php echo $plan['price']; ?>"
                                                           data-duration="<?php echo $plan['duration']; ?>"
                                                           data-duration-type="<?php echo $plan['duration_type']; ?>"
                                                           data-name="<?php echo htmlspecialchars($plan['plan_name']); ?>">
                                                    <label class="form-check-label">Select Plan</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo date('Y-m-d'); ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary nav-button" id="prevBtn2">Previous</button>
                            <button type="button" class="btn btn-primary nav-button" id="nextBtn2">Next</button>
                            <button type="submit" class="btn btn-success nav-button" id="submitBtn2" style="display: none;">Submit Registration</button>
                        </div>
                    </div>

                    <!-- Phase 3: Programs & Services -->
                    <div id="phase3" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Select Programs</h4>
                        
                        <!-- Programs Section -->
                        <div class="mb-4">
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
                            <button type="button" class="btn btn-primary nav-button" id="nextBtn3">Next</button>
                            <button type="submit" class="btn btn-success nav-button" id="submitBtn3" style="display: none;">Submit Registration</button>
                        </div>
                    </div>

                    <!-- Phase 4: Payment Details -->
                    <div id="phase4" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Review & Submit</h4>
                        
                        <!-- Payment Summary -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Payment Summary</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Registration Fee:</td>
                                                <td class="text-end" id="registration_fee">₱<?php echo number_format($registrationFee, 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Membership Plan:</td>
                                                <td class="text-end" id="membership_plan_price">₱0.00</td>
                                            </tr>
                                            <tr>
                                                <td>Rental Services:</td>
                                                <td class="text-end" id="rentals_subtotal">₱0.00</td>
                                            </tr>
                                            <tr class="fw-bold">
                                                <td>Total Amount:</td>
                                                <td class="text-end" id="total_amount">₱0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary nav-button" id="prevBtn4">Previous</button>
                            <button type="submit" class="btn btn-success nav-button" id="submitBtn4">Submit Registration</button>
                            <button type="button" class="btn btn-primary nav-button" id="nextBtn4" style="display: none;">Next</button>
                        </div>
                    </div>

                    <!-- Hidden inputs for selected items -->
                    <input type="hidden" name="action" value="add_member">
                </form>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Coach Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="scheduleAlert" class="alert" style="display: none;"></div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Make navigation pills clickable
            $('.nav-pills .nav-link').click(function() {
                const targetPhase = parseInt($(this).data('phase'));
                const currentPhase = parseInt($('.form-phase:visible').attr('id').replace('phase', ''));
                
                // Going forward requires validation
                if (targetPhase > currentPhase) {
                    for (let i = currentPhase; i < targetPhase; i++) {
                        const validationFunction = window[`validatePhase${i}`];
                        if (validationFunction && !validationFunction()) {
                            return false;
                        }
                    }
                }
                
                showPhase(targetPhase);
            });
            
            // Make membership plan cards clickable
            $('.membership-option').click(function() {
                const radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true).trigger('change');
                
                // Update visual feedback
                $('.membership-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Phase validation functions
            function validatePhase1() {
                const firstName = $('#first_name').val().trim();
                const lastName = $('#last_name').val().trim();
                const birthdate = $('#birthdate').val();
                const gender = $('input[name="sex"]:checked').val();
                const phone = $('#phone').val().trim();
                
                if (!firstName || !lastName || !birthdate || !gender || !phone) {
                    alert('Please fill in all required fields');
                    return false;
                }
                
                return true;
            }
            
            function validatePhase2() {
                const membershipPlan = $('input[name="membership_plan"]:checked').val();
                if (!membershipPlan) {
                    alert('Please select a membership plan');
                    return false;
                }
                return true;
            }
            
            function validatePhase3() {
                // At least one program should have a coach selected
                let hasCoach = false;
                $('.program-coach').each(function() {
                    if ($(this).val()) {
                        hasCoach = true;
                        return false; // Break the loop
                    }
                });
                
                if (!hasCoach) {
                    alert('Please select at least one coach for a program');
                    return false;
                }
                return true;
            }
            
            // Phase Navigation Functions
            function showPhase(phaseNumber) {
                // Hide all phases and buttons first
                $('.form-phase').hide();
                $('.nav-button').hide();
                
                // Show the target phase
                $(`#phase${phaseNumber}`).show();
                
                // Update navigation pills
                $('.nav-link').removeClass('active completed');
                $(`.nav-link[data-phase="${phaseNumber}"]`).addClass('active');
                for (let i = 1; i < phaseNumber; i++) {
                    $(`.nav-link[data-phase="${i}"]`).addClass('completed');
                }
                
                // Show/hide navigation buttons based on phase
                if (phaseNumber > 1) {
                    $(`#prevBtn${phaseNumber}`).show();
                }
                
                if (phaseNumber < 4) {
                    $(`#nextBtn${phaseNumber}`).show();
                    $(`#submitBtn${phaseNumber}`).hide();
                } else {
                    $(`#nextBtn${phaseNumber}`).hide();
                    $(`#submitBtn${phaseNumber}`).show();
                }
                
                return true;
            }

            // Navigation event handlers
            $('[id^="nextBtn"]').click(function() {
                const currentPhase = parseInt(this.id.replace('nextBtn', ''));
                const validationFunction = window[`validatePhase${currentPhase}`];
                
                if (!validationFunction || validationFunction()) {
                    showPhase(currentPhase + 1);
                }
            });

            $('[id^="prevBtn"]').click(function() {
                const currentPhase = parseInt(this.id.replace('prevBtn', ''));
                showPhase(currentPhase - 1);
            });

            // Simple function to update total amount
            function updateTotalAmount() {
                var total = parseFloat($('#registration_fee').text().replace('₱', '').replace(',', '')) || 0;
                
                // Add membership plan price
                var planPrice = parseFloat($('input[name="membership_plan"]:checked').data('price')) || 0;
                $('#membership_plan_price').text('₱' + planPrice.toFixed(2));
                total += planPrice;
                
                // Add rental prices
                var rentalTotal = 0;
                $('.rental-checkbox:checked').each(function() {
                    rentalTotal += parseFloat($(this).data('price')) || 0;
                });
                $('#rentals_subtotal').text('₱' + rentalTotal.toFixed(2));
                total += rentalTotal;
                
                // Update total display
                $('#total_amount').text('₱' + total.toFixed(2));
            }
            
            // Handle membership plan selection
            $('input[name="membership_plan"]').change(updateTotalAmount);
            
            // Handle rental checkbox changes
            $('.rental-checkbox').change(updateTotalAmount);
            
            // Initialize total and show first phase
            updateTotalAmount();
            showPhase(1);
            
            // Form submission
            $('#memberForm').submit(function(e) {
                e.preventDefault();
                
                // Validate all phases before submitting
                for (let i = 1; i <= 3; i++) {
                    const validationFunction = window[`validatePhase${i}`];
                    if (validationFunction && !validationFunction()) {
                        showPhase(i);
                        return;
                    }
                }
                
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            alert('Member added successfully!');
                            window.location.href = 'members.php';
                        } else {
                            alert(response.message || 'Failed to add member');
                        }
                    },
                    error: function() {
                        alert('Failed to add member. Please try again.');
                    }
                });
            });

            // Add change event handler to coach selects
            $('.program-coach').change(function() {
                const coachProgramTypeId = $(this).val();
                if (!coachProgramTypeId) return;

                // Show loading state
                $('#scheduleTableBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
                $('#scheduleAlert').hide();
                $('#scheduleModal').modal('show');

                // Fetch schedule data using absolute path
                $.ajax({
                    url: '/Gym_MembershipSE-XLB/admin/pages/members/add_member.php',
                    method: 'GET',
                    data: {
                        action: 'get_schedule',
                        coach_program_type_id: coachProgramTypeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                let html = '';
                                response.data.forEach(schedule => {
                                    html += `
                                        <tr>
                                            <td>${schedule.day}</td>
                                            <td>${schedule.start_time}</td>
                                            <td>${schedule.end_time}</td>
                                            <td>₱${schedule.price}</td>
                                        </tr>
                                    `;
                                });
                                $('#scheduleTableBody').html(html);
                            } else {
                                // Show message if no schedules
                                $('#scheduleAlert')
                                    .removeClass('alert-danger')
                                    .addClass('alert-info')
                                    .html(response.message || 'No schedules found for this coach')
                                    .show();
                                $('#scheduleTableBody').html('');
                            }
                        } else {
                            // Show error message
                            $('#scheduleAlert')
                                .removeClass('alert-info')
                                .addClass('alert-danger')
                                .html(response.error || 'Failed to fetch schedule')
                                .show();
                            $('#scheduleTableBody').html('');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        $('#scheduleAlert')
                            .removeClass('alert-info')
                            .addClass('alert-danger')
                            .html('Failed to fetch schedule. Please try again.')
                            .show();
                        $('#scheduleTableBody').html('');
                        console.error('Error fetching schedule:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>
