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
$adminPages = ['dashboard', 'members', 'members_new', 'add_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'report', 'staff_management', 'activity_logs'];
$staffPages = ['dashboard', 'members', 'members_new', 'add_member', 'attendance', 'member_status', 'walk_in', 'gym_rates', 'programs', 'rentals', 'payment_records', 'notification', 'accounts', 'announcement', 'website_settings', 'report', 'activity_logs'];

// Check if the user is authorized for the requested page
if (
    ($userRole === 'admin' && !in_array($page, $adminPages)) ||
    ($userRole === 'staff' && (!in_array($page, $staffPages) || $page === 'staff_management'))
) {
    // Provide a more user-friendly error or redirect
    header('Location: dashboard?error=unauthorized');
    exit;
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