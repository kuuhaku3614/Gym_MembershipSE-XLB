<?php
require_once '../../../config.php';
include '../../pages/modal.php';

// Define base URL for AJAX calls
$baseUrl = '/Gym_MembershipSE-XLB/admin/pages/gym rates';

// Fetch programs - simplified query without coach information
$sql = "SELECT * FROM programs WHERE is_removed = 0 ORDER BY id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs Management</title>
    
    <style>
        .btn-group .btn {
            margin: 0 2px;
        }
        .table td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: top;
            padding: 15px !important;
        }
        .description-cell {
            white-space: pre-wrap !important;
            word-break: break-word;
            min-width: 300px;
        }
    </style>
</head>
<body>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Programs</h2>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">Add Program</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="programsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>No.</th>
                        <th>Program Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 1; // Initialize counter
                foreach ($programs as $program) {
                    echo "<tr>";
                    echo "<td>";
                    // Check if the image exists using the correct absolute path
                    if (!empty($program['image']) && file_exists(__DIR__ . "/../../../cms_img/programs/" . $program['image'])) {
                        // Use a path relative to the web root for the src attribute
                        echo "<img src='../../../cms_img/programs/" . htmlspecialchars($program['image']) . "' alt='Programs Image' class='img-thumbnail' width='80'>";
                    } else {
                        echo "No Image";
                    }
                    echo "</td>";
                    echo "<td>" . $count++ . "</td>";
                    echo "<td>" . htmlspecialchars($program['program_name']) . "</td>";
                    echo "<td>";
                    $description = $program['description'] ?: 'N/A';
                    echo strlen($description) > 50 ? 
                        htmlspecialchars(substr($description, 0, 50) . '...') : 
                        htmlspecialchars($description);
                    echo "</td>";
                    echo "<td>" . htmlspecialchars($program['status']) . "</td>";
                    echo "<td>";
                    echo "<div class='d-grid gap-2'>";
                    if ($program['status'] === 'active') {
                        echo "<button class='btn btn-warning btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-new-status='inactive'>Deactivate</button>";
                    } else {
                        echo "<button class='btn btn-success btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-new-status='active'>Activate</button>";
                    }
                    echo "<button class='btn btn-primary btn-sm edit-btn' data-id='" . $program['id'] . "'>Edit</button>";
                    echo "<button class='btn btn-danger btn-sm remove-btn' data-id='" . $program['id'] . "'>Remove</button>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal" id="addProgramModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addProgramModalLabel">Add Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProgramForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="programImage" class="form-label">Program Image</label>
                                    <input type="file" class="form-control" id="programImage" name="programImage" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="programName" class="form-label">Program Name</label>
                                    <input type="text" class="form-control" id="programName" name="programName" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="programStatus" class="form-label">Status</label>
                                    <select class="form-select" id="programStatus" name="programStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Full Width Description -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProgramBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Program Modal -->
    <div class="modal fade" id="editProgramModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editProgramModalLabel">Edit Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProgramForm">
                        <input type="hidden" id="editProgramId" name="programId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editProgramImage" class="form-label">Program Image</label>
                                    <input type="file" class="form-control" id="editProgramImage" name="editProgramImage" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editProgramName" class="form-label">Program Name</label>
                                    <input type="text" class="form-control" id="editProgramName" name="programName" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editProgramStatus" class="form-label">Status</label>
                                    <select class="form-select" id="editProgramStatus" name="programStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Full Width Description -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="editDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateProgramBtn">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Remove</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove this program?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modals -->
    <div class="modal fade" id="activateModal" tabindex="-1" aria-labelledby="activateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="activateModalLabel">Confirm Activation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to activate this program?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmActivate">Activate</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="deactivateModalLabel">Confirm Deactivation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to deactivate this program?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning text-white" id="confirmDeactivate">Deactivate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Program added successfully!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateSuccessModal" tabindex="-1" aria-labelledby="updateSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="updateSuccessModalLabel">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Program updated successfully!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
// Continuing from the previous script
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#programsTable')) {
        $('#programsTable').DataTable().destroy(); // Destroy existing instance
    }
    
    $('#programsTable').DataTable({
        responsive: true,
        order: [[1, 'asc']], // Sort by second column ascending for consistency
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search programs..."
        },
        columnDefs: [
            { orderable: false, targets: [0, 5] } // Don't allow sorting on image and actions columns
        ]
    });

    // Refresh button
    $('#refreshBtn').on('click', function() {
        location.reload();
    });

    // Add Program Form Submit
    $('#saveProgramBtn').on('click', function() {
        const formData = new FormData($('#addProgramForm')[0]);
        formData.append('programStatus', $('#programStatus').val());
        
        $.ajax({
            url: '../admin/pages/gym rates/functions/save_programs.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    $('#addProgramModal').modal('hide');
                    $('#successModal').modal('show');
                    $('#successModal').on('hidden.bs.modal', function() {
                        location.reload();
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Handle Edit Button Click
    $('.edit-btn').on('click', function() {
        const programId = $(this).data('id');
        
        // Fetch program data
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_programs.php',
            type: 'GET',
            data: { id: programId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const program = response.data;
                    
                    // Populate form fields
                    $('#editProgramId').val(program.id);
                    $('#editProgramName').val(program.program_name);
                    $('#editDescription').val(program.description);
                    $('#editProgramStatus').val(program.status);
                    
                    // Show modal
                    $('#editProgramModal').modal('show');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Update Program Form Submit
    $('#updateProgramBtn').on('click', function() {
        const formData = new FormData($('#editProgramForm')[0]);
        formData.append('action', 'update');
        
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_programs.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#editProgramModal').modal('hide');
                    $('#updateSuccessModal').modal('show');
                    $('#updateSuccessModal').on('hidden.bs.modal', function() {
                        location.reload();
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Handle Remove Button Click
    $('.remove-btn').on('click', function() {
        const programId = $(this).data('id');
        $('#deleteModal').data('id', programId).modal('show');
    });

    // Confirm Delete
    $('#confirmDelete').on('click', function() {
        const programId = $('#deleteModal').data('id');
        
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_programs.php',
            type: 'POST',
            data: {
                action: 'remove',
                programId: programId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Handle Status Toggle Buttons
    $('.toggle-status-btn').on('click', function() {
        const programId = $(this).data('id');
        const newStatus = $(this).data('new-status');
        
        // Show appropriate confirmation modal
        if (newStatus === 'active') {
            $('#activateModal').data('id', programId).modal('show');
        } else {
            $('#deactivateModal').data('id', programId).modal('show');
        }
    });

    // Confirm Activate
    $('#confirmActivate').on('click', function() {
        const programId = $('#activateModal').data('id');
        toggleStatus(programId, 'active');
    });

    // Confirm Deactivate
    $('#confirmDeactivate').on('click', function() {
        const programId = $('#deactivateModal').data('id');
        toggleStatus(programId, 'inactive');
    });

    // Toggle Status Function
    function toggleStatus(programId, newStatus) {
        $.ajax({
            url: '../admin/pages/gym rates/functions/save_programs.php',
            type: 'POST',
            data: {
                action: 'toggle_status',
                id: programId,
                new_status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#activateModal').modal('hide');
                    $('#deactivateModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    }
});
</script>