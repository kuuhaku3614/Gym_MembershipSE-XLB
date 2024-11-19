<?php
// Start the session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Handle logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: website.php');
    exit(); // Ensure the script stops after redirecting
}

// Function to ensure login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/login.php');
        exit(); // Prevent further execution
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <link rel="stylesheet" href="../css/landing1.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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

    <?php if ($isLoggedIn): ?>
        <div class="dropdown">
                <button class="dropbtn"></button>
                <div class="dropdown-content">
                    <a href="profile.php" class="username"><?php echo getFullName();?></a>
                    <hr>
                    <a href="#"> Notifications</a>
                    <a href="?logout=1"> Logout</a>
                </div>
            </div>
        <?php else: ?>
          <a href="../login/login.php" class="home-signIn">Sign In</a>
        <?php endif; ?>
    </nav>