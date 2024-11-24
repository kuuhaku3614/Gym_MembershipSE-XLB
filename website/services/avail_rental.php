<?php
    session_start();
    require_once 'services.class.php';
    require_once 'cart.class.php';

    $Obj = new Services_Class();
    $Cart = new Cart();
    
    // Handle POST request for adding to cart
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
        if (isset($_SESSION['user_id'])) {
            try {
                $item = [
                    'id' => $_POST['rental_id'],
                    'name' => $_POST['service_name'],
                    'price' => $_POST['price'],
                    'validity' => $_POST['validity']
                ];
                
                $Cart->addRental($item);
                
                $_SESSION['success'] = "Rental service added to cart successfully!";
                header("Location: ../services.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $_POST['rental_id']);
                exit();
            }
        } else {
            $_SESSION['error'] = "Please login to add items to cart.";
            header("Location: ../../login/login.php");
            exit();
        }
    }
    
    // Handle GET request
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['id'])) {
            $rental_id = $_GET['id'];
            $record = $Obj->fetchRental($rental_id);
            if (!empty($record)) {
                $service_name = $record['service_name'];
                $price = $record['price'];
                $duration = $record['duration'];
                $duration_type = $record['duration_type'];
                $available_slots = $record['available_slots'];
                $description = $record['description'];
            } else {
                echo 'No rental service found';
                exit;
            }
        } else {
            echo 'No rental service id found';
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
</style>

<div class="avail-rental-page">
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
                    <h2 class="fs-4 fw-bold mb-0">Rental Service</h2>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-between" style="padding: 2rem;">
                    <h3 class="fs-5 fw-bold mb-4"><?= $service_name ?></h3>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Validity: <?= $duration ?> <?= $duration_type ?></p>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Price: ₱<?= number_format($price, 2) ?></p>
                    </div>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Available Slots: <?= $available_slots ?></p>
                    </div>
                    <?php if (!empty($description)) { ?>
                        <div class="mb-3 p-2 border rounded">
                            <p class="mb-0">Description: <?= htmlspecialchars($description) ?></p>
                        </div>
                    <?php } ?>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="../services.php" class="btn btn-outline-danger btn-lg" style="width: 48%;">Return</a>
                        <?php if (isset($_SESSION['user_id'])) { ?>
                            <form method="POST" style="width: 48%;">
                                <input type="hidden" name="rental_id" value="<?= $rental_id ?>">
                                <input type="hidden" name="service_name" value="<?= $service_name ?>">
                                <input type="hidden" name="price" value="<?= $price ?>">
                                <input type="hidden" name="validity" value="<?= $duration . ' ' . $duration_type ?>">
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
</body>
</html>
