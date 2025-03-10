<?php
require_once '../../../config.php';
include '../../pages/modal.php';

// Define base URL for AJAX calls
$baseUrl = '/Gym_MembershipSE-XLB/admin/pages/gym rates';

// Fetch programs with additional coach program type information
$sql = "SELECT 
        p.*,
        pt.type_name AS program_type,
        dt.type_name AS duration_type,
        GROUP_CONCAT(u.username ORDER BY u.id) AS coaches,
        GROUP_CONCAT(cpt.price ORDER BY u.id) AS prices,
        GROUP_CONCAT(cpt.description ORDER BY u.id) AS coach_descriptions,
        COUNT(u.id) AS coach_count
    FROM programs p
    LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id
    LEFT JOIN users u ON cpt.coach_id = u.id
    JOIN program_types pt ON p.program_type_id = pt.id
    JOIN duration_types dt ON p.duration_type_id = dt.id
    WHERE p.is_removed = 0
    GROUP BY p.id
    ORDER BY p.id;
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for DataTables
$tableData = [];
foreach ($programs as $program) {
    $coaches = explode(',', $program['coaches']);
    $prices = explode(',', $program['prices']);
    $coachDescriptions = explode(',', $program['coach_descriptions']);
    
    // Add main row
    if (!empty($coaches[0])) {
        $tableData[] = [
            'id' => $program['id'],
            'program_name' => $program['program_name'],
            'program_type' => $program['program_type'],
            'duration' => $program['duration'] . ' ' . $program['duration_type'],
            'coach' => $coaches[0],
            'price' => $prices[0],
            'description' => isset($coachDescriptions[0]) ? $coachDescriptions[0] : '',
            'status' => $program['status'],
            'actions' => '<div class="btn-group" role="group">
                            <button class="btn btn-info btn-sm view-coaches-btn" data-id="' . $program['id'] . '" data-coaches=\'' . htmlspecialchars(json_encode(array_map(function($coach, $price, $description) {
                                return ['coach' => $coach, 'price' => $price, 'description' => $description];
                            }, $coaches, $prices, $coachDescriptions)), ENT_QUOTES, 'UTF-8') . '\' data-program-name="' . htmlspecialchars($program['program_name'], ENT_QUOTES) . '">
                                <i class="fas fa-users"></i> View Coaches</button> 
                            <button class="btn btn-warning btn-sm deactivate-btn" data-id="' . $program['id'] . '">
                                <i class="fas fa-ban"></i> Deactivate</button>
                            <button class="btn btn-primary btn-sm edit-btn" data-id="' . $program['id'] . '">
                                <i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-danger btn-sm remove-btn" data-id="' . $program['id'] . '" title="Remove Program">
                                <i class="fas fa-trash"></i>
                            </button>
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
                    'description' => isset($coachDescriptions[$i]) ? $coachDescriptions[$i] : '',
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
                            <button class="btn btn-info btn-sm view-coaches-btn" data-id="' . $program['id'] . '" data-coaches=\'' . htmlspecialchars(json_encode(array()), ENT_QUOTES, 'UTF-8') . '\' data-program-name="' . htmlspecialchars($program['program_name'], ENT_QUOTES) . '">
                                <i class="fas fa-users"></i> View Coaches</button> 
                            <button class="btn btn-warning btn-sm deactivate-btn" data-id="' . $program['id'] . '">
                                <i class="fas fa-ban"></i> Deactivate</button>
                            <button class="btn btn-primary btn-sm edit-btn" data-id="' . $program['id'] . '">
                                <i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-danger btn-sm remove-btn" data-id="' . $program['id'] . '" title="Remove Program">
                                <i class="fas fa-trash"></i>
                            </button>
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
    
    <!-- jQuery
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    Bootst-->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.ss" rel="stylesheet"> -->
    
    <!-- DataTables -->
    <!-- <link rel="stylesheet" type="text/css" href="https://cdn.datats.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script> --> -->
    
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
        .table td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: top;
            padding: 15px !important;
        }
        .description-cell {
            white-space: pre-wrap !important;
            word-break: break-word;
            min-width: 300px;
        }
        .coach-table {
            table-layout: fixed;
            width: 100%;
        }
        .coach-table th {
            padding: 15px !important;
        }
        #coachesTableBody td:last-child {
            max-width: 0;
            overflow: visible;
        }
        
        /* Modal styles */
        .modal-dialog.modal-lg {
            max-width: 800px;
            margin: 1.75rem auto;
        }
        
        /* Table styles */
        .coach-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        #coachesTableContainer{
            box-shadow: none;
        }
        
        .coach-details-table th,
        .coach-details-table td {
            padding: 1rem;
            text-align: left;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .coach-details-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .coach-name-col {
            width: 20%;
        }
        
        .price-col {
            width: 15%;
        }
        
        .description-col {
            width: 65%;
            white-space: pre-line;
            word-break: normal;
        }
        
        .description-text {
            white-space: pre-line;
            word-wrap: break-word;
            max-width: 100%;
        }
    </style>
</head>
<body>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Programs</h2>
            <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">Add Program</button>
            <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="programsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>No.</th>
                        <th>Program Name</th>
                        <th>Program Type</th>
                        <th>Duration</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 1; // Initialize counter outside the loop
                foreach ($programs as $program) {
                    // Fetch coaches for this program
                    $coachQuery = "SELECT cpt.*, pd.first_name, pd.last_name 
                                 FROM coach_program_types cpt 
                                 INNER JOIN personal_details pd ON pd.user_id = cpt.coach_id
                                 WHERE cpt.program_id = :program_id 
                                 AND cpt.status = 'active'";
                    $coachStmt = $pdo->prepare($coachQuery);
                    $coachStmt->execute([':program_id' => $program['id']]);
                    $coaches = $coachStmt->fetchAll(PDO::FETCH_ASSOC);
                    $coachDataJson = htmlspecialchars(json_encode($coaches), ENT_QUOTES, 'UTF-8');

                    echo "<tr>";
                    echo "<td>";
            // Check if the image exists using the correct absolute path
            if (!empty($program['image']) && file_exists(__DIR__ . "/../../../cms_img/programs/" . $program['image'])) {
                // Use a path relative to the web root for the src attribute
                echo "<img src='__DIR__ . ../../../cms_img/programs/" . htmlspecialchars($program['image']) . "' alt='Programs Image' class='img-thumbnail' width='80'>";
            } else {
                echo "No Image";
            }
            echo "</td>";
                    echo "<td>" . $count++ . "</td>";
                    echo "<td>" . htmlspecialchars($program['program_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($program['program_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($program['duration'] . ' ' . $program['duration_type']) . "</td>";
                    echo "<td>";
                    $description = $program['description'] ?: 'N/A';
                    echo strlen($description) > 50 ? 
                        htmlspecialchars(substr($description, 0, 50) . '...') : 
                        htmlspecialchars($description);
                    echo "</td>";
                    echo "<td>" . htmlspecialchars($program['status']) . "</td>";
                    echo "<td>";
                    echo "<div class='d-grid gap-2'>";
                    echo "<button class='btn btn-info btn-sm view-coaches-btn' data-id='" . $program['id'] . "' data-coaches='" . $coachDataJson . "' 
                            data-program-name='" . htmlspecialchars($program['program_name'], ENT_QUOTES) . "'>View Coaches</button>";
                    if ($program['status'] === 'active') {
                        echo "<button class='btn btn-warning btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-new-status='inactive'>Deactivate</button>";
                    } else {
                        echo "<button class='btn btn-success btn-sm toggle-status-btn' data-id='" . $program['id'] . "' data-new-status='active'>Activate</button>";
                    }
                    echo "<button class='btn btn-primary btn-sm edit-btn' data-id='" . $program['id'] . "'>Edit</button>";
                    echo "<button class='btn btn-danger btn-sm remove-btn' data-id='" . $program['id'] . "'>Remove</button>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Coaches Modal -->
    <div class="modal fade" id="viewCoachesModal" tabindex="-1" aria-labelledby="viewCoachesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewCoachesModalLabel">Program Coaches</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="program-name-display mb-3"></h6>
                    <div class="table-responsive" id="coachesTableContainer">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Coach Name</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="coachesTableBody">
                                <!-- Coaches will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary add-coach-btn" data-program-id="">Add Coach</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Coach Modal -->
    <div class="modal fade" id="addCoachModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addCoachModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCoachModalLabel">Add Coach to Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCoachForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coachSelect" class="form-label">Select Coach</label>
                                    <select class="form-select" id="coachSelect" name="coach_id" required>
                                        <option value="">Select a Coach</option>
                                        <?php 
                                        // Fetch users who are coaches (role_id = 4)
                                        $coachesSql = "SELECT u.id, CONCAT(pd.first_name, ' ', pd.last_name) as full_name 
                                                      FROM users u 
                                                      JOIN personal_details pd ON u.id = pd.user_id 
                                                      WHERE u.role_id = 4";
                                        $coachesStmt = $pdo->prepare($coachesSql);
                                        $coachesStmt->execute();
                                        $coaches = $coachesStmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($coaches as $coach): ?>
                                            <option value="<?= htmlspecialchars($coach['id']) ?>">
                                                <?= htmlspecialchars($coach['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="coachPrice" class="form-label">Price</label>
                                    <input type="number" class="form-control" id="coachPrice" name="price" step="0.01" min="0" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coachDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="coachDescription" name="description" rows="4"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <input type="hidden" id="programId" name="program_id">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCoachBtn">Save Coach</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal" id="addProgramModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addProgramModalLabel">Add Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProgramForm">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">

                                <div class="mb-3">
                                    <label for="programImage" class="form-label">Program Image</label>
                                    <input type="file" class="form-control" id="programImage" name="programImage" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="programName" class="form-label">Program Name</label>
                                    <input type="text" class="form-control" id="programName" name="programName" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration</label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="programType" class="form-label">Program Type</label>
                                    <select class="form-select" id="programType" name="programType" required>
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
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="durationType" class="form-label">Duration Type</label>
                                    <select class="form-select" id="durationType" name="durationType" required>
                                        <option value="">Select Type</option>
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
                                    <div class="invalid-feedback"></div>
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
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProgramBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProgramModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editProgramModalLabel">Edit Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProgramForm">
                        <input type="hidden" id="editProgramId" name="programId">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">

                                <div class="mb-3">
                                    <label for="editProgramImage" class="form-label">Program Image</label>
                                    <input type="file" class="form-control" id="editProgramImage" name="editProgramImage" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editProgramName" class="form-label">Program Name</label>
                                    <input type="text" class="form-control" id="editProgramName" name="programName" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="editDuration" class="form-label">Duration</label>
                                    <input type="number" class="form-control" id="editDuration" name="duration" min="1" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editProgramType" class="form-label">Program Type</label>
                                    <select class="form-select" id="editProgramType" name="programType" required>
                                        <option value="">Select Program Type</option>
                                        <?php foreach ($programTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type['id']) ?>">
                                                <?= htmlspecialchars($type['type_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="editDurationType" class="form-label">Duration Type</label>
                                    <select class="form-select" id="editDurationType" name="durationType" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($durationTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type['id']) ?>">
                                                <?= htmlspecialchars($type['type_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
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
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateProgramBtn">Update</button>
                </div>
            </div>
        </div>
    </div>

<!-- Bootstrap JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> -->
    <script>
        
        $(document).ready(function() {
    // Initialize DataTable with similar configuration to gym rates
    if ($.fn.DataTable.isDataTable('#programsTable')) {
        $('#programsTable').DataTable().destroy(); // Destroy existing instance
    }
    
    $('#programsTable').DataTable({
        responsive: true,
        order: [[1, 'asc']], // Sort by second column ascending for consistency
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search programs..."
        },
        columnDefs: [
            { orderable: false, targets: [0] } // Disable sorting for image column
        ]
    });
});

// Function to validate a field and show error message
function validateField(fieldId, errorMessage) {
    const field = $(`#${fieldId}`);
    const value = field.val().trim();
    
    // Reset previous error state
    field.removeClass('is-invalid');
    const nextFeedback = field.next('.invalid-feedback');
    if (nextFeedback.length) {
        nextFeedback.remove();
    }
    
    if (!value || (field.attr('type') === 'number' && (isNaN(value) || parseInt(value) <= 0))) {
        field.addClass('is-invalid');
        field.after(`<div class="invalid-feedback">${errorMessage}</div>`);
        return false;
    }
    return true;
}

// Save program handler
$('#saveProgramBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Validate all required fields
    const isValid = 
        validateField('programName', 'Program name is required') &
        validateField('programType', 'Program type must be selected') &
        validateField('duration', 'Duration must be greater than 0') &
        validateField('durationType', 'Duration type must be selected');

    // If any validation fails, stop submission
    if (!isValid) {
        return false;
    }

    // Prepare FormData for submission (similar to gym rates)
    var formData = new FormData($('#addProgramForm')[0]);

    // Handle image file (if provided)
    var imageFile = $('#programImage')[0].files[0];
    if (imageFile) {
        formData.append('programImage', imageFile);
    }

    // Send AJAX request
    $.ajax({
        url: '../admin/pages/gym rates/functions/save_programs.php',
        type: 'POST',
        data: formData,
        processData: false,  // Important for file upload
        contentType: false,   // Important for file upload
        success: function(response) {
            console.log("Response:", response);
            
            // Check if response is already an object
            if (typeof response === 'object') {
                if (response.status === 'success') {
                    // Get modal instances using Bootstrap 5 method
                    var addModal = bootstrap.Modal.getInstance(document.getElementById('addProgramModal'));
                    if (addModal) {
                        addModal.hide();
                    } else {
                        // Fallback if the modal instance isn't available
                        $('#addProgramModal').removeClass('show');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                    
                    // Show success modal using Bootstrap 5 method
                    var successModalEl = document.getElementById('successModal');
                    if (successModalEl) {
                        var successModal = new bootstrap.Modal(successModalEl);
                        successModal.show();
                        
                        successModalEl.addEventListener('hidden.bs.modal', function() {
                            location.reload();
                        });
                    } else {
                        // Fallback if we can't show the success modal
                        alert("Success: " + (response.message || "Program added successfully"));
                        location.reload();
                    }
                } else {
                    alert("Error: " + (response.message || "Unknown error occurred"));
                }
            } else {
                // Try to parse as JSON if it's a string
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Same as above, using Bootstrap 5 methods
                        var addModal = bootstrap.Modal.getInstance(document.getElementById('addProgramModal'));
                        if (addModal) {
                            addModal.hide();
                        } else {
                            $('#addProgramModal').removeClass('show');
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        }
                        
                        var successModalEl = document.getElementById('successModal');
                        if (successModalEl) {
                            var successModal = new bootstrap.Modal(successModalEl);
                            successModal.show();
                            
                            successModalEl.addEventListener('hidden.bs.modal', function() {
                                location.reload();
                            });
                        } else {
                            alert("Success: " + (data.message || "Program added successfully"));
                            location.reload();
                        }
                    } else {
                        alert("Error: " + (data.message || "Unknown error occurred"));
                    }
                } catch (e) {
                    // Plain string response
                    alert("Success: Program added successfully");
                    location.reload();
                }
            }
        },
        error: function(xhr, status, error) {
            alert("AJAX error: " + error);
        }
    });
});


$(document).on('click', '.edit-btn', function() {
    const programId = $(this).data('id');

    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_programs.php',
        type: 'GET',
        data: { id: programId },
        dataType: 'json',
        success: function(response) {
            console.log("Response from edit:", response);
            
            // Changed from response.status === 'success' to response.success === true
            if (response.success === true) {
                const program = response.data;
                console.log("Program data:", program);

                // Populate the form
                $('#editProgramId').val(program.id);
                $('#editProgramName').val(program.program_name);
                $('#editProgramType').val(program.program_type_id);
                $('#editDuration').val(program.duration);
                $('#editDurationType').val(program.duration_type_id);
                $('#editDescription').val(program.description);

                // Show the modal using Bootstrap 5 method
                const editModal = new bootstrap.Modal(document.getElementById('editProgramModal'));
                editModal.show();
            } else {
                alert('Error: ' + (response.message || 'Failed to load program details'));
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
            alert('Error occurred while fetching program details: ' + error);
        }
    });
});

// Update program button handler
$('#updateProgramBtn').click(function() {
    // Clear previous error messages
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Validate all required fields
    const isValid = 
        validateField('editProgramName', 'Program name is required') &
        validateField('editProgramType', 'Program type must be selected') &
        validateField('editDuration', 'Duration must be greater than 0') &
        validateField('editDurationType', 'Duration type must be selected');

    // If any validation fails, stop submission
    if (!isValid) {
        return false;
    }

    // Prepare FormData for submission (similar to gym rates)
    const formData = new FormData($('#editProgramForm')[0]);
    formData.append('action', 'update');

    // Send AJAX request
    $.ajax({
        url: '../admin/pages/gym rates/functions/edit_programs.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.success || data.status === 'success') {
                    // Update the modal body with the success message
                    $('#updateSuccessModal .modal-body p').text(data.message || "Updated successfully!");
                    
                    // Show the success modal
                    $('#updateSuccessModal').modal('show');
                    
                    // Hide the edit modal
                    $('#editProgramModal').modal('hide');
                    
                    // Reload the page after the modal is hidden
                    $('#updateSuccessModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            } catch(e) {
                if (response.trim() === "success") {
                    $('#editProgramModal').modal('hide');
                    $('#updateSuccessModal').modal('show');
                    $('#updateSuccessModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
                } else {
                    alert('Error: ' + response);
                }
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while updating program: ' + error);
        }
    });
});

// Reset edit form when modal is closed
$('#editProgramModal').on('hidden.bs.modal', function () {
    $('#editProgramForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
});


                $(document).on('click', '.toggle-status-btn', function() {
            const btn = $(this);
            const programId = btn.data('id');
            const newStatus = btn.data('new-status');
            
            // Set up the appropriate modal based on the new status
            if (newStatus === 'active') {
                $('#activateModal').data('id', programId);
                $('#activateModal').modal('show');
            } else {
                $('#deactivateModal').data('id', programId);
                $('#deactivateModal').modal('show');
            }
        });

        // Handler for activate confirmation
        $('#confirmActivate').on('click', function() {
            const programId = $('#activateModal').data('id');
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>/functions/save_programs.php',
                type: 'POST',
                data: { 
                    action: 'toggle_status',
                    id: programId,
                    new_status: 'active'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                    $('#activateModal').modal('hide');
                },
                error: function(xhr, status, error) {
                    alert('Error occurred while updating status. Please try again.');
                    console.error('Error:', error);
                    $('#activateModal').modal('hide');
                }
            });
        });

        // Handler for deactivate confirmation
        $('#confirmDeactivate').on('click', function() {
            const programId = $('#deactivateModal').data('id');
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>/functions/save_programs.php',
                type: 'POST',
                data: { 
                    action: 'toggle_status',
                    id: programId,
                    new_status: 'inactive'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                    $('#deactivateModal').modal('hide');
                },
                error: function(xhr, status, error) {
                    alert('Error occurred while updating status. Please try again.');
                    console.error('Error:', error);
                    $('#deactivateModal').modal('hide');
                }
            });
        });

        // Refresh button handler
        $('#refreshBtn').click(function() {
            location.reload();
        });

        // View Coaches button click handler
        $('.view-coaches-btn').click(function() {
            try {
                const coachesData = $(this).data('coaches');
                const coaches = typeof coachesData === 'string' ? JSON.parse(coachesData) : coachesData;
                const programName = $(this).data('program-name');
                const programId = $(this).data('id'); // Get program ID
                
                // Update modal title with program name
                $('.program-name-display').text('Coaches for: ' + programName);
                
                // Set program ID on Add Coach button
                $('.add-coach-btn').data('program-id', programId);
                
                // Clear existing table content
                const tbody = $('#coachesTableBody');
                tbody.empty();
                
                if (!coaches || coaches.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center">No coaches assigned to this program</td></tr>');
                } else {
                    // Add coaches to table
                    coaches.forEach((coach, index) => {
                        const description = coach.description || 'N/A';
                        const truncatedDesc = description.length > 50 ? 
                            description.substring(0, 50) + '...' : 
                            description;
                        
                        const row = `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td>${coach.first_name} ${coach.last_name}</td>
                                <td class="text-end">₱${parseFloat(coach.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td>${truncatedDesc}</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
                
                // Show the modal
                $('#viewCoachesModal').modal('show');
            } catch (error) {
                console.error('Error parsing coach data:', error);
                alert('Error loading coach data. Please try again.');
            }
        });

        $(document).on('click', '.remove-btn', function() {
    const programId = $(this).data('id');
    $('#deleteModal').data('program-id', programId);
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
});

        // Event listener for the confirm delete button in the modal
        $(document).on('click', '#confirmDelete', function() {
            const programId = $('#deleteModal').data('program-id');
            
            $.ajax({
                url: '../admin/pages/gym rates/functions/edit_programs.php',
                type: 'POST',
                data: { 
                    action: 'remove', 
                    programId: programId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message || "Error removing program");
                    }
                    // Close the modal
                    $('#deleteModal').modal('hide');
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert(response.message || "Error removing program. Please try again.");
                    } catch(e) {
                        alert("Error removing program. Please try again.");
                    }
                    // Close the modal
                    $('#deleteModal').modal('hide');
                }
            });
        });

        // Add Coach Button Click
        $(document).on('click', '.add-coach-btn', function() {
            $('#viewCoachesModal').modal('hide');
            $('#programId').val($(this).data('program-id'));
            $('#addCoachModal').modal('show');
        });

        // Add Coach Form Submission
        $('#saveCoachBtn').on('click', function() {
            // Clear previous validation states
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // Validate all required fields
            const isValid = validateCoachForm();
            
            if (!isValid) return;

            const formData = new FormData($('#addCoachForm')[0]);
            
            // Log form data for debugging
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            $.ajax({
                url: '../admin/pages/gym rates/functions/add_coaches.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        alert('Coach added successfully!');
                        $('#addCoachModal').modal('hide');
                        location.reload(); // Reload the page after successful addition
                    } else {
                        // Show error message in the appropriate field
                        handleCoachFormError(data.message);
                    }
                },
                error: function(xhr, status, error) {
                    handleCoachFormError('Error adding coach: ' + error);
                }
            });
        });

        function validateCoachForm() {
            let isValid = true;
            
            // Validate coach selection
            if (!$('#coachSelect').val()) {
                $('#coachSelect').addClass('is-invalid');
                $('#coachSelect').siblings('.invalid-feedback').text('Please select a coach');
                isValid = false;
            }

            // Validate price
            const price = $('#coachPrice').val();
            if (!price || price <= 0) {
                $('#coachPrice').addClass('is-invalid');
                $('#coachPrice').siblings('.invalid-feedback').text('Please enter a valid price');
                isValid = false;
            }

            return isValid;
        }

        function handleCoachFormError(errorMessage) {
            // Check if error message contains specific keywords to determine which field to show error on
            if (errorMessage.toLowerCase().includes('coach')) {
                $('#coachSelect').addClass('is-invalid');
                $('#coachSelect').siblings('.invalid-feedback').text(errorMessage);
            } else if (errorMessage.toLowerCase().includes('price')) {
                $('#coachPrice').addClass('is-invalid');
                $('#coachPrice').siblings('.invalid-feedback').text(errorMessage);
            } else {
                // If no specific field error, show on description as general error
                $('#coachDescription').addClass('is-invalid');
                $('#coachDescription').siblings('.invalid-feedback').text(errorMessage);
            }
        }

        // Clear validation on modal close
        $('#addCoachModal').on('hidden.bs.modal', function () {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        });


    </script>
</body>
</html>