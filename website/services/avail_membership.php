<?php
session_start();
require_once '../../functions/sanitize.php';
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

$Services = new Services_Class();
$Cart = new Cart_Class();

if (!isset($_GET['id'])) {
    header('location: ../services.php');
    exit;
}

$membership_id = clean_input($_GET['id']);
$membership = $Services->fetchGymrate($membership_id);

if (!$membership) {
    $_SESSION['error'] = "Invalid membership plan.";
    header('location: ../services.php');
    exit;
}

// Construct validity from duration and duration_type
$membership['validity'] = $membership['duration'] . ' ' . strtolower($membership['duration_type']);

// Initialize error variables
$start_dateErr = '';
$start_date = date('Y-m-d'); // Initialize with today's date

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $membership_id = clean_input($_POST['membership_id']);
        $start_date = clean_input($_POST['start_date']);
        
        if (empty($membership_id)) {
            throw new Exception("Please select a membership plan.");
        }
        
        if (empty($start_date)) {
            throw new Exception("Please select a start date.");
        }
        
        $membership = $Services->fetchGymrate($membership_id);
        if (!$membership) {
            throw new Exception("Invalid membership plan selected.");
        }
        
        // Construct validity again for the selected membership
        $membership['validity'] = $membership['duration'] . ' ' . strtolower($membership['duration_type']);
        
        // Calculate end date based on validity period
        $duration = $membership['duration'];
        $duration_type = strtolower($membership['duration_type']);
        
        // Convert duration type to a format strtotime understands
        switch ($duration_type) {
            case 'month':
            case 'months':
                $interval = $duration . ' month';
                break;
            case 'year':
            case 'years':
                $interval = $duration . ' year';
                break;
            case 'day':
            case 'days':
                $interval = $duration . ' day';
                break;
            default:
                throw new Exception("Invalid duration type");
        }
        
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $interval));
        
        $item = array_merge($membership, [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'validity' => $membership['validity']
        ]);
        
        error_log("Adding membership to cart: " . print_r($item, true));
        
        if ($Cart->addMembership($item)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Membership added to cart successfully',
                'redirect' => '../services.php'
            ]);
            exit;
        } else {
            throw new Exception("Failed to add membership to cart.");
        }
        
    } catch (Exception $e) {
        error_log("Error in avail_membership.php: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
    body {
        height: 100vh;
    }
    .main-container {
        max-height: 100vh;
    }
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

<div class="avail-membership-page">
    <div class="container-fluid p-0">
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
                    <h2 class="h4 fw-bold mb-0 text-center"><?= $membership['plan_type'] ?> Membership</h2>
                </div>
                <div class="card-body p-3">
                    <h3 class="h5 fw-bold text-center mb-4"><?= $membership['plan_name'] ?></h3>

                <section class="scrollable-section">

                    <div class="row g-3">
                    <div class="col-12">
                        <div class="border rounded p-3">
                        <p class="mb-0">Validity: <?= $membership['validity'] ?></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date:</label>
                            <input type="date" 
                               class="form-control form-control-lg" 
                               id="start_date" 
                               name="start_date" 
                               min="<?= date('Y-m-d', strtotime('today')) ?>" 
                               value="<?= $start_date ?>"
                               required
                               onchange="updateEndDate(this.value, <?= $membership['duration'] ?>)">
                            <?php if(!empty($start_dateErr)): ?>
                            <div class="text-danger mt-1"><?= $start_dateErr ?></div>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                        <p class="mb-0">End Date: <span id="end_date">Select start date</span></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                        <p class="mb-0">Price: ₱<?= number_format($membership['price'], 2) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($membership['description'])): ?>
                    <div class="col-12">
                        <div class="border rounded p-3">
                        <p class="mb-0">Description: <?= nl2br(htmlspecialchars($membership['description'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    </div>
                </section>
                    <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                    <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" class="flex-fill" onsubmit="return validateForm(event)">
                        <input type="hidden" name="membership_id" value="<?= $membership_id ?>">
                        <input type="hidden" name="start_date" id="hidden_start_date">
                        <input type="hidden" name="end_date" id="hidden_end_date">
                        <button type="submit" class="btn btn-lg w-100 add-cart" style="height: 48px!;">Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <button  class="btn btn-custom-red btn-lg "><a href="../../login/login.php">Login to Add</a></button>
                    <?php endif; ?>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm(event) {
    const startDate = document.getElementById('start_date').value;
    if (!startDate) {
        alert('Please select a start date.');
        return false;
    }
    
    // Prevent default form submission
    event.preventDefault();
    
    // Get form data
    const form = event.target.closest('form');
    const formData = new FormData(form);
    
    // Update hidden fields
    document.getElementById('hidden_start_date').value = startDate;
    const endDate = calculateEndDate(startDate, <?= $membership['duration'] ?>);
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
    formData.set('end_date', document.getElementById('hidden_end_date').value);
    
    // Submit form via fetch
    fetch(form.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request');
    });
    
    return false;
}

function updateEndDate(startDate, duration) {
    const endDate = calculateEndDate(startDate, duration);
    document.getElementById('end_date').textContent = formatDate(endDate);
    
    // Update hidden input for form submission
    document.getElementById('hidden_start_date').value = startDate;
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
}

function calculateEndDate(startDate, duration) {
    const start = new Date(startDate);
    let end = new Date(start);
    
    // Get the duration type from PHP
    const durationType = '<?= strtolower($membership['duration_type']) ?>';
    
    // Calculate end date based on duration type
    switch(durationType) {
        case 'month':
        case 'months':
            end.setMonth(end.getMonth() + parseInt(duration));
            break;
        case 'year':
        case 'years':
            end.setFullYear(end.getFullYear() + parseInt(duration));
            break;
        case 'day':
        case 'days':
            end.setDate(end.getDate() + parseInt(duration));
            break;
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