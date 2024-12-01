<?php
require_once '../../../config.php';
$baseUrl = 'http://localhost/Gym_MembershipSE-XLB';

// Fetch data for display
$sql = "
    SELECT 
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

<h1 class="nav-title">Facility and Service Rentals</h1>

<div class="search-section">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="search-controls">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add Service</button>
            </div>
        </div>
        <div class="col-md-6 d-flex justify-content-end">
            <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr class="text-center">
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
                    $toggleBtnClass = $rental['status'] === 'active' ? 'btn-danger' : 'btn-success';
                ?>
                <tr>
                    <td class="text-center"><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($rental['service_name']); ?></td>
                    <td class="text-center"><?php echo $rental['duration'] . ' ' . $rental['duration_type']; ?></td>
                    <td class="text-end">₱<?php echo number_format($rental['price'], 2); ?></td>
                    <td class="text-center"><?php echo $rental['total_slots']; ?></td>
                    <td class="text-center"><?php echo $rental['available_slots']; ?></td>
                    <td><?php 
                        $description = $rental['description'] ?: 'N/A';
                        echo strlen($description) > 50 ? 
                            htmlspecialchars(substr($description, 0, 50) . '...') : 
                            htmlspecialchars($description);
                    ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $statusBadgeClass; ?>">
                            <?php echo ucfirst($rental['status']); ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm <?php echo $toggleBtnClass; ?> toggle-status-btn" 
                                data-id="<?php echo $rental['id']; ?>">
                            <?php echo $toggleBtnText; ?>
                        </button>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="<?php echo $rental['id']; ?>">Edit</button>
                        <button class="btn btn-sm btn-danger remove-btn" 
                                data-id="<?php echo $rental['id']; ?>">
                            Remove
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
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

<script>
$(document).ready(function () {
    // Initialize DataTable
    $('#rentalsTable').DataTable({
        "ordering": false,
        "searching": true,
        "responsive": true,
        "lengthChange": true,
        "pageLength": 10,
        "language": {
            "emptyTable": "No rentals available"
        }
    });

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
            url: '<?php echo $baseUrl; ?>/admin/pages/gym rates/functions/save_rentals.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.trim() === 'success') {
                    alert('Rental service added successfully!');
                    $('#addServiceModal').modal('hide');
                    location.reload();
                } else {
                    alert(response);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while saving: ' + error);
            }
        });
    });

    // Toggle status button handler
    $(document).on('click', '.toggle-status-btn', function() {
        if (!confirm('Are you sure you want to change the status?')) {
            return;
        }

        var id = $(this).data('id');
        var newStatus = $(this).text().toLowerCase() === 'activate' ? 'active' : 'inactive';
        
        $.ajax({
            url: 'functions/toggle_rental_status.php',
            type: 'POST',
            data: {
                id: id,
                status: newStatus
            },
            success: function(response) {
                if (response.trim() === 'success') {
                    location.reload();
                } else {
                    alert(response);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while updating status: ' + error);
            }
        });
    });

    // Delete rental handler
    $(document).on('click', '.remove-btn', function() {
        if (!confirm('Are you sure you want to delete this rental?')) {
            return;
        }

        var id = $(this).data('id');

        $.ajax({
            url: 'functions/delete_rental.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.trim() === 'success') {
                    location.reload();
                } else {
                    alert(response);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while deleting: ' + error);
            }
        });
    });

    // Reset form when modal is closed
    $('#addServiceModal').on('hidden.bs.modal', function () {
        $('#addServiceForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });

    // Refresh button handler
    $('#refreshBtn').click(function() {
        location.reload();
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
