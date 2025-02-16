<?php

// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '../../config.php';

// Check if user is logged in and has valid session data
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);

// Handle logout logic
if (isset($_GET['logout'])) {
    // Clear all session data
    session_unset();
    session_destroy();
    header('Location: ../website/website.php');
    exit();
}

// Avoid redeclaring functions if already included
if (!function_exists('requireLogin')) {
    // Function to ensure login
    function requireLogin() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: ../login/login.php');
            exit(); // Prevent further execution
        }
    }
}

// Fetch transaction notifications for the user
$transaction_sql = "SELECT id, status, created_at 
                    FROM transactions 
                    WHERE user_id = ? AND created_at >= ?
                    ORDER BY created_at DESC";
$stmt = $pdo->prepare($transaction_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $user_created_at, PDO::PARAM_STR);
$stmt->execute();
$transaction_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch announcements from the database
$sql = "SELECT id, message, applied_date, applied_time, announcement_type, created_at 
        FROM announcements 
        WHERE is_active = 1 
        ORDER BY created_at DESC";
$result = $pdo->query($sql);

include('includes/header.php');
?>

<style>
        html{
            background-color: transparent;
        }
        body{
            height: 100vh;
            background-color: #efefef!important;
        }
        .home-navbar{
            background-color: red;
            position: fixed;
            border-radius: 0;
        }
        .main-content{
           padding-top: 100px;
        }
        .list-group{
            height: 70vh;
            overflow-y: auto;
        }
</style>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<section class="main-content">
<div class="container mt-4">
        <h2 class="mb-4 text-center">Notifications</h2>
        <div class="list-group">
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 text-primary"> <?= htmlspecialchars($row['announcement_type']) ?> Announcement</h5>
                        <small class="text-muted"> <?= htmlspecialchars($row['applied_date']) ?> at <?= htmlspecialchars($row['applied_time']) ?></small>
                    </div>
                    <p class="mb-1"> <?= nl2br(htmlspecialchars($row['message'])) ?> </p>
                    <small class="text-muted">Posted on: <?= htmlspecialchars($row['created_at']) ?></small>
                </div>
            <?php endwhile; ?>

            <?php foreach ($transaction_result as $row): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 text-success">Transaction Update</h5>
                        <small class="text-muted">Created on: <?= htmlspecialchars($row['created_at']) ?></small>
                    </div>
                    <p class="mb-1">Your transaction with ID <strong><?= htmlspecialchars($row['id']) ?></strong> has been <strong><?= htmlspecialchars($row['status']) ?></strong>.</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog justify-content-center">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
                <small id="modalDate" class="text-muted"></small>
            </div>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
    $('.list-group-item').click(function() {
        const type = $(this).find('h5').text();
        const message = $(this).find('p').text();
        const date = $(this).find('small:last').text();
        
        $('#notificationModalLabel').text(type);
        $('#modalMessage').text(message);
        $('#modalDate').text(date);
        
        $('#notificationModal').modal('show');
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

