<?php
// session_start();
// require_once 'config.php';

// function checkAdminSession() {
//     if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//         header("Location: ../log in/login.php");
//         exit();
//     }
// }
// checkAdminSession();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/css/navbar.css">
</head>
<body>
    <!-- Burger Menu Button -->
    <button class="burger-menu" id="burgerMenu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php
        require_once '../admin/nav/navbar.php';
    ?>
    
     <!-- Main Content Container -->
    <div class="main-content">
        <!-- dynamic fill -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../admin/js/navbar.js"></script>
</body>
</html>