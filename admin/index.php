<?php
session_start();
$_SESSION['allow_checkin_access'] = true;
require_once '../config.php';
// Ensure the session contains the necessary information
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login/login.php');
    exit;
}
$userId = $_SESSION['user_id'];

$userRole = $_SESSION['role']; // Retrieve role from session
$page = $_GET['page'] ?? 'dashboard'; // Default to 'dashboard' if not set

// Redirect 'members' to 'members_new'
if ($page === 'members') {
    header('Location: members_new');
    exit;
}

// Define role-based page access
$adminPages = ['dashboard', 'members', 'members_new', 'add_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'report', 'staff_management', 'staff_activity_log', 'coach_log'];
$staffPages = ['dashboard', 'members', 'members_new', 'add_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'report', 'staff_activity_log', 'coach_log'];

// Check if the user is authorized for the requested page
if (
    ($userRole === 'admin' && !in_array($page, $adminPages)) ||
    ($userRole === 'staff' && !in_array($page, $staffPages))
) {
    die('Access denied.');
}
function checkAndPerformReset($pdo) {
    try {
        // Get current time and date in the server's timezone
        $currentDateTime = new DateTime('now');
        $resetTime = new DateTime('05:00:00'); // 5 AM reset time
        
        // Get the last reset date from the database
        $checkResetSql = "SELECT value FROM system_controls WHERE key_name = 'last_attendance_reset'";
        $stmt = $pdo->prepare($checkResetSql);
        $stmt->execute();
        $lastResetTimestamp = $stmt->fetchColumn();
        
        // If no last reset timestamp exists, create one for yesterday to force a reset
        if (!$lastResetTimestamp) {
            $lastResetDate = new DateTime('yesterday 05:00:00');
        } else {
            $lastResetDate = new DateTime($lastResetTimestamp);
        }
        
        // Calculate the next reset time
        // If current time is before 5 AM, next reset is today at 5 AM
        // If current time is after 5 AM, next reset is tomorrow at 5 AM
        $nextResetTime = new DateTime('today 05:00:00');
        if ($currentDateTime >= $nextResetTime) {
            $nextResetTime->modify('+1 day');
        }
        
        // Check if we need to perform a reset
        // This happens when the current time is past 5 AM and we haven't reset since the last 5 AM
        if ($currentDateTime >= $resetTime && $lastResetDate < new DateTime('today 05:00:00')) {
            $pdo->beginTransaction();
            
            // Step 1: Record missed attendances
            $insertMissedAttendancesSql = "
                INSERT INTO attendance_history (attendance_id, time_in, time_out, created_at, status)
                SELECT 
                    a.id,
                    a.time_in,
                    a.time_out,
                    a.created_at,
                    'missed' as status
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                JOIN transactions t ON u.id = t.user_id
                JOIN memberships m ON t.id = m.transaction_id
                WHERE (a.status IS NULL OR a.status = 'checked_in')
                AND m.status = 'active'
                AND CURRENT_DATE BETWEEN m.start_date AND m.end_date
            ";
            $pdo->exec($insertMissedAttendancesSql);
            
            // Step 2: Create fresh attendance records for active members
            $createNewRecordsSql = "
                INSERT INTO attendance (user_id, date, created_at)
                SELECT DISTINCT u.id, CURRENT_DATE, CURRENT_TIMESTAMP
                FROM users u
                JOIN transactions t ON u.id = t.user_id
                JOIN memberships m ON t.id = m.transaction_id
                WHERE m.status = 'active'
                AND CURRENT_DATE BETWEEN m.start_date AND m.end_date
                ON DUPLICATE KEY UPDATE 
                    date = CURRENT_DATE,
                    time_in = NULL,
                    time_out = NULL,
                    status = NULL,
                    created_at = CURRENT_TIMESTAMP
            ";
            $pdo->exec($createNewRecordsSql);
            
            // Step 3: Update the last reset timestamp
            $updateResetTimeSql = "
                INSERT INTO system_controls (key_name, value) 
                VALUES ('last_attendance_reset', :timestamp)
                ON DUPLICATE KEY UPDATE value = :timestamp
            ";
            $stmt = $pdo->prepare($updateResetTimeSql);
            $stmt->execute([
                ':timestamp' => $currentDateTime->format('Y-m-d H:i:s')
            ]);
            
            $pdo->commit();
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Attendance reset failed: " . $e->getMessage());
        return false;
    }
}
?>


    <?php
        require_once '../admin/includes/header.php';
    ?>
<body>
    <!-- Burger Menu Button -->
    <button class="burger-menu" id="burgerMenu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php
        require_once '../admin/includes/navbar.php';
    ?>
    
     <!-- Main Content Container -->
     <div class="main-content">
            <!-- dynamic content here -->
     </div>

    <?php
        require_once '../admin/includes/footer.php';
    ?>

</body>
</html>