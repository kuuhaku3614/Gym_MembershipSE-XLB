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
    // Function to get the full name of the user
    function getFullName() {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];

            // Database connection
            $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Fetch first and last name
            $stmt = $conn->prepare("SELECT first_name, last_name FROM personal_details WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                $conn->close();
                return htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
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
  <link rel="stylesheet" href="../css/landing1.css">
  <link rel="stylesheet" href="../css/browse_services.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="home-navbar">
    <div class="home-logo">
        <img src="" alt="logo" />
    </div>
    <ul class="nav-links">
        <li><a href="website.php">Home</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="#S-About">About</a></li>
        <li><a href="#S-ContactUs">Contact</a></li>
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
                <button class="dropbtn" aria-label="User Menu" title="User Menu">
                </button>
                <div class="dropdown-content">
                    <a href="profile.php" class="username"><?php echo getFullName(); ?></a>
                    <hr>
                    <a href="#"> Notifications</a>
                    <a href="?logout=1"> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="../login/login.php" class="home-signIn">Sign In</a>
        <?php endif; ?>
    </div>
</nav>