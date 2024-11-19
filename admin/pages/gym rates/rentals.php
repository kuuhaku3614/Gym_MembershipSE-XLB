<?php
require_once '../../../config.php';


// Fetch data for display
$sql = "
    SELECT 
        rs.*, 
        dt.type_name AS duration_type, 
        st.status_name AS status 
    FROM rental_services rs
    JOIN duration_types dt ON rs.duration_type_id = dt.id
    JOIN status_types st ON rs.status_id = st.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch duration types
$durationTypesSql = "SELECT id, type_name FROM duration_types";
$durationTypesStmt = $pdo->prepare($durationTypesSql);
$durationTypesStmt->execute();
$durationTypes = $durationTypesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch status types
$statusTypesSql = "SELECT id, status_name FROM status_types";
$statusTypesStmt = $pdo->prepare($statusTypesSql);
$statusTypesStmt->execute();
$statusTypes = $statusTypesStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Facility and Service Rentals</h2>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
    Add Service
</button>

<table id="gymRatesTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No.</th>
            <th>Service Name</th>
            <th>Price</th>
            <th>Total Slots</th>
            <th>Available Slots</th>
            <th>Duration</th>
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
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['total_slots']) . "</td>";
            echo "<td>" . htmlspecialchars($row['available_slots']) . "</td>";
            echo "<td>" . htmlspecialchars($row['duration']) . " " . htmlspecialchars($row['duration_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>
                    <button class='btn btn-warning btn-sm' data-id='" . $row['id'] . "'>Deactivate</button>
                    <button class='btn btn-primary btn-sm' data-id='" . $row['id'] . "'>Edit</button>
                    <button class='btn btn-danger btn-sm' data-id='" . $row['id'] . "'>Remove</button>
                </td>";
            echo "</tr>";
            $count++;
        }
    } else {
        echo "<tr><td colspan='8'>No data available</td></tr>";
    }
    ?>
</tbody>
</table>

<!-- Modal for adding/updating service -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addServiceModalLabel">Add Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addServiceForm">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Service Name -->
                            <div class="mb-3">
                                <label for="serviceName" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="serviceName" name="serviceName" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Price -->
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" required min="0" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Slots -->
                            <div class="mb-3">
                                <label for="slots" class="form-label">Slots</label>
                                <input type="number" class="form-control" id="slots" name="slots" required min="1">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <!-- Duration -->
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration</label>
                                <input type="number" class="form-control" id="duration" name="duration" required min="1">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <!-- Duration Type -->
                            <div class="mb-3">
                                <label for="durationType" class="form-label">Duration Type</label>
                                <select class="form-select" id="durationType" name="durationType" required>
                                    <option value="">Select Type</option>
                                    <?php
                                    foreach ($durationTypes as $type) {
                                        echo "<option value='" . $type['id'] . "'>" . htmlspecialchars($type['type_name'], ENT_QUOTES, 'UTF-8') . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="55" placeholder="Add a description"></textarea>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveServiceBtn">Save Service</button>
            </div>
        </div>
    </div>
</div>


<script>
// Save button handler with AJAX
$(document).ready(function () {
    // Save button handler
    $('#saveServiceBtn').click(function () {
        // Serialize the form data
        var formData = $('#addServiceForm').serialize();

        // Validate required fields (you can expand this for stricter validation)
        if ($('#serviceName').val() === "" || 
            $('#price').val() === "" || 
            $('#slots').val() === "" || 
            $('#duration').val() === "" || 
            $('#durationType').val() === "") {
            alert("All fields are required. Please fill out the form completely.");
            return;
        }

        // Send AJAX request
        $.ajax({
            url: '../admin/pages/gym rates/functions/save_rentals.php', // Adjust to the correct path
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.trim() === "success") {
                    alert("Service saved successfully!");
                    $('#addServiceModal').modal('hide'); // Close modal
                    location.reload(); // Reload page to show the new data
                } else {
                    alert("Error saving service: " + response); // Display server-side error
                }
            },
            error: function (xhr, status, error) {
                alert("AJAX error: " + error);
            }
        });
    });

    // Reset form when modal is closed
    $('#addServiceModal').on('hidden.bs.modal', function () {
        $('#addServiceForm')[0].reset(); // Clear form
    });
});

</script>
