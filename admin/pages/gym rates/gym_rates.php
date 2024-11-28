<?php
require_once '../../../config.php';
// select gym rates
$sql = "SELECT mp.*, dt.type_name as duration_type, st.status_name as status 
        FROM membership_plans mp
        LEFT JOIN duration_types dt ON mp.duration_type_id = dt.id
        LEFT JOIN status_types st ON mp.status_id = st.id
        ORDER BY mp.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1 class="nav-title">Gym Rates</h1>

<div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <div class="search-controls">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGymRateModal">Add Gym Rate</button>

                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                          <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
                </div>
            </div>
        </div>


    <div class="table-responsive">
    <table id="gymRatesTable" class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <th>No.</th>
            <th>Promo Name</th>
            <th>Promo Type</th>
            <th>Duration</th>
            <th>Start Date</th>
            <th>Deactivation Date</th>
            <th>Price</th>
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
                echo "<td>" . $row['plan_name'] . "</td>";
                echo "<td>" . $row['plan_type'] . "</td>";
                echo "<td>" . $row['duration'] . " " . $row['duration_type'] . "</td>";
                echo "<td>" . $row['start_date'] . "</td>";
                echo "<td>" . $row['end_date'] . "</td>";
                echo "<td>" . $row['price'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>
                        <button class='btn btn-warning btn-sm edit-btn' data-id='" . $row['id'] . "'>Deactivate</button>
                        <button class='btn btn-primary btn-sm edit-btn' data-id='" . $row['id'] . "'>Edit</button>
                        <button class='btn btn-danger btn-sm remove-btn' data-id='" . $row['id'] . "'>Remove</button>
                    </td>";
                echo "</tr>";
                $count++;
            }
        } else {
            echo "<tr><td colspan='9'>No data available</td></tr>";
        }
        ?>
    </tbody>
</table>
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
                                    <option value="walk-in">Walk-in</option>
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

<script>
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
            if (response === "success") {
                $('#addGymRateModal').modal('hide');
                location.reload();
            } else {
                // Display error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response,
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'AJAX Error',
                text: error,
                confirmButtonText: 'OK'
            });
        }
    });
});

// Reset form when modal is closed
$('#addGymRateModal').on('hidden.bs.modal', function () {
    $('#addGymRateForm')[0].reset();
    $('#totalPriceDisplay').hide();
});
</script>