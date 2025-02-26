<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config file
require_once '../config.php';
// Now you have access to the $database variable from config.php

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
            
            // Use the database instance from config.php
            // $database is already available from the require_once above
            
            // Load read notifications from database
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
</head>
<body>

<nav class="home-navbar">
    <div class="home-logo">
        <img src="<?php echo $basePath; ?>cms_img/jc_logo1.png" alt="Gym Logo" class="logo-image">
    </div>
    <ul class="nav-links">
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
<script src="<?php echo $isCoachFolder ? '../../website/js/edit_profile.js' : 'js/edit_profile.js'; ?>"></script>
<script src="<?php echo $isCoachFolder ? '../../website/js/dropdown.js' : 'js/dropdown.js'; ?>"></script>