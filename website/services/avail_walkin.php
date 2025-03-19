<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/sanitize.php';
require_once __DIR__ . '/services.class.php';
require_once __DIR__ . '/cart.class.php';

// if (!isset($_SESSION['user_id'])) {
//     header('location: ../../login/login.php');
//     exit;
// }

// Initialize Services class
$Services = new Services_Class();

// Get walk-in details
try {
    $db = new Database();
    $conn = $db->connect();
    
    $sql = "SELECT w.*, dt.type_name as duration_type 
            FROM walk_in w 
            JOIN duration_types dt ON w.duration_type_id = dt.id 
            WHERE w.id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $walkin = $stmt->fetch();
    
    if (!$walkin) {
        throw new Exception("Walk-in service not found");
    }
    
    $price = $walkin['price'];
    $duration = $walkin['duration'];
    $duration_type = $walkin['duration_type'];
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('location: ../services.php');
    exit;
}

// Initialize variables
$start_date = date('Y-m-d'); // Initialize with today's date

// Error variables
$start_dateErr = '';

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login first.');
    }

    $date = isset($_POST['date']) ? trim($_POST['date']) : '';

    if (empty($date)) {
        throw new Exception('Please select a date.');
    }

    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        throw new Exception('Selected date cannot be in the past.');
    }

    $Cart = new Cart_Class();
    if ($Cart->addWalkinToCart(1, $date)) {
        $_SESSION['success_message'] = "Successfully added item to the list!";
                
                // Return a JSON response instead of redirecting immediately
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'redirect' => '../services.php'
                ]);
                exit;
    } else {
        throw new Exception('Failed to add walk-in service to cart.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
    exit;
}
// Centralized function for querying the database
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}
$color = executeQuery("SELECT * FROM website_content WHERE section = 'color'")[0] ?? [];
function decimalToHex($decimal) {
    $hex = dechex(abs(floor($decimal * 16777215)));
    // Ensure hex values are properly formatted with leading zeros
    return '#' . str_pad($hex, 6, '0', STR_PAD_LEFT);
}

$primaryHex = isset($color['latitude']) ? decimalToHex($color['latitude']) : '#000000';
$secondaryHex = isset($color['longitude']) ? decimalToHex($color['longitude']) : '#000000';
?>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
    :root {
        --primary-color: <?= $primaryHex ?>;
        --secondary-color: <?= $secondaryHex ?>;
    }
    .bg-custom-red {
        background-color: var(--primary-color) !important;
    }
    .card-header, .btn-custom-red {
        background-color: #ff0000;
        color: white;
    }
    .card-header {
        background-color: var(--primary-color);
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

<div class="avail-walkin-page">
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
                    <h2 class="h4 fw-bold mb-0 text-center">Walk-in Service</h2>
                </div>
                <div class="card-body p-3">
                    <section class="scrollable-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Validity: <?= $duration ?> <?= strtolower($duration_type) ?></p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="form-group">
                                        <label for="start_date" class="form-label">Start Date:</label>
                                        <input type="date" 
                                            class="form-control form-control-lg" 
                                            id="start_date" 
                                            name="date" 
                                            min="<?= date('Y-m-d', strtotime('today')) ?>" 
                                            value="<?= $start_date ?>"
                                            required
                                            onchange="updateEndDate(this.value)">
                                        <div id="start_date_error" class="text-danger mt-1"></div> <!-- Error message area -->
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
                                    <p class="mb-0">Price: ₱<?= number_format($price, 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                        <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="flex-fill" onsubmit="return validateForm(event)">
                                <input type="hidden" name="walkin_id" value="1">
                                <input type="hidden" name="date" id="hidden_start_date">
                                <button type="submit" class="btn btn-lg w-100 add-cart" style="height: 48px!;">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <a href="../../login/login.php" class="btn btn-custom-red btn-lg w-100">Login to Add</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function updateEndDate(startDate) {
    if (!startDate) {
        document.getElementById('end_date').textContent = 'Select start date';
        return;
    }
    
    const duration = <?= json_encode($duration) ?>; // Ensure proper PHP-to-JS conversion
    if (!duration || isNaN(duration)) {
        console.error('Invalid duration value');
        return;
    }

    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + parseInt(duration, 10));
    
    const formattedEndDate = endDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    document.getElementById('end_date').textContent = formattedEndDate;
}

function validateForm(event) {
    event.preventDefault();
    
    const startDateInput = document.getElementById('start_date');
    const startDate = startDateInput.value;
    const startDateError = document.getElementById('start_date_error');

    // Clear previous error messages
    startDateError.textContent = '';

    if (!startDate) {
        startDateError.textContent = 'Please select a date.';
        return false;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const selectedDate = new Date(startDate);
    
    if (selectedDate < today) {
        startDateError.textContent = 'Date cannot be in the past.';
        return false;
    }
    
    document.getElementById('hidden_start_date').value = startDate;
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            startDateError.textContent = data.message; // Show error message in the form
        }
    })
    .catch(error => {
        console.error('Error:', error);
        startDateError.textContent = 'An error occurred. Please try again.';
    });

    return false;
}

// Add event listener only if the element exists
document.addEventListener("DOMContentLoaded", function() {
    const startDateInput = document.getElementById('start_date');
    if (startDateInput) {
        startDateInput.addEventListener('change', function() {
            updateEndDate(this.value);
            document.getElementById('hidden_start_date').value = this.value;
        });

        // Initialize end date on page load
        if (startDateInput.value) {
            updateEndDate(startDateInput.value);
        }
    }
});
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">