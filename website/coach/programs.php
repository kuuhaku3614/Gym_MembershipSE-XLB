<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    require_once '../../website/coach.class.php';
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    
    try {
        $coach = new Coach_class();
        
        // Handle AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
                    if ($_GET['action'] === 'get_schedule') {
                        $programTypeId = intval($_GET['program_type_id']);
                        $type = $_GET['type'];
                        
                        if ($type === 'group') {
                            $schedules = $coach->getGroupSchedule($programTypeId);
                        } else {
                            $schedules = $coach->getPersonalSchedule($programTypeId);
                        }
                        
                        echo json_encode($schedules);
                        exit;
                    }
                }
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $postData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                    
                    if (isset($postData['action'])) {
                        switch ($postData['action']) {
                            case 'toggle_status':
                                $coachProgramTypeId = intval($postData['coach_program_type_id']);
                                $currentStatus = $postData['current_status'];
                                $result = $coach->toggleProgramStatus($coachProgramTypeId, $_SESSION['user_id'], $currentStatus);
                                echo json_encode(['success' => $result]);
                                exit;
                                
                            case 'save_group_schedule':
                                $scheduleId = !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
                                $programTypeId = intval($_POST['program_type_id']);
                                $day = $_POST['day'];
                                $startTime = $_POST['start_time'];
                                $endTime = $_POST['end_time'];
                                $capacity = intval($_POST['capacity']);
                                $price = floatval($_POST['price']);
                                
                                $result = $coach->saveGroupSchedule($scheduleId, $programTypeId, $day, $startTime, $endTime, $capacity, $price);
                                echo json_encode($result);
                                exit;
                                
                            case 'save_personal_schedule':
                                $scheduleId = !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
                                $programTypeId = intval($_POST['program_type_id']);
                                $day = $_POST['day'];
                                $startTime = $_POST['start_time'];
                                $endTime = $_POST['end_time'];
                                $price = floatval($_POST['price']);
                                $duration = intval($_POST['duration_rate']);
                                
                                $result = $coach->savePersonalSchedule($scheduleId, $programTypeId, $day, $startTime, $endTime, $price, $duration);
                                echo json_encode($result);
                                exit;
                                
                            case 'delete_schedule':
                                $scheduleId = intval($_POST['schedule_id']);
                                $type = $_POST['type'];
                                
                                $result = $coach->deleteSchedule($scheduleId, $type);
                                echo json_encode($result);
                                exit;
                        }
                    }
                }
                
                throw new Exception('Invalid action');
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
        
        $coachPrograms = $coach->getCoachPrograms($_SESSION['user_id']);
        include('../coach.nav.php');
    } catch (Exception $e) {
        echo 'An error occurred: ' . $e->getMessage();
        exit;
    }
?>

<style>
    .modal-content {
        padding: 0;
        max-width: 100%;
    }
    
    /* Match members.php styling */
    html {
        background-color: transparent;
    }
    body {
        max-height: 100vh;
        background-color: #efefef!important;
    }
    th {
        font-weight: 500;
        font-size: 1.25em;
    }
    td {
        font-family: "Inter", sans-serif!important;
        font-weight: 600;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
        display: none;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .is-invalid:focus {
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
    }
</style>

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
                                                <td>
                                                    <?php if ($program['coach_program_status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn <?= $program['coach_program_status'] === 'active' ? 'btn-danger' : 'btn-success' ?> btn-sm me-2"
                                                            onclick="toggleStatus(<?= $program['coach_program_type_id'] ?>, '<?= $program['coach_program_status'] ?>', this)">
                                                        <?php if ($program['coach_program_status'] === 'active'): ?>
                                                            <i class="fas fa-times-circle"></i> Deactivate
                                                        <?php else: ?>
                                                            <i class="fas fa-check-circle"></i> Activate
                                                        <?php endif; ?>
                                                    </button>
                                                    <?php if ($program['coach_program_type'] === 'group'): ?>
                                                        <button type="button" class="btn btn-info btn-sm" onclick="viewGroupSchedule(<?= $program['coach_program_type_id'] ?>)">
                                                            <i class="fas fa-calendar"></i> View Availability
                                                        </button>
                                                    <?php elseif ($program['coach_program_type'] === 'personal'): ?>
                                                        <button type="button" class="btn btn-info btn-sm" onclick="viewPersonalSchedule(<?= $program['coach_program_type_id'] ?>)">
                                                            <i class="fas fa-calendar"></i> View Availability
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No programs found</td>
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

<!-- Group Schedule Modal -->
<div class="modal fade" id="groupScheduleModal" tabindex="-1" aria-labelledby="groupScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupScheduleModalLabel">Group Training Schedule</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn btn-primary" onclick="showAddGroupSchedule()">Add Schedule</button>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Schedule Time</th>
                                <th>Price</th>
                                <th>Member Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="groupScheduleTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Personal Schedule Modal -->
<div class="modal fade" id="personalScheduleModal" tabindex="-1" aria-labelledby="personalScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personalScheduleModalLabel">Personal Training Schedule</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn btn-primary" onclick="showAddPersonalSchedule()">Add Schedule</button>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Available Time</th>
                                <th>Min. rate</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="personalScheduleTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Group Schedule Modal -->
<div class="modal fade" id="editGroupScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGroupScheduleTitle">Add Group Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="groupScheduleForm" onsubmit="return validateAndSaveGroupSchedule(event)">
                    <input type="hidden" id="groupScheduleId" name="schedule_id">
                    <input type="hidden" id="groupProgramTypeId" name="program_type_id">
                    
                    <div class="mb-3">
                        <label for="groupDay" class="form-label">Day</label>
                        <select class="form-select" id="groupDay" name="day">
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                        <div class="error-message" id="groupDay-error">Please select a day</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupStartTime" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="groupStartTime" name="start_time">
                        <div class="error-message" id="groupStartTime-error">Please enter a start time</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupEndTime" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="groupEndTime" name="end_time">
                        <div class="error-message" id="groupEndTime-error">Please enter an end time that is after the start time</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupCapacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="groupCapacity" name="capacity" min="1">
                        <div class="error-message" id="groupCapacity-error">Please enter a valid capacity (minimum 1)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupPrice" class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="groupPrice" name="price" min="0" step="0.01">
                        <div class="error-message" id="groupPrice-error">Please enter a valid price (minimum 0)</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Personal Schedule Modal -->
<div class="modal fade" id="editPersonalScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPersonalScheduleTitle">Add Personal Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="personalScheduleForm" onsubmit="return validateAndSavePersonalSchedule(event)">
                    <input type="hidden" id="personalScheduleId" name="schedule_id">
                    <input type="hidden" id="personalProgramTypeId" name="program_type_id">
                    
                    <div class="mb-3">
                        <label for="personalDay" class="form-label">Day</label>
                        <select class="form-select" id="personalDay" name="day">
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                        <div class="error-message" id="personalDay-error">Please select a day</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="personalStartTime" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="personalStartTime" name="start_time">
                        <div class="error-message" id="personalStartTime-error">Please enter a start time</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="personalEndTime" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="personalEndTime" name="end_time">
                        <div class="error-message" id="personalEndTime-error">Please enter an end time that is after the start time</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="personalPrice" class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="personalPrice" name="price" min="0" step="0.01">
                        <div class="error-message" id="personalPrice-error">Please enter a valid price (minimum 0)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="personalDuration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="personalDuration" name="duration_rate" min="1">
                        <div class="error-message" id="personalDuration-error">Please enter a valid duration in minutes (minimum 1)</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(coachProgramTypeId, currentStatus, button) {
    fetch('programs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'toggle_status',
            coach_program_type_id: coachProgramTypeId,
            current_status: currentStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update program status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
    });
}

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    let hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    hour = hour ? hour : 12; // Convert 0 to 12
    return `${hour}:${minutes} ${ampm}`;
}

function viewGroupSchedule(programTypeId) {
    fetch(`programs.php?action=get_schedule&program_type_id=${programTypeId}&type=group`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const tableBody = document.getElementById('groupScheduleTableBody');
        tableBody.innerHTML = '';
        
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No schedule found</td></tr>';
        } else {
            data.forEach(schedule => {
                const startTime = formatTime(schedule.start_time);
                const endTime = formatTime(schedule.end_time);
                const memberNames = schedule.member_names || 'No members enrolled';
                const row = `
                    <tr>
                        <td>${schedule.day}</td>
                        <td>${startTime} - ${endTime}</td>
                        <td>₱ ${schedule.price}</td>
                        <td>
                            ${schedule.current_members || 0}/${schedule.capacity}
                            <i class="fas fa-info-circle text-info ms-2" 
                               style="cursor: pointer;" 
                               data-bs-toggle="tooltip" 
                               data-bs-placement="top" 
                               title="${memberNames.replace(/"/g, '&quot;')}">
                            </i>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editGroupSchedule(${schedule.id}, '${schedule.day}', '${schedule.start_time}', '${schedule.end_time}', ${schedule.capacity}, ${schedule.price})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id}, 'group')">Delete</button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
        document.getElementById('groupProgramTypeId').value = programTypeId;
        const modal = new bootstrap.Modal(document.getElementById('groupScheduleModal'));
        modal.show();
        
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltipEl => {
            new bootstrap.Tooltip(tooltipEl);
        });
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching the schedule');
    });
}

function viewPersonalSchedule(programTypeId) {
    fetch(`programs.php?action=get_schedule&program_type_id=${programTypeId}&type=personal`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const tableBody = document.getElementById('personalScheduleTableBody');
        tableBody.innerHTML = '';
        
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No schedule found</td></tr>';
        } else {
            data.forEach(schedule => {
                const startTime = formatTime(schedule.start_time);
                const endTime = formatTime(schedule.end_time);
                const row = `
                    <tr>
                        <td>${schedule.day}</td>
                        <td>${startTime} - ${endTime}</td>
                        <td>₱ ${schedule.price}</td>
                        <td>${schedule.duration_rate}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editPersonalSchedule(${schedule.id}, '${schedule.day}', '${schedule.start_time}', '${schedule.end_time}', ${schedule.price}, ${schedule.duration_rate})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id}, 'personal')">Delete</button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
        document.getElementById('personalProgramTypeId').value = programTypeId;
        const modal = new bootstrap.Modal(document.getElementById('personalScheduleModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching the schedule');
    });
}

function showAddGroupSchedule() {
    const programTypeId = document.getElementById('groupProgramTypeId').value;
    document.getElementById('groupScheduleForm').reset();
    document.getElementById('groupScheduleId').value = '';
    document.getElementById('groupProgramTypeId').value = programTypeId;
    document.getElementById('editGroupScheduleTitle').textContent = 'Add Group Schedule';
    
    // Hide the parent modal first
    const groupScheduleModal = document.getElementById('groupScheduleModal');
    const parentModal = bootstrap.Modal.getInstance(groupScheduleModal);
    if (parentModal) {
        parentModal.hide();
        // Store reference to parent modal
        window.activeGroupModal = parentModal;
    }
    
    // Show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editGroupScheduleModal'));
    editModal.show();
}

function showAddPersonalSchedule() {
    const programTypeId = document.getElementById('personalProgramTypeId').value;
    document.getElementById('personalScheduleForm').reset();
    document.getElementById('personalScheduleId').value = '';
    document.getElementById('personalProgramTypeId').value = programTypeId;
    document.getElementById('editPersonalScheduleTitle').textContent = 'Add Personal Schedule';
    
    // Hide the parent modal first
    const personalScheduleModal = document.getElementById('personalScheduleModal');
    const parentModal = bootstrap.Modal.getInstance(personalScheduleModal);
    if (parentModal) {
        parentModal.hide();
        // Store reference to parent modal
        window.activePersonalModal = parentModal;
    }
    
    // Show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editPersonalScheduleModal'));
    editModal.show();
}

function editGroupSchedule(id, day, startTime, endTime, capacity, price) {
    const programTypeId = document.getElementById('groupProgramTypeId').value;
    document.getElementById('groupScheduleId').value = id;
    document.getElementById('groupProgramTypeId').value = programTypeId;
    document.getElementById('groupDay').value = day;
    document.getElementById('groupStartTime').value = startTime;
    document.getElementById('groupEndTime').value = endTime;
    document.getElementById('groupCapacity').value = capacity;
    document.getElementById('groupPrice').value = price;
    document.getElementById('editGroupScheduleTitle').textContent = 'Edit Group Schedule';
    
    // Hide the parent modal first
    const groupScheduleModal = document.getElementById('groupScheduleModal');
    const parentModal = bootstrap.Modal.getInstance(groupScheduleModal);
    if (parentModal) {
        parentModal.hide();
        // Store reference to parent modal
        window.activeGroupModal = parentModal;
    }
    
    // Show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editGroupScheduleModal'));
    editModal.show();
}

function editPersonalSchedule(id, day, startTime, endTime, price, duration) {
    const programTypeId = document.getElementById('personalProgramTypeId').value;
    document.getElementById('personalScheduleId').value = id;
    document.getElementById('personalProgramTypeId').value = programTypeId;
    document.getElementById('personalDay').value = day;
    document.getElementById('personalStartTime').value = startTime;
    document.getElementById('personalEndTime').value = endTime;
    document.getElementById('personalPrice').value = price;
    document.getElementById('personalDuration').value = duration;
    document.getElementById('editPersonalScheduleTitle').textContent = 'Edit Personal Schedule';
    
    // Hide the parent modal first
    const personalScheduleModal = document.getElementById('personalScheduleModal');
    const parentModal = bootstrap.Modal.getInstance(personalScheduleModal);
    if (parentModal) {
        parentModal.hide();
        // Store reference to parent modal
        window.activePersonalModal = parentModal;
    }
    
    // Show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editPersonalScheduleModal'));
    editModal.show();
}

function removeModalBackdrop() {
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

function saveGroupSchedule() {
    const formData = new FormData(document.getElementById('groupScheduleForm'));
    formData.append('action', 'save_group_schedule');
    
    fetch('programs.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editGroupScheduleModal'));
            editModal.hide();
            removeModalBackdrop();
            
            if (window.activeGroupModal) {
                window.activeGroupModal.show();
                viewGroupSchedule(formData.get('program_type_id'));
            }
        } else {
            alert('Error: ' + (data.message || 'An error occurred while saving the schedule'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

function savePersonalSchedule() {
    const formData = new FormData(document.getElementById('personalScheduleForm'));
    formData.append('action', 'save_personal_schedule');
    
    fetch('programs.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editPersonalScheduleModal'));
            editModal.hide();
            removeModalBackdrop();
            
            if (window.activePersonalModal) {
                window.activePersonalModal.show();
                viewPersonalSchedule(formData.get('program_type_id'));
            }
        } else {
            alert('Error: ' + (data.message || 'An error occurred while saving the schedule'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

function deleteSchedule(id, type) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        const formData = new FormData();
        formData.append('action', 'delete_schedule');
        formData.append('schedule_id', id);
        formData.append('type', type);
        
        fetch('programs.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (type === 'group') {
                    viewGroupSchedule(document.getElementById('groupProgramTypeId').value);
                } else {
                    viewPersonalSchedule(document.getElementById('personalProgramTypeId').value);
                }
            } else {
                alert('Error: ' + (data.message || 'An error occurred while deleting the schedule'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }
}

function showError(inputId, message) {
    const input = document.getElementById(inputId);
    const errorDiv = document.getElementById(inputId + '-error');
    input.classList.add('is-invalid');
    errorDiv.style.display = 'block';
    errorDiv.textContent = message;
}

function hideError(inputId) {
    const input = document.getElementById(inputId);
    const errorDiv = document.getElementById(inputId + '-error');
    input.classList.remove('is-invalid');
    errorDiv.style.display = 'none';
}

function validateTime(startTime, endTime) {
    if (!startTime || !endTime) return false;
    return startTime < endTime;
}

function validateAndSaveGroupSchedule(event) {
    event.preventDefault();
    let isValid = true;
    
    // Reset all errors first
    ['groupDay', 'groupStartTime', 'groupEndTime', 'groupCapacity', 'groupPrice'].forEach(id => hideError(id));
    
    // Validate Day
    const day = document.getElementById('groupDay').value;
    if (!day) {
        showError('groupDay', 'Please select a day');
        isValid = false;
    }
    
    // Validate Times
    const startTime = document.getElementById('groupStartTime').value;
    const endTime = document.getElementById('groupEndTime').value;
    
    if (!startTime) {
        showError('groupStartTime', 'Please enter a start time');
        isValid = false;
    }
    
    if (!endTime) {
        showError('groupEndTime', 'Please enter an end time');
        isValid = false;
    }
    
    if (startTime && endTime && !validateTime(startTime, endTime)) {
        showError('groupEndTime', 'End time must be after start time');
        isValid = false;
    }
    
    // Validate Capacity
    const capacity = document.getElementById('groupCapacity').value;
    if (!capacity || capacity < 1) {
        showError('groupCapacity', 'Please enter a valid capacity (minimum 1)');
        isValid = false;
    }
    
    // Validate Price
    const price = document.getElementById('groupPrice').value;
    if (!price || price < 0) {
        showError('groupPrice', 'Please enter a valid price (minimum 0)');
        isValid = false;
    }
    
    if (isValid) {
        saveGroupSchedule();
    }
    
    return false;
}

function validateAndSavePersonalSchedule(event) {
    event.preventDefault();
    let isValid = true;
    
    // Reset all errors first
    ['personalDay', 'personalStartTime', 'personalEndTime', 'personalPrice', 'personalDuration'].forEach(id => hideError(id));
    
    // Validate Day
    const day = document.getElementById('personalDay').value;
    if (!day) {
        showError('personalDay', 'Please select a day');
        isValid = false;
    }
    
    // Validate Times
    const startTime = document.getElementById('personalStartTime').value;
    const endTime = document.getElementById('personalEndTime').value;
    
    if (!startTime) {
        showError('personalStartTime', 'Please enter a start time');
        isValid = false;
    }
    
    if (!endTime) {
        showError('personalEndTime', 'Please enter an end time');
        isValid = false;
    }
    
    if (startTime && endTime && !validateTime(startTime, endTime)) {
        showError('personalEndTime', 'End time must be after start time');
        isValid = false;
    }
    
    // Validate Price
    const price = document.getElementById('personalPrice').value;
    if (!price || price < 0) {
        showError('personalPrice', 'Please enter a valid price (minimum 0)');
        isValid = false;
    }
    
    // Validate Duration
    const duration = document.getElementById('personalDuration').value;
    if (!duration || duration < 1) {
        showError('personalDuration', 'Please enter a valid duration in minutes (minimum 1)');
        isValid = false;
    }
    
    if (isValid) {
        savePersonalSchedule();
    }
    
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    const modals = [
        'groupScheduleModal',
        'personalScheduleModal',
        'editGroupScheduleModal',
        'editPersonalScheduleModal'
    ];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        modal.addEventListener('hidden.bs.modal', function() {
            removeModalBackdrop();
        });
    });
});

</script>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
