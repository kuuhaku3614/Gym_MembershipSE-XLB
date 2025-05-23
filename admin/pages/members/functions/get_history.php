<?php
require_once(__DIR__ . '/../../../../config.php');
require_once 'attendance_class.php';

$attendance = new AttendanceSystem($pdo);
$history = $attendance->getAllAttendanceHistory();
?>
<div class="table-responsive" style="color: black;">
    <table id="historyTable" class="table table-striped table-bordered">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Member Name</th>
                <th>Username</th>
                <th>Action Time</th>
                <th>Action Type</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $record): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                    <td><?= htmlspecialchars($record['full_name']) ?></td>
                    <td><?= htmlspecialchars($record['username']) ?></td>
                    <td>
                        <span class="text-primary">
                            <i class="bi bi-clock"></i>
                            <?= date('h:i A', strtotime($record['action_timestamp'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($record['status'] === 'checked_in'): ?>
                            <span class="badge bg-success">Check In</span>
                        <?php elseif ($record['status'] === 'checked_out'): ?>
                            <span class="badge bg-danger">Check Out</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= ucfirst($record['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($record['status'] === 'checked_in'): ?>
                            <span class="text-success">
                                <i class="bi bi-box-arrow-in-right"></i>
                                Time In: <?= date('h:i A', strtotime($record['time_in'])) ?>
                            </span>
                        <?php elseif ($record['status'] === 'checked_out'): ?>
                            <span class="text-danger">
                                <i class="bi bi-box-arrow-right"></i>
                                Time Out: <?= date('h:i A', strtotime($record['time_out'])) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        responsive: true,
        order: [[0, 'desc'], [3, 'desc']], // Sort by date and action time descending
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ records",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            infoEmpty: "Showing 0 to 0 of 0 records",
            emptyTable: "No attendance records found"
        },
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-5"i><"col-sm-7"p>>'
    });
});
</script>