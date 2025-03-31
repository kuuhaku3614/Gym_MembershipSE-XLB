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
$adminPages = ['dashboard', 'members', 'members_new', 'add_member', 'renew_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'content_management', 'report', 'staff_management', 'activity_logs'];
$staffPages = ['dashboard', 'members', 'members_new', 'add_member', 'renew_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'content_management', 'report', 'activity_logs'];

// Check if the user is authorized for the requested page
if (
    ($userRole === 'admin' && !in_array($page, $adminPages)) ||
    ($userRole === 'staff' && (!in_array($page, $staffPages) || $page === 'staff_management'))
) {
    // Provide a more user-friendly error or redirect
    header('Location: dashboard?error=unauthorized');
    exit;
}

// Membership and rental status management functions
class StatusManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Update membership statuses based on expiration dates
     * @return array Number of memberships updated [expired, expiring]
     */
    public function updateMembershipStatuses() {
        // Update expired memberships
        $expiredQuery = "UPDATE 
                    memberships m
                SET 
                    m.status = 'expired'
                WHERE 
                    m.status = 'expiring'
                    AND m.end_date < CURDATE()";
        
        $expiredStmt = $this->pdo->query($expiredQuery);
        $expiredCount = $expiredStmt ? $expiredStmt->rowCount() : 0;
        
        // Flag expiring memberships (within 7 days)
        $expiringQuery = "UPDATE 
                    memberships m
                SET 
                    m.status = 'expiring'
                WHERE 
                    m.status = 'active' 
                    AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        
        $expiringStmt = $this->pdo->query($expiringQuery);
        $expiringCount = $expiringStmt ? $expiringStmt->rowCount() : 0;
        
        return ['expired' => $expiredCount, 'expiring' => $expiringCount];
    }
    
    /**
     * Update rental subscription statuses based on expiration dates
     * @return array Number of rentals updated [expired, expiring]
     */
    public function updateRentalStatuses() {
        // Update expired rental subscriptions
        $expiredQuery = "UPDATE 
                    rental_subscriptions rs
                SET 
                    rs.status = 'expired'
                WHERE 
                    rs.status = 'expiring'
                    AND rs.end_date < CURDATE()";
        
        $expiredStmt = $this->pdo->query($expiredQuery);
        $expiredCount = $expiredStmt ? $expiredStmt->rowCount() : 0;
        
        // Flag expiring rental subscriptions (within 7 days)
        $expiringQuery = "UPDATE 
                    rental_subscriptions rs
                SET 
                    rs.status = 'expiring'
                WHERE 
                    rs.status = 'active' 
                    AND rs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        
        $expiringStmt = $this->pdo->query($expiringQuery);
        $expiringCount = $expiringStmt ? $expiringStmt->rowCount() : 0;
        
        return ['expired' => $expiredCount, 'expiring' => $expiringCount];
    }
    
    /**
     * Run all status updates and return results
     * @return array Update counts for memberships and rentals
     */
    public function runAllStatusUpdates() {
        $membershipResults = $this->updateMembershipStatuses();
        $rentalResults = $this->updateRentalStatuses();
        
        return [
            'memberships' => $membershipResults,
            'rentals' => $rentalResults
        ];
    }
}

// Run status updates if user is admin or staff
if (in_array($userRole, ['admin', 'staff']) && isset($database)) {
    $pdo = $database->connect();
    $statusManager = new StatusManager($pdo);
    $statusUpdates = $statusManager->runAllStatusUpdates();
    
    // Optionally log or display the results
    $_SESSION['status_updates'] = $statusUpdates;
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