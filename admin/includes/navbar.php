<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!function_exists('getFullName')) {
    // Updated function to get the full name and profile photo of the user
    function getFullName() {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];

            // Database connection
            $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Fetch first name, last name, and profile photo
            $sql = "SELECT pd.first_name, pd.last_name, pp.photo_path 
                   FROM personal_details pd 
                   LEFT JOIN profile_photos pp ON pd.user_id = pp.user_id 
                   WHERE pd.user_id = ? AND (pp.is_active = 1 OR pp.is_active IS NULL)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                $photoPath = $row['photo_path'] ? htmlspecialchars($row['photo_path']) : '../cms_img/user.png';
                
                // Store both name and photo path
                $_SESSION['user_photo'] = $photoPath;
                
                $stmt->close();
                $conn->close();
                return $fullName;
            }

            // Close connections
            $stmt->close();
            $conn->close();
        }
        return 'Guest';
    }
}

// If user is logged in but personal_details is not set, fetch them
if ($isLoggedIn && !isset($_SESSION['personal_details'])) {
    require_once __DIR__ . '/../../website/user_account/profile.class.php';
    $profile = new Profile_class();
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);
    $_SESSION['personal_details'] = $userDetails;
}
?>

<?php
        require_once 'header.php';
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>
.current-user-container2, .current-user-container {
    padding: 10px;
    background-color: #f8f9fa;
    text-align: center;
}

.current-user-container {
    background-color: #c92f2f!important;
    border-bottom-right-radius: 10px;
    border-right: 2px solid #be2222;
}

.current-user-container2 {
    border-radius: 10px;
}

.user-info {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.user-photo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-bottom: 10px;
}

.user-name {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
}

.user-role {
    font-size: 14px;
    color: #6c757d;
}

.modal {
    z-index: 1050 !important;
}
.modal-backdrop {
    z-index: 1040 !important;
}
</style>

<div class="sidebar" id="sidebar">
    <!-- Logo and Admin Container -->
    <div class="logo-container">
        <img src="../cms_img/jc_logo_2.png" alt="Gym Logo" class="logo-image">
        <p class="admin-text">JC Powerzone</p>
    </div>

    <!-- Navigation Links Container -->
    <div class="nav-links-container">
        <nav>
            <a href="dashboard" id="dashboard-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </div>
            </a>

            <a href="members_new" id="members-link" class="nav-item has-subnav">
                <div class="nav-item-content">
                    <i class="fas fa-users"></i>
                    Members
                </div>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sub-nav">
                <a href="attendance" id="attendance-link" class="sub-nav-item">
                    <i class="fas fa-clipboard-check"></i>
                    Attendance
                </a>
                <a href="member_status" id="member_status-link" class="sub-nav-item">
                    <i class="fas fa-user-check"></i>
                    Members Status
                </a>
            </div>

            <a href="walk_in" id="walk_in-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-walking"></i>
                    Walk In
                </div>
            </a>

            <!-- Gym Rates Section -->
            <a href="gym_rates" id="gym_rates-link" class="nav-item has-subnav">
                <div class="nav-item-content">
                    <i class="fas fa-dumbbell"></i>
                    Gym rates
                </div>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sub-nav">
                <a href="programs" id="programs-link" class="sub-nav-item">
                    <i class="fas fa-list-alt"></i>
                    Programs
                </a>
                <a href="rentals" id="rentals-link" class="sub-nav-item">
                    <i class="fas fa-hand-holding-usd"></i>
                    Rentals
                </a>
            </div>

            <a href="payment_records" id="payment_records-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-credit-card"></i>
                    Payment Records
                </div>
            </a>

            <!-- Notifications Section -->
            <a href="notification" id="notification-link" class="nav-item has-subnav">
                <div class="nav-item-content">
                    <i class="fas fa-bell"></i>
                    Notifications
                </div>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sub-nav">
                <a href="announcement" id="announcement-link" class="sub-nav-item">
                    <i class="fas fa-bullhorn"></i>
                    Announcements
                </a>
            </div>
            <a href="accounts" id="accounts-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-user"></i>
                    Accounts
                </div>
            </a>

            <a href="website_settings" id="website_settings-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-cog"></i>
                    Website Settings
                </div>
            </a>

            <a href="report" id="report-link" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-file-alt"></i>
                    Report
                </div>
            </a>

                <!-- Staff Management Section - Only visible to admins -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="staff_management" id="staff_management-link" class="nav-item has-subnav">
                    <div class="nav-item-content">
                        <i class="fas fa-user-cog"></i>
                        Staff Management
                    </div>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Updated Logout Link and Modal -->
    <div class="logout-container">
        <a href="#" class="nav-item" id="logoutLink">
            <div class="nav-item-content">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </div>
        </a>
    </div>

    <!-- Updated Modal Structure without backdrop -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="../login/logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Current User Container -->
    <div class="current-user-container bg-danger">
    <div class="current-user-container2">
        <div class="user-info d-flex align-items-center justify-content-center flex-column">
            <div class="d-flex align-items-center justify-content-start w-100">
            <img src="<?php echo $_SESSION['user_photo']; ?>" alt="Profile Photo" class="user-photo me-2">
            <span class="user-name"><?php echo getFullName(); ?></span>
            </div>
            <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
        </div>
    </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    
    // Add click event listener to the logout link
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        logoutModal.show();
    });
});
</script>