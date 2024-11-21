<?php
    session_start();
    require_once 'services.class.php';

    $Obj = new Services_Class();
    
    // Handle POST request for saving membership
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['save_membership']) && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $membership_plan_id = $_POST['membership_plan_id'];
            $total_amount = $_POST['total_amount'];

            if ($Obj->saveMembership($user_id, $membership_plan_id, $total_amount)) {
                $_SESSION['success'] = "Membership plan successfully added!";
                header("Location: ../services.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to add membership plan. Please try again.";
            }
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
                        <p class="mb-0">Validity : <?= $duration ?> <?= $record['duration_type'] ?></p>
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
                            <form method="POST" style="width: 48%;">
                                <input type="hidden" name="membership_plan_id" value="<?= $membership_plan_id ?>">
                                <input type="hidden" name="total_amount" value="<?= $price ?>">
                                <button type="submit" name="save_membership" class="btn btn-custom-red btn-lg w-100">Add</button>
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