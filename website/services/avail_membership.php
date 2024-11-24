<?php
    session_start();
    require_once 'services.class.php';
    require_once 'cart.class.php';

    $Obj = new Services_Class();
    $Cart = new Cart();
    
    // Handle POST request for adding to cart
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
        if (isset($_SESSION['user_id'])) {
            if (empty($_POST['start_date'])) {
                $_SESSION['error'] = "Please select a start date.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $_POST['membership_plan_id']);
                exit();
            }

            try {
                $item = [
                    'id' => $_POST['membership_plan_id'],
                    'name' => $_POST['plan_name'],
                    'price' => $_POST['price'],
                    'validity' => $_POST['validity'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date']
                ];
                
                $Cart->addMembership($item);
                
                $_SESSION['success'] = "Membership plan added to cart successfully!";
                header("Location: ../services.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $_POST['membership_plan_id']);
                exit();
            }
        } else {
            $_SESSION['error'] = "Please login to add items to cart.";
            header("Location: ../../login/login.php");
            exit();
        }
    }
    
    // Existing GET request handling code...
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['id'])) {
            $membership_plan_id = $_GET['id'];
            $record = $Obj->fetchGymrate($membership_plan_id);
            if (!empty($record)) {
                $plan_name = $record['plan_name'];
                $plan_type = $record['plan_type'];
                $price = $record['price'];
                $duration = $record['duration'];
                $duration_type_id = $record['duration_type_id'];
                $description = $record['description'];
            } else {
                echo 'No membership plan found';
                exit;
            }
        } else {
            echo 'No membership plan id found';
            exit;
        }
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
        .card-body {
            border: 1px solid #ff0000;
        }
    </style>

<div class="avail-membership-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center">
            <button class="btn text-white me-3">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

        <div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="card shadow" style="width: 90%; max-width: 800px; min-height: 400px;">
                <div class="card-header text-center">
                    <h2 class="fs-4 fw-bold mb-0"><?= $plan_type ?> rates</h2>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-between" style="padding: 2rem;">
                    <h3 class="fs-5 fw-bold mb-4"><?= $plan_name ?></h3>
                    <div class="mb-3 p-2 border rounded">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               min="<?= date('Y-m-d', strtotime('today')) ?>" 
                               value="<?= date('Y-m-d') ?>"
                               required
                               onchange="updateEndDate(this.value, <?= $duration ?>, '<?= $record['duration_type'] ?>')">
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">End Date: <span id="end_date">Select start date</span></p>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Price : ₱<?= number_format($price, 2) ?></p>
                    </div>
                    <?php if (!empty($description)) { ?>
                        <div class="mb-3 p-2 border rounded">
                            <p class="mb-0">Description: <?= htmlspecialchars($description) ?></p>
                        </div>
                    <?php } ?>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="../services.php" class="btn btn-outline-danger btn-lg" style="width: 48%;">Return</a>
                        <?php if (isset($_SESSION['user_id'])) { ?>
                            <form method="POST" style="width: 48%;" onsubmit="return validateForm()">
                                <input type="hidden" name="membership_plan_id" value="<?= $membership_plan_id ?>">
                                <input type="hidden" name="plan_name" value="<?= $plan_name ?>">
                                <input type="hidden" name="price" value="<?= $price ?>">
                                <input type="hidden" name="validity" value="<?= $duration . ' ' . $record['duration_type'] ?>">
                                <input type="hidden" name="start_date" id="hidden_start_date">
                                <input type="hidden" name="end_date" id="hidden_end_date">
                                <button type="submit" name="add_to_cart" class="btn btn-custom-red btn-lg w-100">Add to Cart</button>
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
    if (!startDate) {
        alert('Please select a start date.');
        return false;
    }
    
    // Update hidden fields before submission
    document.getElementById('hidden_start_date').value = startDate;
    const endDate = calculateEndDate(startDate, <?= $duration ?>, '<?= $record['duration_type'] ?>');
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