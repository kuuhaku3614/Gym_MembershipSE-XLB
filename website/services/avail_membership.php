<?php
session_start();
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$membership_plan_id = $plan_name = $plan_type = $price = $duration = $duration_type = $description = '';
$start_date = $end_date = '';

// Error variables
$membership_plan_idErr = $start_dateErr = $end_dateErr = '';

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $membership_plan_id = clean_input($_GET['id']);
        $record = $Services->fetchGymrate($membership_plan_id);
        
        if (!$record) {
            $_SESSION['error'] = 'Membership plan not found';
            header('location: ../services.php');
            exit;
        }
        
        // Check if plan is still active
        if ($record['status_id'] != 1) {
            $_SESSION['error'] = 'This membership plan is no longer available';
            header('location: ../services.php');
            exit;
        }
        
        $plan_name = $record['plan_name'];
        $plan_type = $record['plan_type'];
        $price = $record['price'];
        $duration = $record['duration'];
        $duration_type = $record['duration_type'];
        $description = $record['description'];
    } else {
        header('location: ../services.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $membership_plan_id = clean_input($_POST['membership_plan_id']);
    $start_date = clean_input($_POST['start_date']);
    $end_date = clean_input($_POST['end_date']);
    $price = clean_input($_POST['price']);
    $plan_name = clean_input($_POST['plan_name']);

    // Get validity directly from the record
    $record = $Services->fetchGymrate($membership_plan_id);
    if (!$record) {
        $_SESSION['error'] = 'Invalid membership plan';
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

    if(empty($start_dateErr)) {
        $Cart = new Cart();
        try {
            $item = [
                'id' => $membership_plan_id,
                'name' => $plan_name,
                'price' => $price,
                'validity' => $validity,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
            
            if($Cart->addMembership($item)) {
                header('location: ../services.php');
                exit;
            } else {
                throw new Exception('Failed to add membership to cart');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('location: ../services.php');
            exit;
        }
    }
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
    .card-body {
        border: 2px solid #ff0000;
    }
</style>

<div class="avail-membership-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

        <div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="card shadow" style="width: 90%; max-width: 800px; min-height: 400px;">
                <div class="card-header text-center">
                    <h2 class="fs-4 fw-bold mb-0"><?= $plan_type ?> Membership</h2>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-between" style="padding: 2rem;">
                    <h3 class="fs-5 fw-bold mb-4"><?= $plan_name ?></h3>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               min="<?= date('Y-m-d', strtotime('today')) ?>" 
                               value="<?= $start_date ?>"
                               required
                               onchange="updateEndDate(this.value, <?= $duration ?>, '<?= $duration_type ?>')">
                        <?php if(!empty($start_dateErr)): ?>
                            <span class="text-danger"><?= $start_dateErr ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">End Date: <span id="end_date">Select start date</span></p>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Price: ₱<?= number_format($price, 2) ?></p>
                    </div>
                    <?php if (!empty($description)) { ?>
                        <div class="mb-3 p-2 border rounded">
                            <p class="mb-0">Description: <?= nl2br(htmlspecialchars($description)) ?></p>
                        </div>
                    <?php } ?>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="../services.php" class="btn btn-outline-danger btn-lg" style="width: 48%;">Return</a>
                        <?php if (isset($_SESSION['user_id'])) { ?>
                            <form method="POST" style="width: 48%;" onsubmit="return validateForm()">
                                <input type="hidden" name="membership_plan_id" value="<?= $membership_plan_id ?>">
                                <input type="hidden" name="plan_name" value="<?= $plan_name ?>">
                                <input type="hidden" name="price" value="<?= $price ?>">
                                <input type="hidden" name="start_date" id="hidden_start_date">
                                <input type="hidden" name="end_date" id="hidden_end_date">
                                <button type="submit" class="btn btn-custom-red btn-lg w-100">Add to Cart</button>
                            </form>
                        <?php } else { ?>
                            <a href="../../login/login.php" class="btn btn-custom-red btn-lg" style="width: 48%;">Login to Add</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    const startDate = document.getElementById('start_date').value;
    if (!startDate) {
        alert('Please select a start date.');
        return false;
    }
    
    // Update hidden fields before submission
    document.getElementById('hidden_start_date').value = startDate;
    const endDate = calculateEndDate(startDate, <?= $duration ?>, '<?= $duration_type ?>');
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
    
    return true;
}

function updateEndDate(startDate, duration, durationType) {
    const endDate = calculateEndDate(startDate, duration, durationType);
    document.getElementById('end_date').textContent = formatDate(endDate);
    
    // Update hidden input for form submission
    document.getElementById('hidden_start_date').value = startDate;
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
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