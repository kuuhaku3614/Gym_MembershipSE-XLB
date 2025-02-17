<?php
require_once '../../../config.php';
include '../../pages/modal.php';

// Fetch data for display
$sql = "SELECT 
        rs.id,
        rs.service_name,
        rs.price,
        rs.total_slots,
        rs.available_slots,
        rs.duration,
        rs.description,
        dt.type_name AS duration_type,
        rs.status
    FROM rental_services rs
    LEFT JOIN duration_types dt ON rs.duration_type_id = dt.id
    WHERE rs.is_removed = 0
    ORDER BY rs.id DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching rentals: " . $e->getMessage());
    $rentals = [];
}

// Fetch duration types for the form
$durationTypesSql = "SELECT id, type_name FROM duration_types ORDER BY id";
try {
    $durationTypesStmt = $pdo->prepare($durationTypesSql);
    $durationTypesStmt->execute();
    $durationTypes = $durationTypesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching duration types: " . $e->getMessage());
    $durationTypes = [];
}
?>



<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Facility and Service Rentals</h2>
            <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add Service</button>
            <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
            </div>
        </div>
<div class="card">
<div class="card-body">
    <table id="rentalsTable" class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No.</th>
                <th>Service Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Total Slots</th>
                <th>Available Slots</th>
                <th>Description</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rentals)): ?>
            <tr>
                <td colspan="9" class="text-center">No rental services found</td>
            </tr>
            <?php else: ?>
                <?php 
                $count = 1;
                foreach ($rentals as $rental): 
                    $statusBadgeClass = $rental['status'] === 'active' ? 'bg-success' : 'bg-danger';
                    $toggleBtnText = $rental['status'] === 'active' ? 'Deactivate' : 'Activate';
                    $toggleBtnClass = $rental['status'] === 'active' ? 'btn-warning' : 'btn-success';
                ?>
                <tr>
                    <td class="text-center"><?= $count++ ?></td>
                    <td><?= htmlspecialchars($rental['service_name']) ?></td>
                    <td class="text-center"><?= $rental['duration'] . ' ' . $rental['duration_type'] ?></td>
                    <td class="text-end">₱<?= number_format($rental['price'], 2) ?></td>
                    <td class="text-center"><?= $rental['total_slots'] ?></td>
                    <td class="text-center"><?= $rental['available_slots'] ?></td>
                    <td><?php 
                        $description = $rental['description'] ?: 'N/A';
                        echo strlen($description) > 50 ? 
                            htmlspecialchars(substr($description, 0, 50) . '...') : 
                            htmlspecialchars($description);
                    ?></td>
                    <td class="text-center">
                        <span class="badge <?= $statusBadgeClass ?>">
                            <?= ucfirst($rental['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm <?= $toggleBtnClass ?> toggle-status" 
                                    data-id="<?= $rental['id'] ?>" data-status="<?= $rental['status'] ?>">
                                <?= $toggleBtnText ?>
                            </button>
                            <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $rental['id'] ?>">Edit</button>
                            <button class="btn btn-sm btn-danger remove-btn" data-id="<?= $rental['id'] ?>">Remove</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addServiceModalLabel">Add Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addServiceForm">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="serviceName" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="serviceName" name="serviceName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="totalSlots" class="form-label">Total Slots</label>
                                <input type="number" class="form-control" id="totalSlots" name="totalSlots" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="durationType" class="form-label">Duration Type</label>
                                        <select class="form-select" id="durationType" name="durationType" required>
                                            <option value="">Select Type</option>
                                            <?php foreach ($durationTypes as $type): ?>
                                                <option value="<?= htmlspecialchars($type['id']) ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveServiceBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editServiceForm">
                    <input type="hidden" id="editId" name="id">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editServiceName" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="editServiceName" name="serviceName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="editPrice" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editPrice" name="price" step="0.01" min="0" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editTotalSlots" class="form-label">Total Slots</label>
                                <input type="number" class="form-control" id="editTotalSlots" name="totalSlots" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editDuration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="editDuration" name="duration" min="1" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editDurationType" class="form-label">Duration Type</label>
                                        <select class="form-select" id="editDurationType" name="durationType" required>
                                            <option value="">Select Type</option>
                                            <?php foreach ($durationTypes as $type): ?>
                                                <option value="<?= htmlspecialchars($type['id']) ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateServiceBtn">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
    // Initialize DataTable
    $('#rentalsTable').DataTable({
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
$(document).ready(function () {
    
    // Save button handler
    $('#saveServiceBtn').click(function () {
        // Clear previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Validate all required fields
        const isValid = 
            validateField('serviceName', 'Service name is required') &
            validateField('price', 'Price must be greater than 0') &
            validateField('totalSlots', 'Number of slots must be greater than 0') &
            validateField('duration', 'Duration must be greater than 0') &
            validateField('durationType', 'Duration type must be selected');

        // If any validation fails, stop submission
        if (!isValid) {
            return false;
        }

        // Get form data
        var formData = {
            serviceName: $('#serviceName').val().trim(),
            duration: $('#duration').val(),
            durationType: $('#durationType').val(),
            totalSlots: $('#totalSlots').val(),
            price: $('#price').val(),
            description: $('#description').val().trim()
        };

        // Send AJAX request
        $.ajax({
        url: '../admin/pages/gym rates/functions/save_rentals.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.trim() === 'success') {
                // Show the success modal
                $('#successModal').modal('show');
                $('#addServiceModal').modal('hide');
                // Optionally reload the page after a delay
                setTimeout(function() {
                    location.reload();
                }, 2000); // Adjust the delay as needed
            } else {
                alert(response);
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while saving: ' + error);
        }
    });
    });

    // Edit button handler
    $(document).on('click', '.edit-btn', function() {
        const rentalId = $(this).data('id');
        
        // Fetch rental service details
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_rental_services.php',
            type: 'GET',
            data: { id: rentalId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // Populate form fields
                    $('#editId').val(data.data.id);
                    $('#editServiceName').val(data.data.service_name);
                    $('#editDuration').val(data.data.duration);
                    $('#editDurationType').val(data.data.duration_type_id);
                    $('#editTotalSlots').val(data.data.total_slots);
                    $('#editPrice').val(data.data.price);
                    $('#editDescription').val(data.data.description);
                    
                    // Show modal
                    $('#editServiceModal').modal('show');
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while fetching rental service details: ' + error);
            }
        });
    });

    // Update button handler
    $('#updateServiceBtn').click(function() {
        // Clear previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Validate all required fields
        const isValid = 
            validateField('editServiceName', 'Service name is required') &
            validateField('editPrice', 'Price must be greater than 0') &
            validateField('editTotalSlots', 'Number of slots must be greater than 0') &
            validateField('editDuration', 'Duration must be greater than 0') &
            validateField('editDurationType', 'Duration type must be selected');

        // If any validation fails, stop submission
        if (!isValid) {
            return false;
        }

        // Get form data
        var formData = {
            id: $('#editId').val(),
            serviceName: $('#editServiceName').val().trim(),
            duration: $('#editDuration').val(),
            durationType: $('#editDurationType').val(),
            totalSlots: $('#editTotalSlots').val(),
            price: $('#editPrice').val(),
            description: $('#editDescription').val().trim()
        };

        // Send AJAX request
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_rental_services.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // Update the modal body with the success message
                    $('#updateSuccessModal .modal-body p').text(response.message || "Updated successfully!");
                    
                    // Show the success modal
                    $('#updateSuccessModal').modal('show');
                    
                    // Hide the edit modal
                    $('#editServiceModal').modal('hide');
                    
                    // Reload the page after the modal is hidden
                    $('#updateSuccessModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
                } else {
                    // Show an error modal (you may want to create this modal)
                    $('#errorModal').modal('show').find('.modal-body').text('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                // Show an error modal (you may want to create this modal)
                $('#errorModal').modal('show').find('.modal-body').text('Error occurred while updating: ' + error);
            }
        });
    });

    // Toggle status button handler
$(document).on('click', '.toggle-status', function() {
    const rentalId = $(this).data('id');
    const currentStatus = $(this).data('status');
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

    // Show the appropriate modal based on the current status
    if (currentStatus === 'active') {
        $('#deactivateModal').modal('show');
    } else {
        $('#activateModal').modal('show');
    }

    // Add click handlers to the modal buttons
    $('#confirmDeactivate, #confirmActivate').off('click').on('click', function() {
        $.ajax({
            url: '../admin/pages/gym rates/functions/save_rentals.php',
            type: 'POST',
            data: {
                action: 'toggle_status',
                id: rentalId,
                status: newStatus
            },
            success: function(response) {
                if (response === 'success') {
                    // Close the modal and reload the page
                    $('#deactivateModal, #activateModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while updating status: ' + error);
            }
        });
    });
});

        // Remove button handler
    $(document).on('click', '.remove-btn', function() {
        const rentalId = $(this).data('id');
        
        // Show the delete confirmation modal
        $('#deleteModal').modal('show');

        // Set the rental ID in a data attribute for later use
        $('#confirmDelete').data('id', rentalId);
    });

    // Confirm delete button handler
    $(document).on('click', '#confirmDelete', function() {
        const rentalId = $(this).data('id');

        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_rental_services.php',
            type: 'POST',
            data: {
                action: 'remove',
                id: rentalId
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // alert(data.message); // You can replace this with a success modal if desired
                    location.reload();
                } else {
                    alert('Error: ' + data.message); // You can also replace this with an error modal
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while removing rental service: ' + error); // Replace with an error modal if desired
            }
        });

        // Hide the modal after confirming deletion
        $('#deleteModal').modal('hide');
    });

    // Refresh button handler
    $('#refreshBtn').click(function() {
        location.reload();
    });

    // Reset form when modal is closed
    $('#addServiceModal').on('hidden.bs.modal', function () {
        $('#addServiceForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });

    // Reset edit form when modal is closed
    $('#editServiceModal').on('hidden.bs.modal', function () {
        $('#editServiceForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });

    // Function to validate a field and show error message
    function validateField(fieldId, errorMessage) {
        const field = $('#' + fieldId);
        const value = field.val().trim();
        
        // Clear previous validation
        field.removeClass('is-invalid');
        field.next('.invalid-feedback').text('');
        
        if (!value || (field.attr('type') === 'number' && (isNaN(value) || parseFloat(value) <= 0))) {
            field.addClass('is-invalid');
            field.next('.invalid-feedback').text(errorMessage);
            return false;
        }
        return true;
    }
});
</script>
