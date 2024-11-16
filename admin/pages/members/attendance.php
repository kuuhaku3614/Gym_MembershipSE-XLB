<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Gym_MembershipSE-XLB/functions/config.php';


// Fetch personal details
$sql = "
SELECT 
    pd.id AS user_id,
    CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS full_name
FROM personal_details pd
JOIN users u ON pd.user_id = u.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$personalDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance data for current date
// Fetch attendance data for the current date
$sql = "
SELECT 
    a.user_id,
    a.date, 
    a.time_in,
    a.time_out,
    a.status
FROM attendance a
WHERE a.date = CURDATE()
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<h1>Attendance Table</h1>
<table id="attendanceTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Full Name</th>
            <th>Date</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($personalDetails as $detail): ?>
<?php
    $attendance = array_filter($attendanceData, function($row) use ($detail) {
        return $row['user_id'] == $detail['user_id'];
    });
    $attendance = current($attendance) ?: [
        'date' => date('Y-m-d'),
        'time_in' => '',
        'time_out' => '',
        'status' => 'pending'
    ];
    $isDisabled = $attendance['status'] === 'Checked Out' ? 'disabled' : '';
?>
<tr>
    <td><?= htmlspecialchars($detail['full_name']) ?></td>
    <td><?= htmlspecialchars($attendance['date']) ?></td>
    <td><?= htmlspecialchars($attendance['time_in']) ?></td>
    <td><?= htmlspecialchars($attendance['time_out']) ?></td>
    <td><?= htmlspecialchars($attendance['status']) ?></td>
    <td>
        <button class="btn btn-primary btn-sm action-btn"
                data-user-id="<?= htmlspecialchars($detail['user_id']) ?>"
                data-date="<?= htmlspecialchars($attendance['date']) ?>"
                data-status="<?= htmlspecialchars($attendance['status']) ?>"
                <?= $isDisabled ?>>
            <?= $attendance['status'] === 'Checked In' ? 'Check Out' : 'Check In' ?>
        </button>
    </td>
</tr>
<?php endforeach; ?>
    </tbody>
</table>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    // Initialize DataTable
    $('#attendanceTable').DataTable();

    // Handle Check In / Check Out actions
    $('#attendanceTable').on('click', '.action-btn', function () {
        var userId = $(this).data('user-id');
        var date = $(this).data('date');
        var status = $(this).data('status');
        var newStatus = status === 'Checked In' ? 'Checked Out' : 'Checked In';
        var $currentRow = $(this).closest('tr');

        updateAttendance(userId, date, newStatus, $currentRow);
    });

    // Reset attendance table daily at 5:00 AM
    setInterval(function () {
        var now = new Date();
        if (now.getHours() === 5 && now.getMinutes() === 0 && now.getSeconds() === 0) {
            resetAttendance();
        }
    }, 60000); // Check every minute
});

function updateAttendance(userId, date, status, $row) {
    $.ajax({
        url: '../admin/pages/members/functions/update_attendance.php',
        type: 'POST',
        data: { userId: userId, date: date, status: status },
        success: function (response) {
            if (response.trim() === "success") {
                const now = new Date().toLocaleTimeString();
                if (status === 'Checked In') {
                    $row.find('td:nth-child(3)').text(now); // Time in
                } else if (status === 'Checked Out') {
                    $row.find('td:nth-child(4)').text(now); // Time out
                    $row.find('td:nth-child(6) button').prop('disabled', true); // Disable button
                }

                // Update status and button text
                $row.find('td:nth-child(5)').text(status);
                $row.find('td:nth-child(6) button').text(status === 'Checked In' ? 'Check Out' : 'Check In')
                    .data('status', status);
            } else {
                alert("Error updating attendance: " + response);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error: " + error);
        }
    });
}


function resetAttendance() {
    $.ajax({
        url: '../admin/pages/members/functions/reset_attendance.php',
        type: 'POST',
        success: function (response) {
            if (response.trim() === "success") {
                alert("Attendance table reset successfully.");
                location.reload(); // Re-enable buttons by refreshing the table
            } else {
                console.error("Reset failed: " + response);
            }
        },
        error: function (xhr, status, error) {
            console.error("Reset AJAX error: " + error);
        }
    });
}
</script>