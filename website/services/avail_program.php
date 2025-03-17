<?php
session_start();
require_once '../../functions/sanitize.php';
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$program_id = $program_name = $duration = $duration_type = $description = '';
$start_date = $end_date = '';
$coach_id = $coach_name = '';
$price = '';

// Error variables
$start_dateErr = $coach_idErr = '';

$Services = new Services_Class();

// Check if user has active membership
// if (!$Services->checkActiveMembership($_SESSION['user_id'])) {
//     $_SESSION['error'] = 'You need an active membership to avail program services. Please purchase a membership plan first.';
//     header('location: ../services.php');
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $program_id = clean_input($_GET['id']);
        $record = $Services->fetchProgram($program_id);
        
        if (!$record) {
            $_SESSION['error'] = 'Program not found';
            header('location: ../services.php');
            exit;
        }
        
        $program_name = $record['program_name'];
        $duration = $record['duration'];
        $duration_type = $record['duration_type'];
        $description = $record['description'];
        
        // Fetch available coaches
        $coaches = $Services->fetchProgramCoaches($program_id);
        if (empty($coaches)) {
            $_SESSION['error'] = 'No coaches available for this program';
            header('location: ../services.php');
            exit;
        }
    } else {
        header('location: ../services.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program_id = clean_input($_POST['program_id']);
    $program_name = clean_input($_POST['program_name']);
    $start_date = clean_input($_POST['start_date']);
    $end_date = clean_input($_POST['end_date']);
    $coach_id = clean_input($_POST['coach_id']);
    $coach_name = clean_input($_POST['coach_name']);
    $price = clean_input($_POST['price']);

    // Get validity directly from the record
    $record = $Services->fetchProgram($program_id);
    if (!$record) {
        $_SESSION['error'] = 'Invalid program';
        header('location: ../services.php');
        exit;
    }

    $validity = $record['duration'] . ' ' . $record['duration_type'];

    // Validate inputs
    if(empty($start_date)) {
        $start_dateErr = 'Start date is required';
    } else {
        // Validate start date is not in the past
        $today = new DateTime();
        $start = new DateTime($start_date);
        if ($start < $today) {
            $start_dateErr = 'Start date cannot be in the past';
        }
    }

    if(empty($coach_id)) {
        $coach_idErr = 'Please select a coach';
    } else {
        // Verify coach is still available
        $coaches = $Services->fetchProgramCoaches($program_id);
        $coach_found = false;
        foreach ($coaches as $coach) {
            if ($coach['coach_id'] == $coach_id) {
                $coach_found = true;
                break;
            }
        }
        if (!$coach_found) {
            $coach_idErr = 'Selected coach is no longer available';
        }
    }

    if(empty($start_dateErr) && empty($coach_idErr)) {
        $Cart = new Cart_Class();
        try {
            $item = [
                'id' => $program_id,
                'name' => $program_name,
                'price' => $price,
                'validity' => $validity,
                'coach_id' => $coach_id,
                'coach_name' => $coach_name,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
            
            if($Cart->addProgram($item)) {
                $_SESSION['success_message'] = "Successfully added item to the list!";
                header('location: ../services.php');
                exit;
            } else {
                throw new Exception('Failed to add program to cart');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('location: ../services.php');
            exit;
        }
    }
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
    .bg-custom-red {
        background-color: #ff0000;
    }
    .card-header, .btn-custom-red {
        background-color: #ff0000;
        color: white;
    }
    .card-header {
        background-color: #ff0000;
        border-bottom: 2px solid #ff0000;
        padding: 1rem;
    }

    @media screen and (max-width: 480px) {
    /* 1. Hide the services-header */
    .services-header {
        display: none !important;
    }
    
    /* 2. Make the content fill the entire screen and scale properly */
    body, html {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    .avail-membership-page {
        width: 100%;
        height: 100%;
    }
    
    .container-fluid {
        padding: 0;
        margin: 0;
        width: 100%;
        background-color: #f5f5f5;
    }
    
    .main-container {
        height: 100%;
        width: 100%;
        padding: 0;
        margin: 0;
    }
    
    .col-12 {
        padding: 10px;
        margin-top: 0px!important;
        margin-bottom: 0px!important;
    }
    

    .card-body{
        height: 100%;
    }
    
    .scrollable-section {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 0;
    }
    
    /* .btn-lg {
        padding: 5px;
        font-size: 0.875rem;
    } */
    
    .row {
        margin: 0;
        height: 100%;
    }
/*     
    .form-control-lg {
        font-size: 0.875rem;
    } */
    .d-grid{
        display: flex!important;
        flex-direction: row!important;
        flex-wrap: nowrap;
    }
    .h5{
        font-size: 1.5rem!important;
        margin-bottom: 5px!important;
    }
}
</style>

<div class="avail-program-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center services-header">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

        <div class="container-fluid">
    <div class="row flex-grow-1 overflow-auto">
        <div class="col-12 col-lg-8 mx-auto py-3 main-container">
            <div class="card main-content" style="width: 100%;">
            <div class="card-header py-3">
                    <h2 class="h4 fw-bold mb-0 text-center">Program</h2>
                </div>
                <div class="card-body p-3">
                    <h3 class="h5 fw-bold text-center mb-4"><?= $program_name ?></h3>

                    <section class="scrollable-section">
                        <div class="row g-3">

                            <form method="POST" onsubmit="return validateForm()" class="col-12">
                                <input type="hidden" name="program_id" value="<?= htmlspecialchars($program_id) ?>">
                                <input type="hidden" name="program_name" value="<?= htmlspecialchars($program_name) ?>">
                            <div class="col-12">
                            <div class="border rounded p-3">
                                <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
                            </div>
                            </div>
                            <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="form-group">
                                            <label for="coach" class="form-label">Select Coach:</label>
                                            <select class="form-select form-select-lg" id="coach" name="coach_id">
                                                <option value="">Choose a coach</option>
                                                <?php foreach ($coaches as $coach) { ?>
                                                    <option value="<?= $coach['coach_id'] ?>" 
                                                            data-coach-name="<?= htmlspecialchars($coach['coach_name']) ?>"
                                                            data-price="<?= $coach['coach_price'] ?>">
                                                        <?= htmlspecialchars($coach['coach_name']) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <div id="coach_id_error" class="text-danger mt-1"></div> <!-- Error message -->
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <p class="mb-0">Price: ₱<span id="price_display">0.00</span></p>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="form-group">
                                            <label for="start_date" class="form-label">Start Date:</label>
                                            <input type="date" 
                                                class="form-control form-control-lg" 
                                                id="start_date" 
                                                name="start_date" 
                                                min="<?= date('Y-m-d', strtotime('today')) ?>" 
                                                value="<?= $start_date ?>"
                                                onchange="updateEndDate(this.value, <?= $duration ?>, '<?= $duration_type ?>')">
                                            <div id="start_date_error" class="text-danger mt-1"></div> <!-- Error message -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <p class="mb-0">End Date: <span id="end_date">Select start date</span></p>
                                    </div>
                                </div>

                                <input type="hidden" name="coach_name" id="selected_coach_name">
                                <input type="hidden" name="price" id="selected_price">
                                <input type="hidden" name="end_date" id="hidden_end_date">
                                </section>
                                <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                                    <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                                    <button type="submit" class="btn btn-lg flex-fill add-cart">Add to Cart</button>
                                </div>
                            </form>
                        </div>
                   
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('coach').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const coachName = selectedOption.dataset.coachName;
    const price = selectedOption.dataset.price;

    document.getElementById('selected_coach_name').value = coachName;
    document.getElementById('selected_price').value = price;
    document.getElementById('price_display').textContent =
        new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(price || 0);

    // Remove error message when coach is selected
    document.getElementById('coach_id_error').textContent = '';
});

function validateForm() {
    let isValid = true;

    // Clear previous errors
    document.getElementById('start_date_error').textContent = '';
    document.getElementById('coach_id_error').textContent = '';

    const startDate = document.getElementById('start_date').value;
    const coachId = document.getElementById('coach').value;

    if (!startDate) {
        document.getElementById('start_date_error').textContent = 'Please select a start date.';
        isValid = false;
    }

    if (!coachId) {
        document.getElementById('coach_id_error').textContent = 'Please select a coach.';
        isValid = false;
    }

    if (isValid) {
        const endDate = calculateEndDate(startDate, <?= $duration ?>, '<?= $duration_type ?>');
        document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
    }

    return isValid;
}

function updateEndDate(startDate, duration, durationType) {
    const endDate = calculateEndDate(startDate, duration, durationType);
    document.getElementById('end_date').textContent = formatDate(endDate);
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];

    // Remove error message when valid date is selected
    document.getElementById('start_date_error').textContent = '';
}

function calculateEndDate(startDate, duration, durationType) {
    const start = new Date(startDate);
    let end = new Date(start);

    if (durationType === 'days') {
        end.setDate(end.getDate() + parseInt(duration));
    } else if (durationType === 'months') {
        end.setMonth(end.getMonth() + parseInt(duration));
    } else if (durationType === 'year') {
        end.setFullYear(end.getFullYear() + parseInt(duration));
    }

    return end;
}

function formatDate(date) {
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
}

</script>