<?php

session_start();

$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();


if(isset($_GET['action']) && $_GET['action'] == 'logout' && !isset($_SESSION['logout_processed'])) {

    $_SESSION['logout_processed'] = true;
    
    header("Location: checkin.php");
    exit();
}

require_once '../config.php';

// Get current date
$currentDate = date('Y-m-d');
$currentTime = date('h:i A');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid px-4 py-5">
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0">Check-in System</h2>
            <div>
                <span class="me-3">
                    <strong>Date:</strong> <?= date('F d, Y') ?>
                </span>
                <span class="me-3">
                    <strong>Time:</strong> <span id="currentTime"><?= $currentTime ?></span>
                </span>
            </div>
        </div>
        <div class="card-body">
            <!-- Check-in Form -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <form id="checkinForm" method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Check-ins Table -->
            <div class="table-responsive">
                <table id="checkinTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <img src="../<?= htmlspecialchars($checkin['photo_path'] ?? 'default-avatar.png') ?>" 
                                     alt="Profile" 
                                     class="rounded-circle"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td><?= htmlspecialchars($checkin['full_name']) ?></td>
                            <td><?= htmlspecialchars($checkin['username']) ?></td>
                            <td><?= date('h:i A', strtotime($checkin['time_in'])) ?></td>
                            <td>
                                <span class="badge bg-success">Checked In</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Required Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#checkinTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search check-ins..."
        },
        columnDefs: [
            { orderable: false, targets: [0] } // Disable sorting for photo column
        ]
    });

    // Update current time
    setInterval(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        $('#currentTime').text(timeString);
    }, 1000);

    // Handle check-in form submission
    $('#checkinForm').on('submit', function(e) {
        e.preventDefault();
        // Add your check-in logic here
        // You might want to make an AJAX call to process the check-in
        alert('Check-in functionality to be implemented');
    });
});
</script>

</body>
</html>