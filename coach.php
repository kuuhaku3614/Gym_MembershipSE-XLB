
<?php
session_start();

require_once 'user_account/profile.class.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login/login.php'); // Redirect to login page if not logged in
    exit();
}

// Initialize database and CoachingSystem
$database = new Database();
$coachingSystem = new CoachingSystem($database);

// Get the current user's ID
$currentUserId = $_SESSION['user_id'];

// Fetch user's availed sessions
$availedSessions = $coachingSystem->getUserAvailedSessions($currentUserId);

// Check for errors
$hasErrors = isset($availedSessions['error']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Availed Sessions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
    .badge-personal {
        background-color: #007bff; /* Blue */
        color: white;
    }
    .badge-group {
        background-color: #28a745; /* Green */
        color: white;
    }
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #007bff; /* Or any color */
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 16px;
        margin-right: 10px;
    }
    </style>
</head>
<body>
    <?php include('../user.nav.php'); ?>
    <div id="content">
        <div class="dashboard-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>Your Availed Sessions</h4>
                    <div>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-calendar-day"></i> <?php echo date('F d, Y l'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-4">
            <?php if ($hasErrors): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> There was an error fetching data. Please check your database connection.
                </div>
            <?php elseif (empty($availedSessions)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> You have not availed any coaching sessions yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Coach</th>
                                <th>Program</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availedSessions as $session): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($session['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                <?php echo strtoupper(substr($session['coach_name'], 0, 1)); ?>
                                            </div>
                                            <span class="ms-2"><?php echo htmlspecialchars($session['coach_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($session['program_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $session['session_type'] === 'Personal' ? 'badge-personal' : 'badge-group'; ?>">
                                            <?php echo $session['session_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php
                                            if ($session['status'] === 'cancelled') {
                                                echo 'badge-cancelled';
                                            } elseif ($session['status'] === 'completed') {
                                                echo 'badge-completed';
                                            } else {
                                                echo $session['is_paid'] ? 'badge-paid' : 'badge-unpaid';
                                            }
                                        ?>">
                                            <?php
                                            if ($session['status'] === 'cancelled') {
                                                echo 'Cancelled';
                                            } elseif ($session['status'] === 'completed') {
                                                echo 'Completed';
                                            } else {
                                                echo $session['is_paid'] ? 'Paid' : 'Unpaid';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1vO5uAGn3y+iwwC6o9uzIvZeh9P4m" crossorigin="anonymous"></script>
</body>
</html>
