<?php
require_once "../../../config.php";
require_once 'functions/members.class.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/functions/debug.log');

error_log("Script started - " . date('Y-m-d H:i:s'));
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

session_start();

// Initialize Members class
$members = new Members();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    error_log("POST Data received: " . print_r($_POST, true));
    error_log("Files Data received: " . print_r($_FILES, true));

    $phase = $_POST['phase'] ?? null;
    $response = ['success' => false, 'message' => 'Invalid phase'];

    error_log("Processing phase: " . $phase);

    try {
        switch ($phase) {
            case '1':
                error_log("Processing Phase 1 - Personal Details");
                // Handle personal details
                $response = $members->savePhase1($_POST);
                if ($response['success']) {
                    $_SESSION['registration'] = [
                        'user_id' => $response['user_id'],
                        'username' => $response['username'],
                        'password' => $response['password']
                    ];
                    error_log("Phase 1 successful. User ID: " . $response['user_id']);
                }
                break;

            case '2':
                error_log("Processing Phase 2 - Membership Plan");
                // Handle membership plan
                if (!isset($_SESSION['registration']['user_id'])) {
                    throw new Exception("Personal details not saved. Please complete phase 1 first.");
                }
                $response = $members->savePhase2($_SESSION['registration']['user_id'], $_POST);
                if ($response['success']) {
                    $_SESSION['registration']['transaction_id'] = $response['transaction_id'];
                    error_log("Phase 2 successful. Transaction ID: " . $response['transaction_id']);
                }
                break;

            case '3':
                error_log("Processing Phase 3 - Programs and Services");
                // Handle programs and services
                if (!isset($_SESSION['registration']['transaction_id'])) {
                    throw new Exception("Membership plan not saved. Please complete phase 2 first.");
                }
                $response = $members->savePhase3(
                    $_SESSION['registration']['user_id'],
                    $_SESSION['registration']['transaction_id'],
                    $_POST
                );
                if ($response['success']) {
                    error_log("Phase 3 successful");
                }
                break;

            case '4':
                error_log("Processing Phase 4 - Finalization");
                // Finalize registration
                if (!isset($_SESSION['registration']['user_id']) || !isset($_SESSION['registration']['transaction_id'])) {
                    throw new Exception("Previous phases not completed. Please complete all phases in order.");
                }
                $response = $members->finalizeRegistration(
                    $_SESSION['registration']['user_id'],
                    $_SESSION['registration']['transaction_id']
                );
                if ($response['success']) {
                    error_log("Phase 4 successful - Registration completed");
                    // Clear registration session data after successful completion
                    unset($_SESSION['registration']);
                }
                break;

            case 'rollback':
                error_log("Processing Rollback Request");
                // Handle rollback request
                if (isset($_SESSION['registration']['user_id'])) {
                    $response = $members->rollbackRegistration($_SESSION['registration']['user_id']);
                    if ($response['success']) {
                        error_log("Rollback successful");
                        unset($_SESSION['registration']);
                    }
                }
                break;

            default:
                error_log("Invalid phase received: " . $phase);
                break;
        }
    } catch (Exception $e) {
        error_log("Error occurred: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Member - Gym Management System</title>
    <?php include '../../includes/header.php'; ?>
    <style>
        /* Card styles */
        .card {
            transition: all 0.3s ease!important;
        }
        .card.selected {
            border: 2px solid #0d6efd!important;    
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.2)!important;
        }

        /* Phase navigation styles */
        .form-phase {
            display: none;
        }
        .form-phase.active {
            display: block;
        }
        .phase-nav {
            margin-bottom: 2rem;
        }
        .phase-nav .nav-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .phase-nav .nav-link {
            padding: 1rem;
            border-bottom: 3px solid #dee2e6;
            color: #6c757d;
        }
        .phase-nav .nav-link.active {
            border-bottom-color: #0d6efd;
            color: #0d6efd;
        }
        .phase-nav .nav-link.completed {
            border-bottom-color: #198754;
            color: #198754;
        }
        .btn-nav {
            min-width: 100px;
        }

        /* Validation styles */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .border-danger {
            border: 1px solid #dc3545 !important;
        }
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">Add New Member</h3>
                
                <!-- Phase Navigation -->
                <ul class="nav nav-pills phase-nav">
                    <li class="nav-item">
                        <a class="nav-link active" data-phase="1">1. Personal Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="2">2. Membership Plan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="3">3. Programs & Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-phase="4">4. Payment Summary</a>
                    </li>
                </ul>

                <form method="POST" enctype="multipart/form-data" id="memberForm" onsubmit="return prepareFormSubmission()">
                    <!-- Hidden fields for credentials -->
                    <input type="hidden" name="username" id="usernameField">
                    <input type="hidden" name="password" id="passwordField">
                    <!-- Phase 1: Personal Details -->
                    <div id="phase1" class="form-phase active">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Personal Information</h5>
                                <!-- Personal Details Fields -->
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Sex</label>
                                        <select class="form-select" name="sex" required>
                                            <option value="">Select Sex</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Birthdate</label>
                                        <input type="date" class="form-control" name="birthdate" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone_number" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" class="form-control" name="photo" accept="image/*" id="photoInput">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <img id="photoPreview" src="#" alt="Profile Preview" style="max-width: 200px; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary btn-nav" onclick="nextPhase(1)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="form-phase">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Select Membership Plan</h5>
                                <div class="row">
                                    <?php foreach ($members->getMembershipPlans() as $plan): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($plan['plan_name']); ?></h6>
                                                <p class="card-text">
                                                    Type: <?php echo htmlspecialchars($plan['plan_type']); ?><br>
                                                    Price: ₱<?php echo number_format($plan['price'], 2); ?>
                                                </p>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="membership_plan" 
                                                           value="<?php echo $plan['id']; ?>"
                                                           data-price="<?php echo $plan['price']; ?>"
                                                           required>
                                                    <label class="form-check-label">
                                                        Select Plan
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary btn-nav me-2" onclick="previousPhase(2)">Previous</button>
                            <button type="button" class="btn btn-primary btn-nav" onclick="nextPhase(2)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 3: Programs & Services -->
                    <div id="phase3" class="form-phase">
                        <h4 class="mb-4">Programs & Services</h4>
                        
                        <!-- Programs Section -->
                        <div class="mb-4">
                            <h5>Available Programs</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                <?php foreach ($members->getPrograms() as $program): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $program['program_name']; ?></h5>
                                            <p class="card-text"><?php echo $program['description']; ?></p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Select Coach:</label>
                                                <select class="form-select coach-select" name="program_coach[<?php echo $program['id']; ?>]" 
                                                        data-program-id="<?php echo $program['id']; ?>">
                                                    <option value="">Choose a coach...</option>
                                                    <?php 
                                                    $coaches = $members->getProgramCoaches();
                                                    foreach ($coaches as $coach) {
                                                        if ($coach['program_id'] == $program['id']) {
                                                            echo "<option value='{$coach['coach_id']}' data-price='{$coach['price']}'>{$coach['coach_name']} - ₱{$coach['price']}</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <input type="hidden" class="program-input" name="programs[]" 
                                                   value="<?php echo $program['id']; ?>" 
                                                   data-program-id="<?php echo $program['id']; ?>" 
                                                   disabled>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Rental Services Section -->
                        <div class="mb-4">
                            <h5>Rental Services</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                <?php foreach ($members->getRentalServices() as $rental): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $rental['service_name']; ?></h5>
                                            <p class="card-text"><?php echo $rental['description']; ?></p>
                                            <div class="form-check">
                                                <input class="form-check-input rental-checkbox" type="checkbox" 
                                                       name="rentals[]" 
                                                       value="<?php echo $rental['id']; ?>"
                                                       data-price="<?php echo $rental['price']; ?>"
                                                       id="rental<?php echo $rental['id']; ?>">
                                                <label class="form-check-label" for="rental<?php echo $rental['id']; ?>">
                                                    ₱<?php echo $rental['price']; ?> per month
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary btn-nav me-2" onclick="previousPhase(3)">Previous</button>
                            <button type="button" class="btn btn-primary btn-nav" onclick="nextPhase(3)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 4: Payment Summary -->
                    <div id="phase4" class="form-phase">
                        <h4 class="mb-4">Payment Summary</h4>
                        
                        <!-- Account Information -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Generated Account Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Username:</div>
                                    <div class="col-md-8" id="generatedUsername"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 fw-bold">Password:</div>
                                    <div class="col-md-8" id="generatedPassword"></div>
                                </div>
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-info-circle"></i> Please save these credentials. They will be needed to log in.
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-8">Registration Fee:</div>
                                    <div class="col-md-4 text-end" id="registrationSubtotal">₱<?php echo number_format($members->getRegistrationFee(), 2); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-8">Membership Plan:</div>
                                    <div class="col-md-4 text-end" id="membershipSubtotal">₱0.00</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-8">Programs & Coaches:</div>
                                    <div class="col-md-4 text-end" id="programsSubtotal">₱0.00</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-8">Rental Services:</div>
                                    <div class="col-md-4 text-end" id="rentalsSubtotal">₱0.00</div>
                                </div>
                                <div class="row fw-bold">
                                    <div class="col-md-8">Total Amount:</div>
                                    <div class="col-md-4 text-end" id="totalAmount">₱0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary btn-nav me-2" onclick="previousPhase(4)">Previous</button>
                            <button type="submit" class="btn btn-success btn-nav">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    let currentUserId = <?php echo isset($_SESSION['registration']['user_id']) ? $_SESSION['registration']['user_id'] : 'null'; ?>;
    let currentTransactionId = <?php echo isset($_SESSION['registration']['transaction_id']) ? $_SESSION['registration']['transaction_id'] : 'null'; ?>;
    let registrationFee = <?php echo $members->getRegistrationFee(); ?>;

    // Get the base URL for AJAX calls
    const baseUrl = '/Gym_MembershipSE-XLB/admin/pages/members/add_member.php';

    async function savePhase(phase) {
        const form = document.getElementById('memberForm');
        const formData = new FormData(form);
        formData.append('phase', phase.toString());

        try {
            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                console.error('Response was not JSON:', text);
                throw new TypeError("Response was not JSON. Check console for details.");
            }

            const result = await response.json();
            
            if (result.success) {
                if (phase === 1) {
                    currentUserId = result.user_id;
                    // Display credentials
                    document.getElementById('generatedUsername').textContent = result.username;
                    document.getElementById('generatedPassword').textContent = result.password;
                } else if (phase === 2) {
                    currentTransactionId = result.transaction_id;
                }
                return true;
            } else {
                alert(result.message || 'Error saving data');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving data: ' + error.message);
            return false;
        }
    }

    async function rollbackRegistration() {
        if (!currentUserId) return true;

        try {
            const formData = new FormData();
            formData.append('phase', 'rollback');

            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.success) {
                currentUserId = null;
                currentTransactionId = null;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error during rollback:', error);
            return false;
        }
    }

    async function previousPhase(currentPhase) {
        if (await rollbackRegistration()) {
            document.querySelector(`#phase${currentPhase}`).classList.remove('active');
            document.querySelector(`#phase${currentPhase - 1}`).classList.add('active');
            updatePhaseNav(currentPhase - 1);
            window.scrollTo(0, 0);
        }
    }

    async function nextPhase(currentPhase) {
        if (validatePhase(currentPhase)) {
            if (await savePhase(currentPhase)) {
                document.querySelector(`#phase${currentPhase}`).classList.remove('active');
                document.querySelector(`#phase${currentPhase + 1}`).classList.add('active');
                updatePhaseNav(currentPhase + 1);
                window.scrollTo(0, 0);

                // Update total amount when entering payment summary
                if (currentPhase + 1 === 4) {
                    updateTotalAmount();
                }
            }
        }
    }

    // Handle form submission for the final phase
    document.getElementById('memberForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (await savePhase(4)) {
            alert('Registration completed successfully!');
            window.location.href = 'index.php'; // Redirect to members list
        }
    });

    function validatePhase(phase) {
        const currentPhase = document.querySelector(`#phase${phase}`);
        const inputs = currentPhase.querySelectorAll('input[required], select[required]');
        let isValid = true;
        let emptyFields = [];

        // Clear previous validation
        currentPhase.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            const feedback = el.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.remove();
            }
        });

        // Check each required field
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                emptyFields.push(input.previousElementSibling.textContent.trim());
                input.classList.add('is-invalid');
                
                // Add error message
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'This field is required';
                input.parentNode.insertBefore(feedback, input.nextSibling);
            }
        });

        // Special validation for phase 2 (membership plan)
        if (phase === 2) {
            const membershipSelected = currentPhase.querySelector('input[name="membership_plan"]:checked');
            if (!membershipSelected) {
                isValid = false;
                alert('Please select a membership plan');
            }
        }

        // Special validation for phase 3 (programs & coaches)
        if (phase === 3) {
            const selectedPrograms = currentPhase.querySelectorAll('.program-checkbox:checked');
            selectedPrograms.forEach(program => {
                const programId = program.value;
                const coachSelect = currentPhase.querySelector(`select[name="program_coach[${programId}]"]`);
                if (coachSelect && !coachSelect.value) {
                    isValid = false;
                    coachSelect.classList.add('is-invalid');
                    alert('Please select a coach for each selected program');
                }
            });
        }

        if (!isValid) {
            if (emptyFields.length > 0) {
                alert('Please fill in all required fields: ' + emptyFields.join(', '));
            }
            return false;
        }

        return true;
    }

    function updatePhaseNav(currentPhase) {
        document.querySelectorAll('.nav-link').forEach(link => {
            const phase = parseInt(link.dataset.phase);
            link.classList.remove('active', 'completed');
            if (phase < currentPhase) {
                link.classList.add('completed');
            } else if (phase === currentPhase) {
                link.classList.add('active');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Handle coach selection changes
        document.querySelectorAll('.coach-select').forEach(select => {
            select.addEventListener('change', function() {
                const programId = this.dataset.programId;
                const programInput = document.querySelector(`.program-input[data-program-id="${programId}"]`);
                const card = this.closest('.card');
                
                if (this.value) {
                    programInput.disabled = false;
                    card.classList.add('selected');
                } else {
                    programInput.disabled = true;
                    card.classList.remove('selected');
                }
                
                updateTotalAmount();
            });
        });

        // Remove invalid state when user selects a membership plan
        document.querySelectorAll('input[name="membership_plan"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="membership_plan"]').forEach(r => {
                    r.closest('.card').classList.remove('border-danger');
                });
                updateTotalAmount();
            });
        });

        // Add change event listener for rental checkboxes
        document.querySelectorAll('.rental-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateTotalAmount);
        });

        // Remove invalid state when user types or changes input
        document.querySelectorAll('input[required], select[required]').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.remove('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.remove();
                    }
                }
            });
        });

        // Handle photo preview
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const preview = document.getElementById('photoPreview');
            const file = e.target.files[0];
            
            if (file) {
                preview.style.display = 'block';
                preview.src = URL.createObjectURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Initial calculation
        updateTotalAmount();
    });

    function updateTotalAmount() {
        let total = registrationFee;
        let membershipSubtotal = 0;
        let programsSubtotal = 0;
        let rentalsSubtotal = 0;

        // Calculate membership plan cost
        const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
        if (selectedPlan) {
            membershipSubtotal = parseFloat(selectedPlan.dataset.price) || 0;
        }
        total += membershipSubtotal;

        // Calculate programs and coaches cost
        document.querySelectorAll('.coach-select').forEach(select => {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const coachPrice = parseFloat(selectedOption.dataset.price) || 0;
                programsSubtotal += coachPrice;
            }
        });
        total += programsSubtotal;

        // Calculate rentals cost
        document.querySelectorAll('.rental-checkbox:checked').forEach(checkbox => {
            const rentalPrice = parseFloat(checkbox.dataset.price) || 0;
            rentalsSubtotal += rentalPrice;
        });
        total += rentalsSubtotal;

        // Update display
        document.getElementById('registrationSubtotal').textContent = '₱' + registrationFee.toFixed(2);
        document.getElementById('membershipSubtotal').textContent = '₱' + membershipSubtotal.toFixed(2);
        document.getElementById('programsSubtotal').textContent = '₱' + programsSubtotal.toFixed(2);
        document.getElementById('rentalsSubtotal').textContent = '₱' + rentalsSubtotal.toFixed(2);
        document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
    }
    </script>
</body>
</html>
