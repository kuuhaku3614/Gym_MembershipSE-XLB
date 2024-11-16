<?php
require_once 'config.php';

// SQL query remains the same
$sql = "
SELECT 
    pd.id AS user_id,
    CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS full_name,
    u.username,
    pp.photo_path,
    a.time_in,
    a.time_out,
    a.status,
    a.date
FROM personal_details pd
JOIN users u ON pd.user_id = u.id
LEFT JOIN profile_photos pp ON u.id = pp.user_id
LEFT JOIN attendance a ON pd.id = a.user_id AND a.date = CURRENT_DATE()
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$personalDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Add Font Awesome for the default user icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Add custom styles for the default icon -->
<style>
.profile-container {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 2px solid #dee2e6;
}

.default-user-icon {
    font-size: 80px;
    color: #adb5bd;
}

.profile-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #dee2e6;
}
</style>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Verify Identity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="photoContainer" class="profile-container">
                        <!-- This will either contain the photo or the default icon -->
                    </div>
                    <h4 id="userName" class="mt-2"></h4>
                    <p id="userUsername" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Please enter your password to verify:</label>
                    <input type="password" class="form-control" id="password" required>
                    <div id="passwordError" class="invalid-feedback"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="verifyButton">Verify</button>
            </div>
        </div>
    </div>
</div>

<h1>Attendance Table</h1>
<table id="attendanceTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Full Name</th>
            <th>Date</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($personalDetails as $detail): ?>
        <?php 
        $buttonText = 'Check In';
        $isDisabled = '';
        
        if (!empty($detail['status'])) {
            if ($detail['status'] === 'checked In') {
                $buttonText = 'Check Out';
            } else if ($detail['status'] === 'checked out') {
                $buttonText = 'Check Out';
                $isDisabled = 'disabled';
            }
        }
        ?>
        <tr>
            <td><?= htmlspecialchars($detail['full_name']) ?></td>
            <td><?= $detail['date'] ?? '' ?></td>
            <td><?= $detail['time_in'] ?? '' ?></td>
            <td><?= $detail['time_out'] ?? '' ?></td>
            <td><?= $detail['status'] ?? '' ?></td>
            <td>
                <button class="btn btn-primary btn-sm action-btn"
                        data-user-id="<?= htmlspecialchars($detail['user_id']) ?>"
                        data-current-status="<?= $detail['status'] ?? 'pending' ?>"
                        <?= $isDisabled ?>>
                    <?= $buttonText ?>
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
    $('#attendanceTable').DataTable();

    let currentUserData = null;
    let $currentButton = null;
    let $currentRow = null;

    // Modified click handler remains the same
    $('#attendanceTable').on('click', '.action-btn', function () {
        $currentButton = $(this);
        $currentRow = $currentButton.closest('tr');
        const userId = $currentButton.data('user-id');
        const currentStatus = $currentButton.data('current-status');
        
        if (currentStatus === 'checked In') {
            updateAttendance(userId, 'checked out', $currentRow, $currentButton);
        } else {
            fetchUserData(userId);
        }
    });

    function fetchUserData(userId) {
        $.ajax({
            url: '../admin/pages/members/functions/get_user_data.php',
            type: 'POST',
            data: { userId: userId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    currentUserData = data.user;
                    showVerificationModal(data.user);
                } else {
                    alert("Error fetching user data: " + data.message);
                }
            }
        });
    }

    function showVerificationModal(userData) {
        const photoContainer = $('#photoContainer');
        
        // Clear previous content
        photoContainer.empty();
        
        if (userData.photo_path) {
            // If there's a photo, show it
            photoContainer.html(`<img src="${userData.photo_path}" class="profile-photo" alt="Profile Photo">`);
        } else {
            // If no photo, show default icon
            photoContainer.html('<i class="fas fa-user default-user-icon"></i>');
        }
        
        $('#userName').text(userData.full_name);
        $('#userUsername').text(userData.username);
        $('#password').val('').removeClass('is-invalid');
        $('#passwordError').hide();
        $('#verificationModal').modal('show');
    }

    $('#verifyButton').click(function() {
        const password = $('#password').val();
        
        if (!password) {
            $('#passwordError').text("Please enter your password.").show();
            $('#password').addClass('is-invalid');
            return;
        }
        
        $.ajax({
            url: '../admin/pages/members/functions/verify_password.php',
            type: 'POST',
            data: {
                userId: currentUserData.user_id,
                password: password
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#verificationModal').modal('hide');
                    updateAttendance(currentUserData.user_id, 'checked In', $currentRow, $currentButton);
                } else {
                    $('#passwordError').text("Invalid password. Please try again.").show();
                    $('#password').addClass('is-invalid');
                }
            }
        });
    });

    // Add handler for when modal is hidden
    $('#verificationModal').on('hidden.bs.modal', function () {
        $('#password').val('').removeClass('is-invalid');
        $('#passwordError').hide();
    });

function updateAttendance(userId, status, $row, $button) {
    $.ajax({
        url: '../admin/pages/members/functions/update_attendance.php',
        type: 'POST',
        data: { 
            userId: userId, 
            status: status,
            date: new Date().toISOString().split('T')[0]
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                updateRowData($row, data.attendance);
                updateButtonState($button, status);
            } else {
                alert("Error updating attendance: " + data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error: " + error);
        }
    });
}

function updateRowData($row, data) {
    $row.find('td:eq(1)').text(data.date || '');
    $row.find('td:eq(2)').text(data.time_in || '');
    $row.find('td:eq(3)').text(data.time_out || '');
    $row.find('td:eq(4)').text(data.status || '');
}

function updateButtonState($button, status) {
    $button.data('current-status', status);
    if (status === 'checked In') {
        $button.text('Check Out').prop('disabled', false);
    } else if (status === 'checked out') {
        $button.text('Check Out').prop('disabled', true);
    }
}

function checkForReset() {
    const now = new Date();
    if (now.getHours() === 5 && now.getMinutes() === 0) {
        $.ajax({
            url: '../admin/pages/members/functions/reset_attendance.php',
            type: 'POST',
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                }
            }
        });
    }
}
});
</script>