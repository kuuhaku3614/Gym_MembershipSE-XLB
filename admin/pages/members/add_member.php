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

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['generate_credentials'])) {
    header('Content-Type: application/json');
    
    // Handle schedule request
    if (isset($_GET['action']) && $_GET['action'] === 'get_schedule' && isset($_GET['program_type_id'])) {
        $program_type_id = intval($_GET['program_type_id']);
        
        error_log("Debug - Fetching schedule for program_type_id: " . $program_type_id);
        
        // Verify coach program type exists
        $query = "SELECT id FROM coach_program_types WHERE id = :id AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $program_type_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            error_log("Debug - Invalid program type ID: " . $program_type_id);
            echo json_encode(['success' => false, 'message' => 'Invalid program type']);
            exit;
        }
        
        $result = $members->getCoachSchedule($program_type_id);
        error_log("Debug - Schedule response: " . json_encode($result));
        
        echo json_encode($result);
        exit;
    }
    
    // Handle credential generation
    if (isset($_POST['generate_credentials'])) {
        $firstName = $_POST['first_name'] ?? '';
        
        if (empty($firstName)) {
            echo json_encode(['success' => false, 'message' => 'First name is required']);
            exit;
        }
        
        try {
            $username = strtolower($firstName) . rand(1000, 9999);
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            
            echo json_encode([
                'success' => true,
                'username' => $username,
                'password' => $password
            ]);
        } catch (Exception $e) {
            error_log("Error generating credentials: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate credentials'
            ]);
        }
        exit;
    }
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

// Handle other form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_member') {
        try {
            $member = new Members();
            
            // Prepare member data
            $memberData = [
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'first_name' => $_POST['first_name'],
                'middle_name' => $_POST['middle_name'],
                'last_name' => $_POST['last_name'],
                'gender' => $_POST['sex'],
                'birthdate' => $_POST['birthdate'],
                'contact_number' => $_POST['phone'],
                'membership_plan_id' => $_POST['membership_plan'],
                'start_date' => $_POST['start_date']
            ];
            
            // Add programs with schedules
            if (isset($_POST['programs'])) {
                $memberData['programs'] = json_decode($_POST['programs'], true);
            }
            
            // Add rentals
            if (isset($_POST['rentals'])) {
                $memberData['rentals'] = json_decode($_POST['rentals'], true);
            }
            
            // Handle photo upload
            if (isset($_FILES['photo'])) {
                $photo = $_FILES['photo'];
                if ($photo['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = uniqid() . '_' . basename($photo['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($photo['tmp_name'], $targetPath)) {
                        $memberData['photo_path'] = $targetPath;
                    }
                }
            }
            
            $result = $member->addMember($memberData);
            
            if ($result['status'] === 'success') {
                echo json_encode(['success' => true, 'message' => 'Member registered successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Handle other form submissions here
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

// Handle AJAX request for coach schedule
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_schedule' && isset($_GET['program_type_id'])) {
        $program_type_id = intval($_GET['program_type_id']);
        
        error_log("Debug - Fetching schedule for program_type_id: " . $program_type_id);
        
        // Verify coach program type exists
        $query = "SELECT id FROM coach_program_types WHERE id = :id AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $program_type_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            error_log("Debug - Invalid program type ID: " . $program_type_id);
            echo json_encode(['success' => false, 'message' => 'Invalid program type']);
            exit;
        }
        
        $result = $members->getCoachSchedule($program_type_id);
        error_log("Debug - Schedule response: " . json_encode($result));
        
        echo json_encode($result);
        exit;
    }
    // Handle other AJAX actions here
    exit;
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
                // Add type filter radios
                $html .= '<div class="form-group mb-3">';
                $html .= '<label class="form-label d-block">Training Type:</label>';
                $html .= '<div class="btn-group" role="group">';
                $html .= '<input type="radio" class="btn-check training-type-radio" name="training_type_' . $program['id'] . '" id="personal_' . $program['id'] . '" value="personal" checked>';
                $html .= '<label class="btn btn-outline-primary btn-sm" for="personal_' . $program['id'] . '">Personal</label>';
                $html .= '<input type="radio" class="btn-check training-type-radio" name="training_type_' . $program['id'] . '" id="group_' . $program['id'] . '" value="group">';
                $html .= '<label class="btn btn-outline-primary btn-sm" for="group_' . $program['id'] . '">Group</label>';
                $html .= '</div>';
                $html .= '</div>';

                // Add coach dropdown
                $html .= '<div class="form-group">';
                $html .= '<label class="form-label">Select Coach:</label>';
                $html .= '<select class="form-select program-coach" ';
                $html .= 'name="program_coaches[' . $program['id'] . ']" ';
                $html .= 'data-program-id="' . $program['id'] . '">';
                $html .= '<option value="">Choose a coach</option>';
                
                // Pre-filter coaches to show only personal trainers by default
                foreach ($coachesByProgram[$program['id']] as $coach) {
                    $style = $coach['type'] !== 'personal' ? ' style="display:none;"' : '';
                    $html .= '<option value="' . htmlspecialchars($coach['coach_id']) . '" ';
                    $html .= 'data-price="' . htmlspecialchars($coach['price']) . '" ';
                    $html .= 'data-program-type-id="' . htmlspecialchars($coach['program_type_id']) . '" ';
                    $html .= 'data-type="' . htmlspecialchars($coach['type']) . '"' . $style . '>';
                    $html .= htmlspecialchars($coach['coach_name']) . ' - ₱';
                    $html .= number_format($coach['price'], 2);
                    $html .= '</option>';
                }
                
                $html .= '</select>';
                
                // Add schedule display container
                $html .= '<div id="coach-schedule-' . $program['id'] . '" class="mt-2"></div>';
                
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
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input rental-checkbox" type="checkbox" ';
            $html .= 'name="rental_services[]" ';
            $html .= 'value="' . $rental['id'] . '" ';
            $html .= 'data-price="' . $rental['price'] . '" ';
            $html .= 'data-duration="' . $rental['duration'] . '" ';
            $html .= 'data-duration-type="' . $rental['duration_type'] . '">';
            $html .= '<label class="form-check-label">Select Service</label>';
            $html .= '</div>';
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
    <title>Add Member</title>
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
            display: none;
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

                <form id="memberForm" method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault();">
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
                                    $plans = $members->getMembershipPlans();
                                    error_log("Membership plans: " . print_r($plans, true));
                                    foreach ($plans as $plan): ?>
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
                                echo renderPrograms($programs);
                                ?>
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
    // Global variables
    var BASE_URL = '<?php echo $baseUrl; ?>';
    console.log('BASE_URL:', BASE_URL);
    
    // Initialize registration fee as a number
    let registrationFee = parseFloat(<?php echo $members->getRegistrationFee(); ?>) || 0;
    
    $(document).ready(function() {
        console.log('Document ready - Setting up event listeners');
        
        // Debug - Log all program coach dropdowns
        const dropdowns = $('.program-coach');
        console.log('Found program coach dropdowns:', dropdowns.length);
        dropdowns.each(function() {
            console.log('Dropdown:', {
                id: $(this).attr('id'),
                programId: $(this).data('program-id'),
                value: $(this).val(),
                options: Array.from(this.options).map(opt => ({
                    value: opt.value,
                    text: opt.text,
                    programTypeId: $(opt).data('program-type-id')
                }))
            });
        });

        // Using event delegation for dynamic elements
        $(document).on('change', '.program-coach', function() {
            console.log('Coach dropdown changed!');
            const $dropdown = $(this);
            const programId = $dropdown.data('program-id');
            const $selected = $dropdown.find(':selected');
            const programTypeId = $selected.data('program-type-id');
            
            console.log('Change event details:', {
                dropdownId: $dropdown.attr('id'),
                programId: programId,
                selectedValue: $dropdown.val(),
                programTypeId: programTypeId,
                selectedOption: $selected.text(),
                allOptions: Array.from(this.options).map(opt => ({
                    value: opt.value,
                    text: opt.text,
                    programTypeId: $(opt).data('program-type-id')
                }))
            });

            // Find schedule container
            const $scheduleContainer = $('#coach-schedule-' + programId);
            console.log('Schedule container:', {
                selector: '#coach-schedule-' + programId,
                found: $scheduleContainer.length > 0,
                html: $scheduleContainer.html()
            });

            if (!$dropdown.val() || !programTypeId) {
                console.log('No valid selection');
                $scheduleContainer.empty();
                return;
            }

            $scheduleContainer.html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading schedule...</div>');

            // Make the AJAX request
            const url = `${BASE_URL}/admin/pages/members/add_member.php?action=get_schedule&program_type_id=${programTypeId}`;
            console.log('Making AJAX request to:', url);
            
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    console.log('AJAX Success - Raw response:', response);
                    
                    try {
                        const data = (typeof response === 'string') ? JSON.parse(response) : response;
                        console.log('Parsed schedule data:', data);
                        
                        if (data.success && data.schedule && data.schedule.length > 0) {
                            let hasAvailableSlots = false;
                            let availableTimesByDay = {};
                            
                            // Process available times by day
                            data.schedule.forEach(day => {
                                if (day.available && day.time_slots.length > 0) {
                                    hasAvailableSlots = true;
                                    availableTimesByDay[day.day] = day.time_slots;
                                }
                            });
                            
                            if (!hasAvailableSlots) {
                                console.log('No available slots found in schedule');
                                $scheduleContainer.html('<div class="alert alert-info mt-2">No schedule available for this coach.</div>');
                                return;
                            }
                            
                            // Create schedule selection form
                            let html = '<div class="schedule-container mt-3">';
                            html += '<div class="card">';
                            html += '<div class="card-header bg-primary text-white">Schedule Selection</div>';
                            html += '<div class="card-body">';
                            
                            // Selected schedules display
                            html += '<div class="selected-schedules mb-3"></div>';
                            
                            // Schedule selection form
                            html += '<div class="schedule-form">';
                            
                            // Day selection
                            html += '<div class="form-group mb-3">';
                            html += '<label class="form-label">Select Day:</label>';
                            html += '<select class="form-select schedule-day-select">';
                            html += '<option value="">Choose a day</option>';
                            
                            Object.keys(availableTimesByDay).forEach(day => {
                                html += `<option value="${day}">${day}</option>`;
                            });
                            
                            html += '</select>';
                            html += '</div>';
                            
                            // Time selection (initially hidden)
                            html += '<div class="form-group mb-3 time-selection" style="display: none;">';
                            html += '<label class="form-label">Select Time:</label>';
                            html += '<div class="row">';
                            html += '<div class="col-md-5">';
                            html += '<select class="form-select schedule-start-time" name="start_time">';
                            html += '<option value="">Start Time</option>';
                            html += '</select>';
                            html += '</div>';
                            html += '<div class="col-md-5">';
                            html += '<select class="form-select schedule-end-time" name="end_time" disabled>';
                            html += '<option value="">End Time</option>';
                            html += '</select>';
                            html += '</div>';
                            html += '<div class="col-md-2">';
                            html += '<button type="button" class="btn btn-success add-schedule-btn" disabled>Add</button>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                            
                            html += '</div>'; // schedule-form
                            
                            // Available schedule display
                            html += '<div class="mt-3">';
                            html += '<h6>Available Schedules:</h6>';
                            html += '<div class="row g-0">';
                            
                            data.schedule.forEach(daySchedule => {
                                if (daySchedule.available && daySchedule.time_slots.length > 0) {
                                    html += `
                                        <div class="col">
                                            <div class="day-column">
                                                <div class="day-header">${daySchedule.day}</div>
                                                <div class="time-slots">
                                    `;
                                    
                                    daySchedule.time_slots.forEach(slot => {
                                        html += `
                                            <div class="time-slot">
                                                <div class="time-range">${slot.start_time} - ${slot.end_time}</div>
                                            </div>
                                        `;
                                    });
                                    
                                    html += `
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                            
                            html += '</div>'; // row
                            html += '</div>'; // Available schedule display
                            
                            html += '</div>'; // card-body
                            html += '</div>'; // card
                            html += '</div>'; // schedule-container
                            
                            // Add the schedule to the container
                            $scheduleContainer.html(html);
                            
                            // Store available times data
                            $scheduleContainer.data('availableTimes', availableTimesByDay);
                            $scheduleContainer.data('selectedSchedules', []);
                            
                            // Handle day selection change
                            $scheduleContainer.on('change', '.schedule-day-select', function() {
                                const selectedDay = $(this).val();
                                const $timeSelection = $(this).closest('.card-body').find('.time-selection');
                                const $startTime = $timeSelection.find('.schedule-start-time');
                                const $endTime = $timeSelection.find('.schedule-end-time');
                                const $addBtn = $timeSelection.find('.add-schedule-btn');
                                
                                if (selectedDay) {
                                    const availableTimes = availableTimesByDay[selectedDay];
                                    
                                    // Generate time options with 30-minute intervals
                                    let startTimeOptions = ['<option value="">Start Time</option>'];
                                    availableTimes.forEach(slot => {
                                        const start = new Date(`2000-01-01 ${slot.start_time}`);
                                        const end = new Date(`2000-01-01 ${slot.end_time}`);
                                        
                                        while (start < end) {
                                            const timeStr = start.toLocaleTimeString('en-US', { 
                                                hour: 'numeric', 
                                                minute: '2-digit', 
                                                hour12: true 
                                            });
                                            startTimeOptions.push(`<option value="${timeStr}">${timeStr}</option>`);
                                            start.setMinutes(start.getMinutes() + 30);
                                        }
                                    });
                                    
                                    $startTime.html(startTimeOptions.join(''));
                                    $timeSelection.show();
                                } else {
                                    $timeSelection.hide();
                                    $startTime.html('<option value="">Start Time</option>');
                                    $endTime.html('<option value="">End Time</option>');
                                    $addBtn.prop('disabled', true);
                                }
                                
                                $endTime.prop('disabled', true);
                            });
                            
                            // Handle start time selection
                            $scheduleContainer.on('change', '.schedule-start-time', function() {
                                const selectedDay = $(this).closest('.card-body').find('.schedule-day-select').val();
                                const selectedStart = $(this).val();
                                const $endTime = $(this).closest('.row').find('.schedule-end-time');
                                const $addBtn = $(this).closest('.row').find('.add-schedule-btn');
                                
                                if (selectedStart && selectedDay) {
                                    const availableTimes = availableTimesByDay[selectedDay];
                                    const selectedSlot = availableTimes.find(slot => {
                                        const slotStart = new Date(`2000-01-01 ${slot.start_time}`);
                                        const slotEnd = new Date(`2000-01-01 ${slot.end_time}`);
                                        const selected = new Date(`2000-01-01 ${selectedStart}`);
                                        return selected >= slotStart && selected < slotEnd;
                                    });
                                    
                                    if (selectedSlot) {
                                        const start = new Date(`2000-01-01 ${selectedStart}`);
                                        const end = new Date(`2000-01-01 ${selectedSlot.end_time}`);
                                        let endTimeOptions = ['<option value="">End Time</option>'];
                                        
                                        start.setMinutes(start.getMinutes() + 30);
                                        while (start <= end) {
                                            const timeStr = start.toLocaleTimeString('en-US', { 
                                                hour: 'numeric', 
                                                minute: '2-digit', 
                                                hour12: true 
                                            });
                                            endTimeOptions.push(`<option value="${timeStr}">${timeStr}</option>`);
                                            start.setMinutes(start.getMinutes() + 30);
                                        }
                                        
                                        $endTime.html(endTimeOptions.join(''));
                                        $endTime.prop('disabled', false);
                                    }
                                } else {
                                    $endTime.html('<option value="">End Time</option>');
                                    $endTime.prop('disabled', true);
                                    $addBtn.prop('disabled', true);
                                }
                            });
                            
                            // Handle end time selection
                            $scheduleContainer.on('change', '.schedule-end-time', function() {
                                const $addBtn = $(this).closest('.row').find('.add-schedule-btn');
                                $addBtn.prop('disabled', !$(this).val());
                            });
                            
                            // Handle add schedule button
                            $scheduleContainer.on('click', '.add-schedule-btn:not(:disabled)', function() {
                                const $form = $(this).closest('.schedule-form');
                                const selectedDay = $form.find('.schedule-day-select').val();
                                const selectedStart = $form.find('.schedule-start-time').val();
                                const selectedEnd = $form.find('.schedule-end-time').val();
                                
                                if (selectedDay && selectedStart && selectedEnd) {
                                    const schedules = $scheduleContainer.data('selectedSchedules') || [];
                                    
                                    // Check for overlapping schedules
                                    const isOverlapping = schedules.some(schedule => {
                                        if (schedule.day !== selectedDay) return false;
                                        
                                        const existingStart = new Date(`2000-01-01 ${schedule.startTime}`);
                                        const existingEnd = new Date(`2000-01-01 ${schedule.endTime}`);
                                        const newStart = new Date(`2000-01-01 ${selectedStart}`);
                                        const newEnd = new Date(`2000-01-01 ${selectedEnd}`);
                                        
                                        return (newStart < existingEnd && newEnd > existingStart);
                                    });
                                    
                                    if (isOverlapping) {
                                        alert('This schedule overlaps with an existing schedule. Please choose a different time.');
                                        return;
                                    }
                                    
                                    // Add the schedule
                                    schedules.push({
                                        day: selectedDay,
                                        startTime: selectedStart,
                                        endTime: selectedEnd
                                    });
                                    
                                    // Update the display
                                    const $selectedSchedules = $scheduleContainer.find('.selected-schedules');
                                    $selectedSchedules.html(schedules.map((schedule, index) => `
                                        <div class="selected-schedule alert alert-success alert-dismissible fade show mb-2">
                                            <strong>${schedule.day}:</strong> ${schedule.startTime} - ${schedule.endTime}
                                            <input type="hidden" name="schedule[${index}][day]" value="${schedule.day}">
                                            <input type="hidden" name="schedule[${index}][start_time]" value="${schedule.startTime}">
                                            <input type="hidden" name="schedule[${index}][end_time]" value="${schedule.endTime}">
                                            <button type="button" class="btn-close remove-schedule" data-index="${index}"></button>
                                        </div>
                                    `).join(''));
                                    
                                    // Store updated schedules
                                    $scheduleContainer.data('selectedSchedules', schedules);
                                    
                                    // Reset form
                                    $form.find('select').val('');
                                    $form.find('.time-selection').hide();
                                    $form.find('.add-schedule-btn').prop('disabled', true);
                                }
                            });
                            
                            // Handle remove schedule
                            $scheduleContainer.on('click', '.remove-schedule', function() {
                                const index = $(this).data('index');
                                const schedules = $scheduleContainer.data('selectedSchedules');
                                
                                schedules.splice(index, 1);
                                $scheduleContainer.data('selectedSchedules', schedules);
                                
                                // Update the display
                                const $selectedSchedules = $scheduleContainer.find('.selected-schedules');
                                $selectedSchedules.html(schedules.map((schedule, index) => `
                                    <div class="selected-schedule alert alert-success alert-dismissible fade show mb-2">
                                        <strong>${schedule.day}:</strong> ${schedule.startTime} - ${schedule.endTime}
                                        <input type="hidden" name="schedule[${index}][day]" value="${schedule.day}">
                                        <input type="hidden" name="schedule[${index}][start_time]" value="${schedule.startTime}">
                                        <input type="hidden" name="schedule[${index}][end_time]" value="${schedule.endTime}">
                                        <button type="button" class="btn-close remove-schedule" data-index="${index}"></button>
                                    </div>
                                `).join(''));
                            });
                            
                            // Add custom styles if not already added
                            const styleId = 'schedule-custom-styles';
                            if (!$('#' + styleId).length) {
                                $('<style>')
                                    .attr('id', styleId)
                                    .text(`
                                        .schedule-container {
                                            font-size: 0.9em;
                                        }
                                        .day-column {
                                            border-right: 1px solid #dee2e6;
                                            min-height: 150px;
                                        }
                                        .day-column:last-child {
                                            border-right: none;
                                        }
                                        .day-header {
                                            padding: 8px;
                                            background-color: #f8f9fa;
                                            border-bottom: 1px solid #dee2e6;
                                            text-align: center;
                                            font-weight: 500;
                                        }
                                        .time-slots {
                                            padding: 8px;
                                        }
                                        .time-slot {
                                            background-color: #e8f5e9;
                                            border: 1px solid #c8e6c9;
                                            border-radius: 4px;
                                            padding: 6px 8px;
                                            margin: 4px 0;
                                        }
                                        .time-range {
                                            color: #2e7d32;
                                            font-size: 0.85em;
                                            text-align: center;
                                        }
                                        .selected-schedule {
                                            margin-bottom: 0.5rem;
                                        }
                                    `)
                                    .appendTo('head');
                            }
                        } else {
                            console.error('Invalid or empty schedule data:', data);
                            $scheduleContainer.html('<div class="alert alert-danger mt-2">Error processing schedule data.</div>');
                        }
                    } catch (error) {
                        console.error('Error parsing response:', error);
                        $scheduleContainer.html('<div class="alert alert-danger mt-2">Error processing schedule data.</div>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', {
                        status: jqXHR.status,
                        textStatus: textStatus,
                        error: errorThrown,
                        response: jqXHR.responseText
                    });
                    $scheduleContainer.html('<div class="alert alert-danger mt-2">Failed to load schedule.</div>');
                }
            });
        });

        // Function to filter coaches based on selected type
        function filterCoachesByType(selectElement, selectedType) {
            const $select = $(selectElement);
            const $options = $select.find('option');
            
            // First hide all options except the default one
            $options.each(function() {
                const $option = $(this);
                if ($option.val() === '') {
                    $option.show(); // Keep "Choose a coach" visible
                } else {
                    const type = $option.data('type');
                    $option.toggle(type === selectedType);
                }
            });
            
            // If the currently selected option is now hidden, reset to default
            if ($select.find('option:selected').is(':hidden')) {
                $select.val('');
            }
            
            // Clear the schedule display
            const programId = $select.data('program-id');
            $('#coach-schedule-' + programId).empty();
        }
        
        // Handle radio button changes
        $(document).on('change', '.training-type-radio', function() {
            const programId = this.id.split('_')[1];
            const selectedType = $(this).val();
            const selectElement = $(`select[data-program-id="${programId}"]`);
            
            filterCoachesByType(selectElement, selectedType);
        });
        
        // Apply initial filtering to all coach dropdowns
        $('.program-coach').each(function() {
            const $select = $(this);
            const programId = $select.data('program-id');
            const $radioGroup = $(`input[name="training_type_${programId}"]`);
            const selectedType = $radioGroup.filter(':checked').val() || 'personal';
            
            // Ensure the radio button is checked
            $radioGroup.filter(`[value="${selectedType}"]`).prop('checked', true);
            
            // Apply the filter
            filterCoachesByType(this, selectedType);
        });
        
        // Other event listeners
        $('input[name="membership_plan"]').on('change', updateTotalAmount);
        $('.program-coach').on('change', updateTotalAmount);
        $('input[name="rental_services[]"]').on('change', updateTotalAmount);
        
        // Initial calculation
        updateTotalAmount();
        
        // Debug - Log initial state
        console.log('Initial state:', {
            baseUrl: BASE_URL,
            dropdowns: $('.program-coach').length,
            containers: $('[id^=coach-schedule-]').length
        });
    });
    
    // Update total amount function
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
    }
    
    function formatPrice(amount) {
        // Ensure amount is a number
        amount = parseFloat(amount) || 0;
        return `₱${amount.toFixed(2)}`;
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
            fetch('../admin/pages/members/add_member.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('usernameField').value = data.username;
                    document.getElementById('passwordField').value = data.password;
                    document.getElementById('phase1').style.display = 'none';
                    document.getElementById('phase2').style.display = 'block';
                } else {
                    throw new Error(data.message || 'Failed to generate credentials');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error generating credentials: ' + error.message);
            });
            
            return; // Don't proceed with normal phase transition
        } else if (currentPhase === 3) {
            if (!validatePhase3()) return;
            
            // Calculate dates
            const today = new Date();
            const membershipPlan = document.querySelector('input[name="membership_plan"]:checked');
            const membershipDuration = parseInt(membershipPlan.dataset.duration);
            const membershipDurationType = membershipPlan.dataset.durationType;
            
            const startDate = today;
            const endDate = new Date(today);
            if (membershipDurationType === 'days') {
                endDate.setDate(endDate.getDate() + membershipDuration);
            } else if (membershipDurationType === 'months') {
                endDate.setMonth(endDate.getMonth() + membershipDuration);
            } else if (membershipDurationType === 'year') {
                endDate.setFullYear(endDate.getFullYear() + membershipDuration);
            }

            // Function to format date in words
            function formatDateInWords(date) {
                const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
            }

            // Build summary HTML
            let summaryHtml = '<div class="card mb-4">';
            summaryHtml += '<div class="card-header bg-primary text-white">Registration Summary</div>';
            summaryHtml += '<div class="card-body">';
            
            // Personal Details
            summaryHtml += '<h5 class="card-title mb-3">Personal Information</h5>';
            summaryHtml += '<div class="table-responsive mb-4">';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<tr><th>Full Name</th><td>' + 
                document.querySelector('input[name="first_name"]').value + ' ' + 
                (document.querySelector('input[name="middle_name"]').value ? document.querySelector('input[name="middle_name"]').value + ' ' : '') + 
                document.querySelector('input[name="last_name"]').value + '</td></tr>';
            summaryHtml += '<tr><th>Sex</th><td>' + document.querySelector('input[name="sex"]:checked').value + '</td></tr>';
            
            // Format date of birth
            const birthdate = new Date(document.querySelector('input[name="birthdate"]').value);
            summaryHtml += '<tr><th>Date of Birth</th><td>' + formatDateInWords(birthdate) + '</td></tr>';
            
            summaryHtml += '<tr><th>Phone Number</th><td>' + document.querySelector('input[name="phone"]').value + '</td></tr>';
            summaryHtml += '</table>';
            summaryHtml += '</div>';

            // Membership Details
            summaryHtml += '<h5 class="card-title mb-3">Membership Plan</h5>';
            summaryHtml += '<div class="table-responsive mb-4">';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<tr><th>Plan</th><td>' + membershipPlan.getAttribute('data-name') + '</td></tr>';
            summaryHtml += '<tr><th>Duration</th><td>' + membershipDuration + ' ' + membershipPlan.getAttribute('data-duration-type') + '</td></tr>';
            summaryHtml += '<tr><th>Start Date</th><td>' + formatDateInWords(startDate) + '</td></tr>';
            summaryHtml += '<tr><th>End Date</th><td>' + formatDateInWords(endDate) + '</td></tr>';
            summaryHtml += '<tr><th>Price</th><td>₱' + parseFloat(membershipPlan.dataset.price).toFixed(2) + '</td></tr>';
            summaryHtml += '</table>';
            summaryHtml += '</div>';

            // Programs Details
            const selectedPrograms = document.querySelectorAll('.program-coach');
            let hasPrograms = false;
            selectedPrograms.forEach(select => {
                if (select.value) {
                    if (!hasPrograms) {
                        summaryHtml += '<h5 class="card-title mb-3">Selected Programs</h5>';
                        summaryHtml += '<div class="table-responsive mb-4">';
                        summaryHtml += '<table class="table table-bordered">';
                        hasPrograms = true;
                    }

                    const programCard = select.closest('.card');
                    const programName = programCard.querySelector('.card-title').textContent;
                    const coachOption = select.options[select.selectedIndex];
                    const coachName = coachOption.text.split(' - ')[0]; // Remove the price part
                    const scheduleContainer = document.getElementById('coach-schedule-' + select.dataset.programId);
                    const selectedSchedules = $(scheduleContainer).data('selectedSchedules') || [];

                    summaryHtml += '<tr class="table-primary"><th colspan="2">' + programName + '</th></tr>';
                    summaryHtml += '<tr><th>Coach</th><td>' + coachName + '</td></tr>';
                    summaryHtml += '<tr><th>Training Type</th><td>' + coachOption.dataset.type.charAt(0).toUpperCase() + coachOption.dataset.type.slice(1) + '</td></tr>';
                    summaryHtml += '<tr><th>Price</th><td>₱' + parseFloat(coachOption.dataset.price).toFixed(2) + '</td></tr>';
                    summaryHtml += '<tr><th>Start Date</th><td>' + formatDateInWords(startDate) + '</td></tr>';
                    summaryHtml += '<tr><th>End Date</th><td>' + formatDateInWords(endDate) + '</td></tr>';
                    summaryHtml += '<tr><th>Schedules</th><td>';
                    selectedSchedules.forEach(schedule => {
                        summaryHtml += '<div class="mb-1">' + schedule.day + ': ' + schedule.startTime + ' - ' + schedule.endTime + '</div>';
                    });
                    summaryHtml += '</td></tr>';
                }
            });
            if (hasPrograms) {
                summaryHtml += '</table>';
                summaryHtml += '</div>';
            }

            // Rental Services Details
            const selectedServices = document.querySelectorAll('.rental-checkbox:checked');
            if (selectedServices.length > 0) {
                summaryHtml += '<h5 class="card-title mb-3">Selected Services</h5>';
                summaryHtml += '<div class="table-responsive mb-4">';
                summaryHtml += '<table class="table table-bordered">';
                selectedServices.forEach(service => {
                    const serviceCard = service.closest('.card');
                    const serviceName = serviceCard.querySelector('.card-title').textContent;
                    const serviceEndDate = new Date(startDate);
                    if (service.dataset.durationType === 'days') {
                        serviceEndDate.setDate(serviceEndDate.getDate() + parseInt(service.dataset.duration));
                    } else if (service.dataset.durationType === 'months') {
                        serviceEndDate.setMonth(serviceEndDate.getMonth() + parseInt(service.dataset.duration));
                    }
                    
                    summaryHtml += '<tr class="table-info"><th colspan="2">' + serviceName + '</th></tr>';
                    summaryHtml += '<tr><th>Duration</th><td>' + service.dataset.duration + ' ' + service.dataset.durationType + '</td></tr>';
                    summaryHtml += '<tr><th>Start Date</th><td>' + formatDateInWords(startDate) + '</td></tr>';
                    summaryHtml += '<tr><th>End Date</th><td>' + formatDateInWords(serviceEndDate) + '</td></tr>';
                    summaryHtml += '<tr><th>Price</th><td>₱' + parseFloat(service.dataset.price).toFixed(2) + '</td></tr>';
                });
                summaryHtml += '</table>';
                summaryHtml += '</div>';
            }

            // Total Amount
            summaryHtml += '<div class="row">';
            summaryHtml += '<div class="col-md-6 offset-md-6">';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<tr><th>Registration Fee</th><td class="text-end">₱' + parseFloat(registrationFee).toFixed(2) + '</td></tr>';
            summaryHtml += '<tr><th>Membership</th><td class="text-end">₱' + parseFloat(membershipPlan.dataset.price).toFixed(2) + '</td></tr>';
            
            let programTotal = 0;
            selectedPrograms.forEach(select => {
                if (select.value) {
                    programTotal += parseFloat(select.options[select.selectedIndex].dataset.price);
                }
            });
            summaryHtml += '<tr><th>Programs</th><td class="text-end">₱' + programTotal.toFixed(2) + '</td></tr>';
            
            let servicesTotal = 0;
            selectedServices.forEach(service => {
                servicesTotal += parseFloat(service.dataset.price);
            });
            summaryHtml += '<tr><th>Services</th><td class="text-end">₱' + servicesTotal.toFixed(2) + '</td></tr>';
            
            const totalAmount = registrationFee + parseFloat(membershipPlan.dataset.price) + programTotal + servicesTotal;
            summaryHtml += '<tr class="fw-bold"><th>Total Amount</th><td class="text-end">₱' + totalAmount.toFixed(2) + '</td></tr>';
            summaryHtml += '</table>';
            summaryHtml += '</div>';
            summaryHtml += '</div>';

            // Credentials
            summaryHtml += '<h5 class="card-title mb-3">Account Credentials</h5>';
            summaryHtml += '<div class="table-responsive">';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<tr><th>Username</th><td>' + document.getElementById('usernameField').value + '</td></tr>';
            summaryHtml += '<tr><th>Password</th><td>' + document.getElementById('passwordField').value + '</td></tr>';
            summaryHtml += '</table>';
            summaryHtml += '</div>';

            summaryHtml += '</div>'; // card-body
            summaryHtml += '</div>'; // card

            // Update the summary container
            document.getElementById('phase4').innerHTML = summaryHtml + 
                '<div class="mt-4 text-center">' +
                '<button type="button" class="btn btn-primary" onclick="submitForm()">Submit Registration</button>' +
                '</div>';
        }

        // For all other phase transitions
        document.getElementById('phase' + currentPhase).style.display = 'none';
        document.getElementById('phase' + (currentPhase + 1)).style.display = 'block';
    }

    function prevPhase(currentPhase) {
        document.getElementById('phase' + currentPhase).style.display = 'none';
        document.getElementById('phase' + (currentPhase - 1)).style.display = 'block';
        
        // Update total amount when changing phases
        updateTotalAmount();
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

    function validatePhase3() {
        let isValid = true;
        let errorMessage = '';
        
        // Check each program coach dropdown
        $('.program-coach').each(function() {
            const $select = $(this);
            if ($select.val()) { // If a coach is selected
                const programId = $select.data('program-id');
                const $scheduleContainer = $('#coach-schedule-' + programId);
                const selectedSchedules = $scheduleContainer.data('selectedSchedules') || [];
                
                if (selectedSchedules.length === 0) {
                    isValid = false;
                    const programName = $select.closest('.card').find('.card-title').text();
                    errorMessage += `Please select at least one schedule for ${programName}\n`;
                }
            }
        });
        
        if (!isValid) {
            alert(errorMessage);
        }
        return isValid;
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

    function submitForm() {
        console.log("Starting form submission...");
        
        // Get all form data
        const formData = new FormData();
        
        // Add action
        formData.append('action', 'add_member');
        
        // Phase 1 - Personal Details
        const personalFields = ['first_name', 'middle_name', 'last_name', 'sex', 'birthdate', 'phone'];
        personalFields.forEach(field => {
            const input = document.querySelector(`input[name="${field}"]`);
            if (input) {
                formData.append(field, input.value);
                console.log(`Adding ${field}: ${input.value}`);
            }
        });
        
        // Add photo if selected
        const photoInput = document.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files[0]) {
            formData.append('photo', photoInput.files[0]);
            console.log('Photo added to form data');
        }

        // Phase 2 - Membership Plan
        const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
        if (selectedPlan) {
            formData.append('membership_plan', selectedPlan.value);
            console.log(`Adding membership_plan: ${selectedPlan.value}`);
        }

        // Phase 3 - Programs and Rentals
        // Get selected programs, their coaches, and schedules
        const programs = [];
        document.querySelectorAll('.program-coach').forEach(coachSelect => {
            if (coachSelect.value) {
                const programId = coachSelect.getAttribute('data-program-id');
                const $scheduleContainer = $(`#coach-schedule-${programId}`);
                const selectedSchedules = $scheduleContainer.data('selectedSchedules') || [];
                
                programs.push({
                    program_id: programId,
                    coach_id: coachSelect.value,
                    schedules: selectedSchedules.map(schedule => ({
                        day: schedule.day,
                        start_time: schedule.startTime,
                        end_time: schedule.endTime
                    }))
                });
            }
        });
        formData.append('program_coach', JSON.stringify(programs));
        console.log('Programs and schedules:', JSON.parse(formData.get('program_coach')));

        // Get selected rentals
        const selectedRentals = [];
        document.querySelectorAll('.rental-checkbox:checked').forEach(rental => {
            selectedRentals.push(rental.value);
        });
        formData.append('rentals', JSON.stringify(selectedRentals));
        console.log('Rentals added:', selectedRentals);

        // Phase 4 - Credentials
        const username = document.getElementById('usernameField').value;
        const password = document.getElementById('passwordField').value;
        formData.append('username', username);
        formData.append('password', password);
        console.log(`Adding credentials - Username: ${username}`);

        // Add start date
        const today = new Date();
        const startDate = today.toISOString().split('T')[0];
        formData.append('start_date', startDate);
        console.log(`Adding start_date: ${startDate}`);

        console.log("All form data prepared, sending request...");
        
        // Submit the form
        fetch('../admin/pages/members/add_member.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log("Server response status:", response.status);
            return response.text().then(text => {
                console.log("Raw server response:", text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Failed to parse server response as JSON:", e);
                    throw new Error('Invalid JSON response from server: ' + text);
                }
            });
        })
        .then(data => {
            console.log("Parsed response data:", data);
            if (data.success) {
                alert('Member registered successfully!');
                window.location.href = `${BASE_URL}/admin/members_new`;
            } else {
                alert('Error: ' + (data.message || 'Failed to register member'));
            }
        })
        .catch(error => {
            console.error('Error during form submission:', error);
            alert('Error registering member: ' + error.message);
        });
    }
    </script>
</body>
</html>
