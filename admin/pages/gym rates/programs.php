<?php
require_once '../../../config.php';

// Define base URL for AJAX calls
$baseUrl = '/Gym_MembershipSE-XLB/admin/pages/gym%20rates';

// Fetch programs with additional coach program type information
$sql = "
    SELECT 
        p.*,
        pt.type_name AS program_type,
        dt.type_name AS duration_type,
        st.status_name AS status,
        st.id AS status_id,
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
    GROUP BY p.id
    ORDER BY p.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for DataTables
$tableData = [];
foreach ($programs as $program) {
    $coaches = explode(',', $program['coaches']);
    $prices = explode(',', $program['prices']);
    $descriptions = explode(',', $program['descriptions']);
    
    // Add main row
    if (!empty($coaches[0])) {
        $tableData[] = [
            'id' => $program['id'],
            'program_name' => $program['program_name'],
            'program_type' => $program['program_type'],
            'duration' => $program['duration'] . ' ' . $program['duration_type'],
            'coach' => $coaches[0],
            'price' => $prices[0],
            'description' => $descriptions[0],
            'status' => $program['status'],
            'actions' => '<div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm deactivate-btn" data-id="' . $program['id'] . '">Deactivate</button>
                            <button class="btn btn-primary btn-sm edit-btn" data-id="' . $program['id'] . '">Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="' . $program['id'] . '">Delete</button>
                        </div>'
        ];
        
        // Add additional rows for other coaches
        for ($i = 1; $i < count($coaches); $i++) {
            if (!empty($coaches[$i])) {
                $tableData[] = [
                    'id' => '',
                    'program_name' => '',
                    'program_type' => '',
                    'duration' => '',
                    'coach' => $coaches[$i],
                    'price' => $prices[$i],
                    'description' => $descriptions[$i],
                    'status' => '',
                    'actions' => ''
                ];
            }
        }
    } else {
        $tableData[] = [
            'id' => $program['id'],
            'program_name' => $program['program_name'],
            'program_type' => $program['program_type'],
            'duration' => $program['duration'] . ' ' . $program['duration_type'],
            'coach' => '-',
            'price' => '-',
            'description' => '-',
            'status' => $program['status'],
            'actions' => '<div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm deactivate-btn" data-id="' . $program['id'] . '">Deactivate</button>
                            <button class="btn btn-primary btn-sm edit-btn" data-id="' . $program['id'] . '">Edit</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="' . $program['id'] . '">Delete</button>
                        </div>'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs Management</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    
    <style>
        .coach-info {
            margin: 5px 0;
        }
        .coach-info:not(:last-child) {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .btn-group .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="nav-title">Programs</h1>

        <div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="search-controls">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">Add Program</button>
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                    <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="programsTable" class="table table-striped table-bordered">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($programs as $program) {
                    $coaches = explode(',', $program['coaches']);
                    $prices = explode(',', $program['prices']);
                    $descriptions = explode(',', $program['descriptions']);
                    
                    // Create coach info HTML
                    $coachInfoHtml = '';
                    for ($i = 0; $i < count($coaches); $i++) {
                        if (!empty($coaches[$i])) {
                            if ($i > 0) $coachInfoHtml .= '<hr class="my-1">'; // Add separator between coaches
                            $coachInfoHtml .= '<div class="coach-info">';
                            $coachInfoHtml .= '<div>' . htmlspecialchars($coaches[$i]) . '</div>';
                            $coachInfoHtml .= '<div>' . htmlspecialchars($prices[$i]) . '</div>';
                            $coachInfoHtml .= '<div>' . htmlspecialchars($descriptions[$i]) . '</div>';
                            $coachInfoHtml .= '</div>';
                        }
                    }
                    
                    // If no coaches, show placeholder
                    if (empty($coachInfoHtml)) {
                        $coachInfoHtml = '<div class="coach-info">-</div>';
                    }
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($program['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($program['program_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($program['program_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($program['duration'] . ' ' . $program['duration_type']) . "</td>";
                    echo "<td>" . $coachInfoHtml . "</td>";
                    echo "<td>" . $coachInfoHtml . "</td>";
                    echo "<td>" . $coachInfoHtml . "</td>";
                    echo "<td>" . htmlspecialchars($program['status']) . "</td>";
                    echo "<td>";
                    echo "<div class='btn-group' role='group'>";
                    // Change button based on status_id (1 is Active, 2 is Inactive)
                    if ($program['status_id'] == 1) {
                        echo "<button class='btn btn-warning btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-status='deactivate'>Deactivate</button> ";
                    } else {
                        echo "<button class='btn btn-success btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-status='activate'>Activate</button> ";
                    }
                    echo "<button class='btn btn-primary btn-sm edit-btn' data-id='" . $program['id'] . "'>Edit</button> ";
                    echo "<button class='btn btn-danger btn-sm delete-btn' data-id='" . $program['id'] . "'>Delete</button>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
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
                                <?php 
                                // Fetch program types
                                $programTypesSql = "SELECT id, type_name FROM program_types";
                                $programTypesStmt = $pdo->prepare($programTypesSql);
                                $programTypesStmt->execute();
                                $programTypes = $programTypesStmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($programTypes as $type): ?>
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
                                    <?php 
                                    // Fetch duration types
                                    $durationTypesSql = "SELECT id, type_name FROM duration_types";
                                    $durationTypesStmt = $pdo->prepare($durationTypesSql);
                                    $durationTypesStmt->execute();
                                    $durationTypes = $durationTypesStmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($durationTypes as $type): ?>
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

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script>
    $(document).ready(function() {
        $('#programsTable').DataTable({
            "ordering": false,
            "searching": true,
            "paging": true,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });

        // Save program handler
        $('#saveProgram').click(function() {
            var formData = {
                programName: $('#programName').val().trim(),
                programType: $('#programType').val(),
                duration: $('#duration').val(),
                durationType: $('#durationType').val(),
                description: $('#description').val().trim()
            };

            // Validate form data
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
            if (!formData.description) {
                alert('Please enter a description');
                return;
            }

            $.ajax({
                url: '<?php echo $baseUrl; ?>/functions/save_programs.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Response:', response); // For debugging
                    if (response.trim() === 'success') {
                        alert('Program saved successfully!');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addProgramModal'));
                        modal.hide();
                        location.reload();
                    } else {
                        alert('Error occurred while saving: ' + response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error occurred while saving the program: ' + error);
                }
            });
        });

        // Handle Deactivate button click
        $(document).on('click', '.deactivate-btn', function() {
            var programId = $(this).data('id');
            if (confirm('Are you sure you want to deactivate this program?')) {
                $.ajax({
                    url: '<?php echo $baseUrl; ?>/functions/save_programs.php',
                    type: 'POST',
                    data: { 
                        action: 'deactivate',
                        id: programId 
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.status === 'success') {
                                alert('Program deactivated successfully!');
                                location.reload();
                            } else {
                                alert('Error occurred: ' + result.message);
                            }
                        } catch (e) {
                            alert('Error processing response');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error occurred: ' + error);
                    }
                });
            }
        });

        // Handle toggle status button click
        $(document).on('click', '.toggle-status-btn', function() {
            var programId = $(this).data('id');
            var status = $(this).data('status');
            var actionText = status === 'activate' ? 'activate' : 'deactivate';
            
            if (confirm('Are you sure you want to ' + actionText + ' this program?')) {
                $.ajax({
                    url: '<?php echo $baseUrl; ?>/functions/save_programs.php',
                    type: 'POST',
                    data: { 
                        action: 'toggle_status',
                        id: programId, 
                        status: status 
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.status === 'success') {
                                location.reload();
                            } else {
                                alert('Error: ' + (result.message || 'Unknown error occurred'));
                            }
                        } catch (e) {
                            alert('Error processing response: ' + e.message);
                            console.error('Response:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            }
        });
    });
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>