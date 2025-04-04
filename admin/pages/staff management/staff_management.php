<?php
// Database connection
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();

// Fetch staff data
$query = "SELECT 
    u.id, 
    u.username, 
    r.role_name as role,
    pd.first_name, 
    pd.middle_name, 
    pd.last_name, 
    pd.phone_number,
    'N/A' as program_types,
    'N/A' as display_program_type
    FROM users u
    LEFT JOIN personal_details pd ON u.id = pd.user_id
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name IN ('staff', 'coach')
    ORDER BY pd.last_name"; 

$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Staff Management</h2>
            <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">Add New Staff</button>
            </div>
        </div>

    <!-- Staff Table -->
    <div class="table-responsive">
    <table id="staffManagementTable" class="table table-striped table-bordered" id="staffTable">
        <thead >
            <tr>
                <th >Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($result as $row): ?>
            <tr>
                <td>
                    <?php 
                    echo $row['first_name'] . ' ' . 
                        ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . 
                        $row['last_name']; 
                    ?>
                </td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo ucfirst($row['role']); ?></td>
                <td><?php echo $row['phone_number']; ?></td>
                <td>
                    <button class="btn btn-sm btn-primary edit-btn" 
                            data-id="<?php echo $row['id']; ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#editStaffModal">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" 
                            data-id="<?php echo $row['id']; ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addStaffForm">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sex</label>
                                <select class="form-select" name="sex" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" class="form-control" name="birthdate" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="phone_number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="staff">Staff</option>
                                    <option value="coach">Coach</option>
                                </select>
                            </div>
                        </div>

                        <!-- Credentials Section -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStaffBtn">Save Staff</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStaffForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name" id="edit_middle_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sex</label>
                                <select class="form-select" name="sex" id="edit_sex" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" class="form-control" name="birthdate" id="edit_birthdate" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="phone_number" id="edit_phone_number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" id="edit_role" required>
                                    <option value="">Select Role</option>
                                    <option value="staff">Staff</option>
                                    <option value="coach">Coach</option>
                                </select>
                            </div>
                        </div>

                        <!-- Credentials Section -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="edit_username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" name="password" id="edit_password">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateStaffBtn">Update Staff</button>
            </div>
        </div>
    </div>
</div>

<!-- Staff Activity Log Section -->
<div class="container-fluid py-4">
    <h2>Activity Logs</h2>
    <div class="table-responsive">
        <?php
        // Fetch activity log data with staff details
        $logQuery = "SELECT 
            sal.id,
            sal.activity,
            sal.description,
            sal.timestamp,
            CONCAT(pd.first_name, ' ', pd.last_name) as staff_name
            FROM staff_activity_log sal
            LEFT JOIN users u ON sal.staff_id = u.id
            LEFT JOIN personal_details pd ON u.id = pd.user_id
            ORDER BY sal.timestamp DESC";
            
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute();
        $logResult = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <table id="activityLogTable" class="table table-striped table-bordered w-100">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>Staff Member</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logResult as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['activity']); ?></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo htmlspecialchars($log['staff_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    // Check if table is already initialized before initializing
    if (!$.fn.DataTable.isDataTable("#staffManagementTable")) {
        const table = $("#staffManagementTable").DataTable({
            pageLength: 10,
            ordering: false,
            responsive: true,
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        });
    }

    // Initialize Bootstrap modal
    const addStaffModal = new bootstrap.Modal(document.getElementById('addStaffModal'));

    // Handle form submission
    $('#saveStaffBtn').click(function() {
    const formData = new FormData($('#addStaffForm')[0]);
    
    $.ajax({
        url: '../admin/pages/staff management/functions/save_staff.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',  // Explicitly parse as JSON
        success: function(response) {
            if (response.success) {
                alert(response.message);  // Show success message
                addStaffModal.hide();
                $('#addStaffForm')[0].reset();
                location.reload();
            } else {
                // Show specific error message
                alert('Error: ' + response.message);
                console.error('Staff Save Error:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.log("Response Text:", xhr.responseText);
            alert('An error occurred while saving the staff member. Check console for details.');
        }
    });
});

    // Handle delete
    $('.delete-btn').click(function() {
        if (confirm('Are you sure you want to delete this staff member?')) {
            const staffId = $(this).data('id');
            
            $.ajax({
                url: '../admin/pages/staff management/functions/delete_staff.php',
                type: 'POST',
                data: { id: staffId },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the staff member.');
                }
            });
        }
    });

    // Handle modal close reset
    $('#addStaffModal').on('hidden.bs.modal', function () {
        $('#addStaffForm')[0].reset();
        // Removed reference to non-existent programTypeDiv
    });

    // Initialize Bootstrap modal for edit
    const editStaffModal = new bootstrap.Modal(document.getElementById('editStaffModal'));

    // Handle edit button click
    $('.edit-btn').click(function() {
        const staffId = $(this).data('id');
        
        // Fetch staff details
        $.ajax({
            url: '../admin/pages/staff management/functions/get_staff_details.php',
            type: 'GET',
            data: { id: staffId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const staff = response.data;
                    
                    // Populate form fields
                    $('#edit_id').val(staff.id);
                    $('#edit_username').val(staff.username);
                    $('#edit_first_name').val(staff.first_name);
                    $('#edit_middle_name').val(staff.middle_name);
                    $('#edit_last_name').val(staff.last_name);
                    $('#edit_sex').val(staff.sex);
                    $('#edit_birthdate').val(staff.birthdate);
                    $('#edit_phone_number').val(staff.phone_number);
                    $('#edit_role').val(staff.role);
                    
                    // Clear password field (it's optional for editing)
                    $('#edit_password').val('');
                    
                    // Show modal
                    editStaffModal.show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while fetching staff details.');
            }
        });
    });

    // Handle update form submission
    $('#updateStaffBtn').click(function() {
        const formData = new FormData($('#editStaffForm')[0]);
        
        $.ajax({
            url: '../admin/pages/staff management/functions/update_staff.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    editStaffModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response Text:", xhr.responseText);
                alert('An error occurred while updating the staff member. Check console for details.');
            }
        });
    });

    // Reset form when modal is closed
    $('#editStaffModal').on('hidden.bs.modal', function () {
        $('#editStaffForm')[0].reset();
    });

    // Check if activity log table is already initialized before initializing
    if (!$.fn.DataTable.isDataTable("#activityLogTable")) {
        $('#activityLogTable').DataTable({
            pageLength: 10,
            ordering: true,
            order: [[3, 'desc']], // Sort by timestamp by default
            responsive: true,
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        });
    }
});
</script>