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

<div class="avail-program-page">
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
                <div class="card-body p-3">
                    <h3 class="h5 fw-bold text-center mb-4"><?= $program_name ?></h3>

                    <section class="scrollable-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
                                </div>
                            </div>

                            <form method="POST" onsubmit="return validateForm()" class="col-12">
                                <input type="hidden" name="program_id" value="<?= htmlspecialchars($program_id) ?>">
                                <input type="hidden" name="program_name" value="<?= htmlspecialchars($program_name) ?>">

                                <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="form-group">
                                            <label for="coach" class="form-label">Select Coach:</label>
                                            <select class="form-select form-select-lg" id="coach" name="coach_id" required>
                                                <option value="">Choose a coach</option>
                                                <?php foreach ($coaches as $coach) { ?>
                                                    <option value="<?= $coach['coach_id'] ?>" 
                                                            data-coach-name="<?= htmlspecialchars($coach['coach_name']) ?>"
                                                            data-price="<?= $coach['coach_price'] ?>">
                                                        <?= htmlspecialchars($coach['coach_name']) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <?php if(!empty($coach_idErr)): ?>
                                                <div class="text-danger mt-1"><?= $coach_idErr ?></div>
                                            <?php endif; ?>
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
                                                required
                                                onchange="updateEndDate(this.value, <?= $duration ?>, '<?= $duration_type ?>')">
                                            <?php if(!empty($start_dateErr)): ?>
                                                <div class="text-danger mt-1"><?= $start_dateErr ?></div>
                                            <?php endif; ?>
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
document.getElementById('coach').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const coachName = selectedOption.dataset.coachName;
    const price = selectedOption.dataset.price;
    
    document.getElementById('selected_coach_name').value = coachName;
    document.getElementById('selected_price').value = price;
    document.getElementById('price_display').textContent = 
        new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(price || 0);
});

function validateForm() {
    const startDate = document.getElementById('start_date').value;
    const coachId = document.getElementById('coach').value;
    
    if (!startDate) {
        alert('Please select a start date.');
        return false;
    }
    
    if (!coachId) {
        alert('Please select a coach.');
        return false;
    }
    
    const endDate = calculateEndDate(startDate, <?= $duration ?>, '<?= $duration_type ?>');
    document.getElementById('hidden_end_date').value = endDate.toISOString().split('T')[0];
    
    return true;
}

function updateEndDate(startDate, duration, durationType) {
    const endDate = calculateEndDate(startDate, duration, durationType);
    document.getElementById('end_date').textContent = formatDate(endDate);
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