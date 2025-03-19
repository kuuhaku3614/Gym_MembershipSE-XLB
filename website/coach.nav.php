<?php
    require_once 'coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    include('includes/header.php');
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
?>

<style>
    html {
        background-color: transparent;
    }
    body {
        max-height: 100vh;
        background-color: #efefef!important;
    }
    .home-navbar {
        background-color:var(--primary-color);
        position: fixed;
        border-radius: 0;
        width: 100%;
        z-index: 1000;
    }
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 80px; /* Match header height */
        height: calc(100vh - 60px); /* Adjust for header height */
        width: 250px;
        background: #ffffff;
        color: #404040;
        transition: 0.3s;
        z-index: 0;
        display: flex;
        flex-direction: column;
    }

    th{
        font-weight: 500;
        font-size: 1.25em;
    }
    td{
        font-family: "Inter", sans-serif!important;
        font-weight: 600;
    }

    h4{
        font-weight: 500;
        font-size: 1.75em;
    }

    .logo-container {
        padding: 20px;
        text-align: center;
        background: #ffffff;
        color: #404040;
        border-right: 1px solid #ccc;

    }

    .admin-text {
        font-size: 1.5em;
        font-weight: bold;
    }

    .nav-links-container {
        flex: 1;
        overflow-y: auto;
        border-right: 1px solid #ccc;
    }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color:rgb(82, 82, 82);
        text-decoration: none;
        transition: 0.2s;
    }

    .nav-item:hover {
        background: #efefef;
    }

    .nav-item.active {
        color: #000000;
    }

    .nav-item-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-item i {
        width: 20px;
        text-align: center;
    }

    .logout-container {
        padding: 20px;
        border-top: 1px solid #2c3136;
    }

    /* Burger Menu and Overlay */
    .burger-menu {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: none;
        border: none;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    /* Content Area */
    #content {
        margin-left: 250px;
        padding: 20px;
        transition: 0.3s;
        margin-top: 80px; /* Match header height */
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .burger-menu {
            display: block;
        }

        .sidebar-overlay.active {
            display: block;
        }

        #content {
            margin-left: 0;
        }
    }
</style>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Logo and Coach Container -->
    <div class="logo-container">
        <div class="admin-text"><?= $_SESSION['personal_details']['name'] ?></div>
        <div>Coach</div>
    </div>

    <!-- Navigation Links Container -->
    <div class="nav-links-container">
        <nav>
            <a href="dashboard.php" id="dashboard-link" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <div class="nav-item-content">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </div>
            </a>

            <a href="programs.php" id="programs-link" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'programs.php' ? 'active' : '' ?>">
                <div class="nav-item-content">
                    <i class="fas fa-dumbbell"></i>
                    My Programs
                </div>
            </a>

            <a href="members.php" id="members-link" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'members.php' ? 'active' : '' ?>">
                <div class="nav-item-content">
                    <i class="fas fa-users"></i>
                    Program Members
                </div>
            </a>

            <a href="calendar.php" id="calendar-link" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'calendar.php' ? 'active' : '' ?>">
                <div class="nav-item-content">
                    <i class="fas fa-calendar"></i>
                    Calendar
                </div>
            </a>

            <a href="transactions.php" id="transactions-link" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : '' ?>">
                <div class="nav-item-content">
                    <i class="fas fa-money-bill"></i>
                    Transactions
                </div>
            </a>
        </nav>
    </div>


</div>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- Burger Menu -->
<button id="burgerMenu" class="burger-menu">
    <i class="fas fa-bars"></i>
</button>