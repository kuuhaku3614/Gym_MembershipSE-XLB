<?php
require_once 'pages/notification/functions/expiry-notifications.class.php';
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

// Get notification counts - UPDATED to use ExpiryNotifications class
function getNotificationCounts() {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    $counts = [
        'pending_transactions' => 0,
        'expiring' => 0,
        'expired' => 0,
        'total' => 0
    ];
    
    // Get counts from database for pending transactions
    $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
    if ($conn->connect_error) {
        return $counts;
    }
    
    // Pending Transactions Query
    $pending_transactions_sql = "
        SELECT COALESCE(COUNT(*), 0) AS pending_transactions 
        FROM transactions 
        WHERE status = 'pending'
    ";
    
    $result = $conn->query($pending_transactions_sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['pending_transactions'] = (int)$row['pending_transactions'];
    }
    
    $conn->close();
    
    // Use ExpiryNotifications class to get unread notifications counts
    if ($userId > 0) {
        try {
            $expiryNotificationsObj = new ExpiryNotifications();
            $unreadCounts = $expiryNotificationsObj->countUnreadNotifications($userId);
            
            $counts['expiring'] = $unreadCounts['expiring'];
            $counts['expired'] = $unreadCounts['expired'];
            $counts['total'] = $counts['pending_transactions'] + $unreadCounts['total'];
            
        } catch (Exception $e) {
            // Log error but continue
            error_log('Error getting notification counts: ' . $e->getMessage());
        }
    }
    
    return $counts;
}

// Get notification counts
$notificationCounts = getNotificationCounts();

// Centralized function for querying the database
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}

// Fetch specific content for sections
$welcomeContent = executeQuery("SELECT * FROM website_content WHERE section = 'welcome'")[0] ?? [];
$logo = executeQuery("SELECT * FROM website_content WHERE section = 'logo'")[0] ?? [];
?>

<?php
        require_once 'header.php';
        require_once('loadingScreen.php');
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>
/* .current-user-container2, .current-user-container {
    padding: 10px;
    background-color: #f8f9fa;
    text-align: center;
} */


.current-user-container2 {
    border-top: 1px solid #ececec;
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

/* Notification Badge Styles */
.dropdown-icon-container {
    position: relative;
    margin-left: 5px;
}

.badge-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.notification-section {
    position: relative;
}

.sub-nav-badge {
    margin-left: 5px;
    font-size: 10px;
    padding: 2px 6px;
}
</style>

<button class="mobile-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <!-- Logo and Admin Container -->
    <div class="logo-container">
        <img src="../<?php 
                echo htmlspecialchars($logo['location']); 
            ?>" alt="Gym Logo" class="logo-image">
        <p class="admin-text"><?php 
                echo htmlspecialchars($welcomeContent['company_name'] ?? 'Company Name'); 
            ?></p>
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

            <!-- Notifications Section with Badge on dropdown icon -->
            <a href="notification" id="notification-link" class="nav-item has-subnav">
                <div class="nav-item-content">
                    <i class="fas fa-bell"></i>
                    Notifications
                </div>
                <div class="dropdown-icon-container">
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                    <?php if ($notificationCounts['total'] > 0): ?>
                    <span class="badge-count" id="total-notifications-badge"><?php echo $notificationCounts['total']; ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <div class="sub-nav">
                <a href="transactions" id="transactions-link" class="sub-nav-item">
                    <i class="fas fa-exchange-alt"></i>
                    Requests
                    <?php if ($notificationCounts['pending_transactions'] > 0): ?>
                    <span class="badge bg-danger sub-nav-badge"><?php echo $notificationCounts['pending_transactions']; ?></span>
                    <?php endif; ?>
                </a>
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

            <a href="website_settings" id="website_settings-link" class="nav-item has-subnav">
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
    <div class="current-user-container2">
        <div class="user-info d-flex align-items-center justify-content-center flex-column" style="padding:15px 25px">
        <a href="#" class="logout-item d-flex align-items-center justify-content-between w-100" id="logoutLink" style="padding: 15px;">

            <span class="user-name d-flex align-items-center"><?php echo getFullName(); ?></span>
                <i class="fas fa-sign-out-alt"></i>

        </a>
            </div>
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
    
    // Toggle notification sub-menu when clicking on notification link
    const notificationLink = document.getElementById('notification-link');
    notificationLink.addEventListener('click', function(e) {
        // The dropdown functionality is already handled by the existing code,
        // but we can add additional notification-specific behavior here if needed
    });
    
    // Get all navigation items without sub-navs
    const mainNavLinks = document.querySelectorAll('.nav-item:not(.has-subnav)');
    
    // Add click event listener to each main nav link without sub-nav
    mainNavLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Get the href attribute
            const href = this.getAttribute('href');
            
            // If it's a valid page, reload to that page
            if (href && href !== '#') {
                e.preventDefault();
                window.location.href = href;
            }
        });
    });
    
    // Function to update notification badges after marking as read
    window.updateNotificationBadges = function() {
        // Make an AJAX call to get updated notification counts
        fetch('/admin/pages/notification/functions/get-notification-counts.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update total notifications badge
            const totalBadge = document.getElementById('total-notifications-badge');
            if (data.total > 0) {
                if (totalBadge) {
                    totalBadge.textContent = data.total;
                } else {
                    // Create badge if it doesn't exist
                    const badgeContainer = document.querySelector('.dropdown-icon-container');
                    if (badgeContainer) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge-count';
                        newBadge.id = 'total-notifications-badge';
                        newBadge.textContent = data.total;
                        badgeContainer.appendChild(newBadge);
                    }
                }
            } else if (totalBadge) {
                totalBadge.remove();
            }
            
            // Update expiry badge
            const expiryBadge = document.getElementById('expiry-badge');
            const expiryTotal = data.expiring + data.expired;
            if (expiryTotal > 0) {
                if (expiryBadge) {
                    expiryBadge.textContent = expiryTotal;
                } else {
                    // Create badge if it doesn't exist
                    const expiryLink = document.getElementById('expiry-notifications-link');
                    if (expiryLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger sub-nav-badge';
                        newBadge.id = 'expiry-badge';
                        newBadge.textContent = expiryTotal;
                        expiryLink.appendChild(newBadge);
                    }
                }
            } else if (expiryBadge) {
                expiryBadge.remove();
            }
        })
        .catch(error => {
            console.error('Error updating notification badges:', error);
        });
    };

    // Mobile sidebar toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        
        if (window.innerWidth <= 991) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth > 991) {
            sidebar.classList.remove('active');
        }
    });
});
</script>