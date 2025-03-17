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
$rental_id = $service_name = $price = $duration = $duration_type = $available_slots = $description = '';
$start_date = $end_date = '';

// Error variables
$rental_idErr = $start_dateErr = $end_dateErr = $priceErr = '';

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $rental_id = clean_input($_GET['id']);
        $record = $Services->fetchRental($rental_id);
        
        if (!$record) {
            $_SESSION['error'] = 'Rental service not found';
            header('location: ../services.php');
            exit;
        }
        
        if ($record['available_slots'] < 1) {
            $_SESSION['error'] = 'No available slots for this service';
            header('location: ../services.php');
            exit;
        }
        
        $service_name = $record['service_name'];
        $price = $record['price'];
        $duration = $record['duration'];
        $duration_type = $record['duration_type'];
        $available_slots = $record['available_slots'];
        $description = $record['description'];
    } else {
        header('location: ../services.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rental_id = clean_input($_POST['rental_id']);
    $service_name = clean_input($_POST['service_name']);
    $start_date = clean_input($_POST['start_date']);
    $end_date = clean_input($_POST['end_date']);
    $price = clean_input($_POST['price']);

    // Get validity directly from the record
    $record = $Services->fetchRental($rental_id);
    if (!$record) {
        $_SESSION['error'] = 'Invalid rental service';
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

    if(empty($rental_id)) {
        $rental_idErr = 'Rental service is required';
    }

    // Check if service still has available slots
    if ($record['available_slots'] < 1) {
        $rental_idErr = 'No available slots for this service';
    }

    if(empty($start_dateErr) && empty($rental_idErr)) {
        $Cart = new Cart_Class();
        try {
            $item = [
                'id' => $rental_id,
                'name' => $service_name,
                'price' => $price,
                'validity' => $validity,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
            
            if($Cart->addRental($item)) {
                $_SESSION['success_message'] = "Successfully added item to the list!";
                header('location: ../services.php');
                exit;
            } else {
                throw new Exception('Failed to add rental to cart');
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

<div class="avail-rental-page">
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
                    <h2 class="h4 fw-bold mb-0 text-center">Rental Service</h2>
                </div>
                <div class="card-body p-3">
                    <h3 class="h5 fw-bold text-center mb-4"><?= $service_name ?></h3>

                    <section class="scrollable-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
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
                                            onchange="updateEndDate(this.value, <?= $duration ?>, '<?= $duration_type ?>')">
                                        <?php if(!empty($start_dateErr)): ?>
                                            <div class="text-danger mt-1"><?= $start_dateErr ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">End Date: <span id="end_date">Select start date</span></p>
                                    <?php if(!empty($end_dateErr)): ?>
                                        <div class="text-danger mt-1"><?= $end_dateErr ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Price: ₱<?= number_format($price, 2) ?></p>
                                    <?php if(!empty($priceErr)): ?>
                                        <div class="text-danger mt-1"><?= $priceErr ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($description)): ?>
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Description: <?= nl2br(htmlspecialchars($description)) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                        <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="flex-fill" onsubmit="return validateForm()">
                                <input type="hidden" name="rental_id" value="<?= $rental_id ?>">
                                <input type="hidden" name="service_name" value="<?= $service_name ?>">
                                <input type="hidden" name="price" value="<?= $price ?>">
                                <input type="hidden" name="validity" value="<?= $duration . ' ' . $duration_type ?>">
                                <input type="hidden" name="start_date" id="hidden_start_date">
                                <input type="hidden" name="end_date" id="hidden_end_date">
                                <button type="submit" name="add_to_cart" class="btn btn-lg w-100 add-cart">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <a href="../../login/login.php" class="btn btn-lg w-100 add-cart">Login to Add</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add status messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger mt-3">
        <?= $_SESSION['error']; ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success mt-3">
        <?= $_SESSION['success']; ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<script>
function validateForm() {
    const startDate = document.getElementById('start_date').value;
   
    
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
</body>
</html>
