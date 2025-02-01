<?php
date_default_timezone_set('Asia/Manila');
require_once "../../../config.php";
require_once 'functions/members.class.php';

$members = new Members();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Disable error output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'generate_credentials') {
            $firstName = $_POST['first_name'];
            $result = $members->generateCredentials($firstName);
            echo json_encode($result);
            exit;
        }
        
        $result = $members->handleRequest($_POST);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Re-enable error reporting for the rest of the script
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

                <form id="memberForm" method="POST" enctype="multipart/form-data">
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
                                        <label class="form-label">Birthdate</label>
                                        <input type="date" class="form-control" name="birthdate" 
                                               max="<?php echo date('Y-m-d', strtotime('-1 day')); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" 
                                               pattern="[0-9]{11}" 
                                               title="Please enter a valid 11-digit phone number">
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

                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" onclick="nextPhase(1)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 2: Membership Plan -->
                    <div id="phase2" class="form-phase">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Select Membership Plan</h5>
                                <div id="membershipError" class="alert alert-danger mb-3" style="display: none;">
                                    Please select a membership plan
                                </div>
                                <div class="row">
                                    <?php foreach ($members->getMembershipPlans() as $plan): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card membership-option">
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
                                                           data-price="<?php echo $plan['price']; ?>">
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
                                               min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" onclick="prevPhase(2)">Previous</button>
                            <button type="button" class="btn btn-primary" onclick="nextPhase(2)">Next</button>
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

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" onclick="prevPhase(3)">Previous</button>
                            <button type="button" class="btn btn-primary" onclick="nextPhase(3)">Next</button>
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

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" onclick="prevPhase(4)">Previous</button>
                            <button type="submit" class="btn btn-success">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Global variables for registration
    let registrationData = {};
    let registrationFee = <?php echo $members->getRegistrationFee(); ?>;
    const baseUrl = '/Gym_MembershipSE-XLB/admin/pages/members/add_member.php';

    async function submitPhase(phase) {
        const form = document.getElementById('memberForm');
        const currentFormData = new FormData(form);
        console.log('Submitting phase:', phase);

        // For phase 1, use server-side validation without saving
        if (phase === 1) {
            currentFormData.append('phase', phase);
            currentFormData.append('validate_only', 'true');
            currentFormData.append('action', 'validate_phase1');

            try {
                const response = await fetch(baseUrl, {
                    method: 'POST',
                    body: currentFormData
                });
                const result = await response.json();
                console.log('Phase 1 validation result:', result);

                if (!result.success) {
                    if (result.errors) {
                        showValidationErrors(result.errors);
                        return false;
                    }
                    throw new Error(result.message || 'An error occurred');
                }

                // Store phase 1 data
                registrationData.phase1 = {};
                currentFormData.forEach((value, key) => {
                    registrationData.phase1[key] = value;
                });
                return true;
            } catch (error) {
                console.error('Error in phase 1 validation:', error);
                document.getElementById('phase1').insertAdjacentHTML('afterbegin', `<div class="alert alert-danger">${error.message}</div>`);
                return false;
            }
        } else if (phase === 2) {
            const errors = {};
            
            // Validate membership plan selection
            const membershipSelected = document.querySelector('input[name="membership_plan"]:checked');
            if (!membershipSelected) {
                errors.membership_plan = 'Please select a membership plan';
            }

            // Validate start date
            const startDate = document.querySelector('input[name="start_date"]');
            if (!startDate.value) {
                errors.start_date = 'Please select a start date';
            } else {
                const selectedDate = new Date(startDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Reset time part for proper date comparison
                
                if (selectedDate < today) {
                    errors.start_date = 'Start date cannot be in the past';
                }
            }

            // Show errors if any
            if (Object.keys(errors).length > 0) {
                showValidationErrors(errors);
                return false;
            }

            // Clear any previous error messages
            document.getElementById('membershipError').style.display = 'none';
            
            // Store phase 2 data
            registrationData.phase2 = {
                membership_plan: membershipSelected.value,
                start_date: startDate.value
            };
            return true;
        }
        return true;
    }

    function showValidationErrors(errors) {
        // Clear previous validation state
        const inputs = document.querySelectorAll('.is-invalid');
        inputs.forEach(input => {
            input.classList.remove('is-invalid');
        });

        // Clear all existing error messages
        document.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.remove();
        });

        // Show validation errors
        Object.entries(errors).forEach(([field, message]) => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                
                // Special handling for radio buttons
                if (input.type === 'radio') {
                    const container = input.closest('.membership-option') || input.closest('.d-flex');
                    if (container) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = message;
                        container.appendChild(feedback);
                    }
                } else {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = message;
                    input.parentNode.insertBefore(feedback, input.nextSibling);
                }
            }
        });
    }

    async function nextPhase(currentPhase) {
        try {
            if (await submitPhase(currentPhase)) {
                // Generate credentials when moving to Phase 4
                if (currentPhase === 3) {
                    const firstName = document.querySelector('input[name="first_name"]').value;
                    const formData = new FormData();
                    formData.append('action', 'generate_credentials');
                    formData.append('first_name', firstName);

                    const response = await fetch(baseUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Update the credentials display
                        document.getElementById('generatedUsername').textContent = result.username;
                        document.getElementById('generatedPassword').textContent = result.password;
                        
                        // Store in hidden fields
                        document.getElementById('usernameField').value = result.username;
                        document.getElementById('passwordField').value = result.password;
                    } else {
                        console.error('Error generating credentials:', result.message);
                    }
                }

                // Hide current phase and show next phase
                document.querySelector(`#phase${currentPhase}`).classList.remove('active');
                document.querySelector(`#phase${currentPhase + 1}`).classList.add('active');
                
                // Update navigation
                document.querySelector(`.nav-link[data-phase="${currentPhase}"]`).classList.remove('active');
                document.querySelector(`.nav-link[data-phase="${currentPhase}"]`).classList.add('completed');
                document.querySelector(`.nav-link[data-phase="${currentPhase + 1}"]`).classList.add('active');
                
                // Update progress
                updateProgress(currentPhase + 1);

                // Update total amount when entering payment summary
                if (currentPhase === 3) {
                    updateTotalAmount();
                }
                
                // Scroll to top
                window.scrollTo(0, 0);
            }
        } catch (error) {
            console.error('Error in nextPhase:', error);
        }
    }

    function updateProgress(phase) {
        // Update navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            const phaseNumber = parseInt(link.dataset.phase);
            link.classList.remove('active', 'completed');
            if (phaseNumber < phase) {
                link.classList.add('completed');
            } else if (phaseNumber === phase) {
                link.classList.add('active');
            }
        });
    }

    async function prevPhase(currentPhase) {
        // Hide current phase and show previous phase
        document.querySelector(`#phase${currentPhase}`).classList.remove('active');
        document.querySelector(`#phase${currentPhase - 1}`).classList.add('active');
        updateProgress(currentPhase - 1);
    }

    // Function to update total amount
    function updateTotalAmount() {
        // Get membership plan price
        const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
        const membershipPrice = selectedPlan ? parseFloat(selectedPlan.dataset.price) : 0;
        document.getElementById('membershipSubtotal').textContent = `₱${membershipPrice.toFixed(2)}`;

        // Calculate programs total
        let programsTotal = 0;
        document.querySelectorAll('.coach-select').forEach(select => {
            if (select.value) {
                const price = parseFloat(select.options[select.selectedIndex].dataset.price || 0);
                programsTotal += price;
            }
        });
        document.getElementById('programsSubtotal').textContent = `₱${programsTotal.toFixed(2)}`;

        // Calculate rentals total
        let rentalsTotal = 0;
        document.querySelectorAll('input[name="rentals[]"]:checked').forEach(rental => {
            rentalsTotal += parseFloat(rental.dataset.price || 0);
        });
        document.getElementById('rentalsSubtotal').textContent = `₱${rentalsTotal.toFixed(2)}`;

        // Calculate total
        const total = registrationFee + membershipPrice + programsTotal + rentalsTotal;
        document.getElementById('totalAmount').textContent = `₱${total.toFixed(2)}`;
    }

    // Handle form submission for the final phase
    document.getElementById('memberForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous messages
        const alertMessages = document.querySelectorAll('#phase4 .alert');
        alertMessages.forEach(alert => alert.remove());
        
        try {
            const formData = new FormData(this);
            formData.append('action', 'add_member');

            // Add membership plan data
            const membershipPlan = document.querySelector('input[name="membership_plan"]:checked');
            const startDate = document.querySelector('input[name="start_date"]');

            if (!membershipPlan || !startDate.value) {
                document.getElementById('phase4').insertAdjacentHTML('afterbegin', 
                    '<div class="alert alert-danger">Please complete all previous phases before submitting.</div>'
                );
                return;
            }

            formData.append('membership_plan', membershipPlan.value);
            formData.append('start_date', startDate.value);

            // Add programs and coaches data
            const { programs, programCoaches } = getSelectedProgramsAndCoaches();
            if (programs.length > 0) {
                formData.append('programs', JSON.stringify(programs));
                formData.append('program_coach', JSON.stringify(programCoaches));
            }

            const response = await fetch(baseUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            console.log('Submission result:', result);

            if (!result.success) {
                document.getElementById('phase4').insertAdjacentHTML('afterbegin', 
                    `<div class="alert alert-danger">${result.message}</div>`
                );
                return;
            }

            // Show success message with credentials
            document.getElementById('phase4').insertAdjacentHTML('afterbegin', 
                `<div class="alert alert-success">
                    Registration completed successfully!<br>
                    Please save these credentials:<br>
                    Username: ${document.getElementById('usernameField').value}<br>
                    Password: ${document.getElementById('passwordField').value}<br>
                    Redirecting...
                </div>`
            );
            
            setTimeout(() => {
                window.location.href = 'members_new';
            }, 3000);

        } catch (error) {
            console.error('Error submitting form:', error);
            document.getElementById('phase4').insertAdjacentHTML('afterbegin', 
                `<div class="alert alert-danger">An error occurred: ${error.message}</div>`
            );
        }
    });

    // Function to collect selected programs and coaches
    function getSelectedProgramsAndCoaches() {
        const programs = [];
        const programCoaches = {};
        
        document.querySelectorAll('.coach-select').forEach(select => {
            if (select.value) {
                const programId = select.dataset.programId;
                programs.push(programId);
                programCoaches[programId] = select.value;
            }
        });
        
        return { programs, programCoaches };
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Handle coach selection changes
        document.querySelectorAll('.coach-select').forEach(select => {
            select.addEventListener('change', function() {
                const programId = this.dataset.programId;
                const programInput = document.querySelector(`.program-input[data-program-id="${programId}"]`);
                
                if (this.value) {
                    // Enable program input when coach is selected
                    programInput.disabled = false;
                } else {
                    // Disable program input when no coach is selected
                    programInput.disabled = true;
                }
                
                // Update total amount
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
        document.querySelectorAll('input, select').forEach(input => {
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

    </script>
</body>
</html>
