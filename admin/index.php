<?php
// session_start();
require_once '../config.php';


// function checkAdminSession() {
//     if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//         header("Location: ../log in/login.php");
//         exit();
//     }
// }
// checkAdminSession();
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