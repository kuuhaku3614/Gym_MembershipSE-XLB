<div class="sidebar" id="sidebar">
        <!-- Logo and Admin Container -->
        <div class="logo-container">
            <img src="../cms_img/jc_logo_2.png" alt="Gym Logo" class="logo-image">
            <div class="admin-text">ADMIN</div>
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

                <!-- Notifications Section -->
                <a href="notification" id="notification-link" class="nav-item has-subnav">
                    <div class="nav-item-content">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </div>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <div class="sub-nav">
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

                <a href="website_settings" id="website_settings-link" class="nav-item">
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
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <div class="sub-nav">
                    <a href="activity_log" id="activity_log-link" class="sub-nav-item">
                        <i class="fas fa-user-clock"></i>
                        Staff Activity Log
                    </a>
                    <a href="coach_log" id="coach_log-link" class="sub-nav-item">
                        <i class="fas fa-user-tie"></i>
                        Coach Activity Log
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Logout Container -->
        <div class="logout-container">
            <a href="../login/logout.php" class="nav-item">
                <div class="nav-item-content">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </div>
            </a>
        </div>
    </div>
