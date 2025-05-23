<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'DatabaseExtended.php';
// Check if user is logged in and has valid session data
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);
$unreadNotificationsCount = 0;

// Initialize notification count if user is logged in
if ($isLoggedIn) {
    // Check if notification_queries.php exists and include it
    $notificationQueriesPath = __DIR__ . '/../notification_queries.php';
    if (file_exists($notificationQueriesPath)) {
        require_once $notificationQueriesPath;

        // Always refresh notification session from DB on login
        $_SESSION['read_notifications'] = [
            'transactions' => [],
            'memberships' => [],
            'announcements' => [],
            'program_confirmations' => [],
            'program_cancellations' => [],
            'cancelled_sessions' => [],
            'completed_sessions' => [],
            'transaction_receipts' => []
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

/**
 * Checks for pending transactions (memberships, walk-ins, rentals)
 * where the start date has passed the current date for a specific user.
 * Removes only the expired associated services and only removes 
 * transactions if they have no remaining associated services.
 *
 * @param PDO $pdo The database connection object.
 * @param int $userId The ID of the user to check transactions for.
 * @return array An array of removed service details.
 */
function checkAndRemovePendingTransactions($pdo, $userId) {
    $removedServices = [];
    $transactionsToCheck = [];
    $currentDate = date('Y-m-d');

    // --- Check Memberships ---
    $sqlMemberships = "SELECT
                           t.id AS transaction_id,
                           m.id AS membership_id,
                           mp.plan_name AS service_name,
                           m.start_date AS service_start_date,
                           t.created_at AS transaction_added_date
                       FROM
                           transactions t
                       JOIN
                           memberships m ON t.id = m.transaction_id
                       JOIN
                           membership_plans mp ON m.membership_plan_id = mp.id
                       WHERE
                           t.user_id = :userId
                           AND t.status = 'pending'
                           AND m.is_paid = 0
                           AND m.start_date < :currentDate";

    $stmtMemberships = $pdo->prepare($sqlMemberships);
    $stmtMemberships->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtMemberships->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmtMemberships->execute();
    $pendingMemberships = $stmtMemberships->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pendingMemberships as $membership) {
        // Delete the specific membership
        $sqlDeleteMembership = "DELETE FROM memberships WHERE id = :membershipId";
        $stmtDeleteMembership = $pdo->prepare($sqlDeleteMembership);
        $stmtDeleteMembership->bindParam(':membershipId', $membership['membership_id'], PDO::PARAM_INT);
        $stmtDeleteMembership->execute();
        
        // Add to removed services list
        $removedServices[] = [
            'type' => 'Membership',
            'service_name' => $membership['service_name'],
            'start_date' => $membership['service_start_date'],
            'added_date' => $membership['transaction_added_date'],
            'transaction_id' => $membership['transaction_id']
        ];
        
        // Add transaction to check list
        $transactionsToCheck[$membership['transaction_id']] = true;
    }

    // --- Check Walk-in Records ---
    $sqlWalkins = "SELECT
                       t.id AS transaction_id,
                       wr.id AS walkin_id,
                       'Walk-in' AS service_name,
                       wr.date AS service_start_date,
                       t.created_at AS transaction_added_date
                   FROM
                       transactions t
                   JOIN
                       walk_in_records wr ON t.id = wr.transaction_id
                   WHERE
                       t.user_id = :userId
                       AND t.status = 'pending'
                       AND wr.is_paid = 0
                       AND wr.date < :currentDate";

    $stmtWalkins = $pdo->prepare($sqlWalkins);
    $stmtWalkins->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtWalkins->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmtWalkins->execute();
    $pendingWalkins = $stmtWalkins->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pendingWalkins as $walkin) {
        // Delete the specific walk-in record
        $sqlDeleteWalkin = "DELETE FROM walk_in_records WHERE id = :walkinId";
        $stmtDeleteWalkin = $pdo->prepare($sqlDeleteWalkin);
        $stmtDeleteWalkin->bindParam(':walkinId', $walkin['walkin_id'], PDO::PARAM_INT);
        $stmtDeleteWalkin->execute();
        
        // Add to removed services list
        $removedServices[] = [
            'type' => 'Walk-in',
            'service_name' => $walkin['service_name'],
            'start_date' => $walkin['service_start_date'],
            'added_date' => $walkin['transaction_added_date'],
            'transaction_id' => $walkin['transaction_id']
        ];
        
        // Add transaction to check list
        $transactionsToCheck[$walkin['transaction_id']] = true;
    }

    // --- Check Rental Subscriptions ---
    $sqlRentals = "SELECT
                        t.id AS transaction_id,
                        rs.id AS rental_id,
                        rsrv.service_name AS service_name,
                        rs.start_date AS service_start_date,
                        t.created_at AS transaction_added_date
                    FROM
                        transactions t
                    JOIN
                        rental_subscriptions rs ON t.id = rs.transaction_id
                    JOIN
                        rental_services rsrv ON rs.rental_service_id = rsrv.id
                    WHERE
                        t.user_id = :userId
                        AND t.status = 'pending'
                        AND rs.is_paid = 0
                        AND rs.start_date < :currentDate";

    $stmtRentals = $pdo->prepare($sqlRentals);
    $stmtRentals->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtRentals->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmtRentals->execute();
    $pendingRentals = $stmtRentals->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pendingRentals as $rental) {
        // Delete the specific rental subscription
        $sqlDeleteRental = "DELETE FROM rental_subscriptions WHERE id = :rentalId";
        $stmtDeleteRental = $pdo->prepare($sqlDeleteRental);
        $stmtDeleteRental->bindParam(':rentalId', $rental['rental_id'], PDO::PARAM_INT);
        $stmtDeleteRental->execute();
        
        // Add to removed services list
        $removedServices[] = [
            'type' => 'Rental Subscription',
            'service_name' => $rental['service_name'],
            'start_date' => $rental['service_start_date'],
            'added_date' => $rental['transaction_added_date'],
            'transaction_id' => $rental['transaction_id']
        ];
        
        // Add transaction to check list
        $transactionsToCheck[$rental['transaction_id']] = true;
    }

    // Now check if any transactions need to be deleted (no remaining services)
    if (!empty($transactionsToCheck)) {
        foreach (array_keys($transactionsToCheck) as $transactionId) {
            // Check if any services are still associated with this transaction
            $sqlCheckServices = "SELECT 
                                  (SELECT COUNT(*) FROM memberships WHERE transaction_id = :transId) +
                                  (SELECT COUNT(*) FROM walk_in_records WHERE transaction_id = :transId) +
                                  (SELECT COUNT(*) FROM rental_subscriptions WHERE transaction_id = :transId) AS service_count";
            
            $stmtCheckServices = $pdo->prepare($sqlCheckServices);
            $stmtCheckServices->bindParam(':transId', $transactionId, PDO::PARAM_INT);
            $stmtCheckServices->execute();
            $serviceCount = (int)$stmtCheckServices->fetchColumn();
            
            // If no services remain, delete the transaction
            if ($serviceCount === 0) {
                $sqlDeleteTransaction = "DELETE FROM transactions WHERE id = :transId";
                $stmtDeleteTransaction = $pdo->prepare($sqlDeleteTransaction);
                $stmtDeleteTransaction->bindParam(':transId', $transactionId, PDO::PARAM_INT);
                $stmtDeleteTransaction->execute();
            }
        }
    }

    return $removedServices;
}


// Run status updates if user is logged in and has admin or staff role
if ($isLoggedIn && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff'])) {
    if (isset($database)) {
        $pdo = $database->connect();
        updateMembershipStatuses($pdo);
        updateRentalStatuses($pdo);
    }
}

// --- Check and remove pending transactions for logged-in users ---
if ($isLoggedIn && isset($database)) {
    try {
        $pdo = $database->connect();
        $removedTransactions = checkAndRemovePendingTransactions($pdo, $_SESSION['user_id']);

        if (!empty($removedTransactions)) {
            // Prepare a notification message
            $notificationMessage = "The following pending transactions have been automatically removed because their start date has passed:<br>";
            $notificationMessage .= "<ul>";
            foreach ($removedTransactions as $transaction) {
                $notificationMessage .= "<li><strong>" . htmlspecialchars($transaction['service_name']) . "</strong> (" . htmlspecialchars($transaction['type']) . ") - Supposed Start Date: " . htmlspecialchars($transaction['start_date']) . ", Added On: " . htmlspecialchars($transaction['added_date']) . "</li>";
            }
            $notificationMessage .= "</ul>";

            // Store the notification message in the session to be displayed later
            $_SESSION['transaction_removal_notification'] = $notificationMessage;
        }
    } catch (PDOException $e) {
        // Log the error or handle it appropriately
        error_log("Database error during pending transaction check: " . $e->getMessage());
        // Optionally, set a generic error message for the user
        // $_SESSION['transaction_removal_error'] = "An error occurred while checking your transactions.";
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
        return 'Admin';
    }
}

// If user is logged in but personal_details is not set, fetch them
if ($isLoggedIn && !isset($_SESSION['personal_details'])) {
    require_once __DIR__ . '/../user_account/profile.class.php';
    $profile = new Profile_class();
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);
    $_SESSION['personal_details'] = $userDetails;
}
require_once 'DatabaseExtended.php';
// Get the logo and color
$logo = getWebsiteContent('logo');
$color = getWebsiteContent('color');

$primaryHex = isset($color['latitude']) ? decimalToHex($color['latitude']) : '#000000';
$secondaryHex = isset($color['longitude']) ? decimalToHex($color['longitude']) : '#000000';
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
  <link rel="icon" href="../cms_img/icon_xlb.png">
  <link rel="stylesheet" href="<?php echo $basePath; ?>css/landing1.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    :root {
    --primary-color: <?php echo $primaryHex; ?>;
    --secondary-color: <?php echo $secondaryHex; ?>;
    }
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

    /* Styles for the transaction removal notification popup */
    .transaction-notification-popup {
        display: none; /* Hidden by default */
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f8d7da; /* Light red background */
        color: #721c24; /* Dark red text */
        border: 1px solid #f5c6cb; /* Red border */
        border-radius: 5px;
        padding: 15px;
        z-index: 2000; /* Ensure it's above other content */
        max-width: 500px;
        width: 90%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .transaction-notification-popup .close-btn {
        position: absolute;
        top: 5px;
        right: 10px;
        color: #721c24;
        font-size: 20px;
        cursor: pointer;
    }

    .transaction-notification-popup ul {
        margin-top: 10px;
        padding-left: 20px;
    }

    .transaction-notification-popup li {
        margin-bottom: 5px;
    }
</style>

</head>
<body>

<nav class="home-navbar">
    <div class="home-logo">
    <?php if (!empty($logo['location'])): ?>
        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($logo['location']); ?>" alt="Gym Logo" class="logo-image">
    <?php else: ?>
        <img src="<?php echo BASE_URL; ?>/assets/images/default-logo.png" alt="Gym Logo" class="logo-image">
    <?php endif; ?>
</div>

    <div class="hamburger-menu d-lg-none">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="overlay d-lg-none"></div>

    <ul class="nav-links">
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
                    <?php if (isset($_SESSION['personal_details']['role_name']) && ($_SESSION['personal_details']['role_name'] === 'coach' || $_SESSION['personal_details']['role_name'] === 'coach/staff')): ?>
                        <a href="<?php echo $isCoachFolder ? 'programs.php' : './coach/dashboard.php'; ?>" class="username" role="menuitem"><?php echo getFullName(); ?></a>
                    <?php else: ?>
                        <a href="profile.php" class="username" role="menuitem"><?php echo getFullName(); ?></a>
                    <?php endif; ?>
                    <hr class="dropdown-divider" style="margin: 5px auto; border-top: 1px solid rgba(0,0,0,0.2); width: 90%;">

                    <?php if ((isset($_SESSION['personal_details']['role_name']) &&
                            ($_SESSION['personal_details']['role_name'] === 'admin' ||
                            $_SESSION['personal_details']['role_name'] === 'staff' ||
                            $_SESSION['personal_details']['role_name'] === 'coach/staff')) ||
                            (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                        <a href="<?php echo $basePath; ?>admin" role="menuitem">
                            <i class="fas fa-user-shield pe-3"></i> Go to Admin
                        </a>
                    <?php endif; ?>

                    <a class="notifications-btn" href="<?php echo $isCoachFolder ? '../notifications.php' : 'notifications.php'; ?>">
                        <i class="fas fa-bell pe-3"></i> Notifications
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="notification-badge"><?php echo $unreadNotificationsCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#" onclick="showLogoutConfirmation(event)" role="menuitem"> <i class="fas fa-sign-out-alt pe-3"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo $basePath; ?>login/login.php" class="home-LogIn">Log In</a>
            <a href="<?php echo $basePath; ?>register/register.php" class="home-signIn">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<div id="logoutModal" class="modal" style="display: none; position: fixed; z-index: 2500; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fff; padding: 20px; border-radius: 5px; width: 300px; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
        <h4>Confirm Logout</h4>
        <p>Are you sure you want to logout?</p>
        <div style="margin-top: 20px;">
            <a href="<?php echo $basePath; ?>login/logout.php" class="btn btn-danger" style="margin-right: 10px;">Yes, Logout</a>
            <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<style>
@media screen and (max-width: 480px) {
    #logoutModal .modal-content {
        width: 90%;
        padding: 15px;
        font-size: 14px;
    }
    #logoutModal .modal-content h4 {
        font-size: 18px;
    }
    #logoutModal .modal-content p {
        font-size: 14px;
    }
    #logoutModal .modal-content .btn {
        font-size: 12px;
        padding: 8px 12px;
    }
}
</style>

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

<div id="transactionRemovalPopup" class="transaction-notification-popup">
    <span class="close-btn" onclick="closeTransactionRemovalPopup()">&times;</span>
    <div id="transactionRemovalMessage"></div>
</div>


<script>
function showLogoutConfirmation(event) {
    event.preventDefault();
    document.getElementById('logoutModal').style.display = 'block';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Update cart count on page load
document.addEventListener("DOMContentLoaded", function() {
    let cartCount = localStorage.getItem("cartTotal") || 0;
    const cartCountElement = document.getElementById("cartCount");
        if (cartCountElement) {
            cartCountElement.textContent = cartCount;
        }

    // Show transaction removal notification if present in session
    const transactionRemovalMessage = <?php echo json_encode(isset($_SESSION['transaction_removal_notification']) ? $_SESSION['transaction_removal_notification'] : null); ?>;
    if (transactionRemovalMessage) {
        document.getElementById('transactionRemovalMessage').innerHTML = transactionRemovalMessage;
        document.getElementById('transactionRemovalPopup').style.display = 'block';
        // Clear the session variable after displaying
        <?php unset($_SESSION['transaction_removal_notification']); ?>
    }
});

function closeTransactionRemovalPopup() {
    document.getElementById('transactionRemovalPopup').style.display = 'none';
}


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
<script src="<?php echo $isCoachFolder ? '../../website/js/dropdown.js' : 'js/dropdown.js'; ?>"></script>
