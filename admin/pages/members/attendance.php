<?php
$_SESSION['allow_checkin_access'] = true;
require_once 'config.php';
require_once 'functions/attendance_class.php';

?>
 
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Today's Member Attendance</h2>
        <div class="mt-2">
        <a href="../website/checkin_page.php" class="me-2">
                <button class="btn btn-primary">Attendance Page</button>
            </a>
            <button onclick="openHistoryModal()" class="btn btn-info">
                <i class="bi bi-clock-history"></i> Overall History
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php
            $attendance = new AttendanceSystem($pdo);
            $members = $attendance->getTodayCheckins();
            
            if (empty($members)): ?>
                <div class="alert alert-info">
                    No members have marked attendance today.
                </div>
            <?php else: ?>
                <table id="attendanceTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Time in</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): 
                            $isRecent = (time() - strtotime($member['time_in'])) <= 300; // 5 minutes = 300 seconds
                        ?>
                        <tr>
                            <td>
                                <img src="../<?= htmlspecialchars($member['photo_path'] ?? 'default-avatar.png') ?>" 
                                     alt="Profile" 
                                     class="rounded-circle"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td>
                                <?= htmlspecialchars($member['full_name']) ?>
                                <?php if ($isRecent): ?>
                                    <span class="badge bg-warning text-dark">New</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($member['username']) ?></td>
                            <td><?= date('h:i A', strtotime($member['time_in'])) ?></td>
                            <td>
                                <span class="badge bg-success">
                                    <?= htmlspecialchars(
                                        $member['status'] == 'checked in' ? 'Time In' : 
                                        ($member['status'] == 'checked out' ? 'Time Out' : 
                                        $member['status'])
                                    ) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Overall Attendance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function openHistoryModal() {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    
    // Show loading state
    $('#historyContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    // Fetch history data
    $.ajax({
        url: 'pages/members/functions/get_history.php',
        success: function(response) {
            $('#historyContent').html(response);
            modal.show();

            // Move focus to the close button when the modal opens
            const closeButton = document.querySelector('#historyModal .btn-close');
            closeButton.focus();
        },
        error: function() {
            $('#historyContent').html('<div class="alert alert-danger">Error loading history</div>');
            modal.show();
        }
    });
}

$(document).ready(function() {
    // Initialize DataTable
    $('#attendanceTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip', // Custom layout
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search members..."
        },
        columnDefs: [
            { orderable: false, targets: [0] } // Disable sorting for photo column
        ]
    });
});
</script>