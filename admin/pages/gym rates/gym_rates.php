<?php
require_once 'config.php';
// select gym rates
$sql = "SELECT * FROM membership_plans";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Gym Rates</h2>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGymRateModal">
    Add Gym Rate
</button>

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

<div class="modal fade" id="addGymRateModal" tabindex="-1" aria-labelledby="addGymRateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGymRateModalLabel">Add Gym Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addGymRateForm">
                    <div class="mb-3">
                        <label for="promoName" class="form-label">Promo Name</label>
                        <input type="text" class="form-control" id="promoName" name="promoName" required>
                    </div>
                    <div class="mb-3">
                        <label for="promoType" class="form-label">Promo Type</label>
                        <select class="form-select" id="promoType" name="promoType" required>
                            <option value="standard">Standard</option>
                            <option value="special">Special</option>
                            <option value="walk-in">Walk-in</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration</label>
                        <input type="number" class="form-control" id="duration" name="duration" required>
                    </div>
                    <div class="mb-3">
                        <label for="durationType" class="form-label">Duration Type</label>
                        <select class="form-select" id="durationType" name="durationType" required>
                            <option value="days">Days</option>
                            <option value="months">Months</option>
                            <option value="year">Year</option>
                        </select>
                    </div>
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
                        <input type="number" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="membershipFee" class="form-label">Membership Fee (Optional)</label>
                        <input type="number" class="form-control" id="membershipFee" name="membershipFee">
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
$('#saveGymRateBtn').click(function() {
    var formData = $('#addGymRateForm').serialize();
    $.ajax({
        url: '../admin/pages/gym rates/save_gym_rates.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response === "success") {
                $('#addGymRateModal').modal('hide');
                // Add code to refresh the table or reload data
            } else {
                alert("Error saving gym rate: " + response);
            }
        },
        error: function(xhr, status, error) {
            alert("AJAX error: " + error);
        }
    });
});

</script>