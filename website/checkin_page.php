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

function checkResetStatus($pdo) {
    try {
        // Get current time in the server's timezone
        $currentDateTime = new DateTime('now');
        $currentDate = $currentDateTime->format('Y-m-d');
        $resetTime = new DateTime($currentDate . ' 05:00:00'); // Today's reset time
        
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
            
            return ($lastResetDateOnly !== $currentDate && $currentDateTime >= $resetTime);
        }
    } catch (Exception $e) {
        error_log("Check reset status failed: " . $e->getMessage());
        return false;
    }
}

date_default_timezone_set('Asia/Manila');

try {
    // Initialize database
    $database = new Database();
    $pdo = $database->connect();
    
    // Check if reset is needed
    $resetNeeded = checkResetStatus($pdo);
    $resetButtonVisible = false;

    if ($resetNeeded) {
        // Record missed attendance first
        checkAndRecordMissedAttendance($pdo);
        $resetButtonVisible = true;
    }
    
    // Get current date and time
    $currentDateTime = new DateTime('now');
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('h:i A');

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
    <title>Check-in System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid px-4 py-5">
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
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
        <div class="card-body">
            <!-- Check-in Form -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <form id="checkinForm" method="POST" action="">
                                <div class="mb-3">
                                    <label for="identifier" class="form-label">Username or First Name</label>
                                    <input type="text" class="form-control" id="identifier" name="identifier" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Time In
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Check-ins Table -->
            <div class="table-responsive">
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
                                        $statusText = 'Checked In';
                                    } else if ($row['status'] === 'checked_out') {
                                        $statusClass = 'bg-secondary';
                                        $statusText = 'Checked Out';
                                    } else if ($row['status'] === 'missed') {
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Missed';
                                    } else {
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Not Checked In';
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
$(document).ready(function() {
    // Initialize DataTable with updated configuration
    const checkinTable = $('#checkinTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search check-ins..."
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
                
                // Show success message
                alert('Check-in successful!');
                
                // Reload the page after successful check-in
                window.location.reload();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('An error occurred during check-in.');
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
        
        if (confirm('Are you sure you want to check out?')) {
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
                        rowData[4] = timeOut; // Update check-out time
                        rowData[5] = '<span class="badge bg-secondary">Checked Out</span>'; // Update status
                        rowData[6] = '<button class="btn btn-warning btn-sm checkout-btn" disabled>Check Out</button>'; // Disable button
                        
                        checkinTable.row(rowIndex).data(rowData).draw(false);
                        
                        alert('Check-out successful!');
                        
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Checkout error:', error);
                    alert('An error occurred during check-out.');
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