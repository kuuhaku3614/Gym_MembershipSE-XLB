<?php
session_start();

$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

require_once '../config.php';

function checkAndRecordMissedAttendance($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Step 1: Mark records as missed where time_in is still NULL
        $updateMissedSql = "
            UPDATE attendance 
            SET status = 'missed' 
            WHERE (status IS NULL OR time_in IS NULL)
            AND date < CURRENT_DATE
            AND user_id IN (
                SELECT DISTINCT user_id 
                FROM attendance
            )
        ";
        $pdo->exec($updateMissedSql);

        // Step 2: Record these missed attendances in history
        $insertMissedAttendancesSql = "
            INSERT INTO attendance_history (attendance_id, time_in, time_out, created_at, status)
            SELECT 
                a.id,
                a.time_in,
                a.time_out,
                a.created_at,
                'missed'
            FROM attendance a
            WHERE (a.status = 'missed' OR a.time_in IS NULL)
            AND a.date < CURRENT_DATE
        ";
        $pdo->exec($insertMissedAttendancesSql);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Recording missed attendance failed: " . $e->getMessage());
        return false;
    }
}

// In checkResetStatus function
function checkResetStatus($pdo) {
    try {
        // Get current time in the server's timezone
        $currentDateTime = new DateTime('now');
        $currentDate = $currentDateTime->format('Y-m-d');
        
        // First, fetch the opening time from website_content table
        $scheduleQuery = "SELECT hours FROM website_content WHERE section = 'schedule'";
        $scheduleStmt = $pdo->prepare($scheduleQuery);
        $scheduleStmt->execute();
        $scheduleHours = $scheduleStmt->fetchColumn();
        
        // Parse the opening hours (format is expected to be like "05:30 AM - 10:00 PM")
        $openingTime = null;
        if ($scheduleHours) {
            $hoursParts = explode('-', $scheduleHours);
            if (count($hoursParts) >= 1) {
                $openingTimePart = trim($hoursParts[0]);
                // Create a DateTime object with the opening time
                try {
                    $openingTime = DateTime::createFromFormat('h:i A', $openingTimePart);
                    if ($openingTime) {
                        // Set to today's date with the opening time
                        $resetTime = new DateTime($currentDate . ' ' . $openingTime->format('H:i:s'));
                    } else {
                        // Fallback to default 5:00 AM if parsing fails
                        $resetTime = new DateTime($currentDate . ' 05:00:00');
                        error_log("Failed to parse opening time: {$openingTimePart}, using default 05:00:00");
                    }
                } catch (Exception $e) {
                    // Fallback to default 5:00 AM if an exception occurs
                    $resetTime = new DateTime($currentDate . ' 05:00:00');
                    error_log("Exception parsing opening time: " . $e->getMessage() . ", using default 05:00:00");
                }
            } else {
                // Fallback to default if the format is unexpected
                $resetTime = new DateTime($currentDate . ' 05:00:00');
                error_log("Unexpected schedule hours format: {$scheduleHours}, using default 05:00:00");
            }
        } else {
            // If no schedule found, use default 5:00 AM
            $resetTime = new DateTime($currentDate . ' 05:00:00');
            error_log("No schedule found in website_content, using default 05:00:00");
        }
        
        // Store the calculated reset time for display
        global $calculatedResetTime;
        $calculatedResetTime = $resetTime->format('h:i A');
        
        // Get the last reset date from the database
        $checkResetSql = "SELECT value FROM system_controls WHERE key_name = 'last_attendance_reset'";
        $stmt = $pdo->prepare($checkResetSql);
        $stmt->execute();
        $lastResetTimestamp = $stmt->fetchColumn();
        
        // If no last reset timestamp exists, or it's from a previous day
        if (!$lastResetTimestamp) {
            return true;
        } else {
            $lastResetDate = new DateTime($lastResetTimestamp);
            $lastResetDateOnly = $lastResetDate->format('Y-m-d');
            
            // Check if the reset was very recent (within last 60 seconds)
            $timeSinceReset = $currentDateTime->getTimestamp() - $lastResetDate->getTimestamp();
            if ($timeSinceReset < 60) {
                // Reset was just performed, don't show the button
                return false;
            }
            
            // Reset is needed if the last reset was on a previous day and current time is after reset time
            return ($lastResetDateOnly !== $currentDate && $currentDateTime >= $resetTime);
        }
    } catch (Exception $e) {
        error_log("Check reset status failed: " . $e->getMessage());
        return false;
    }
}

date_default_timezone_set('Asia/Manila');
$calculatedResetTime = ""; // Initialize the global variable

try {
    // Initialize database
    $database = new Database();
    $pdo = $database->connect();
    
    // Check if reset is needed
    $resetNeeded = checkResetStatus($pdo);
    $resetButtonVisible = false;

    // Check if a reset was just completed
    $resetJustCompleted = isset($_SESSION['attendance_reset_completed']) && $_SESSION['attendance_reset_completed'] === true;

    // Clear the session flag if it exists
    if ($resetJustCompleted) {
        unset($_SESSION['attendance_reset_completed']);
    }

    if ($resetNeeded && !$resetJustCompleted) {
        // Record missed attendance first
        checkAndRecordMissedAttendance($pdo);
        $resetButtonVisible = true;
    }
    
    // Get current date and time
    $currentDateTime = new DateTime('now');
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('h:i A');
    
    // Get gym schedule for display
    $scheduleQuery = "SELECT hours FROM website_content WHERE section = 'schedule'";
    $scheduleStmt = $pdo->prepare($scheduleQuery);
    $scheduleStmt->execute();
    $gymHours = $scheduleStmt->fetchColumn() ?: "Not set";

    // Rest of your existing attendance query
    $attendanceQuery = "SELECT u.id AS user_id, 
        CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name, 
        u.username, 
        pp.photo_path,
        a.date, 
        a.time_in, 
        a.time_out, 
        a.status, 
        a.date 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        JOIN personal_details pd ON u.id = pd.user_id 
        LEFT JOIN profile_photos pp ON u.id = pp.user_id 
        JOIN transactions t ON u.id = t.user_id 
        JOIN memberships m ON t.id = m.transaction_id 
        WHERE a.date = CURRENT_DATE() OR a.date < CURRENT_DATE() 
        AND m.status = 'active' AND m.is_paid = 1
        AND CURRENT_DATE() BETWEEN m.start_date AND m.end_date 
        GROUP BY full_name ORDER BY a.time_in DESC";

    $stmt = $pdo->prepare($attendanceQuery);
    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        *{
            font-family: "Inter", sans-serif;
        }
        h2, h3{
            font-weight: 600;
        }
        label{
            font-weight: 600;
        }
        .card-body{
            gap: 1rem;
        }
        .highlight-row {
            background-color: #ffffcc !important;
            transition: background-color 1.5s ease;
        }
        .gym-info {
            font-size: 0.85rem;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .reset-time {
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>

<div class="container-fluid vh-100 d-flex flex-column p-0">
    <div class="card shadow h-100">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">

            <h2 class="card-title mb-0">Attendance System</h2>
            <div class="d-flex align-items-center">
                <?php if ($resetButtonVisible): ?>
                <button id="resetButton" class="btn btn-warning">
                    Reset Attendance
                </button>
                <?php endif; ?>
                <span class="me-3">
                    <strong>Date:</strong> <?= $currentDateTime->format('F d, Y') ?>
                </span>
                <span class="me-3">
                    <strong>Time:</strong> <span id="currentTime"><?= $currentTime ?></span>
                </span>
            </div>
        </div>
        <div class="card-body d-flex flex-column overflow-hidden">
            <!-- Gym Schedule & Reset Info -->
            <div class="gym-info">
                <div><strong>Gym Hours:</strong> <?= htmlspecialchars($gymHours) ?></div>
                <div><strong>Daily Reset Time:</strong> <span class="reset-time"><?= htmlspecialchars($calculatedResetTime) ?></span></div>
            </div>
            
            <!-- Check-in Form -->
            <div class="row flex-grow-1">
                <div class="col-md-6 mx-auto my-auto">
                    <div class="card">
                        <div class="card-body flex-grow-1" style="min-height: 300px;">
                            <form id="checkinForm" method="POST" action="" class="h-100 d-flex flex-column justify-content-center">
                                <div class="mb-4">
                                    <label for="identifier" class="form-label">Username or First Name</label>
                                    <input type="text" class="form-control form-control-md" id="identifier" name="identifier" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control form-control-md" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-md w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Time In
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Today's Check-ins Table -->

            <div class="table-responsive flex-grow-1">
            <h3 class="text-center">Attendance List</h2>
                <table id="checkinTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Date</th>
                            <th>Time in</th>
                            <th>Time out</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $row): ?>
                            <tr data-user-id="<?= $row['user_id'] ?>">
                                <td>
                                    <img src="../<?= htmlspecialchars($row['photo_path'] ?? 'default-avatar.png') ?>" 
                                        alt="Profile" 
                                        class="rounded-circle"
                                        style="width: 40px; height: 40px; object-fit: cover;">
                                </td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <?php
                                    $dateText = '';
                                    if (strtotime($row['date']) < strtotime(date('Y-m-d'))) {
                                        $dateText = 'Yesterday ' . htmlspecialchars($row['date']);
                                    } else {
                                        $dateText = htmlspecialchars($row['date']);
                                    }
                                    ?>
                                    <span><?= $dateText ?></span>
                                </td>
                                <td><?= $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-' ?></td>
                                <td><?= $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-' ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    if ($row['status'] === 'checked_in') {
                                        $statusClass = 'bg-success';
                                        $statusText = 'Time In';
                                    } else if ($row['status'] === 'checked_out') {
                                        $statusClass = 'bg-secondary';
                                        $statusText = 'Time Out';
                                    } else if ($row['status'] === 'missed') {
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Missed';
                                    } else {
                                        $statusClass = 'bg-warning';
                                        $statusText = 'No Record';
                                    }
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-warning btn-sm checkout-btn" 
                                            data-user-id="<?= $row['user_id'] ?>"
                                            <?= ($row['status'] !== 'checked_in') ? 'disabled' : '' ?>>
                                        Time Out
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Required Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
// Make DataTable variable globally accessible
let checkinTable;

$(document).ready(function() {
    // Initialize DataTable with updated configuration
    checkinTable = $('#checkinTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search Time-ins..."
        },
        columnDefs: [
            { orderable: false, targets: [0, 6] } // Disable sorting for photo and action columns
        ]
    });

    // Update current time
    setInterval(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        $('#currentTime').text(timeString);
    }, 1000);

    // Handle check-in form submission
    $('#checkinForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'process_checkin.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    // Clear form
                    $('#checkinForm')[0].reset();
                    
                    // Create and show success popup
                    const successDiv = $('<div>')
                        .css({
                            'position': 'fixed',
                            'top': '20px',
                            'left': '50%',
                            'transform': 'translateX(-50%)',
                            'background-color': '#198754', // Green color for success
                            'color': 'white',
                            'padding': '15px 25px',
                            'border-radius': '5px',
                            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                            'z-index': '9999'
                        })
                        .text('Time-in successful!');
                    
                    $('body').append(successDiv);
                    
                    // Add the new record to the table immediately
                    if (response.data) {
                        const userData = response.data;
                        const timeIn = userData.time_in ? new Date('1970-01-01T' + userData.time_in).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        }) : '-';
                        
                        // Format the date
                        const currentDate = new Date().toISOString().split('T')[0];
                        
                        // Create status badge
                        const statusBadge = '<span class="badge bg-success">Time In</span>';
                        
                        // Create action button
                        const actionButton = '<button class="btn btn-warning btn-sm checkout-btn" data-user-id="' + 
                                            userData.user_id + '">Time Out</button>';
                        
                        // Add new row to DataTable
                        const photoPath = userData.photo_path ? '../' + userData.photo_path : '../default-avatar.png';
                        
                        const newRow = [
                            '<img src="' + photoPath + '" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">',
                            userData.full_name,
                            userData.username,
                            currentDate,
                            timeIn,
                            '-', // Time out is empty
                            statusBadge,
                            actionButton
                        ];
                        
                        // Add the new row to the top of the table
                        const newRowNode = checkinTable.row.add(newRow).draw(false).node();
                        $(newRowNode).attr('data-user-id', userData.user_id);
                        
                        // Highlight the new row
                        $(newRowNode).addClass('highlight-row');
                    }
                    
                    // Reload the page after showing the new record and message
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000); // Wait 3 seconds before reloading to allow user to see the new record
                    
                    // Note: We don't need to remove the success popup since the page will reload
                    
                } else {
                    // Create and show error popup
                    const errorDiv = $('<div>')
                        .css({
                            'position': 'fixed',
                            'top': '20px',
                            'left': '50%',
                            'transform': 'translateX(-50%)',
                            'background-color': '#dc3545',
                            'color': 'white',
                            'padding': '15px 25px',
                            'border-radius': '5px',
                            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                            'z-index': '9999'
                        })
                        .text(response.message);
                    
                    $('body').append(errorDiv);
                    
                    // Remove the error popup after 3 seconds
                    setTimeout(() => {
                        errorDiv.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
            },
            error: function() {
                // Error handling
                const errorDiv = $('<div>')
                    .css({
                        'position': 'fixed',
                        'top': '20px',
                        'left': '50%',
                        'transform': 'translateX(-50%)',
                        'background-color': '#dc3545',
                        'color': 'white',
                        'padding': '15px 25px',
                        'border-radius': '5px',
                        'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                        'z-index': '9999'
                    })
                    .text('An error occurred during time-in.');
                
                $('body').append(errorDiv);
                
                // Remove the error popup after 3 seconds
                setTimeout(() => {
                    errorDiv.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    });

    // Handle check-out button clicks
    $(document).on('click', '.checkout-btn:not([disabled])', function() {
        const userId = $(this).data('user-id');
        const button = $(this);
        
        if (!userId) {
            alert('User ID not found');
            return;
        }
        
        if (confirm('Are you sure you want to time out?')) {
            $.ajax({
                url: 'process_checkin.php',
                type: 'POST',
                data: {
                    action: 'checkout',
                    user_id: userId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        const timeOut = new Date('1970-01-01T' + data.time_out).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                        
                        // Update the row
                        const row = button.closest('tr');
                        const rowIndex = checkinTable.row(row).index();
                        
                        const rowData = checkinTable.row(rowIndex).data();
                        rowData[5] = timeOut; // Update check-out time
                        rowData[6] = '<span class="badge bg-secondary">Time Out</span>'; // Update status
                        rowData[7] = '<button class="btn btn-warning btn-sm checkout-btn" disabled>Time Out</button>'; // Disable button
                        
                        checkinTable.row(rowIndex).data(rowData).draw(false);
                        
                        alert('Time-out successful!');
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Checkout error:', error);
                    alert('An error occurred during time-out.');
                }
            });
        }
    });

    // Add reset button functionality
    $('#resetButton').on('click', function() {
        if (confirm('Are you sure you want to reset the attendance records? This action cannot be undone.')) {
            $.ajax({
                url: 'process_reset.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Attendance reset successful!');
                        // Hide the button immediately after successful reset
                        $('#resetButton').hide();
                        window.location.reload();
                    } else {
                        alert('Reset failed: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reset error:', xhr.responseText);
                    alert('An error occurred during reset. Check console for details.');
                }
            });
        }
    });
});
</script>

</body>
</html>