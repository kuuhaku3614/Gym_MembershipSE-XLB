<?php

session_start();
require_once('../functions.php');
require_once('lots.class.php');

if (!isset($_SESSION['account']) || !$_SESSION['account']['is_customer']) {
    header('location: login.php');
    exit;
}

if (!isset($_SESSION['account']['account_id'])) {
    echo "No account ID found in session";
    exit;
} else {
    $account_id = $_SESSION['account']['account_id'];
}

$account_id = $_SESSION['account']['account_id'];

$lot_id = $lot_image = $lot_name = $location = $size = $price = $description = '';
$reservation_date = $payment_plan_id = '';
$reservation_dateErr = $payment_plan_idErr = '';

$burialObj = new Reservation();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['lot_id'])) {
        $lot_id = $_GET['lot_id'];
        $record = $burialObj->fetchLotRecord($lot_id);
        if (!empty($record)) {
            $lot_image = $record['lot_image'];
            $lot_name = $record['lot_name'];
            $location = $record['location'];
            $size = $record['size'];
            $price = $record['price'];
            $description = $record['description'];
        } else {
            echo 'No lot found';
            exit;
        }
    } else {
        echo 'No lot id found';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST'){

    $lot_id = clean_input($_POST['lot_id']);
    $reservation_date = clean_input($_POST['reservation_date']);
    $payment_plan_id = clean_input($_POST['payment_plan']);

    $record = $burialObj->fetchLotRecord($lot_id);
    if (!empty($record)) {
        $lot_image = $record['lot_image'];
        $lot_name = $record['lot_name'];
        $location = $record['location'];
        $size = $record['size'];
        $price = $record['price'];
        $description = $record['description'];
    } else {
        echo 'No lot found';
        exit;
    }

    if(empty($reservation_date)){
        $reservation_dateErr = 'Reservation date is required';
    }

    if(empty($payment_plan_id)){
        $payment_plan_idErr = 'Payment plan is required';
    }

    if(empty($reservation_dateErr) && empty($payment_plan_idErr)){

        $burialObj->reservation_date = $reservation_date;
        $burialObj->payment_plan_id = $payment_plan_id;
        $burialObj->lot_id = $lot_id;
        $burialObj->account_id = $account_id;

        if($burialObj->addReservation()){
            header('location: ../account_profile.php');
        } else {
            echo 'Something went wrong when adding reservation';
        }
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reserve</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<style>
    :root {
        --deep-teal: #006064;
        --light-teal: #e0f2f1;
        --medium-teal: #b2dfdb;
        --blue-grey: #455a64;
        --dark-blue-grey: #263238;
    }

    body {
        background-color: var(--light-teal);
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 2rem 0;
    }

    .card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .left-panel {
        background: var(--deep-teal);
        color: white;
        padding: 2.5rem;
        height: 100%;
    }

    .right-panel {
        padding: 2.5rem;
        background: white;
    }

    .form-control, .form-select {
        border: 2px solid var(--medium-teal);
        padding: 0.75rem;
        border-radius: 8px;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--deep-teal);
        box-shadow: 0 0 0 0.2rem rgba(0, 96, 100, 0.25);
    }

    .btn-primary {
        background-color: var(--deep-teal);
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        width: 100%;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background-color: var(--blue-grey);
    }

    .lot-image {
        width: 100%;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .details-group {
        background: rgba(255, 255, 255, 0.1);
        padding: 1.5rem;
        border-radius: 8px;
        margin-top: 1.5rem;
    }

    .detail-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .account-details {
        background: var(--light-teal);
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }

    .error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    @media (max-width: 767.98px) {
        body {
            padding: 1rem;
        }

        .left-panel, .right-panel {
            padding: 1.5rem;
        }

        .lot-image {
            height: 200px;
        }
    }

    @media (min-width: 768px) {
        .card {
            min-height: 600px;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="row g-0">
            <div class="col-md-6">
                <div class="left-panel">
                    <h2 class="fs-2 fw-bold mb-4">Lot Details</h2>
                    
                    <img src="../admin/lots/<?= $lot_image ?>" alt="Lot Image" class="lot-image">
                    
                    <div class="details-group">
                        <div class="detail-item">
                            <h3 class="fs-2 fw-bold"><?= $lot_name?></h3>
                        </div>
                        <div class="detail-item fs-5">
                            <strong>Location:</strong> <?= $location?>
                        </div>
                        <div class="detail-item fs-5">
                            <strong>Size:</strong> <?= $size?> sqm lot
                        </div>
                        <div class="detail-item fs-5">
                            <strong>Price:</strong><?= $price ?>
                        </div>
                        <div class="detail-item">
                            <?= $description?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
                $account = $burialObj->fetchAccountRecord($account_id);
            ?>
            <div class="col-md-6">
                <div class="right-panel">
                    <h2 class="fs-2 fw-bold mb-4">Reservation Details</h2>
                    <div class="account-details mb-4">
                        <h3 class="fs-5 fw-bold mb-3">Account Information</h3>
                        <div class="mb-2">
                            <strong>Name:</strong> <?= $account['first_name']?> <?= $account['middle_name']?> <?= $account['last_name']?>
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong> <?= $account['email']?>
                        </div>
                        <div>
                            <strong>Phone:</strong> <?= $account['phone_number']?>
                        </div>
                    </div>

                    <form action="" method="post">
                        <input type="hidden" name="lot_id" value="<?= htmlspecialchars($lot_id) ?>">
                        <div class="mb-3">
                            <label class="form-label">Reservation Date <span class="error">*</span></label>
                            <input type="date" name="reservation_date" 
                                value="<?= $reservation_date?>" 
                                class="form-control">
                            <?php if(!empty($reservation_dateErr)): ?>
                                <span class="error"><?= $reservation_dateErr ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Payment Plan <span class="error">*</span></label>
                            <select name="payment_plan" class="form-select">
                                <option value="" hidden>Select Payment Plan</option>
                                <?php
                                    $payment_planList = $burialObj->fetchPayment_plan();
                                    foreach ($payment_planList as $pp){
                                ?>
                                    <option value="<?= $pp['payment_plan_id'] ?>" 
                                            <?= ($payment_plan_id == $pp['payment_plan_id']) ? 'selected' : '' ?>>
                                            <?= $pp['pplan'] ?>
                                    </option>
                                <?php
                                    }
                                ?>
                            </select>
                            <?php if(!empty($payment_plan_idErr)): ?>
                                <span class="error"><?= $payment_plan_idErr ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirm Reservation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>