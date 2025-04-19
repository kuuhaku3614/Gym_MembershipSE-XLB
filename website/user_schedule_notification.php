<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../config.php'; // Adjust this to your actual database connection file

// Function to get user's cancelled and completed sessions
function getUserSessionNotifications($pdo, $user_id) {
    $notifications = [
        'cancelled' => [],
        'completed' => []
    ];
    
    // Get cancelled sessions
    $cancelled_query = "
        SELECT 
            p.id AS schedule_id,
            p.program_subscription_id,
            p.date,
            p.start_time,
            p.end_time,
            p.cancellation_reason,
            pt.description AS program_type_description,
            pt.type AS session_type,
            pr.program_name,
            cu.username AS coach_username
        FROM program_subscription_schedule p
        JOIN program_subscriptions ps ON p.program_subscription_id = ps.id
        JOIN users u ON ps.user_id = u.id
        JOIN coach_program_types pt ON ps.coach_program_type_id = pt.id
        JOIN users cu ON pt.coach_id = cu.id
        JOIN programs pr ON pt.program_id = pr.id
        WHERE p.status = 'cancelled'
        AND ps.user_id = ?
        ORDER BY p.date DESC, p.start_time";

    $stmt = $pdo->prepare($cancelled_query);
    $stmt->execute([$user_id]);
    $notifications['cancelled'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed sessions
    $completed_query = "
        SELECT 
            p.id AS schedule_id,
            p.program_subscription_id,
            p.date,
            p.start_time,
            p.end_time,
            pt.description AS program_type_description,
            pt.type AS session_type,
            pr.program_name,
            cu.username AS coach_username
        FROM program_subscription_schedule p
        JOIN program_subscriptions ps ON p.program_subscription_id = ps.id
        JOIN users u ON ps.user_id = u.id
        JOIN coach_program_types pt ON ps.coach_program_type_id = pt.id
        JOIN users cu ON pt.coach_id = cu.id
        JOIN programs pr ON pt.program_id = pr.id
        WHERE p.status = 'completed'
        AND ps.user_id = ?
        ORDER BY p.date DESC, p.start_time";

    $stmt = $pdo->prepare($completed_query);
    $stmt->execute([$user_id]);
    $notifications['completed'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $notifications;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or handle as needed
    header('Location: login.php');
    exit;
}

// Get the current user's ID
$user_id = $_SESSION['user_id'];

// Connect to database
try {
    // Initialize database connection using the Database class from config.php
    $database = new Database();
    $pdo = $database->connect();
    
    // Get the notifications
    $notifications = getUserSessionNotifications($pdo, $user_id);
    
    // Process the notifications as needed
    $cancelled_sessions = $notifications['cancelled'];
    $completed_sessions = $notifications['completed'];
    
    // Now you can use these arrays for displaying notifications to the user
    
} catch (Exception $e) {
    // Handle database connection errors
    error_log("Database error: " . $e->getMessage());
    echo "An error occurred. Please try again later.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Session Notifications</title>
    <link rel="stylesheet" href="schedule_notification.css">
</head>
<body>
    <div class="container">
        <h1>Your Session Notifications</h1>
        
        <!-- Cancelled Sessions Notifications -->
        <section class="notifications-section">
            <h2>Cancelled Sessions</h2>
            <?php if (empty($cancelled_sessions)): ?>
                <p>You have no cancelled sessions.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($cancelled_sessions as $session): ?>
                        <li class="notification-item cancelled">
                            <div class="notification-header">
                                <h3><?php echo htmlspecialchars($session['program_name']); ?> - <?php echo ucfirst(htmlspecialchars($session['session_type'])); ?> Session</h3>
                                <span class="notification-date"><?php echo date('F j, Y', strtotime($session['date'])); ?></span>
                            </div>
                            <div class="notification-content">
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                                <p><strong>Coach:</strong> <?php echo htmlspecialchars($session['coach_username']); ?></p>
                                <p><strong>Reason for Cancellation:</strong> <?php echo htmlspecialchars($session['cancellation_reason']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        
        <!-- Completed Sessions Notifications -->
        <section class="notifications-section">
            <h2>Completed Sessions</h2>
            <?php if (empty($completed_sessions)): ?>
                <p>You have no completed sessions.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($completed_sessions as $session): ?>
                        <li class="notification-item completed">
                            <div class="notification-header">
                                <h3><?php echo htmlspecialchars($session['program_name']); ?> - <?php echo ucfirst(htmlspecialchars($session['session_type'])); ?> Session</h3>
                                <span class="notification-date"><?php echo date('F j, Y', strtotime($session['date'])); ?></span>
                            </div>
                            <div class="notification-content">
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                                <p><strong>Coach:</strong> <?php echo htmlspecialchars($session['coach_username']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
    
    <script src="schedule_notification.js"></script>
</body>
</html>