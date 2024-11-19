<?php
    
    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);

    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: website.php');
    }

    // Function to check if user is logged in and redirect if not
    function requireLogin() {
      if (!isset($_SESSION['user_id'])) {
          header('Location: ../login/login.php');
          exit();
      }
    }

    if (isset($_GET['logout'])) {
      session_destroy();
      header('Location: website.php');
      exit();
    }

    // Handle Buy Now button click
    if (isset($_GET['services'])) {
      requireLogin();
      // Redirect to buy page if logged in
      header('Location: services.php');
      exit();
    }

    // function getFullName() {
    //     if (isset($_SESSION['user_id'])) {
    //         return $_SESSION['user_id']['first_name'] . ' ' . $_SESSION['user_id']['last_name'];
    //     }
    //     return '';
    // }

    function getFullName() {
        if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; // Retrieve the logged-in user's ID from the session
    
            // Database connection (replace with your database credentials)
            $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
    
            // Prepare a SQL query to fetch the first and last name
            $stmt = $conn->prepare("SELECT first_name, last_name FROM personal_details WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                // Concatenate and return the full name
                return $row['first_name'] . ' ' . $row['last_name'];
            }
    
            // Close connections
            $stmt->close();
            $conn->close();
    
            // Return a default value if the user is not found
            return 'Unknown User';
        }
    
        // Handle case where user_id is not set or invalid
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