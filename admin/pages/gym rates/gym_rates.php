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
<style>
    thead th {
        font-weight: 600;
        text-transform: uppercase;
        color: gray!important;
        font-size: 14px!important;
    }
    table td{
        vertical-align: middle;
        font-size: 14px;

    }
</style>

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
                    <div>Current Registration Fee: ₱<span id="currentRegistrationFee">0.00</span></div>
                    <div>Current Duration: <span id="currentDuration">N/A</span></div>
                </div>
                <form id="updateRegistrationForm">
                    <div class="form-group mb-3">
                        <label for="newRegistrationFee">New Registration Fee</label>
                        <input type="number" class="form-control" id="newRegistrationFee" name="newRegistrationFee" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Duration Validity</label>
                        <div class="d-flex">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="radio" name="durationType" id="durationSpecific" value="specific" checked>
                                <label class="form-check-label" for="durationSpecific">
                                    Specific Period
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="durationType" id="durationLifetime" value="lifetime">
                                <label class="form-check-label" for="durationLifetime">
                                    Lifetime
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="specificDurationFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="durationValue">Duration</label>
                                <input type="number" class="form-control" id="durationValue" name="durationValue" min="1" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="durationType">Period</label>
                                <select class="form-select" id="durationTypeSelect" name="durationTypeSelect">
                                    <option value="1">Days</option>
                                    <option value="2" selected>Months</option>
                                    <option value="3">Years</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRegistrationFeeBtn">Save</button>
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

    <div class="card">
        <div class="card-body">
    <div class="table-responsive">
    <table id="gymRatesTable" class="table table-hovered">
    <thead class="table-light border">
        <tr>
            <th class="border">Image</th>
            <th class="border">No.</th>
            <th class="border">Promo Name</th>
            <th class="border">Promo Type</th>
            <th class="border">Duration</th>
            <th class="border">Start Date</th>
            <th class="border">Deactivation Date</th>
            <th class="border">Price</th>
            <th class="border">Description</th>
            <th class="border">Status</th>
            <th class="border">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $count = 1;
    if (!empty($result)) {
        foreach ($result as $row) {
            echo "<tr>";
            
            // Ensure row has all 11 columns
            echo "<td>";
            // Check if the image exists using the correct absolute path
            if (!empty($row['image']) && file_exists(__DIR__ . "/../../../cms_img/gym_rates/" . $row['image'])) {
                // Use a path relative to the web root for the src attribute
                echo "<img src='__DIR__ . ../../../cms_img/gym_rates/" . htmlspecialchars($row['image']) . "' alt='Promo Image' class='img-thumbnail' width='80'>";
            } else {
                echo "No Image";
            }
            echo "</td>";
    
            echo "<td>" . $count . "</td>";
            echo "<td>" . htmlspecialchars($row['plan_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['plan_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['duration']) . " " . htmlspecialchars($row['duration_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            echo "<td>" . (!empty($row['description']) ? (strlen($row['description']) > 20 ? htmlspecialchars(substr($row['description'], 0, 20)) . '...' : htmlspecialchars($row['description'])) : 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    
            echo "<td class='d-grid gap-2'>";
            if ($row['status'] === 'active') {
                echo "<button class='btn btn-sm btn-warning toggle-status-btn' data-id='" . $row['id'] . "'>Deactivate</button>";
            } else {
                echo "<button class='btn btn-sm btn-success toggle-status-btn' data-id='" . $row['id'] . "'>Activate</button>";
            }
            echo "<button class='btn btn-sm btn-primary edit-gym-rate' data-id='" . $row['id'] . "'>Edit</button>";
            echo "<button class='btn btn-danger btn-sm remove-btn' data-id='" . $row['id'] . "'>Remove</button>";
            echo "</td>";
    
            echo "</tr>";
            $count++;
        }
    }
    ?>
</tbody>

</table>
</div>
</div>
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
                                <label for="promoImage" class="form-label">Promo Image</label>
                                <input type="file" class="form-control" id="promoImage" name="promoImage" accept="image/*">
                            </div>

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
                                <label for="editPromoImage" class="form-label">Promo Image</label>
                                <input type="file" class="form-control" id="editPromoImage" name="editPromoImage" accept="image/*">
                            </div>

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
        $('#gymRatesTable').DataTable().destroy(); // Destroy existing instance
    }
    
    $('#gymRatesTable').DataTable({
        responsive: true,
        order: [[1, 'asc']], // Sort by No. column ascending instead of column 3
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search promos..."
        },
        columnDefs: [
            { orderable: false, targets: [0, 10] } // Disable sorting for image and action columns
        ]
    });
});
    
    http://localhost/gym_membershipse-xlb/admin/member_status
// Validate form before submission
$('#saveGymRateBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

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

    // Validate required fields
    let isValid = 
        validateField('promoName', 'Promo Name is required') &
        validateField('promoType', 'Promo Type must be selected') &
        validateField('duration', 'Duration is required') &
        validateField('durationType', 'Duration Type must be selected') &
        validateField('activationDate', 'Activation Date is required') &
        validateField('deactivationDate', 'Deactivation Date is required');

    // Enhanced price validation (allow 0, disallow negative, require numeric)
    const priceField = $('#price');
    const priceValue = priceField.val().trim();
    if (priceValue === '' || priceValue === null) {
        priceField.addClass('is-invalid');
        priceField.after('<div class="invalid-feedback">Price is required</div>');
        isValid = false;
    } else if (parseFloat(priceValue) < 0) {
        priceField.addClass('is-invalid');
        priceField.after('<div class="invalid-feedback">Price must not be negative</div>');
        isValid = false;
    }

    // Enhanced duration validation (required, numeric, positive integer, >0) in one place
    const durationField = $('#duration');
    const durationValue = durationField.val().trim();
    // Remove existing feedback before adding new
    durationField.next('.invalid-feedback').remove();
    if (durationValue === '' || durationValue === null) {
        durationField.addClass('is-invalid');
        durationField.after('<div class="invalid-feedback">Duration is required</div>');
        isValid = false;
    } else if (isNaN(durationValue) || !/^[0-9]+$/.test(durationValue)) {
        durationField.addClass('is-invalid');
        durationField.after('<div class="invalid-feedback">Duration must be a positive integer</div>');
        isValid = false;
    } else if (parseInt(durationValue, 10) <= 0) {
        durationField.addClass('is-invalid');
        durationField.after('<div class="invalid-feedback">Duration must be greater than 0</div>');
        isValid = false;
    }

    // Validate date range
    const activationDate = new Date($('#activationDate').val());
    const deactivationDate = new Date($('#deactivationDate').val());

    if (activationDate >= deactivationDate) {
        $('#deactivationDate').addClass('is-invalid');
        $('#deactivationDate').after('<div class="invalid-feedback">Deactivation date must be after activation date</div>');
        return false;
    }

    if (!isValid) {
        return false;
    }

    // Prepare FormData for submission
    var formData = new FormData($('#addGymRateForm')[0]);
    var price = parseFloat($('#price').val()) || 0;
    var membershipFee = parseFloat($('#membershipFee').val()) || 0;
    var totalPrice = price + membershipFee;
    formData.set('price', totalPrice); // Update price with total

    // Handle image file (if provided)
    var imageFile = $('#promoImage')[0].files[0];
    if (imageFile) {
        formData.append('promoImage', imageFile);
    }

    $.ajax({
        url: '../admin/pages/gym rates/functions/save_gym_rates.php',
        type: 'POST',
        data: formData,
        processData: false,  // Important for file upload
        contentType: false,   // Important for file upload
        success: function(response) {
            if (response.trim() === "success") {
                $('#addGymRateModal').modal('hide');
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
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

$(document).on('click', '.edit-gym-rate', function() {
    const gymRateId = $(this).data('id');

    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_gym_rates.php',
        type: 'GET',
        data: { id: gymRateId },
        dataType: 'json',  // Ensure response is treated as JSON
        success: function(response) {
            console.log("🔹 Response from server:", response);

            // Since response is already an object, no need for JSON.parse
            if (response.status === 'success') {
                const gymRate = response.data;

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
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("❌ AJAX Error:", error);
            alert('Error occurred while fetching gym rate details: ' + error);
        }
    });
});


// Handle form submission for editing
$('#updateGymRateBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Enhanced price validation (allow 0, disallow negative, require numeric)
    const editPriceField = $('#editPrice');
    const editPriceValue = editPriceField.val().trim();
    editPriceField.next('.invalid-feedback').remove();
    let isValid = true;
    if (editPriceValue === '' || editPriceValue === null) {
        editPriceField.addClass('is-invalid');
        editPriceField.after('<div class="invalid-feedback">Price is required</div>');
        isValid = false;
    } else if (isNaN(editPriceValue)) {
        editPriceField.addClass('is-invalid');
        editPriceField.after('<div class="invalid-feedback">Price must be a valid number</div>');
        isValid = false;
    } else if (parseFloat(editPriceValue) < 0) {
        editPriceField.addClass('is-invalid');
        editPriceField.after('<div class="invalid-feedback">Price must not be negative</div>');
        isValid = false;
    }

    // Enhanced duration validation (required, numeric, positive integer, >0)
    const editDurationField = $('#editDuration');
    const editDurationValue = editDurationField.val().trim();
    editDurationField.next('.invalid-feedback').remove();
    if (editDurationValue === '' || editDurationValue === null) {
        editDurationField.addClass('is-invalid');
        editDurationField.after('<div class="invalid-feedback">Duration is required</div>');
        isValid = false;
    } else if (isNaN(editDurationValue) || !/^[0-9]+$/.test(editDurationValue)) {
        editDurationField.addClass('is-invalid');
        editDurationField.after('<div class="invalid-feedback">Duration must be a positive integer</div>');
        isValid = false;
    } else if (parseInt(editDurationValue, 10) <= 0) {
        editDurationField.addClass('is-invalid');
        editDurationField.after('<div class="invalid-feedback">Duration must be greater than 0</div>');
        isValid = false;
    }

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

    // Validate all other required fields
    isValid =
        validateField('editPromoName', 'Promo Name is required') & isValid;
    isValid =
        validateField('editPromoType', 'Promo Type must be selected') & isValid;
    isValid =
        validateField('editDurationType', 'Duration Type must be selected') & isValid;
    isValid =
        validateField('editActivationDate', 'Activation Date is required') & isValid;
    isValid =
        validateField('editDeactivationDate', 'Deactivation Date is required') & isValid;

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

$('input[name="durationType"]').change(function() {
    if ($(this).val() === 'specific') {
        $('#specificDurationFields').show();
    } else {
        $('#specificDurationFields').hide();
    }
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

    // Get duration data
    var durationType = $('input[name="durationType"]:checked').val();
    var durationData = {
        newRegistrationFee: newFee,
        durationType: durationType
    };
    
    if (durationType === 'specific') {
        var durationValue = $('#durationValue').val();
        var durationTypeId = $('#durationTypeSelect').val();
        
        // Validate duration value
        if (!durationValue || durationValue <= 0) {
            $('#durationValue').addClass('is-invalid');
            $('#durationValue').after('<div class="invalid-feedback">Please enter a valid duration</div>');
            return;
        }
        
        durationData.durationValue = durationValue;
        durationData.durationTypeId = durationTypeId;
    }

    // Send AJAX request
    $.ajax({
        url: '../admin/pages/gym rates/functions/update_registration_fee.php',
        type: 'POST',
        data: durationData,
        success: function(response) {
            if (response.trim() === 'success') {
                // Update the modal body with success message
                $('#updateSuccessModal .modal-body p').text("Registration fee updated successfully!");
                
                // Hide the registration modal
                $('#updateRegistrationModal').modal('hide');
                
                // Show the success modal
                $('#updateSuccessModal').modal('show');
                
                // Reload page after success modal is hidden
                $('#updateSuccessModal').on('hidden.bs.modal', function () {
                    location.reload();
                });
            } else {
                alert('Error: ' + response);
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
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    $('#currentRegistrationFee').text(parseFloat(data.fee).toFixed(2));
                    
                    // Display duration information
                    if (data.durationType === 'lifetime') {
                        $('#currentDuration').text('Lifetime');
                    } else if (data.duration > 0) {
                        $('#currentDuration').text(data.duration + ' ' + data.durationTypeName);
                    } else {
                        $('#currentDuration').text('N/A');
                    }
                } else {
                    alert(data.message);
                    $('#currentRegistrationFee').text('N/A');
                    $('#currentDuration').text('N/A');
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                $('#currentRegistrationFee').text('N/A');
                $('#currentDuration').text('N/A');
            }
        },
        error: function(xhr, status, error) {
            console.log('Error:', error);
            $('#currentRegistrationFee').text('N/A');
            $('#currentDuration').text('N/A');
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