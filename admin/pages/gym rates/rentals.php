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
        rs.image,
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
    <table id="rentalsTable" class="table table-hovered">
        <thead class="table-light border">
            <tr>
                <th class="border">Image</th>
                <th class="border">No.</th>
                <th class="border">Service Name</th>
                <th class="border">Duration</th>
                <th class="border">Price</th>
                <th class="border">Total Slots</th>
                <th class="border">Available Slots</th>
                <th class="border">Description</th>
                <th class="border">Status</th>
                <th class="border">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rentals)): ?>

            <?php else: ?>
                <?php 
                $count = 1;
                foreach ($rentals as $rental): 
                    $statusBadgeClass = $rental['status'] === 'active' ? 'bg-success' : 'bg-danger';
                    $toggleBtnText = $rental['status'] === 'active' ? 'Deactivate' : 'Activate';
                    $toggleBtnClass = $rental['status'] === 'active' ? 'btn-warning' : 'btn-success';
                ?>
                <tr>
                    <td><?php
                    // Check if the image exists using the correct absolute path
                    if (!empty($rental['image']) ) {
                        // Use a path relative to the web root for the src attribute
                        echo "<img src='__DIR__ . ../../../cms_img/rentals/" . htmlspecialchars($rental['image']) . "' alt='Rental Image' class='img-thumbnail' width='80'>";
                    } else {
                        echo "No Image";
                    }
                 
                    ?>
                    </td>
                    <td class="text-center"><?= $count++ ?></td>
                    <td><?= htmlspecialchars($rental['service_name']) ?></td>
                    <td class="text-center"><?= $rental['duration'] . ' ' . $rental['duration_type'] ?></td>
                    <td class="text-end">₱<?= number_format($rental['price'], 2) ?></td>
                    <td class="text-center"><?= $rental['total_slots'] ?></td>
                    <td class="text-center"><?= $rental['available_slots'] ?></td>
                    <td><?php 
                        $description = $rental['description'] ?: 'N/A';
                        echo strlen($description) > 20 ? 
                            htmlspecialchars(substr($description, 0, 20) . '...') : 
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
                            <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $rental['id'] ?>"><i class='fas fa-power-off'></i></button>
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
                                <label for="rentalImage" class="form-label">Rental Image</label>
                                <input type="file" class="form-control" id="rentalImage" name="rentalImage" accept="image/*">
                            </div>


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
                                <label for="editRentalImage" class="form-label">Edit Rental Image</label>
                                <input type="file" class="form-control" id="editRentalImage" name="editRentalImage" accept="image/*">
                            </div>

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
    
    $('#saveServiceBtn').click(function () {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    const isValid = 
        validateField('serviceName', 'Service name is required') &
        validateField('price', 'Price must be greater than 0') &
        validateField('totalSlots', 'Number of slots must be greater than 0') &
        validateField('duration', 'Duration must be greater than 0') &
        validateField('durationType', 'Duration type must be selected');

    if (!isValid) {
        return false;
    }

    // Create FormData object
    let formData = new FormData();
    formData.append('serviceName', $('#serviceName').val().trim());
    formData.append('duration', $('#duration').val());
    formData.append('durationType', $('#durationType').val());
    formData.append('totalSlots', $('#totalSlots').val());
    formData.append('price', $('#price').val());
    formData.append('description', $('#description').val().trim());

    // Append image file (if selected)
    let imageFile = $('#rentalImage')[0].files[0];
    if (imageFile) {
        formData.append('rentalImage', imageFile);
    }

    // Send AJAX request
    $.ajax({
        url: '../admin/pages/gym rates/functions/save_rentals.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        contentType: false,  // Important for file upload
        processData: false,  // Prevent jQuery from processing data
        success: function(response) {
            if (response.status === 'success') {
                $('#addServiceModal').modal('hide');
                $('#successModal .modal-body p').text(response.message || 'Service created successfully!');
                new bootstrap.Modal(document.getElementById('successModal')).show();

                // Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                if (response.debug) {
                    console.error('Error details:', response.debug);
                }
                if (response.errors) {
                    Object.keys(response.errors).forEach(function(field) {
                        const fieldElement = $('#' + field);
                        if (fieldElement.length) {
                            fieldElement.addClass('is-invalid');
                            fieldElement.next('.invalid-feedback').text(response.errors[field]);
                        }
                    });
                } else {
                    alert(response.message || "Error saving service");
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            try {
                var response = JSON.parse(xhr.responseText);
                alert(response.message || "Error saving service. Please try again.");
            } catch (e) {
                alert("Error saving service. Please try again.");
            }
        }
    });
});

$(document).on('click', '.edit-btn', function () {
    const rentalId = $(this).data('id');

    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_rental_services.php',
        type: 'GET',
        data: { id: rentalId },
        success: function (response) {
            console.log("Raw response:", response);
            console.log("Type of response:", typeof response);

            let data = response;

            console.log("Parsed Data:", data);

            if (data.status === 'success') {
                $('#editId').val(data.data.id);
                $('#editServiceName').val(data.data.service_name);
                $('#editDuration').val(data.data.duration);
                $('#editDurationType').val(data.data.duration_type_id);
                $('#editTotalSlots').val(data.data.total_slots);
                $('#editPrice').val(data.data.price);
                $('#editDescription').val(data.data.description);

                if (data.data.image) {
                    $('#currentImage').attr('src', '../cms_img/rentals/' + data.data.image).show();
                } else {
                    $('#currentImage').hide();
                }

                $('#editServiceModal').modal('show');
            } else {
                alert('Error: ' + data.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error, xhr.responseText);
            alert('Error occurred while fetching rental service details: ' + error);
        }
    });
});



// Update button handler
$('#updateServiceBtn').click(function () {
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

    // Prepare form data with an image file
    var formData = new FormData();
    formData.append('id', $('#editId').val());
    formData.append('serviceName', $('#editServiceName').val().trim());
    formData.append('duration', $('#editDuration').val());
    formData.append('durationType', $('#editDurationType').val());
    formData.append('totalSlots', $('#editTotalSlots').val());
    formData.append('price', $('#editPrice').val());
    formData.append('description', $('#editDescription').val().trim());

    // Check if a new image is selected
    var imageFile = $('#editRentalImage')[0].files[0];
    if (imageFile) {
        formData.append('editRentalImage', imageFile);
    }

    // Prepare FormData for submission (similar to gym rates)
    formData.append('action', 'update');
   // Send AJAX request with image handling
$.ajax({
    url: '../admin/pages/gym rates/functions/edit_rental_services.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false, // Required for FormData
    success: function (response) {
        try {
            // Ensure response is an object, if not parse it
            const data = (typeof response === "object") ? response : JSON.parse(response);

            if (data.status === 'success') {
                // Update the modal body with the success message
                $('#updateSuccessModal .modal-body p').text(data.message || "Updated successfully!");

                // Show the success modal
                $('#updateSuccessModal').modal('show');

                // Hide the edit modal
                $('#editServiceModal').modal('hide');

                // Reload the page after the modal is hidden
                $('#updateSuccessModal').on('hidden.bs.modal', function () {
                    location.reload();
                });
            } else {
                // Show an error modal
                $('#errorModal').modal('show').find('.modal-body').text('Error: ' + (data.message || "Unexpected error occurred."));
            }
        } catch (e) {
            console.error("Invalid JSON response:", response);
            $('#errorModal').modal('show').find('.modal-body').text('Error: Invalid JSON response from server.');
        }
    },
    error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
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
                new_status: newStatus
            },
            success: function(response) {
    try {
        const result = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (result.status === 'success') {
            // Close the modal and reload the page
            $('#deactivateModal, #activateModal').modal('hide');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Unknown error'));
        }
    } catch (e) {
        alert('Error parsing response: ' + response);
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
            
            // Show loading overlay
            $('#loadingOverlay').show();
            
            $.ajax({
                url: '../admin/pages/gym rates/functions/edit_rental_services.php',
                type: 'POST',
                data: {
                    action: 'remove',
                    id: rentalId
                },
                success: function(response) {
                    try {
                        const data = typeof response === 'object' ? response : JSON.parse(response);
                        
                        // Hide loading overlay
                        $('#loadingOverlay').hide();
                        
                        if (data.status === 'success') {
                            // Show success message
                            $('#successModal .modal-body p').text(data.message || 'Rental service removed successfully!');
                            $('#successModal').modal('show');
                            
                            // Reload after modal is closed or after short delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            $('#errorModal .modal-body').text('Error: ' + data.message);
                            $('#errorModal').modal('show');
                        }
                    } catch (e) {
                        // Hide loading overlay
                        $('#loadingOverlay').hide();
                        
                        // Show parsing error
                        $('#errorModal .modal-body').text('Error parsing response');
                        $('#errorModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading overlay
                    $('#loadingOverlay').hide();
                    
                    // Show ajax error
                    $('#errorModal .modal-body').text('Error occurred while removing rental service: ' + error);
                    $('#errorModal').modal('show');
                }
            });

            // Hide the confirmation modal
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
