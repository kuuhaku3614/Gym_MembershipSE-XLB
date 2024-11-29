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

<div class="container mt-4">
    <h1 class="nav-title">Staff Management</h1>

    <div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <div class="search-controls">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">Add New Staff</button>
                    </div>
                </div>

                <div class="col-md-6 d-flex justify-content-end">
                          <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
                    </div>
                </div>
            </div>
        </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
    </div>

    <!-- Staff Table -->
    <div class="table-responsive">
    <table id="staffManagementTable" class="table table-striped table-bordered" id="staffTable">
        <thead class="table-dark" >
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
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" 
                            data-id="<?php echo $row['id']; ?>">
                        <i class="fa-solid fa-trash"></i>
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

<script>
$(document).ready(function() {
    const table = $("#staffManagementTable").DataTable({
        pageLength: 10,
        ordering: false,
        responsive: true,
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
    });

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
});
</script>