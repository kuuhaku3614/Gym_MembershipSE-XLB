<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once "../../../config.php";
require_once 'functions/members.class.php';

$members = new Members();

// Get base URL from config or construct it
$baseUrl = isset($config['base_url']) ? $config['base_url'] : (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
    "://" . $_SERVER['HTTP_HOST'] . 
    str_replace('/admin/pages/members/add_member.php', '', $_SERVER['SCRIPT_NAME'])
);

// Generate credentials when moving to Phase 4
if (isset($_POST['generate_credentials'])) {
    header('Content-Type: application/json');
    
    try {
        if (empty($_POST['first_name'])) {
            throw new Exception('First name is required');
        }
        
        $credentials = $members->generateCredentials($_POST['first_name']);
        echo json_encode([
            'success' => true,
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submission to save all data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    header('Content-Type: application/json');
    try {
        // Log raw data for debugging
        error_log("Raw POST data: " . print_r($_POST, true));
        error_log("Raw FILES data: " . print_r($_FILES, true));
        
        // Prepare the data
        $data = $_POST;
        
        // Handle file upload
        if (isset($_FILES['photo'])) {
            $data['photo'] = $_FILES['photo'];
        }
        
        // Add member and get result
        $result = $members->addMember($data);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Member registered successfully!'
            ]);
        } else {
            throw new Exception($result['message'] ?? 'Failed to register member');
        }
    } catch (Exception $e) {
        error_log("Error adding member: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Get membership duration and filtered items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_membership_duration') {
        try {
            $planId = $_POST['plan_id'];
            $membershipPlan = $members->getMembershipPlanDuration($planId);
            if ($membershipPlan) {
                echo json_encode([
                    'success' => true,
                    'duration' => $membershipPlan['duration'],
                    'duration_type_id' => $membershipPlan['duration_type_id']
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Membership plan not found'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error getting membership plan: ' . $e->getMessage()
            ]);
        }
        exit;
    } elseif ($_POST['action'] === 'get_filtered_items') {
        try {
            $duration = $_POST['duration'];
            $durationTypeId = $_POST['duration_type_id'];
            
            $programs = $members->getProgramsByDuration($duration, $durationTypeId);
            $rentals = $members->getRentalServicesByDuration($duration, $durationTypeId);
            
            echo json_encode([
                'success' => true,
                'programs_html' => renderPrograms($programs),
                'rentals_html' => renderRentalServices($rentals)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error getting filtered items: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

function renderPrograms($programs) {
    global $members;
    $programCoaches = $members->getProgramCoaches();
    
    // Organize coaches by program
    $coachesByProgram = [];
    foreach ($programCoaches as $coach) {
        if (!isset($coachesByProgram[$coach['program_id']])) {
            $coachesByProgram[$coach['program_id']] = [];
        }
        $coachesByProgram[$coach['program_id']][] = $coach;
    }

    $html = '';
    if (empty($programs)) {
        $html .= '<div class="col-12"><div class="alert alert-info">No programs available.</div></div>';
    } else {
        foreach ($programs as $program) {
            $html .= '<div class="col">';
            $html .= '<div class="card h-100">';
            $html .= '<div class="card-body">';
            $html .= '<h6 class="card-title">' . htmlspecialchars($program['program_name']) . '</h6>';
            $html .= '<p class="card-text">' . htmlspecialchars($program['description']) . '</p>';
            $html .= '<p class="card-text">';
            $html .= '<small class="text-muted">';
            $html .= 'Duration: ' . htmlspecialchars($program['duration'] . ' ' . $program['duration_type']);
            $html .= '</small>';
            $html .= '</p>';
            
            if (isset($coachesByProgram[$program['id']])) {
                $html .= '<div class="form-group">';
                $html .= '<label class="form-label">Select Coach:</label>';
                $html .= '<select class="form-select program-coach" ';
                $html .= 'name="program_coaches[' . $program['id'] . ']" ';
                $html .= 'data-program-id="' . $program['id'] . '">';
                $html .= '<option value="">Choose a coach</option>';
                
                foreach ($coachesByProgram[$program['id']] as $coach) {
                    $html .= '<option value="' . $coach['coach_id'] . '" ';
                    $html .= 'data-price="' . $coach['price'] . '">';
                    $html .= htmlspecialchars($coach['coach_name']) . ' - ₱';
                    $html .= number_format($coach['price'], 2);
                    $html .= '</option>';
                }
                
                $html .= '</select>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    return $html;
}

function renderRentalServices($rentals) {
    $html = '';
    if (empty($rentals)) {
        $html .= '<div class="col-12"><div class="alert alert-info">No rental services available.</div></div>';
    } else {
        foreach ($rentals as $rental) {
            $html .= '<div class="col">';
            $html .= '<div class="card h-100">';
            $html .= '<div class="card-body">';
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input rental-checkbox" type="checkbox" ';
            $html .= 'name="rental_services[]" ';
            $html .= 'value="' . $rental['id'] . '" ';
            $html .= 'data-price="' . $rental['price'] . '" ';
            $html .= 'data-duration="' . $rental['duration'] . '" ';
            $html .= 'data-duration-type="' . $rental['duration_type'] . '">';
            $html .= '<label class="form-check-label">Select Service</label>';
            $html .= '</div>';
            $html .= '<h6 class="card-title">' . htmlspecialchars($rental['rental_name']) . '</h6>';
            $html .= '<p class="card-text">' . htmlspecialchars($rental['description']) . '</p>';
            $html .= '<p class="card-text">';
            $html .= '<small class="text-muted">';
            $html .= 'Duration: ' . htmlspecialchars($rental['duration'] . ' ' . $rental['duration_type']);
            $html .= '</small>';
            $html .= '</p>';
            $html .= '<p class="card-text">';
            $html .= '<small class="text-muted">';
            $html .= 'Price: ₱' . number_format($rental['price'], 2);
            $html .= '</small>';
            $html .= '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    return $html;
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
                        <a class="nav-link" data-phase="4">4. Payment Details</a>
                    </li>
                </ul>

                <form id="memberForm" method="POST" enctype="multipart/form-data">
                    <!-- Hidden fields for credentials -->
                    <input type="hidden" name="username" id="usernameField">
                    <input type="hidden" name="password" id="passwordField">
                    <input type="hidden" name="form_submitted" value="true">
                    <!-- Phase 1: Personal Details -->
                    <div id="phase1" class="form-phase active">
                        <h4 class="mb-4">Personal Information</h4>
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
                    <div id="phase2" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Membership Plan</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Select Membership Plan</h5>
                                <div id="membershipError" class="alert alert-danger mb-3" style="display: none;">
                                    Please select a membership plan
                                </div>
                                <div class="row">
                                    <?php 
                                    $membershipPlans = $members->getMembershipPlans();
                                    error_log("Membership plans: " . print_r($membershipPlans, true));
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
                                                    <input class="form-check-input" type="radio" 
                                                           name="membership_plan" 
                                                           value="<?php echo $plan['id']; ?>"
                                                           data-price="<?php echo $plan['price']; ?>"
                                                           id="membership_plan_<?php echo $plan['id']; ?>">
                                                    <label class="form-check-label" for="membership_plan_<?php echo $plan['id']; ?>">
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
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo date('Y-m-d'); ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               required>
                                        <div class="invalid-feedback">Start date is required</div>
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
                    <div id="phase3" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Programs & Services</h4>
                        
                        <!-- Programs Section -->
                        <div class="mb-4">
                            <h5>Available Programs</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4 programs-container">
                                <?php 
                                $programs = $members->getPrograms();
                                $programCoaches = $members->getProgramCoaches();
                                
                                // Organize coaches by program
                                $coachesByProgram = [];
                                foreach ($programCoaches as $coach) {
                                    if (!isset($coachesByProgram[$coach['program_id']])) {
                                        $coachesByProgram[$coach['program_id']] = [];
                                    }
                                    $coachesByProgram[$coach['program_id']][] = $coach;
                                }
                                
                                if (empty($programs)) {
                                    echo '<div class="col-12"><div class="alert alert-info">No programs available.</div></div>';
                                } else {
                                    foreach ($programs as $program): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($program['program_name']); ?></h6>
                                                <p class="card-text"><?php echo htmlspecialchars($program['description']); ?></p>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        Duration: <?php echo htmlspecialchars($program['duration'] . ' ' . $program['duration_type']); ?>
                                                    </small>
                                                </p>
                                                <?php if (isset($coachesByProgram[$program['id']])): ?>
                                                <div class="form-group">
                                                    <label class="form-label">Select Coach:</label>
                                                    <select class="form-select program-coach" 
                                                            name="program_coaches[<?php echo $program['id']; ?>]"
                                                            data-program-id="<?php echo $program['id']; ?>">
                                                        <option value="">Choose a coach</option>
                                                        <?php foreach ($coachesByProgram[$program['id']] as $coach): ?>
                                                        <option value="<?php echo $coach['coach_id']; ?>"
                                                                data-price="<?php echo $coach['price']; ?>">
                                                            <?php echo htmlspecialchars($coach['coach_name']); ?> - ₱<?php echo number_format($coach['price'], 2); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach;
                                } ?>
                            </div>
                        </div>
                        
                        <!-- Rental Services Section -->
                        <div class="mb-4">
                            <h5>Rental Services</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4 rentals-container">
                                <?php 
                                $rentals = $members->getRentalServices();
                                if (empty($rentals)) {
                                    echo '<div class="col-12"><div class="alert alert-info">No rental services available.</div></div>';
                                } else {
                                    foreach ($rentals as $rental): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($rental['rental_name']); ?></h6>
                                                <p class="card-text"><?php echo htmlspecialchars($rental['description']); ?></p>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        Duration: <?php echo htmlspecialchars($rental['duration'] . ' ' . $rental['duration_type']); ?>
                                                    </small>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        Price: ₱<?php echo number_format($rental['price'], 2); ?>
                                                    </small>
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

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" onclick="prevPhase(3)">Previous</button>
                            <button type="button" class="btn btn-primary" onclick="nextPhase(3)">Next</button>
                        </div>
                    </div>

                    <!-- Phase 4: Payment Details -->
                    <div id="phase4" class="form-phase" style="display: none;">
                        <h4 class="mb-4">Payment Details</h4>
                        
                        <!-- Payment Summary -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Payment Summary</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Registration Fee:</td>
                                                <td class="text-end" id="registrationFee">₱<?php echo number_format($members->getRegistrationFee(), 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Membership Plan:</td>
                                                <td class="text-end" id="membershipSubtotal">₱0.00</td>
                                            </tr>
                                            <tr>
                                                <td>Programs:</td>
                                                <td class="text-end" id="programsSubtotal">₱0.00</td>
                                            </tr>
                                            <tr>
                                                <td>Rental Services:</td>
                                                <td class="text-end" id="rentalsSubtotal">₱0.00</td>
                                            </tr>
                                            <tr class="fw-bold">
                                                <td>Total Amount:</td>
                                                <td class="text-end" id="totalAmount">₱0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <!-- Generated Credentials -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Generated Credentials</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Username:</td>
                                                <td class="text-end" id="generatedUsername">-</td>
                                            </tr>
                                            <tr>
                                                <td>Password:</td>
                                                <td class="text-end" id="generatedPassword">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" onclick="prevPhase(4)">Previous</button>
                            <button type="button" onclick="submitForm()">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Make base URL available to JavaScript
    const BASE_URL = '<?php echo $baseUrl; ?>';
    
    // Initialize registration fee as a number, not a string
    let registrationFee = parseFloat(<?php echo $members->getRegistrationFee(); ?>) || 0;
    
    function formatPrice(amount) {
        // Ensure amount is a number
        amount = parseFloat(amount) || 0;
        return `₱${amount.toFixed(2)}`;
    }

    function validatePhase1() {
        const form = document.getElementById('memberForm');
        let hasErrors = false;
        
        // Clear previous errors
        clearValidationErrors();
        
        // Validate first name
        if (!form.first_name.value) {
            showError(form.first_name, 'First name is required');
            hasErrors = true;
        }
        
        // Validate last name
        if (!form.last_name.value) {
            showError(form.last_name, 'Last name is required');
            hasErrors = true;
        }
        
        // Validate sex
        const sexInputs = form.querySelectorAll('input[name="sex"]');
        let sexSelected = false;
        sexInputs.forEach(input => {
            if (input.checked) sexSelected = true;
        });
        if (!sexSelected) {
            const sexContainer = document.querySelector('.d-flex.gap-4');
            const error = document.createElement('div');
            error.className = 'invalid-feedback d-block';
            error.textContent = 'Please select your sex';
            sexContainer.appendChild(error);
            hasErrors = true;
        }
        
        // Validate birthdate
        if (!form.birthdate.value) {
            showError(form.birthdate, 'Birthdate is required');
            hasErrors = true;
        } else {
            const birthdate = new Date(form.birthdate.value);
            const today = new Date();
            if (birthdate > today) {
                showError(form.birthdate, 'Birthdate cannot be in the future');
                hasErrors = true;
            }
        }
        
        // Validate phone
        if (!form.phone.value) {
            showError(form.phone, 'Phone number is required');
            hasErrors = true;
        } else if (!/^[0-9]{11}$/.test(form.phone.value)) {
            showError(form.phone, 'Phone number must be 11 digits');
            hasErrors = true;
        }
        
        return !hasErrors;
    }

    function validatePhase2() {
        const membershipSelected = document.querySelector('input[name="membership_plan"]:checked');
        const startDate = document.getElementById('start_date');
        let hasErrors = false;

        if (!membershipSelected) {
            document.getElementById('membershipError').style.display = 'block';
            hasErrors = true;
        }

        if (!startDate.value) {
            startDate.classList.add('is-invalid');
            hasErrors = true;
        } else {
            const selectedDate = new Date(startDate.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                startDate.classList.add('is-invalid');
                startDate.nextElementSibling.textContent = 'Start date cannot be earlier than today';
                hasErrors = true;
            } else {
                startDate.classList.remove('is-invalid');
            }
        }

        return !hasErrors;
    }

    function showError(input, message) {
        input.classList.add('is-invalid');
        const error = document.createElement('div');
        error.className = 'invalid-feedback';
        error.textContent = message;
        input.parentNode.appendChild(error);
    }

    function clearValidationErrors() {
        // Remove invalid class from inputs
        const invalidInputs = document.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => input.classList.remove('is-invalid'));
        
        // Remove error messages
        const errorMessages = document.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(msg => msg.remove());
        
        // Hide membership error
        document.getElementById('membershipError').style.display = 'none';
    }

    function nextPhase(currentPhase) {
        if (currentPhase === 1) {
            if (!validatePhase1()) return;

            // Generate credentials after Phase 1 validation
            console.log("Generating credentials after Phase 1...");
            const firstNameInput = document.querySelector('input[name="first_name"]');
            console.log("First name input element:", firstNameInput);
            
            if (!firstNameInput) {
                console.error('First name input not found');
                return;
            }

            const firstName = firstNameInput.value;
            console.log("First name value:", firstName);
            
            if (!firstName) {
                console.error('First name is empty');
                alert('Please enter a first name');
                return;
            }

            const formData = new FormData();
            formData.append('first_name', firstName);
            formData.append('generate_credentials', '1');
            
            console.log("Sending request to generate credentials...");
            fetch('/Gym_MembershipSE-XLB/admin/pages/members/add_member.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log("Got response:", response);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error("Error response:", text);
                        throw new Error('Network response was not ok');
                    });
                }
                return response.text().then(text => {
                    console.log("Raw response:", text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Failed to parse JSON:", e);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                console.log("Got data:", data);
                if (!data.success) {
                    throw new Error(data.message || 'Failed to generate credentials');
                }
                
                // Store credentials in hidden fields
                document.getElementById('usernameField').value = data.username;
                document.getElementById('passwordField').value = data.password;

                // Move to Phase 2
                document.getElementById('phase1').style.display = 'none';
                document.getElementById('phase2').style.display = 'block';
            })
            .catch(error => {
                console.error('Error generating credentials:', error);
                alert('Error generating credentials. Please try again.');
            });
            return; // Don't proceed with normal phase transition
        } else if (currentPhase === 2) {
            if (!validatePhase2()) return;
            
            // Get selected membership plan
            const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
            if (!selectedPlan) return;
            
            // Get membership plan duration and filter programs/rentals
            fetch(`${BASE_URL}/admin/pages/members/add_member.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_membership_duration&plan_id=${selectedPlan.value}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fetch filtered programs and rentals
                    return fetch(`${BASE_URL}/admin/pages/members/add_member.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_filtered_items&duration=${data.duration}&duration_type_id=${data.duration_type_id}`
                    });
                }
                throw new Error('Failed to get membership duration');
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update programs list
                    const programsContainer = document.querySelector('#phase3 .programs-container');
                    if (programsContainer) {
                        programsContainer.innerHTML = data.programs_html;
                    }
                    
                    // Update rentals list
                    const rentalsContainer = document.querySelector('#phase3 .rentals-container');
                    if (rentalsContainer) {
                        rentalsContainer.innerHTML = data.rentals_html;
                    }
                    
                    // Show phase 3
                    document.getElementById('phase2').style.display = 'none';
                    document.getElementById('phase3').style.display = 'block';
                } else {
                    throw new Error('Failed to get filtered items');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request. Please try again.');
            });
            
            return; // Don't proceed with normal phase transition
        }

        if (currentPhase === 3) {
            const username = document.getElementById('usernameField').value;
            const password = document.getElementById('passwordField').value;
            
            if (!username || !password) {
                alert('Error: Credentials not found. Please go back to Phase 1.');
                return;
            }

            // Display the stored credentials
            document.getElementById('generatedUsername').textContent = username;
            document.getElementById('generatedPassword').textContent = password;
        }

        // For all other phase transitions
        document.getElementById('phase' + currentPhase).style.display = 'none';
        document.getElementById('phase' + (currentPhase + 1)).style.display = 'block';
        
        // Update total amount
        updateTotalAmount();
    }

    function prevPhase(currentPhase) {
        document.getElementById('phase' + currentPhase).style.display = 'none';
        document.getElementById('phase' + (currentPhase - 1)).style.display = 'block';
        
        // Update total amount when changing phases
        updateTotalAmount();
    }

    function updateTotalAmount() {
        // Get membership plan price
        const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
        const membershipPrice = selectedPlan ? parseFloat(selectedPlan.dataset.price) || 0 : 0;
        
        // Get selected programs prices from coach dropdowns
        const programCoachDropdowns = document.querySelectorAll('.program-coach');
        let programTotal = 0;
        programCoachDropdowns.forEach(dropdown => {
            if (dropdown.value) {
                const selectedOption = dropdown.options[dropdown.selectedIndex];
                programTotal += parseFloat(selectedOption.dataset.price) || 0;
            }
        });
        
        // Get selected rental services prices
        const selectedRentals = document.querySelectorAll('input[name="rental_services[]"]:checked');
        let rentalTotal = 0;
        selectedRentals.forEach(rental => {
            rentalTotal += parseFloat(rental.dataset.price) || 0;
        });

        // Update all subtotals
        document.getElementById('registrationFee').textContent = formatPrice(registrationFee);
        document.getElementById('membershipSubtotal').textContent = formatPrice(membershipPrice);
        document.getElementById('programsSubtotal').textContent = formatPrice(programTotal);
        document.getElementById('rentalsSubtotal').textContent = formatPrice(rentalTotal);

        // Calculate and update total
        const total = registrationFee + membershipPrice + programTotal + rentalTotal;
        document.getElementById('totalAmount').textContent = formatPrice(total);

        // Debug output
        console.log({
            registrationFee,
            membershipPrice,
            programTotal,
            rentalTotal,
            total
        });
    }

    function submitForm() {
        // Get all form data
        const formData = new FormData();
        
        // Add action
        formData.append('action', 'add_member');
        
        // Phase 1 - Personal Details
        const personalFields = ['first_name', 'middle_name', 'last_name', 'sex', 'birthdate', 'phone'];
        personalFields.forEach(field => {
            const input = document.querySelector(`input[name="${field}"]`);
            if (input) formData.append(field, input.value);
        });
        
        // Add photo if selected
        const photoInput = document.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files[0]) {
            formData.append('photo', photoInput.files[0]);
        }

        // Phase 2 - Membership Plan
        const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
        if (selectedPlan) {
            formData.append('membership_plan', selectedPlan.value);
        }

        // Phase 3 - Programs and Rentals
        // Get selected programs and their coaches
        const programs = [];
        document.querySelectorAll('.program-coach').forEach(coachSelect => {
            if (coachSelect.value) {
                programs.push({
                    program_id: coachSelect.getAttribute('data-program-id'),
                    coach_id: coachSelect.value
                });
            }
        });
        formData.append('program_coach', JSON.stringify(programs));

        // Get selected rentals
        const selectedRentals = [];
        document.querySelectorAll('.rental-checkbox:checked').forEach(rental => {
            selectedRentals.push(rental.value);
        });
        formData.append('rentals', JSON.stringify(selectedRentals));

        // Phase 4 - Credentials
        const username = document.getElementById('usernameField').value;
        const password = document.getElementById('passwordField').value;
        formData.append('username', username);
        formData.append('password', password);

        // Add start date
        const today = new Date();
        const startDate = today.toISOString().split('T')[0];
        formData.append('start_date', startDate);

        console.log("Submitting form data...");
        
        // Log the form data
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Submit the form
        fetch('/Gym_MembershipSE-XLB/admin/pages/members/add_member.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log("Got response:", response);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error("Error response:", text);
                    throw new Error('Network response was not ok');
                });
            }
            return response.text().then(text => {
                console.log("Raw response:", text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Failed to parse JSON:", e);
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then(data => {
            console.log("Got data:", data);
            if (data.success) {
                alert('Member registered successfully!');
                // Use BASE_URL for redirection
                window.location.href = `${BASE_URL}/admin/members_new`;
            } else {
                alert('Error: ' + (data.message || 'Failed to register member'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error registering member. Please try again.');
        });
    }

    // Add event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Add submit event listener to the form
        const form = document.getElementById('memberForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Collect selected programs and coaches
            const selectedPrograms = [];
            const programCoaches = {};
            const programCoachDropdowns = document.querySelectorAll('.program-coach');
            
            programCoachDropdowns.forEach(dropdown => {
                if (dropdown.value) {
                    const programId = dropdown.getAttribute('data-program-id');
                    selectedPrograms.push(programId);
                    programCoaches[programId] = dropdown.value;
                }
            });

            // Add hidden fields for programs and coaches
            const programsInput = document.createElement('input');
            programsInput.type = 'hidden';
            programsInput.name = 'programs';
            programsInput.value = JSON.stringify(selectedPrograms);
            form.appendChild(programsInput);

            const programCoachInput = document.createElement('input');
            programCoachInput.type = 'hidden';
            programCoachInput.name = 'program_coach';
            programCoachInput.value = JSON.stringify(programCoaches);
            form.appendChild(programCoachInput);

            // Submit the form
            form.submit();
        });

        // Add change event listener to membership plan radios
        const membershipPlans = document.querySelectorAll('input[name="membership_plan"]');
        membershipPlans.forEach(plan => {
            plan.addEventListener('change', updateTotalAmount);
        });

        // Add change event listener to program coach dropdowns
        const programCoachDropdowns = document.querySelectorAll('.program-coach');
        programCoachDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', updateTotalAmount);
        });

        // Add change event listener to rental service checkboxes
        const rentalServices = document.querySelectorAll('input[name="rental_services[]"]');
        rentalServices.forEach(service => {
            service.addEventListener('change', updateTotalAmount);
        });

        // Initial calculation
        updateTotalAmount();
    });
    </script>
</body>
</html>
