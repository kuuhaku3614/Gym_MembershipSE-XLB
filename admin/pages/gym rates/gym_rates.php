<?php
require_once '../../../config.php';
include '../../pages/modal.php';

// select gym rates
$sql = "SELECT mp.*, dt.type_name as duration_type, mp.description 
        FROM membership_plans mp
        LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
        WHERE is_removed = 0
        ORDER BY mp.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Update Registration Modal -->
<div class="modal fade" id="updateRegistrationModal" tabindex="-1" aria-labelledby="updateRegistrationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateRegistrationModalLabel">Update Registration Fee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Current Registration Fee: ₱<span id="currentRegistrationFee">0.00</span>
                </div>
                <form id="updateRegistrationForm">
                    <div class="form-group">
                        <label for="newRegistrationFee">New Registration Fee</label>
                        <input type="number" class="form-control" id="newRegistrationFee" name="newRegistrationFee" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRegistrationFeeBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gym Rates</h2>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGymRateModal">Add Gym Rate</button>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateRegistrationModal">Update Registration</button>
                <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
            </div>
        </div>


    <div class="table-responsive">
    <table id="gymRatesTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No.</th>
            <th>Promo Name</th>
            <th>Promo Type</th>
            <th>Duration</th>
            <th>Start Date</th>
            <th>Deactivation Date</th>
            <th>Price</th>
            <th>Description</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $count = 1;
        if (!empty($result)) {
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $count . "</td>";
                echo "<td>" . htmlspecialchars($row['plan_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['plan_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['duration']) . " " . htmlspecialchars($row['duration_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                echo "<td>";
                $description = $row['description'] ?: 'N/A';
                echo strlen($description) > 50 ? 
                    htmlspecialchars(substr($description, 0, 50) . '...') : 
                    htmlspecialchars($description);
                echo "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td class='d-flex flex-column align-items-center'>";
                if ($row['status'] === 'active') {
                    echo "<button class='btn btn-sm btn-warning toggle-status-btn mb-1' data-id='" . $row['id'] . "' style='width: 100%;'>Deactivate</button>";
                } else {
                    echo "<button class='btn btn-sm btn-success toggle-status-btn mb-1' data-id='" . $row['id'] . "' style='width: 100%;'>Activate</button>";
                }
                echo "<button class='btn btn-sm btn-primary edit-gym-rate mb-1' data-id='" . $row['id'] . "' style='width: 100%;'>Edit</button>";
                echo "<button class='btn btn-danger btn-sm remove-btn' data-id='" . $row['id'] . "' style='width: 100%;'>Remove</button>";
                echo "</td>";
                echo "</tr>";
                $count++;
            }
        } else {
            echo "<tr><td colspan='10'>No data available</td></tr>";
        }
        ?>
    </tbody>
</table>
</div>
</div>

<!-- Modified Modal -->
<div class="modal fade" id="addGymRateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addGymRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addGymRateModalLabel">Add Gym Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addGymRateForm">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="promoName" class="form-label">Promo Name</label>
                                <input type="text" class="form-control" id="promoName" name="promoName" required>
                            </div>
                            <div class="mb-3">
                                <label for="promoType" class="form-label">Promo Type</label>
                                <select class="form-select" id="promoType" name="promoType" required>
                                    <option value="">Select Promo Type</option>
                                    <option value="standard">Standard</option>
                                    <option value="special">Special</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="duration" name="duration" required min="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="durationType" class="form-label">Duration Type</label>
                                        <select class="form-select" id="durationType" name="durationType" required>
                                            <option value="">Select Type</option>
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="year">Year</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="activationDate" class="form-label">Activation Date</label>
                                <input type="date" class="form-control" id="activationDate" name="activationDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="deactivationDate" class="form-label">Deactivation Date</label>
                                <input type="date" class="form-control" id="deactivationDate" name="deactivationDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="price" name="price" required min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <!-- Full Width Description -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <!-- Total Price Display -->
                            <div class="alert alert-info" id="totalPriceDisplay" style="display: none;">
                                Total Price: ₱<span id="totalPriceValue">0.00</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveGymRateBtn">Save</button>
            </div>
        </div>
    </div>
</div>




<!-- Edit Gym Rate Modal -->
<div class="modal fade" id="editGymRateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editGymRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editGymRateModalLabel">Edit Gym Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editGymRateForm">
                    <input type="hidden" id="editGymRateId" name="id">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editPromoName" class="form-label">Promo Name</label>
                                <input type="text" class="form-control" id="editPromoName" name="promoName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editPromoType" class="form-label">Promo Type</label>
                                <select class="form-select" id="editPromoType" name="promoType" required>
                                    <option value="">Select Promo Type</option>
                                    <option value="standard">Standard</option>
                                    <option value="special">Special</option>
                                    <option value="walk-in">Walk-in</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editDuration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="editDuration" name="duration" required min="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editDurationType" class="form-label">Duration Type</label>
                                        <select class="form-select" id="editDurationType" name="durationType" required>
                                            <option value="">Select Type</option>
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="year">Year</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editActivationDate" class="form-label">Activation Date</label>
                                <input type="date" class="form-control" id="editActivationDate" name="activationDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDeactivationDate" class="form-label">Deactivation Date</label>
                                <input type="date" class="form-control" id="editDeactivationDate" name="deactivationDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="editPrice" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editPrice" name="price" required min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <!-- Full Width Description -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="editDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateGymRateBtn">Update</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#gymRatesTable')) {
        $('#gymRatesTable').DataTable().destroy();  // Destroy existing instance
    }
    $('#gymRatesTable').DataTable({ 
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
    
    http://localhost/gym_membershipse-xlb/admin/member_status
// Validate form before submission
$('#saveGymRateBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Create validation function
    function validateField(fieldId, errorMessage) {
        const field = $(`#${fieldId}`);
        const value = field.val().trim();
        if (!value) {
            field.addClass('is-invalid');
            field.after(`<div class="invalid-feedback">${errorMessage}</div>`);
            return false;
        }
        return true;
    }

    // Validate all required fields
    const isValid = 
        validateField('promoName', 'Promo Name is required') &
        validateField('promoType', 'Promo Type must be selected') &
        validateField('duration', 'Duration is required') &
        validateField('durationType', 'Duration Type must be selected') &
        validateField('activationDate', 'Activation Date is required') &
        validateField('deactivationDate', 'Deactivation Date is required') &
        validateField('price', 'Price is required');

    // Check activation and deactivation dates
    const activationDate = new Date($('#activationDate').val());
    const deactivationDate = new Date($('#deactivationDate').val());
    
    if (activationDate >= deactivationDate) {
        $('#deactivationDate').addClass('is-invalid');
        $('#deactivationDate').after('<div class="invalid-feedback">Deactivation date must be after activation date</div>');
        return false;
    }

    // If any validation fails, stop submission
    if (!isValid) {
        return false;
    }

    // Proceed with form submission
    var price = parseFloat($('#price').val()) || 0;
    var membershipFee = parseFloat($('#membershipFee').val()) || 0;
    var totalPrice = price + membershipFee;
    
    var formData = $('#addGymRateForm').serializeArray();
    var updatedFormData = formData.map(function(item) {
        if (item.name === 'price') {
            return { name: 'price', value: totalPrice };
        }
        return item;
    });
    
    $.ajax({
    url: '../admin/pages/gym rates/functions/save_gym_rates.php',
    type: 'POST',
    data: $.param(updatedFormData),
    success: function(response) {
        if (response.trim() === "success") {
            // Hide the add gym rate modal
            $('#addGymRateModal').modal('hide');
            
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            
            // Add event listener for when success modal is hidden
            $('#successModal').on('hidden.bs.modal', function () {
                location.reload();
            });
        } else {
            alert("Error: " + response);
        }
    },
    error: function(xhr, status, error) {
        alert("AJAX error: " + error);
    }
});
});

// Reset form when modal is closed
$('#addGymRateModal').on('hidden.bs.modal', function () {
    $('#addGymRateForm')[0].reset();
    $('#totalPriceDisplay').hide();
});





// Add click handler for edit buttons
$(document).on('click', '.edit-gym-rate', function() {
    const gymRateId = $(this).data('id');
    
    // Fetch gym rate details
    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_gym_rates.php',
        type: 'GET',
        data: { id: gymRateId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                const gymRate = data.data;
                
                // Populate the form
                $('#editGymRateId').val(gymRate.id);
                $('#editPromoName').val(gymRate.plan_name);
                $('#editPromoType').val(gymRate.plan_type);
                $('#editDuration').val(gymRate.duration);
                $('#editDurationType').val(gymRate.duration_type);
                $('#editActivationDate').val(gymRate.start_date);
                $('#editDeactivationDate').val(gymRate.end_date);
                $('#editPrice').val(gymRate.price);
                $('#editDescription').val(gymRate.description);
                
                // Show the modal
                $('#editGymRateModal').modal('show');
            } else {
                alert('Error: ' + data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while fetching gym rate details: ' + error);
        }
    });
});

// Handle form submission for editing
$('#updateGymRateBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Create validation function
    function validateField(fieldId, errorMessage) {
        const field = $(`#${fieldId}`);
        const value = field.val().trim();
        if (!value) {
            field.addClass('is-invalid');
            field.after(`<div class="invalid-feedback">${errorMessage}</div>`);
            return false;
        }
        return true;
    }

    // Validate all required fields
    const isValid = 
        validateField('editPromoName', 'Promo Name is required') &
        validateField('editPromoType', 'Promo Type must be selected') &
        validateField('editDuration', 'Duration is required') &
        validateField('editDurationType', 'Duration Type must be selected') &
        validateField('editActivationDate', 'Activation Date is required') &
        validateField('editDeactivationDate', 'Deactivation Date is required') &
        validateField('editPrice', 'Price is required');

    // Check activation and deactivation dates
    const activationDate = new Date($('#editActivationDate').val());
    const deactivationDate = new Date($('#editDeactivationDate').val());
    
    if (activationDate >= deactivationDate) {
        $('#editDeactivationDate').addClass('is-invalid');
        $('#editDeactivationDate').after('<div class="invalid-feedback">Deactivation date must be after activation date</div>');
        return false;
    }

    if (!isValid) {
        return false;
    }

    // Submit form data
    const formData = new FormData($('#editGymRateForm')[0]);
    formData.append('action', 'update');

    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_gym_rates.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                // Update the modal body with the success message
                $('#updateSuccessModal .modal-body p').text(response.message || "Updated successfully!");
                    
                    // Show the success modal
                    $('#updateSuccessModal').modal('show');
                    
                    // Hide the edit modal
                    $('#editGymRateModal').modal('hide');
                    
                    // Reload the page after the modal is hidden
                    $('#updateSuccessModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
            } else {
                alert('Error: ' + data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while updating gym rate: ' + error);
        }
    });
});

// Reset form when edit modal is closed
$('#editGymRateModal').on('hidden.bs.modal', function () {
    $('#editGymRateForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
});

// Handle remove button click
$(document).on('click', '.remove-btn', function() {
    const gymRateId = $(this).data('id');
    
    // Show delete confirmation modal
    $('#deleteModal').modal('show');
    
    // Set the action for the delete button in the modal
    $('#confirmDelete').off('click').on('click', function() {
        $.ajax({
            url: '../admin/pages/gym rates/functions/edit_gym_rates.php',
            type: 'POST',
            data: {
                action: 'remove',
                id: gymRateId
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while removing gym rate: ' + error);
            }
        });
    });
});

// Save new registration fee
$('#saveRegistrationFeeBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Get the new fee value
    var newFee = $('#newRegistrationFee').val();
    
    // Basic validation
    if (!newFee || newFee <= 0) {
        $('#newRegistrationFee').addClass('is-invalid');
        $('#newRegistrationFee').after('<div class="invalid-feedback">Please enter a valid registration fee amount</div>');
        return;
    }

    // Send AJAX request
        $.ajax({
        url: '../admin/pages/gym rates/functions/update_registration_fee.php',
        type: 'POST',
        data: {
            newRegistrationFee: newFee
        },
        success: function(response) {
            if (response.trim() === 'success') {
                $('#updateRegistrationModal').modal('hide');
                $('#updateSuccessModal').modal('show');
            } else {
                alert(response);
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while updating registration fee: ' + error);
            console.log('AJAX Error:', status, error);
        }
    });
});

// Function to fetch current registration fee
function fetchCurrentRegistrationFee() {
    $.ajax({
        url: '../admin/pages/gym rates/functions/update_registration_fee.php',
        type: 'GET',
        success: function(response) {
            if (!response.startsWith('Error:')) {
                $('#currentRegistrationFee').text(parseFloat(response).toFixed(2));
            } else {
                alert(response);
                $('#currentRegistrationFee').text('N/A');
            }
        },
        error: function(xhr, status, error) {
            console.log('Error:', error);
            $('#currentRegistrationFee').text('N/A');
        }
    });
}

// Fetch current fee when modal is opened
$('#updateRegistrationModal').on('show.bs.modal', function() {
    fetchCurrentRegistrationFee();
});

// Reset form when modal is closed
$('#updateRegistrationModal').on('hidden.bs.modal', function () {
    $('#updateRegistrationForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
});

// Handle status toggle (activate/deactivate)
$('.toggle-status-btn').click(function() {
    const btn = $(this);
    const gymRateId = btn.data('id');
    const currentStatus = btn.text().toLowerCase();
    
    // Determine which modal to show based on current status
    if (currentStatus === 'activate') {
        $('#activateModal').data('id', gymRateId);
        $('#activateModal').modal('show');
    } else if (currentStatus === 'deactivate') {
        $('#deactivateModal').data('id', gymRateId);
        $('#deactivateModal').modal('show');
    }
});

// Confirm Activation Handler
$('#confirmActivate').click(function() {
    const gymRateId = $('#activateModal').data('id');
    
    $.ajax({
        url: '../admin/pages/gym rates/functions/save_gym_rates.php',
        type: 'POST',
        data: {
            action: 'toggle_status',
            id: gymRateId,
            status: 'active'
        },
        success: function(response) {
            if (response.trim() === 'success') {
                location.reload();
            } else {
                alert('Error: ' + response);
            }
            $('#activateModal').modal('hide');
        },
        error: function(xhr, status, error) {
            alert('Error occurred while updating status: ' + error);
            console.log('AJAX Error:', status, error);
            $('#activateModal').modal('hide');
        }
    });
});

// Confirm Deactivation Handler
$('#confirmDeactivate').click(function() {
    const gymRateId = $('#deactivateModal').data('id');
    
    $.ajax({
        url: '../admin/pages/gym rates/functions/save_gym_rates.php',
        type: 'POST',
        data: {
            action: 'toggle_status',
            id: gymRateId,
            status: 'inactive'
        },
        success: function(response) {
            if (response.trim() === 'success') {
                location.reload();
            } else {
                alert('Error: ' + response);
            }
            $('#deactivateModal').modal('hide');
        },
        error: function(xhr, status, error) {
            alert('Error occurred while updating status: ' + error);
            console.log('AJAX Error:', status, error);
            $('#deactivateModal').modal('hide');
        }
    });
});

// Refresh button handler
$('#refreshBtn').click(function() {
        location.reload();
    });

</script>