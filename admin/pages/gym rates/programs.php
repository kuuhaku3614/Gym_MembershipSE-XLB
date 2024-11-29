<?php
require_once '../../../config.php';

// Fetch programs with additional coach program type information
$sql = "
    SELECT 
        p.*,
        pt.type_name AS program_type,
        dt.type_name AS duration_type,
        st.status_name AS status,
        GROUP_CONCAT(u.username ORDER BY u.username) as coaches,
        GROUP_CONCAT(cpt.price ORDER BY u.username) as prices,
        GROUP_CONCAT(IFNULL(cpt.description, '') ORDER BY u.username) as descriptions,
        COUNT(u.id) as coach_count
    FROM programs p
    LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id
    LEFT JOIN users u ON cpt.coach_id = u.id
    JOIN program_types pt ON p.program_type_id = pt.id
    JOIN duration_types dt ON p.duration_type_id = dt.id
    JOIN status_types st ON p.status_id = st.id
    WHERE cpt.status = 'active' OR cpt.status IS NULL
    GROUP BY p.id
    ORDER BY p.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch program types
$programTypesSql = "SELECT id, type_name FROM program_types WHERE status = 'active' ORDER BY type_name";
$programTypesStmt = $pdo->prepare($programTypesSql);
$programTypesStmt->execute();
$programTypes = $programTypesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch duration types
$durationTypesSql = "SELECT id, type_name FROM duration_types WHERE status = 'active' ORDER BY type_name";
$durationTypesStmt = $pdo->prepare($durationTypesSql);
$durationTypesStmt->execute();
$durationTypes = $durationTypesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch coaches (using users table)
$coachesSql = "
    SELECT 
        id, 
        username 
    FROM users 
    WHERE role_id = (SELECT id FROM roles WHERE role_name = 'coach')
";
$coachesStmt = $pdo->prepare($coachesSql);
$coachesStmt->execute();
$coaches = $coachesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1 class="nav-title">Programs</h1>

<div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <div class="search-controls">
                    <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#addProgramModal">Add Program</button>
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                          <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
                </div>
            </div>
        </div>

    <div class="table-responsive">
        <table id="programsTable" class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No.</th>
                    <th>Program Name</th>
                    <th>Program Type</th>
                    <th>Duration</th>
                    <th>Coach</th>
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
                // Split the concatenated values into arrays
                $coaches = explode(',', $row['coaches']);
                $prices = explode(',', $row['prices']);
                $descriptions = explode(',', $row['descriptions']);
                $rowCount = count($coaches);

                // First row with program details
                echo "<tr>";
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>" . $count . "</td>";
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>" . htmlspecialchars($row['program_name']) . "</td>";
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>" . htmlspecialchars($row['program_type']) . "</td>";
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>" . htmlspecialchars($row['duration']) . " " . htmlspecialchars($row['duration_type']) . "</td>";
                
                // First coach's details
                echo "<td>" . htmlspecialchars($coaches[0]) . "</td>";
                echo "<td style='text-align:right;'>₱" . number_format($prices[0], 2) . "</td>";
                echo "<td>" . htmlspecialchars($descriptions[0]) . "</td>";
                
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td rowspan='{$rowCount}' style='vertical-align: middle;'>
                        <button class='btn btn-warning btn-sm status-btn' data-id='" . $row['id'] . "'>Deactivate</button>
                        <button class='btn btn-primary btn-sm edit-btn' data-id='" . $row['id'] . "'>Edit</button>
                        <button class='btn btn-danger btn-sm remove-btn' data-id='" . $row['id'] . "'>Remove</button>
                    </td>";
                echo "</tr>";

                // Additional rows for other coaches
                for ($i = 1; $i < $rowCount; $i++) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($coaches[$i]) . "</td>";
                    echo "<td style='text-align:right;'>₱" . number_format($prices[$i], 2) . "</td>";
                    echo "<td>" . htmlspecialchars($descriptions[$i]) . "</td>";
                    echo "</tr>";
                }
                $count++;
            }
        } else {
            echo "<tr><td colspan='9'>No data available</td></tr>";
        }
        ?>
    </tbody>
</table>
</div>

<div class="modal fade" id="addProgramModal" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProgramModalLabel">Add Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProgramForm">
                    <div class="mb-3">
                        <label for="programName" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="programName" name="programName" required>
                    </div>
                    <div class="mb-3">
                        <label for="programType" class="form-label">Program Type</label>
                        <select class="form-control" id="programType" name="programType" required>
                            <option value="">Select Program Type</option>
                            <?php foreach ($programTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type['id']) ?>">
                                    <?= htmlspecialchars($type['type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                            <select class="form-control" id="durationType" name="durationType" required>
                                <option value="">Select Duration Type</option>
                                <?php foreach ($durationTypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type['id']) ?>">
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProgram">Save Program</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    const table = $("#programsTable").DataTable({
        pageLength: 10,
        ordering: true,
        responsive: true,
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        columnDefs: [
            {
                targets: [4, 5, 6], // Coach, Price, Description columns
                orderable: true
            },
            {
                targets: [8], // Action column
                orderable: false,
                searchable: false
            }
        ]
    });

    // Fetch coaches dynamically when program type is selected
    $('#programType').change(function () {
        var programTypeId = $(this).val();
        if (programTypeId) {
            $.ajax({
                url: '../admin/pages/gym rates/functions/get_coaches.php',
                type: 'POST',
                data: { programTypeId: programTypeId },
                success: function (data) {
                    var coaches = JSON.parse(data);
                    var coachDropdown = $('#coachId');
                    coachDropdown.empty();
                    coachDropdown.append('<option value="">Select Coach</option>'); // Default option
                    coaches.forEach(function (coach) {
                        coachDropdown.append(
                            `<option value="${coach.coach_id}" data-phone="${coach.phone_number}">${coach.coach_name}</option>`
                        );
                    });
                    $('#contactNo').val(''); // Reset contact number
                },
                error: function (xhr, status, error) {
                    alert("Error fetching coaches: " + error);
                },
            });
        } else {
            $('#coachId').empty().append('<option value="">Select Coach</option>');
            $('#contactNo').val('');
        }
    });

    // Auto-fill contact number
    $('#coachId').change(function () {
        var selectedOption = $(this).find('option:selected');
        var phoneNumber = selectedOption.data('phone');
        $('#contactNo').val(phoneNumber || '');
    });
    
    $('#saveProgram').click(function() {
        var formData = {
            programName: $('#programName').val().trim(),
            programType: $('#programType').val(),
            duration: $('#duration').val(),
            durationType: $('#durationType').val(),
            description: $('#description').val().trim()
        };

        // Validate form
        if (!$('#addProgramForm')[0].checkValidity()) {
            $('#addProgramForm')[0].reportValidity();
            return;
        }

        // Additional validation
        if (!formData.programName) {
            alert('Please enter a program name');
            return;
        }
        if (!formData.programType) {
            alert('Please select a program type');
            return;
        }
        if (!formData.duration || formData.duration < 1) {
            alert('Please enter a valid duration');
            return;
        }
        if (!formData.durationType) {
            alert('Please select a duration type');
            return;
        }

        $.ajax({
            url: 'programs_crud.php',
            type: 'POST',
            data: {
                action: 'add',
                ...formData
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.status === 'success') {
                        $('#addProgramModal').modal('hide');
                        $('#addProgramForm')[0].reset();
                        // Refresh the table
                        location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'Unknown error occurred'));
                    }
                } catch (e) {
                    alert('Error processing response from server');
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while saving the program: ' + error);
            }
        });
    });
});
</script>