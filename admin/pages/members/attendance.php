<?php
require_once dirname(__DIR__, 3) . '/config.php'; // Navigates up 3 levels dynamically


// SQL query remains the same
$sql = "
SELECT 
    pd.id AS user_id,
    CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
    u.username,
    pp.photo_path,
    a.time_in,
    a.time_out,
    ast.status_name as status,
    a.date
FROM personal_details pd
JOIN users u ON pd.user_id = u.id
LEFT JOIN profile_photos pp ON u.id = pp.user_id
LEFT JOIN attendance a ON pd.id = a.user_id AND a.date = CURRENT_DATE()
LEFT JOIN attendance_status ast ON a.status_id = ast.id
WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'member')
AND u.is_active = 1;
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$personalDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Add Font Awesome for the default user icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
        .profile-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        border: 3px solid #dee2e6;
        overflow: hidden;
    }

    .profile-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-container .default-user-icon {
        font-size: 60px;
        color: #adb5bd;
    }

    .modal-header {
        border-bottom: none;
    }

    .modal-footer {
        border-top: none;
    }
</style>
<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="verificationModalLabel">
                    <i class="fas fa-user-check me-2"></i>Verify Identity
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div id="photoContainer" class="profile-container">
                        <!-- This will either contain the photo or the default icon -->
                        <i class="default-user-icon fas fa-user-circle"></i>
                    </div>
                    <h4 id="userName" class="fw-bold text-primary">John Doe</h4>
                    <p id="userUsername" class="text-muted mb-0">@johndoe</p>
                </div>
                <form>
                    <div class="form-group mb-4">
                        <label for="password" class="form-label">Enter your password:</label>
                        <input type="password" class="form-control form-control-lg" id="password" placeholder="••••••••" required>
                        <!-- Error message directly below the input field -->
                        <div id="passwordError" class="invalid-feedback mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="verifyButton">
                    <i class="fas fa-check-circle me-2"></i>Verify
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Attendance history modal -->
<div class="modal fade" id="historyModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">Attendance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="historyTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="mb-3">
    <h1>Attendance Table</h1>
    <button type="button" class="btn btn-info" id="showHistoryBtn">
        <i class="fas fa-history"></i> View Attendance History
    </button>
</div>
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
    // Initialize DataTable
    const table = $('#attendanceTable').DataTable();

    // Variables to store current user interaction state
    let currentUserData = null;
    let $currentButton = null;
    let $currentRow = null;
    let verificationModal = null;

    // Initialize the modal
    verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));

    // Add debug logging
    console.log('Script initialized');

    // Action button click handler with debug logging
    $('.action-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Action button clicked');
        
        $currentButton = $(this);
        $currentRow = $currentButton.closest('tr');
        const userId = $currentButton.data('user-id');
        const currentStatus = $currentButton.data('current-status');
        
        console.log('User ID:', userId);
        console.log('Current Status:', currentStatus);

        if (currentStatus === 'checked In') {
            console.log('Processing checkout');
            updateAttendance(userId, 'checked out', $currentRow, $currentButton);
        } else {
            console.log('Fetching user data for check-in');
            fetchUserData(userId);
        }
    });

    // Function to fetch user data with error handling
    function fetchUserData(userId) {
        $.ajax({
            url: '../admin/pages/members/functions/get_user_data.php',
            type: 'POST',
            data: { userId: userId },
            dataType: 'json',
            success: function(response) {
                console.log('User data received:', response);
                if (response.success) {
                    currentUserData = response.user;
                    showVerificationModal(response.user);
                } else {
                    alert("Error fetching user data: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching user data:', error);
                console.error('Server response:', xhr.responseText);
                alert("Error connecting to server. Please try again.");
            }
        });
    }

    // Enhanced verification modal handling
    function showVerificationModal(userData) {
        console.log('Showing verification modal for:', userData);
        
        const photoContainer = $('#photoContainer');
        photoContainer.empty();
        
        if (userData.photo_path) {
            photoContainer.html(`<img src="${userData.photo_path}" class="profile-photo" alt="Profile Photo">`);
        } else {
            photoContainer.html('<i class="fas fa-user default-user-icon"></i>');
        }
        
        $('#userName').text(userData.full_name);
        $('#userUsername').text(userData.username);
        $('#password').val('').removeClass('is-invalid');
        $('#passwordError').hide();
        
        verificationModal.show();
    }

    // Verify button click handler with improved error handling
    $('#verifyButton').on('click', function() {
        console.log('Verify button clicked');
        
        const password = $('#password').val().trim();
        
        if (!password) {
            $('#passwordError').text("Please enter your password.").show();
            $('#password').addClass('is-invalid');
            return;
        }
        
        verifyPassword(password);
    });

    // Separate password verification function
    function verifyPassword(password) {
        $.ajax({
            url: '../admin/pages/members/functions/verify_password.php',
            type: 'POST',
            data: {
                userId: currentUserData.user_id,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                console.log('Password verification response:', response);
                if (response.success) {
                    verificationModal.hide();
                    updateAttendance(currentUserData.user_id, 'checked In', $currentRow, $currentButton);
                } else {
                    $('#passwordError').text("Invalid password. Please try again.").show();
                    $('#password').addClass('is-invalid');
                }
            },
            error: function(xhr, status, error) {
                console.error('Password verification error:', error);
                alert("Error verifying password. Please try again.");
            }
        });
    }

    // Enhanced attendance update function
    function updateAttendance(userId, status, $row, $button) {
        console.log('Updating attendance:', { userId, status });
        
        $.ajax({
            url: '../admin/pages/members/functions/update_attendance.php',
            type: 'POST',
            data: { 
                userId: userId, 
                status: status,
                date: new Date().toISOString().split('T')[0]
            },
            dataType: 'json',
            success: function(response) {
                console.log('Attendance update response:', response);
                if (response.success) {
                    updateRowData($row, response.attendance);
                    updateButtonState($button, status);
                } else {
                    alert("Error updating attendance: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Attendance update error:', error);
                alert("Error updating attendance. Please try again.");
            }
        });
    }

    // Helper functions for updating UI
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

    // Reset check function
    setInterval(checkForReset, 60000); // Check every minute

    function checkForReset() {
        const now = new Date();
        if (now.getHours() === 5 && now.getMinutes() === 0) {
            $.ajax({
                url: '../admin/pages/members/functions/reset_attendance.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function(error) {
                    console.error('Reset error:', error);
                }
            });
        }
    }
});
$(document).ready(function() {
    // Initialize history modal
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    
    // Initialize history DataTable
    const historyTable = $('#historyTable').DataTable({
        order: [[1, 'desc'], [2, 'desc']], // Sort by date and time
        pageLength: 10,
        columns: [
            { data: 'full_name' },
            { data: 'date' },
            { data: 'time_in' },
            { data: 'time_out' },
            { data: 'status' }
        ]
    });
    
    // Show history modal and load data
    $('#showHistoryBtn').on('click', function() {
        loadAttendanceHistory();
        historyModal.show();
    });
    
    // Function to load attendance history
    function loadAttendanceHistory() {
    console.time('attendance-history-load');
    $.ajax({
        url: '../admin/pages/members/functions/get_attendance_history.php',
        type: 'GET',
        dataType: 'json',
        beforeSend: function() {
            console.log('Starting to fetch attendance history...');
            historyTable.clear().draw();
            $('.modal-body').append('<div class="text-center" id="loadingIndicator"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        },
        success: function(response) {
            console.log('Data received:', response);
            console.timeEnd('attendance-history-load');
            $('#loadingIndicator').remove();
            if (response.success) {
                historyTable.rows.add(response.history).draw();
            } else {
                alert("Error loading history: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Load error:', error);
            console.timeEnd('attendance-history-load');
            $('#loadingIndicator').remove();
            alert("Error loading attendance history. Please try again.");
        }
    });
}
});
</script>