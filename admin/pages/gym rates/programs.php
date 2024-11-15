<?php
require_once 'config.php';

$sql = "SELECT programs.*, CONCAT(personal_details.first_name, ' ', personal_details.last_name) AS coach_name 
        FROM programs 
        JOIN users ON programs.user_id = users.id 
        JOIN personal_details ON users.id = personal_details.user_id 
        WHERE users.role = 'coach'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Fitness and Wellness Programs</h2>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
    Add Program
</button>

<table id="programsTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No.</th>
            <th>Program Name</th>
            <th>Program Type</th>
            <th>Coach Name</th> 
            <th>Duration</th>
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
                echo "<td>" . $row['program_name'] . "</td>";
                echo "<td>" . $row['program_type'] . "</td>";
                echo "<td>" . $row['coach_name'] . "</td>";
                echo "<td>" . $row['duration'] . " " . $row['duration_type'] . "</td>";
                echo "<td>" . $row['price'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>
                        <button class='btn btn-warning btn-sm status-btn' data-id='" . $row['id'] . "'>Deactivate</button>
                        <button class='btn btn-primary btn-sm edit-btn' data-id='" . $row['id'] . "'>Edit</button>
                        <button class='btn btn-danger btn-sm remove-btn' data-id='" . $row['id'] . "'>Remove</button>
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

<div class="modal fade" id="addProgramModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
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
                                <label for="programName" class="form-label">Program Name</label>
                                <input type="text" class="form-control" id="programName" name="programName" required>
                            </div>
                            <div class="mb-3">
                                <label for="programType" class="form-label">Program Type</label>
                                <select class="form-select" id="programType" name="programType" required>
                                    <option value="">Select Program Type</option>
                                    <option value="personal">Personal</option>
                                    <option value="group">Group</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="coachId" class="form-label">Coach Name</label>
                                <select class="form-select" id="coachId" name="coachId" required>
                                    <option value="">Select Coach</option>
                                    <?php
                                    // Fetch coach
                                    $coachSql = "SELECT u.id, pd.first_name, pd.last_name, pd.phone_number 
                                               FROM users u 
                                               JOIN personal_details pd ON u.id = pd.user_id 
                                               WHERE u.role = 'coach'";
                                    $coachStmt = $pdo->prepare($coachSql);
                                    $coachStmt->execute();
                                    $coaches = $coachStmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($coaches as $coach) {
                                        echo "<option value='" . $coach['id'] . "' data-phone='" . $coach['phone_number'] . "'>" 
                                            . $coach['first_name'] . " " . $coach['last_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="price" name="price" required min="0" step="0.01">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="contactNo" class="form-label">Contact No.</label>
                                <input type="text" class="form-control" id="contactNo" name="contactNo" readonly>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="duration" name="duration" required min="1" value="1">
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
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProgramBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-fill contact number when coach is selected
    $('#coachId').change(function() {
        var selectedOption = $(this).find('option:selected');
        var phoneNumber = selectedOption.data('phone');
        $('#contactNo').val(phoneNumber || '');
    });

    // Save button handler
    $('#saveProgramBtn').click(function() {
        var formData = $('#addProgramForm').serialize();
        
        $.ajax({
            url: '../admin/pages/gym rates/functions/save_programs.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response === "success") {
                    $('#addProgramModal').modal('hide');
                    location.reload();
                } else {
                    alert("Error saving program: " + response);
                }
            },
            error: function(xhr, status, error) {
                alert("AJAX error: " + error);
            }
        });
    });

    // Reset form when modal is closed
    $('#addProgramModal').on('hidden.bs.modal', function () {
        $('#addProgramForm')[0].reset();
        $('#contactNo').val('');
    });
});
</script>