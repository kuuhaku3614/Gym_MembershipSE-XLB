<?php
    session_start();
    require_once '../coach.class.php';
    require_once __DIR__ . '/../../config.php';

    // Set timezone to Asia/Manila
    date_default_timezone_set('Asia/Manila');

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    $members = $coach->getProgramMembers($_SESSION['user_id']);
    include('../coach.nav.php');
    require_once 'dashboard.class.php';
    
    // Handle the week filter
    $selectedWeek = isset($_GET['week']) ? $_GET['week'] : 'current';
?>

<link rel="stylesheet" href="dashboard.css">

<!-- Content Area -->
<div id="content">
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-chart-line"></i> Coaching Dashboard</h4>
                <div>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-calendar-day"></i> <?php echo date('F d, Y l'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php elseif ($hasErrors): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i> There were errors fetching some data. Please check your database connection.
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['activeSubscriptions'] ?? 0; ?></div>
                    <div class="stat-label">Active Subscriptions</div>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['scheduledSessions'] ?? 0; ?></div>
                    <div class="stat-label">Scheduled Sessions</div>
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($stats['revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Expected Revenue</div>
                </div>
            </div>
            
            <div class="col-md-6 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['personalSessions'] ?? 0; ?></div>
                    <div class="stat-label">Personal Sessions</div>
                </div>
            </div>
            
            <div class="col-md-6 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['groupSessions'] ?? 0; ?></div>
                    <div class="stat-label">Group Sessions</div>
                </div>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-calendar-alt"></i> Upcoming Sessions (Next 48 Hours)
            </div>
            <div class="card-body">
                <?php if (empty($upcomingSessions) || isset($upcomingSessions['error'])): ?>
                    <div class="alert alert-info">No upcoming sessions in the next 48 hours.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Member</th>
                                    <th>Program</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingSessions as $session): 
                                    // Calculate if session is current (today and within time range)
                                    $sessionDate = new DateTime($session['date']);
                                    $today = new DateTime();
                                    $isToday = $sessionDate->format('Y-m-d') === $today->format('Y-m-d');
                                    
                                    $sessionStartTime = new DateTime($session['date'] . ' ' . $session['start_time']);
                                    $sessionEndTime = new DateTime($session['date'] . ' ' . $session['end_time']);
                                    $now = new DateTime();
                                    
                                    $sessionInProgress = ($now >= $sessionStartTime && $now <= $sessionEndTime);
                                    $sessionCompleted = ($now > $sessionEndTime);
                                    $canCancel = !$sessionInProgress && !$sessionCompleted && $session['status'] !== 'cancelled';
                                    $canMarkAttendance = $isToday && $sessionInProgress && $session['status'] === 'scheduled';
                                ?>
                                    <tr class="<?php echo $session['status'] === 'cancelled' ? 'table-danger' : ($session['status'] === 'completed' ? 'table-success' : ''); ?>">
                                        <td><?php echo date('M d, Y', strtotime($session['date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle">
                                                    <?php echo strtoupper(substr($session['username'], 0, 1)); ?>
                                                </div>
                                                <span class="ms-2"><?php echo htmlspecialchars($session['username']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($session['program_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $session['session_type'] === 'Personal' ? 'badge-personal' : 'badge-group'; ?>">
                                                <?php echo $session['session_type']; ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($session['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                if ($session['status'] === 'cancelled') {
                                                    echo 'badge-cancelled';
                                                } elseif ($session['status'] === 'completed') {
                                                    echo 'badge-completed';
                                                } else {
                                                    echo $session['is_paid'] ? 'badge-paid' : 'badge-unpaid';
                                                }
                                            ?>">
                                                <?php 
                                                if ($session['status'] === 'cancelled') {
                                                    echo 'Cancelled';
                                                } elseif ($session['status'] === 'completed') {
                                                    echo 'Completed';
                                                } else {
                                                    echo $session['is_paid'] ? 'Paid' : 'Unpaid';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($canCancel): ?>
                                                <button class="btn btn-sm btn-outline-danger session-action-btn" 
                                                        data-action="cancel" 
                                                        data-session-id="<?php echo $session['id']; ?>"
                                                        data-bs-toggle="tooltip"
                                                        title="Cancel Session">
                                                    <i class="fas fa-times-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($canMarkAttendance): ?>
                                                <button class="btn btn-sm btn-outline-success session-action-btn"
                                                        data-action="complete"
                                                        data-session-id="<?php echo $session['id']; ?>"
                                                        data-bs-toggle="tooltip"
                                                        title="Mark as Completed">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($session['status'] === 'cancelled'): ?>
                                                <span class="text-danger"><i class="fas fa-ban"></i> Cancelled</span>
                                            <?php elseif ($session['status'] === 'completed'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card h-100" id="weekly-schedule-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-week"></i> This Week's Schedule
                        </div>
                        <div>
                            <select class="form-select form-select-sm week-selector" id="weekSelector" style="width: auto;">
                                <option value="current" <?php echo $selectedWeek == 'current' ? 'selected' : ''; ?>>Current Week</option>
                                <option value="next" <?php echo $selectedWeek == 'next' ? 'selected' : ''; ?>>Next Week</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="weekly-schedule">
                            <?php
                            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $currentDay = date('l');
                            
                            // Get the date for each day of the current week
                            $today = new DateTime();
                            $dayOfWeek = $today->format('N') - 1; // 0 (for Monday) through 6 (for Sunday)
                            $monday = (new DateTime())->modify('-' . $dayOfWeek . ' days');
                            
                            // If next week is selected, add 7 days to the Monday date
                            if ($selectedWeek == 'next') {
                                $monday->modify('+7 days');
                            }
                            
                            foreach ($weekdays as $index => $day):
                                $currentDate = clone $monday;
                                $currentDate->modify('+' . $index . ' days');
                                $formattedDate = $currentDate->format('Y-m-d');
                                $isToday = ($day === $currentDay && $selectedWeek == 'current');
                                
                                // Query to get actual session count for this day
                                try {
                                    $conn = $database->connect();
                                    $stmt = $conn->prepare("
                                        SELECT COUNT(*) as session_count 
                                        FROM program_subscription_schedule 
                                        WHERE date = ?
                                    ");
                                    $stmt->execute([$formattedDate]);
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $sessionCount = $result['session_count'] ?? 0;
                                } catch (PDOException $e) {
                                    $sessionCount = 0;
                                }
                            ?>
                                <div class="day-slot <?php echo $isToday ? 'today' : ''; ?>">
                                    <div class="d-flex justify-content-between w-100">
                                        <div>
                                            <div class="day-name"><?php echo $day; ?></div>
                                            <small class="text-muted"><?php echo $currentDate->format('M d'); ?></small>
                                        </div>
                                        <div class="day-sessions d-flex align-items-center">
                                            <?php if ($sessionCount > 0): ?>
                                                <div class="session-count">
                                                    <span class="badge rounded-pill session-badge">
                                                        <?php echo $sessionCount; ?> session<?php echo $sessionCount > 1 ? 's' : ''; ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="no-sessions">No sessions</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Responsive sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const burgerMenu = document.getElementById('burgerMenu');
        const sessionActionButtons = document.querySelectorAll('.session-action-btn');

        if (burgerMenu) {
            burgerMenu.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Make dashboard link active
        const dashboardLink = document.getElementById('dashboard-link');
        if (dashboardLink) {
            dashboardLink.classList.add('active');
        }
        
        // Check for scroll position in session storage
        const savedScrollPosition = sessionStorage.getItem('dashboardScrollPosition');
        if (savedScrollPosition) {
            window.scrollTo({
                top: parseInt(savedScrollPosition),
                behavior: 'instant' // Use 'instant' for immediate scroll without animation
            });
            
            // Clear the stored position after using it
            sessionStorage.removeItem('dashboardScrollPosition');
        }
        
        // Week selector functionality with scroll position preservation
        const weekSelector = document.getElementById('weekSelector');
        if (weekSelector) {
            weekSelector.addEventListener('change', function() {
                // Store current scroll position before navigating
                sessionStorage.setItem('dashboardScrollPosition', window.scrollY.toString());
                
                // Navigate to the selected week
                window.location.href = 'dashboard.php?week=' + this.value;
            });
        }
        
        // Add event listeners for tab changes if needed
        const scheduleTab = document.getElementById('weekly-schedule-card');
        if (scheduleTab) {
            // You can add additional tab-specific functionality here if needed
        }

        sessionActionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const action = this.getAttribute('data-action');
                const sessionId = this.getAttribute('data-session-id');
                
                let confirmMessage = '';
                if (action === 'cancel') {
                    confirmMessage = 'Are you sure you want to cancel this session?';
                } else if (action === 'complete') {
                    confirmMessage = 'Are you sure you want to mark this session as completed?';
                }
                
                if (confirm(confirmMessage)) {
                    // Create and send form data
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('session_id', sessionId);
                    
                    fetch('functions/session_action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Action completed successfully!');
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            });
        });
    });
</script>