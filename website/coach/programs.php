<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();

    // Handle AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        // Get availability list
        if (isset($_GET['action']) && $_GET['action'] === 'get_availability' && isset($_GET['program_type_id'])) {
            $availabilities = $coach->getCoachAvailability($_GET['program_type_id']);
            echo json_encode($availabilities);
            exit;
        }
        
        // Get availability details
        if (isset($_GET['action']) && $_GET['action'] === 'get_availability_details' && isset($_GET['id'])) {
            $availability = $coach->getAvailabilityDetails($_GET['id']);
            echo json_encode($availability);
            exit;
        }
        
        // Save availability
        if (isset($_POST['action']) && $_POST['action'] === 'save_availability') {
            $data = [
                'id' => $_POST['availabilityId'] ?? null,
                'coach_program_type_id' => $_POST['coachProgramTypeId'],
                'day' => $_POST['day'],
                'start_time' => $_POST['startTime'],
                'end_time' => $_POST['endTime']
            ];
            $success = $coach->saveAvailability($data);
            echo json_encode(['success' => $success]);
            exit;
        }
        
        // Delete availability
        if (isset($_POST['action']) && $_POST['action'] === 'delete_availability' && isset($_POST['id'])) {
            $success = $coach->deleteAvailability($_POST['id']);
            echo json_encode(['success' => $success]);
            exit;
        }
        
        // Handle program status toggle
        if (isset($_POST['toggle_status'])) {
            $programId = $_POST['program_id'];
            $currentStatus = $_POST['current_status'];
            $success = $coach->toggleProgramStatus($programId, $_SESSION['user_id'], $currentStatus);
            echo json_encode(['success' => $success]);
            exit;
        }
    }

    $coachPrograms = $coach->getCoachPrograms($_SESSION['user_id']);
    include('../coach.nav.php');
?>

<!-- Add Bootstrap JavaScript before any other scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($message)): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">My Programs</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Program Name</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Duration</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($coachPrograms)): ?>
                                        <?php foreach ($coachPrograms as $program): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($program['program_name']) ?></td>
                                                <td><?= htmlspecialchars($program['coach_program_type']) ?></td>
                                                <td><?= htmlspecialchars($program['coach_program_description']) ?></td>
                                                <td><?= htmlspecialchars($program['duration'] . ' ' . $program['duration_type']) ?></td>
                                                <td>₱<?= number_format($program['coach_program_price'], 2) ?></td>
                                                <td>
                                                    <?php if ($program['coach_program_status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn <?= $program['coach_program_status'] === 'active' ? 'btn-danger' : 'btn-success' ?> btn-sm"
                                                            onclick="toggleStatus(<?= $program['coach_program_type_id'] ?>, '<?= $program['coach_program_status'] ?>', this)">
                                                        <?php if ($program['coach_program_status'] === 'active'): ?>
                                                            <i class="fas fa-times-circle"></i> Deactivate
                                                        <?php else: ?>
                                                            <i class="fas fa-check-circle"></i> Activate
                                                        <?php endif; ?>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-info btn-sm"
                                                            onclick="showAvailability(<?= $program['coach_program_type_id'] ?>)">
                                                        <i class="fas fa-clock"></i> Availability
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No programs found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Availability Modal -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="availabilityModalLabel">Coach Availability</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="availabilityTableBody">
                            <!-- Availability data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary" onclick="showAddAvailabilityForm()">
                    <i class="fas fa-plus"></i> Add Availability
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Availability Form Modal -->
<div class="modal fade" id="availabilityFormModal" tabindex="-1" aria-labelledby="availabilityFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="availabilityFormModalLabel">Add Availability</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="availabilityForm">
                    <input type="hidden" id="availabilityId" name="availabilityId">
                    <input type="hidden" id="coachProgramTypeId" name="coachProgramTypeId">
                    
                    <div class="mb-3">
                        <label for="day" class="form-label">Day</label>
                        <select class="form-select" id="day" name="day" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="startTime" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="startTime" name="startTime" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="endTime" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="endTime" name="endTime" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveAvailability()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(programId, currentStatus, button) {
    // Create form data
    const formData = new FormData();
    formData.append('program_id', programId);
    formData.append('current_status', currentStatus);
    formData.append('toggle_status', '1');

    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button and badge without page reload
            const row = button.closest('tr');
            const badge = row.querySelector('.badge');
            const isCurrentlyActive = currentStatus === 'active';
            
            // Update badge
            badge.className = isCurrentlyActive ? 'badge bg-danger' : 'badge bg-success';
            badge.textContent = isCurrentlyActive ? 'Inactive' : 'Active';
            
            // Update button
            button.className = isCurrentlyActive ? 'btn btn-success btn-sm' : 'btn btn-danger btn-sm';
            button.innerHTML = isCurrentlyActive ? 
                '<i class="fas fa-check-circle"></i> Activate' : 
                '<i class="fas fa-times-circle"></i> Deactivate';
            
            // Update onclick handler
            button.setAttribute('onclick', `toggleStatus(${programId}, '${isCurrentlyActive ? 'inactive' : 'active'}', this)`);
            
            // Show alert
            alert('Program status updated successfully!');
        } else {
            alert('Failed to update program status.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update program status.');
    });
}

let currentProgramTypeId = null;

function showAvailability(programTypeId) {
    currentProgramTypeId = programTypeId;
    // Fetch availability data for this program type
    fetch(`${window.location.href}?action=get_availability&program_type_id=${programTypeId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const tableBody = document.getElementById('availabilityTableBody');
        tableBody.innerHTML = '';
        
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No availability set</td></tr>';
            return;
        }
        
        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.day}</td>
                <td>${item.start_time}</td>
                <td>${item.end_time}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editAvailability(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAvailability(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load availability data.');
    });

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('availabilityModal'));
    modal.show();
}

function showAddAvailabilityForm() {
    // Hide availability modal
    const availabilityModal = bootstrap.Modal.getInstance(document.getElementById('availabilityModal'));
    availabilityModal.hide();
    
    // Reset form
    document.getElementById('availabilityForm').reset();
    document.getElementById('availabilityId').value = '';
    document.getElementById('coachProgramTypeId').value = currentProgramTypeId;
    
    // Show form modal
    const formModal = new bootstrap.Modal(document.getElementById('availabilityFormModal'));
    formModal.show();
}

function editAvailability(availabilityId) {
    // Fetch availability details
    fetch(`${window.location.href}?action=get_availability_details&id=${availabilityId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide availability modal
        const availabilityModal = bootstrap.Modal.getInstance(document.getElementById('availabilityModal'));
        availabilityModal.hide();
        
        // Fill form with data
        document.getElementById('availabilityId').value = data.id;
        document.getElementById('coachProgramTypeId').value = currentProgramTypeId;
        document.getElementById('day').value = data.day;
        document.getElementById('startTime').value = data.start_time;
        document.getElementById('endTime').value = data.end_time;
        
        // Show form modal
        const formModal = new bootstrap.Modal(document.getElementById('availabilityFormModal'));
        formModal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load availability details.');
    });
}

function saveAvailability() {
    const formData = new FormData(document.getElementById('availabilityForm'));
    formData.append('action', 'save_availability');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide form modal
            const formModal = bootstrap.Modal.getInstance(document.getElementById('availabilityFormModal'));
            formModal.hide();
            
            // Refresh and show availability modal
            showAvailability(currentProgramTypeId);
        } else {
            alert('Failed to save availability.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save availability.');
    });
}

function deleteAvailability(availabilityId) {
    if (!confirm('Are you sure you want to delete this availability?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_availability');
    formData.append('id', availabilityId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh availability table
            showAvailability(currentProgramTypeId);
        } else {
            alert('Failed to delete availability.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete availability.');
    });
}
</script>
