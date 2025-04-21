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
        <style>
            @media screen and (max-width: 480px) {
                .stat-card {
                    padding: 8px;
                   height: 150px;
                   width: 150px;
                    height: 100%;
                }
                .row{
                    flex-wrap: wrap;
                    justify-content: center;
                }
                .stat-icon  {
                    font-size: 1rem;
                    margin-bottom: 10px;
                }
                .stat-value {
                    font-size: .75rem;
                }
                .stat-label {
                    font-size: 0.5rem;
                }
                .col-md-4, .col-md-6 {
                    width: 150px;
                    margin: 0 10px;
                    padding: 0 5px;
                
                }
            }
        </style>
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
                            
                            // Simplified logic - only check if the session is already cancelled or completed
                            $sessionCancelled = $session['status'] === 'cancelled';
                            $sessionCompleted = $session['status'] === 'completed';
                            
                            // We'll show both buttons unless the session is already cancelled or completed
                            $showButtons = !$sessionCancelled && !$sessionCompleted;
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
                                    <?php if ($showButtons): ?>
                                        <div class="d-flex gap-1">
                                            <!-- Show complete button with just an icon when both buttons are present -->
                                            <button class="btn btn-sm btn-outline-success session-action-btn"
                                                    data-action="complete"
                                                    data-session-id="<?php echo $session['id']; ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Mark as Completed">
                                                <i class="fas fa-check-circle"></i><?php echo count($upcomingSessions) <= 3 ? ' Complete' : ''; ?>
                                            </button>
                                            
                                            <!-- Show cancel button with just an icon when both buttons are present -->
                                            <button class="btn btn-sm btn-outline-danger session-action-btn" 
                                                    data-action="cancel" 
                                                    data-session-id="<?php echo $session['id']; ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Cancel Session">
                                                <i class="fas fa-times-circle"></i><?php echo count($upcomingSessions) <= 3 ? ' Cancel' : ''; ?>
                                            </button>
                                        </div>
                                    <?php elseif ($session['status'] === 'cancelled'): ?>
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

        <!-- Cancellation Reason Modal -->
        <div class="modal fade" id="cancelSessionModal" tabindex="-1" aria-labelledby="cancelSessionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelSessionModalLabel"><i class="fas fa-times-circle"></i> Cancel Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please select a reason for cancelling this session:</p>
                
                <form id="cancelSessionForm">
                <input type="hidden" id="cancelSessionId" name="session_id" value="">
                <input type="hidden" name="action" value="cancel">
                
                <div class="mb-3">
                    <div class="form-check">
                    <input class="form-check-input cancel-reason-radio" type="radio" name="cancellation_reason" id="reason1" value="Client requested to reschedule">
                    <label class="form-check-label" for="reason1">Client requested to reschedule</label>
                    </div>
                    
                    <div class="form-check">
                    <input class="form-check-input cancel-reason-radio" type="radio" name="cancellation_reason" id="reason2" value="Coach unavailable">
                    <label class="form-check-label" for="reason2">Coach unavailable</label>
                    </div>
                    
                    <div class="form-check">
                    <input class="form-check-input cancel-reason-radio" type="radio" name="cancellation_reason" id="reason3" value="Client did not show up">
                    <label class="form-check-label" for="reason3">Client did not show up</label>
                    </div>
                    
                    <div class="form-check">
                    <input class="form-check-input cancel-reason-radio" type="radio" name="cancellation_reason" id="reason4" value="Emergency situation">
                    <label class="form-check-label" for="reason4">Emergency situation</label>
                    </div>
                    
                    <div class="form-check">
                    <input class="form-check-input cancel-reason-radio" type="radio" name="cancellation_reason" id="reasonOther" value="other">
                    <label class="form-check-label" for="reasonOther">Other reason</label>
                    </div>
                    
                    <div class="mt-3 other-reason-container" style="display: none;">
                    <label for="otherReasonText" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="otherReasonText" rows="3" placeholder="Enter reason for cancellation"></textarea>
                    </div>
                </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Exit</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirm Cancellation</button>
            </div>
            </div>
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
                                        FROM program_subscription_schedule pss
                                        JOIN program_subscriptions ps ON pss.program_subscription_id = ps.id
                                        JOIN coach_program_types cpt ON ps.coach_program_type_id = cpt.id
                                        WHERE pss.date = ? AND cpt.coach_id = ?
                                    ");
                                    $stmt->execute([$formattedDate, $_SESSION['user_id']]);
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // // Responsive sidebar toggle
    // const sidebar = document.getElementById('sidebar');
    // const sidebarOverlay = document.getElementById('sidebarOverlay');
    // const burgerMenu = document.getElementById('burgerMenu');
    // const sessionActionButtons = document.querySelectorAll('.session-action-btn');

    // if (burgerMenu) {
    //     burgerMenu.addEventListener('click', function() {
    //         if (sidebar) sidebar.classList.toggle('active');
    //         if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
    //     });
    // }
    
    // if (sidebarOverlay) {
    //     sidebarOverlay.addEventListener('click', function() {
    //         if (sidebar) sidebar.classList.remove('active');
    //         if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    //     });
    // }
    
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
    
    // Initialize the cancellation modal
    const cancelModal = document.getElementById('cancelSessionModal');
    let cancelModalInstance = null;
    if (cancelModal) {
        cancelModalInstance = new bootstrap.Modal(cancelModal);
    }
    
    // Add event listeners for the cancel reason radios
    const cancelReasonRadios = document.querySelectorAll('.cancel-reason-radio');
    const otherReasonContainer = document.querySelector('.other-reason-container');
    
    if (cancelReasonRadios.length > 0) {
        cancelReasonRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'other') {
                    otherReasonContainer.style.display = 'block';
                } else {
                    otherReasonContainer.style.display = 'none';
                }
            });
        });
    }
    
    // Handle session action buttons
    if (sessionActionButtons && sessionActionButtons.length > 0) {
        sessionActionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const action = this.getAttribute('data-action');
                const sessionId = this.getAttribute('data-session-id');
                
                if (action === 'cancel') {
                    // Check if modal exists
                    if (cancelModalInstance) {
                        // Set the session ID in the modal form
                        document.getElementById('cancelSessionId').value = sessionId;
                        
                        // Reset form 
                        document.querySelectorAll('.cancel-reason-radio').forEach(radio => {
                            radio.checked = false;
                        });
                        document.getElementById('otherReasonText').value = '';
                        otherReasonContainer.style.display = 'none';
                        
                        // Show the cancellation modal
                        cancelModalInstance.show();
                    } else {
                        // Fallback if modal doesn't exist
                        if (confirm('Are you sure you want to cancel this session?')) {
                            const formData = new FormData();
                            formData.append('action', 'cancel');
                            formData.append('session_id', sessionId);
                            sendSessionAction(formData);
                        }
                    }
                } else if (action === 'complete') {
                    // For complete action, keep simple confirmation
                    if (confirm('Are you sure you want to mark this session as completed?')) {
                        // Create and send form data
                        const formData = new FormData();
                        formData.append('action', 'complete');
                        formData.append('session_id', sessionId);
                        
                        sendSessionAction(formData);
                    }
                }
            });
        });
    }
    
    // Handle the cancel confirmation button
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function() {
            const sessionId = document.getElementById('cancelSessionId').value;
            const selectedReason = document.querySelector('input[name="cancellation_reason"]:checked');
            
            if (!selectedReason) {
                alert('Please select a reason for cancellation.');
                return;
            }
            
            // Create form data for submission
            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('session_id', sessionId);
            
            // Handle the cancellation reason
            let cancellationReason = selectedReason.value;
            if (cancellationReason === 'other') {
                const otherReasonText = document.getElementById('otherReasonText').value.trim();
                if (!otherReasonText) {
                    alert('Please specify the other reason for cancellation.');
                    return;
                }
                cancellationReason = otherReasonText;
            }
            
            formData.append('cancellation_reason', cancellationReason);
            
            // Submit the form
            sendSessionAction(formData);
            
            // Hide the modal
            cancelModalInstance.hide();
        });
    }
    
    // Function to send session action request
    function sendSessionAction(formData) {
        fetch('functions/session_action.php', {  // Make sure this path is correct
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
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
            alert('An error occurred while processing your request. Please try again.');
        });
    }
});
</script>