<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'user_account/coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    $message = '';

    // Handle status toggle
    if (isset($_POST['toggle_status']) && isset($_POST['program_id'])) {
        $currentStatus = $_POST['current_status'];
        $programId = $_POST['program_id'];
        if ($coach->toggleProgramStatus($programId, $_SESSION['user_id'], $currentStatus)) {
            echo "<script>alert('Program status updated successfully!');</script>";
        } else {
            echo "<script>alert('Failed to update program status.');</script>";
        }
    }

    $coachPrograms = $coach->getCoachPrograms($_SESSION['user_id']);
    
    // Debug information
    error_log("Coach ID: " . $_SESSION['user_id']);
    error_log("Number of programs found: " . count($coachPrograms));

    include('includes/header.php');
?>

<!-- Add required CSS for calendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<!-- Add required JS for calendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<style>
    .wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
    }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        min-height: 100vh;
        background: #343a40;
        color: #fff;
        transition: all 0.3s;
    }

    #sidebar .sidebar-header {
        padding: 20px;
        background: #2c3136;
    }

    #sidebar ul.components {
        padding: 20px 0;
    }

    #sidebar ul li a {
        padding: 10px 20px;
        font-size: 1.1em;
        display: block;
        color: #fff;
        text-decoration: none;
    }

    #sidebar ul li a:hover {
        background: #2c3136;
    }

    #content {
        width: 100%;
        padding: 20px;
        min-height: 100vh;
    }

    #calendar {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .active-nav {
        background: #2c3136;
    }
</style>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><?= $_SESSION['personal_details']['name'] ?></h3>
            <h5>Coach</h5>
        </div>

        <ul class="list-unstyled components">
            <li>
                <a href="#" class="active-nav" onclick="showSection('programs'); return false;">
                    <i class="fas fa-dumbbell"></i> My Programs
                </a>
            </li>
            <li>
                <a href="#" onclick="showSection('members'); return false;">
                    <i class="fas fa-users"></i> Program Members
                </a>
            </li>
            <li>
                <a href="#" onclick="showSection('calendar'); return false;">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Programs Section -->
        <div id="programs-section">
            <div class="card">
                <div class="card-header">
                    <h4>My Programs</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Program Name</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($coachPrograms)): ?>
                                    <?php foreach ($coachPrograms as $program): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($program['program_name']) ?></td>
                                            <td><?= htmlspecialchars($program['description']) ?></td>
                                            <td><?= htmlspecialchars($program['program_duration']) ?></td>
                                            <td>₱<?= number_format($program['program_price'], 2) ?></td>
                                            <td>
                                                <?php if ($program['program_status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="program_id" value="<?= $program['program_id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $program['program_status'] ?>">
                                                    <?php if ($program['program_status'] === 'active'): ?>
                                                        <button type="submit" name="toggle_status" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-times-circle"></i> Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_status" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check-circle"></i> Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No programs available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Section -->
        <div id="members-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h4>Program Members</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Program</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $programMembers = $coach->getProgramMembers($_SESSION['user_id']);
                                if (!empty($programMembers)): 
                                ?>
                                    <?php foreach ($programMembers as $member): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($member['member_name']) ?></td>
                                            <td><?= htmlspecialchars($member['program_name']) ?></td>
                                            <td><?= htmlspecialchars($member['program_duration']) ?></td>
                                            <td>₱<?= number_format($member['program_price'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($member['start_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($member['end_date'])) ?></td>
                                            <td>
                                                <?php if (strtotime($member['end_date']) >= time()): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No members enrolled in your programs</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div id="calendar-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h4>Schedule Calendar</h4>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: [] // You can add events here
        });
        calendar.render();
    });

    // Function to switch between sections
    function showSection(sectionName) {
        // Hide all sections
        document.getElementById('programs-section').style.display = 'none';
        document.getElementById('members-section').style.display = 'none';
        document.getElementById('calendar-section').style.display = 'none';

        // Show selected section
        document.getElementById(sectionName + '-section').style.display = 'block';

        // Update active state in sidebar
        document.querySelectorAll('#sidebar a').forEach(a => a.classList.remove('active-nav'));
        document.querySelector(`#sidebar a[onclick*="${sectionName}"]`).classList.add('active-nav');

        // Refresh calendar if calendar section is shown
        if (sectionName === 'calendar') {
            window.dispatchEvent(new Event('resize'));
        }
    }
</script>