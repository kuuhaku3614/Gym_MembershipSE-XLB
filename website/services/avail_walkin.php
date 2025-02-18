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
            throw new Exception('Please login first');
        }

        $date = isset($_POST['date']) ? clean_input($_POST['date']) : '';
        
        if (empty($date)) {
            throw new Exception('Please select a date');
        }

        // Create cart instance with correct class name
        $Cart = new Cart_Class();
        if ($Cart->addWalkinToCart(1, $date)) {
            echo json_encode([
                'success' => true,
                'message' => 'Walk-in service added to cart successfully',
                'redirect' => '../services.php'
            ]);
        } else {
            throw new Exception('Failed to add walk-in service to cart');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>

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
</style>

<div class="avail-walkin-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center">
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
                                            min="<?= date('Y-m-d') ?>" 
                                            value="<?= $start_date ?>"
                                            required
                                            onchange="updateEndDate(this.value)">
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
    
    const duration = <?= $duration ?>;
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + duration);
    
    const formattedEndDate = endDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    document.getElementById('end_date').textContent = formattedEndDate;
}

function validateForm(event) {
    event.preventDefault();
    
    const startDate = document.getElementById('start_date').value;
    if (!startDate) {
        alert('Please select a date.');
        return false;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const selectedDate = new Date(startDate);
    
    if (selectedDate < today) {
        alert('Date cannot be in the past.');
        return false;
    }
    
    document.getElementById('hidden_start_date').value = startDate;
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
    
    return false;
}

// Update end date when start date changes
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = this.value;
    if (startDate) {
        document.getElementById('hidden_start_date').value = startDate;
    }
});

// Initialize end date on page load
window.onload = function() {
    const startDate = document.getElementById('start_date').value;
    if (startDate) {
        updateEndDate(startDate);
    }
};
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">