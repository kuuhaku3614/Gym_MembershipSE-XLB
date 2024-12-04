<?php
session_start();
require_once 'services.class.php';

if (!isset($_GET['id'])) {
    header("Location: ../services.php");
    exit();
}

$transaction_id = $_GET['id'];
$Services = new Services_Class();

// Initialize variables
$transaction = $memberships = $programs = $rentals = $walkin = [];
$transactionErr = $membershipErr = $programsErr = $rentalsErr = '';

$db = new Database();
$conn = $db->connect();

try {
    // Fetch transaction details with user info
    $sql = "SELECT t.*, CONCAT(pd.first_name, ' ', pd.last_name) as customer_name 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    // Fetch membership details
    $sql = "SELECT m.*, mp.plan_name, mp.price
            FROM memberships m
            JOIN membership_plans mp ON m.membership_plan_id = mp.id
            WHERE m.transaction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$transaction_id]);
    $memberships = $stmt->fetchAll();

    // Fetch program subscriptions
    $sql = "SELECT ps.*, p.program_name, p.price,
            CONCAT(pd.first_name, ' ', pd.last_name) as coach_name
            FROM program_subscriptions ps
            JOIN programs p ON ps.program_id = p.id
            JOIN users u ON ps.coach_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            WHERE ps.transaction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$transaction_id]);
    $programs = $stmt->fetchAll();

    // Fetch rental subscriptions
    $sql = "SELECT rs.*, r.service_name, r.price
            FROM rental_subscriptions rs
            JOIN rental_services r ON rs.rental_service_id = r.id
            WHERE rs.transaction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$transaction_id]);
    $rentals = $stmt->fetchAll();

    // Fetch walk-in details
    if ($transaction['type'] === 'walkin') {
        $sql = "SELECT w.*, wt.start_date 
                FROM walkin_transactions wt 
                JOIN walk_in w ON w.id = wt.walkin_id 
                WHERE wt.transaction_id = :transaction_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':transaction_id' => $transaction_id]);
        $walkin = $stmt->fetch();
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching transaction details: " . $e->getMessage();
    header("Location: ../services.php");
    exit();
}

// Calculate total amount
$total_amount = 0;
foreach ($memberships as $membership) {
    $total_amount += $membership['price'];
}
foreach ($programs as $program) {
    $total_amount += $program['price'];
}
foreach ($rentals as $rental) {
    $total_amount += $rental['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Availed Successfully</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .card {
            border: 1px solid #ff0000;
        }
        .card-header {
            background-color: #ff0000;
            color: white;
        }
        .btn-custom-red {
            background-color: #ff0000;
            color: white;
        }
        .btn-custom-red:hover {
            background-color: #cc0000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Services Availed Successfully</h4>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h5 class="mb-4">Thank you for availing our services!</h5>
                        
                        <div class="text-start mb-4">
                            <h6 class="fw-bold">Transaction Details:</h6>
                            <p class="mb-1">Transaction ID: <?= $transaction_id ?></p>
                            <p class="mb-1">Customer: <?= htmlspecialchars($transaction['customer_name']) ?></p>
                            <p class="mb-1">Date: <?= date('m/d/Y', strtotime($transaction['created_at'])) ?></p>
                        </div>

                        <?php if ($transaction['type'] === 'walkin' && $walkin): ?>
                            <div class="text-start mb-4">
                                <h6 class="fw-bold">Walk-in Service Details:</h6>
                                <p class="mb-1">Start Date: <?= date('F d, Y', strtotime($walkin['start_date'])) ?></p>
                                <p class="mb-1">Amount: ₱<?= number_format($transaction['amount'], 2) ?></p>
                                <p class="mb-1">Status: <?= ucfirst($transaction['status']) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Membership Details -->
                        <?php if (!empty($memberships)): ?>
                            <div class="text-start mb-4">
                                <h6 class="fw-bold">Membership Plans:</h6>
                                <?php foreach ($memberships as $membership): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Plan: <?= htmlspecialchars($membership['plan_name']) ?></p>
                                        <p class="mb-1">Start Date: <?= date('m/d/Y', strtotime($membership['start_date'])) ?></p>
                                        <p class="mb-1">End Date: <?= date('m/d/Y', strtotime($membership['end_date'])) ?></p>
                                        <p class="mb-1">Amount: ₱<?= number_format($membership['price'], 2) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Program Subscriptions -->
                        <?php if (!empty($programs)): ?>
                            <div class="text-start mb-4">
                                <h6 class="fw-bold">Programs:</h6>
                                <?php foreach ($programs as $program): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Program: <?= htmlspecialchars($program['program_name']) ?></p>
                                        <p class="mb-1">Coach: <?= htmlspecialchars($program['coach_name']) ?></p>
                                        <p class="mb-1">Start Date: <?= date('m/d/Y', strtotime($program['start_date'])) ?></p>
                                        <p class="mb-1">End Date: <?= date('m/d/Y', strtotime($program['end_date'])) ?></p>
                                        <p class="mb-1">Amount: ₱<?= number_format($program['price'], 2) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Rental Subscriptions -->
                        <?php if (!empty($rentals)): ?>
                            <div class="text-start mb-4">
                                <h6 class="fw-bold">Rental Services:</h6>
                                <?php foreach ($rentals as $rental): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Service: <?= htmlspecialchars($rental['service_name']) ?></p>
                                        <p class="mb-1">Start Date: <?= date('m/d/Y', strtotime($rental['start_date'])) ?></p>
                                        <p class="mb-1">End Date: <?= date('m/d/Y', strtotime($rental['end_date'])) ?></p>
                                        <p class="mb-1">Amount: ₱<?= number_format($rental['price'], 2) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Total Amount -->
                        <div class="text-start mb-4">
                            <h6 class="fw-bold">Total Amount: ₱<?= number_format($total_amount, 2) ?></h6>
                        </div>

                        <div class="mt-4">
                            <a href="../services.php" class="btn btn-custom-red">Back to Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>