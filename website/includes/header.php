<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has valid session data
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);
$unreadNotificationsCount = 0;

// Initialize notification count if user is logged in
if ($isLoggedIn) {
    // Check if notification_queries.php exists and include it
    $notificationQueriesPath = __DIR__ . '/../notification_queries.php';
    if (file_exists($notificationQueriesPath)) {
        require_once $notificationQueriesPath;
        
        // Initialize notification session if not set
        if (!isset($_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'] = [
                'transactions' => [],
                'memberships' => [],
                'announcements' => []
            ];
            
            $pdo = $database->connect();
            $sql = "SELECT notification_type, notification_id 
                    FROM notification_reads 
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $db_reads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Populate session with database reads
            foreach ($db_reads as $read) {
                $type = $read['notification_type'];
                $id = (int)$read['notification_id'];
                
                if (isset($_SESSION['read_notifications'][$type])) {
                    $_SESSION['read_notifications'][$type][] = $id;
                }
            }
        }
        
        // Get unread notifications count - use the $database from config.php
        $unreadNotificationsCount = getUnreadNotificationsCount($database, $_SESSION['user_id']);
    }
}

// Function to update membership statuses
function updateMembershipStatuses($pdo) {
    // Update expired memberships
    $expiredQuery = "UPDATE 
                memberships m
            SET 
                m.status = 'expired'
            WHERE 
                m.status = 'active'
                AND m.end_date < CURDATE()";
    
    $expiredStmt = $pdo->query($expiredQuery);
    $expiredCount = $expiredStmt ? $expiredStmt->rowCount() : 0;
    
    // Flag expiring memberships (within 7 days)
    $expiringQuery = "UPDATE 
                memberships m
            SET 
                m.status = 'expiring'
            WHERE 
                m.status = 'active' 
                AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    
    $expiringStmt = $pdo->query($expiringQuery);
    $expiringCount = $expiringStmt ? $expiringStmt->rowCount() : 0;
    
    return ['expired' => $expiredCount, 'expiring' => $expiringCount];
}

// Function to update rental subscription statuses
function updateRentalStatuses($pdo) {
    // Update expired rental subscriptions
    $expiredQuery = "UPDATE 
                rental_subscriptions rs
            SET 
                rs.status = 'expired'
            WHERE 
                rs.status = 'active'
                AND rs.end_date < CURDATE()";
    
    $expiredStmt = $pdo->query($expiredQuery);
    $expiredCount = $expiredStmt ? $expiredStmt->rowCount() : 0;
    
    // Flag expiring rental subscriptions (within 7 days)
    $expiringQuery = "UPDATE 
                rental_subscriptions rs
            SET 
                rs.status = 'expiring'
            WHERE 
                rs.status = 'active' 
                AND rs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    
    $expiringStmt = $pdo->query($expiringQuery);
    $expiringCount = $expiringStmt ? $expiringStmt->rowCount() : 0;
    
    return ['expired' => $expiredCount, 'expiring' => $expiringCount];
}

// Run status updates if user is logged in and has admin or staff role
if ($isLoggedIn && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff'])) {
    if (isset($database)) {
        $pdo = $database->connect();
        updateMembershipStatuses($pdo);
        updateRentalStatuses($pdo);
    }
}

// Check for expired or expiring memberships to show popup
$showMembershipPopup = false;
$membershipDetails = null;
$membershipStatus = '';

if ($isLoggedIn) {
    // Get expired and expiring memberships for the current user if notification_queries.php is included
    if (function_exists('getMembershipNotifications')) {
        $membershipNotifications = getMembershipNotifications($database, $_SESSION['user_id']);
        
        // First check if user has any active membership
        $hasActiveMembership = false;
        
        // We need to add this function call to check for active memberships
        if (function_exists('getActiveMemberships')) {
            $activeMemberships = getActiveMemberships($database, $_SESSION['user_id']);
            $hasActiveMembership = count($activeMemberships) > 0;
        }
        
        // Only show popup if user doesn't have any active memberships
        if (!$hasActiveMembership) {
            // First check for expired memberships (higher priority)
            foreach ($membershipNotifications as $membership) {
                if ($membership['status'] === 'expired') {
                    // We have an expired membership, show the popup
                    $showMembershipPopup = true;
                    $membershipDetails = $membership;
                    $membershipStatus = 'expired';
                    break; // Just get the first expired membership
                }
            }
            
            // If no expired memberships found, check for expiring ones
            if (!$showMembershipPopup) {
                foreach ($membershipNotifications as $membership) {
                    if ($membership['status'] === 'expiring') {
                        // We have an expiring membership, show the popup
                        $showMembershipPopup = true;
                        $membershipDetails = $membership;
                        $membershipStatus = 'expiring';
                        break; // Just get the first expiring membership
                    }
                }
            }
        }
        
        // Check if user has dismissed this popup before
        if (isset($_SESSION['dismissed_membership_popup'])) {
            $showMembershipPopup = false;
        }
    }
}

// Handle Ajax request to dismiss the popup
if (isset($_POST['dismiss_membership_popup'])) {
    $_SESSION['dismissed_membership_popup'] = true;
    echo json_encode(['success' => true]);
    exit;
}
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
    require_once __DIR__ . '/../user_account/profile.class.php';
    $profile = new Profile_class();
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);
    $_SESSION['personal_details'] = $userDetails;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <?php
  // Check if we're in the coach folder
  $isCoachFolder = strpos($_SERVER['PHP_SELF'], '/coach/') !== false;
  $basePath = $isCoachFolder ? '../../' : '../';
  ?>
  <link rel="stylesheet" href="<?php echo $basePath; ?>css/landing1.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    .cart-btn {
        position: relative;
    }
    
    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #ff0000;
        color: white;
        border-radius: 50%;
        font-size: 12px;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 3px;
        font-weight: bold;
    }
    
    /* Hide the count when it's zero */
    .cart-count.empty {
        display: none;
    }
</style>

</head>
<body>

<nav class="home-navbar">
    <!-- This logo shows on desktop only -->
    <div class="home-logo">
        <img src="<?php echo $basePath; ?>cms_img/jc_logo1.png" alt="Gym Logo" class="logo-image">
    </div>
    
    <!-- Hamburger menu button -->
    <div class="hamburger-menu d-lg-none">
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div class="overlay d-lg-none"></div>
    
    <ul class="nav-links">
        <!-- Logo at the top of sidebar on mobile -->
        <div class="sidebar-logo">
            <img src="<?php echo $basePath; ?>cms_img/jc_logo1.png" alt="Gym Logo" class="logo-image">
        </div>
        
        <li><a href="<?php echo $isCoachFolder ? '../website.php' : 'website.php'; ?>">Home</a></li>
        <li><a href="<?php echo $isCoachFolder ? '../services.php' : 'services.php'; ?>">Services</a></li>
        <li><a href="<?php echo $isCoachFolder ? '../website.php#S-AboutUs' : 'website.php#S-AboutUs'; ?>">About</a></li>
        <li><a href="<?php echo $isCoachFolder ? '../website.php#S-ContactUs' : 'website.php#S-ContactUs'; ?>">Contact</a></li>
    </ul>

    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
            <?php 
            // Get the current page filename
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page === 'services.php'): 
            ?>
                <button class="cart-btn" id="showCartBtn" aria-label="Open Shopping Cart" title="Open Shopping Cart">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
            <?php endif; ?>
            
            <div class="dropdown">
                <button class="dropbtn" 
                        aria-label="User Menu" 
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="user-dropdown"
                        title="User Menu" 
                        style="background-image: url('<?php echo $basePath; ?><?php echo isset($_SESSION['user_photo']) ? $_SESSION['user_photo'] : 'cms_img/user.png'; ?>');">
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="notification-badge2"><?php echo $unreadNotificationsCount; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-content" 
                    id="user-dropdown" 
                    role="menu" 
                    aria-label="User menu options">
                    <?php if (isset($_SESSION['personal_details']['role_name']) && $_SESSION['personal_details']['role_name'] === 'coach'): ?>
                        <a href="<?php echo $isCoachFolder ? 'programs.php' : './coach/programs.php'; ?>" class="username" role="menuitem"><?php echo getFullName(); ?></a>
                    <?php else: ?>
                        <a href="profile.php" class="username" role="menuitem"><?php echo getFullName(); ?></a>
                    <?php endif; ?>
                    <hr class="dropdown-divider" aria-hidden="true">
                    <a href="<?php echo $isCoachFolder ? '../notifications.php' : 'notifications.php'; ?>">
                        <i class="fas fa-bell pe-3"></i> Notifications
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="notification-badge"><?php echo $unreadNotificationsCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="" class="edit-profile-link" role="menuitem"> <i class="fas fa-user-edit pe-3"></i> Edit Profile</a>
                    <a href="<?php echo $basePath; ?>login/logout.php" role="menuitem"> <i class="fas fa-sign-out-alt pe-3"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo $basePath; ?>login/login.php" class="home-signIn">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>
<!-- Profile Edit Modal -->
<div id="profileEditModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal"><i class="fas fa-times"></i></span>
        <h2>Edit Profile</h2>
        <div class="profile-edit-container">
            <div class="profile-photo-container">
                <div class="photo-wrapper">
                    <img src="<?php echo $basePath; ?><?php echo isset($_SESSION['user_photo']) ? $_SESSION['user_photo'] : 'cms_img/user.png'; ?>" 
                         alt="Profile Photo" 
                         id="profilePhoto">
                    <div class="photo-edit-overlay">
                        <label for="photoInput" class="edit-icon">
                            <i class="fas fa-pencil-alt"></i>
                        </label>
                        <input type="file" 
                               id="photoInput" 
                               accept="image/*" 
                               style="display: none;">
                    </div>
                </div>
            </div>
            <div class="username-container">
                <div class="input-wrapper">
                    <input type="text" 
                           id="usernameInput" 
                           value="<?php echo $_SESSION['username'] ?? ''; ?>" 
                           readonly>
                    <button class="edit-username-btn">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </div>
            </div>
            <div class="save-actions">
                <button id="saveChanges" class="save-btn">Save Changes</button>
                <button id="cancelChanges" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Membership Renewal Popup -->
<div id="membershipRenewalPopup" class="membership-popup" style="display: none;">
    <div class="membership-popup-content">
        <span class="close-popup"><i class="fas fa-times"></i></span>
        <div class="popup-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3 id="popupTitle">Membership Notice</h3>
        <p id="popupMessage"></p>
        <p>Renew now to continue enjoying our services without interruption!</p>
        <div class="popup-actions">
            <a href="<?php echo $isCoachFolder ? '../services.php' : 'services.php'; ?>" class="renew-btn">Renew Membership</a>
            <button id="dismissPopup" class="dismiss-btn">Don't Show Again</button>
        </div>
    </div>
</div>

<script>
    // Update cart count on page load
    document.addEventListener("DOMContentLoaded", function() {
        let cartCount = localStorage.getItem("cartTotal") || 0;
        document.getElementById("cartCount").textContent = cartCount;
    });


    document.addEventListener('DOMContentLoaded', function() {
  const hamburger = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');
  const overlay = document.querySelector('.overlay');
  
  hamburger.addEventListener('click', function() {
    hamburger.classList.toggle('active');
    navLinks.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.classList.toggle('no-scroll');
  });
  
  // Close menu when clicking on overlay
  overlay.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navLinks.classList.remove('active');
    overlay.classList.remove('active');
    document.body.classList.remove('no-scroll');
  });
  
  // Close menu when clicking on a link
  const links = document.querySelectorAll('.nav-links a');
  links.forEach(link => {
    link.addEventListener('click', function() {
      hamburger.classList.remove('active');
      navLinks.classList.remove('active');
      overlay.classList.remove('active');
      document.body.classList.remove('no-scroll');
    });
  });
});
document.addEventListener('DOMContentLoaded', function() {
    // Show membership renewal popup if needed
    <?php if ($showMembershipPopup && $membershipDetails): ?>
    const popup = document.getElementById('membershipRenewalPopup');
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');
    const popupIcon = document.querySelector('.popup-icon i');
    
    // Set membership details based on status
    const membershipStatus = '<?php echo $membershipStatus; ?>';
    const planName = '<?php echo htmlspecialchars($membershipDetails['plan_name']); ?>';
    const dateFormatted = '<?php echo date('F j, Y', strtotime($membershipDetails['end_date'])); ?>';
    
    if (membershipStatus === 'expired') {
        popupTitle.textContent = 'Membership Expired';
        popupMessage.innerHTML = `Your <strong>${planName}</strong> membership expired on <strong>${dateFormatted}</strong>.`;
        popupIcon.style.color = '#e74c3c'; // Red for expired
        document.querySelector('.membership-popup-content').classList.add('expired');
    } else if (membershipStatus === 'expiring') {
        popupTitle.textContent = 'Membership Expiring Soon';
        popupMessage.innerHTML = `Your <strong>${planName}</strong> membership will expire on <strong>${dateFormatted}</strong>.`;
        popupIcon.style.color = '#f39c12'; // Orange for expiring
        popupIcon.className = 'fas fa-clock';
        document.querySelector('.membership-popup-content').classList.add('expiring');
    }
    
    // Show popup with a slight delay for better UX
    setTimeout(() => {
        popup.style.display = 'flex';
    }, 1500);
    
    // Close button functionality
    document.querySelector('.close-popup').addEventListener('click', function() {
        popup.style.display = 'none';
    });
    
    // Dismiss button functionality
    document.getElementById('dismissPopup').addEventListener('click', function() {
        // Send AJAX request to dismiss the popup
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                popup.style.display = 'none';
            }
        };
        xhr.send('dismiss_membership_popup=1');
    });
    <?php endif; ?>
});
</script>
<script src="<?php echo $isCoachFolder ? '../../website/js/edit_profile.js' : 'js/edit_profile.js'; ?>"></script>
<script src="<?php echo $isCoachFolder ? '../../website/js/dropdown.js' : 'js/dropdown.js'; ?>"></script>