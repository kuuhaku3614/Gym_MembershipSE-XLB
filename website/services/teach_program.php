<?php
session_start();
require_once '../../functions/sanitize.php';
require_once '../../login/functions.php';
require_once '../../website/coach.class.php';
require_once 'services.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

$coach = new Coach_class();
$services = new Services_Class();
$user_id = $_SESSION['user_id'];

// Get program by id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location: ../services.php');
    exit;
}
$program_id = intval($_GET['id']);
$program = $services->getProgramById($program_id); // You may need to implement this in services.class.php
if (!$program) {
    header('location: ../services.php');
    exit;
}
// Get coach's availed programs
$coach_programs = $coach->getCoachPrograms($user_id);

// Get website colors
$color = executeQuery("SELECT * FROM website_content WHERE section = 'color'")[0] ?? [];
function decimalToHex($decimal) {
    $hex = dechex(abs(floor($decimal * 16777215)));
    // Ensure hex values are properly formatted with leading zeros
    return '#' . str_pad($hex, 6, '0', STR_PAD_LEFT);
}
$primaryHex = isset($color['latitude']) ? decimalToHex($color['latitude']) : '#000000';
$secondaryHex = isset($color['longitude']) ? decimalToHex($color['longitude']) : '#000000';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teach a Program - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryHex; ?>;
            --secondary-color: <?php echo $secondaryHex; ?>;
        }
        .bg-custom-red { background-color: var(--primary-color); }
        .text-custom-red { color: var(--primary-color); }
        .btn-custom-red {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-custom-red:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        .teach-program-page { min-height: 100vh; display: flex; flex-direction: column; }
        .main-container { max-width: 1200px; }
        .program-card { transition: transform 0.2s; cursor: pointer; }
        .program-card:hover { transform: translateY(-5px); }
        .schedule-list { max-height: 300px; overflow-y: auto; }
        .is-invalid { border-color: #dc3545 !important; }
        .is-invalid:focus { box-shadow: 0 0 0 0.25rem rgba(220,53,69,0.25) !important; }
    </style>
</head>
<body>
<div class="teach-program-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center services-header">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">TEACH A PROGRAM</h1>
        </div>
        <div class="row flex-grow-1 overflow-auto">
            <div class="col-12 col-lg-8 mx-auto py-3 main-container">
                <div class="card main-content mb-4">
    <div class="card-header py-3 text-center">
        <h2 class="h4 fw-bold mb-0">Teach: <?php echo htmlspecialchars($program['program_name']); ?></h2>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <h4><?php echo htmlspecialchars($program['program_name']); ?></h4>
            <p class="mb-1 text-muted"><?php echo htmlspecialchars($program['description'] ?? ''); ?></p>
        </div>
        <form id="teachProgramForm">
            <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typeGroup" value="group" checked required>
                            <label class="form-check-label" for="typeGroup">Group</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="typePersonal" value="personal" required>
                            <label class="form-check-label" for="typePersonal">Personal</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label for="programDescription" class="form-label">Description (optional)</label>
                    <textarea class="form-control" id="programDescription" name="description" rows="2" placeholder="Add a description for this offering (optional)"></textarea>
                </div>
            </div>

            <div id="existingSchedulesSection" class="mt-4" style="display:none;">
    <h5>Existing Schedules</h5>
    <ul id="existingGroupSchedules" class="list-group mb-2"></ul>
    <ul id="existingPersonalSchedules" class="list-group mb-2"></ul>
</div>
<div id="scheduleInputs" class="mt-4" style="display:none;"></div>
            <div class="mt-3">
                <button type="button" class="btn btn-custom-red" id="addScheduleBtn">Add Schedule to List</button>
            </div>
        </form>
        <div id="scheduleCartSection" class="mt-4" style="display:none;">
            <h5>Schedules to Add</h5>
            <ul id="scheduleCart" class="list-group mb-3"></ul>
            <button type="button" class="btn btn-success" id="saveAllSchedulesBtn" style="display:none;">Save All Schedules</button>
        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// All DOM references and event listeners are declared only once below
let groupDescription = '';
let personalDescription = '';

window.addEventListener('DOMContentLoaded', function() {
    const scheduleInputs = document.getElementById('scheduleInputs');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const descriptionInput = document.getElementById('programDescription');
    const addScheduleBtn = document.getElementById('addScheduleBtn');
    let lastType = document.querySelector('input[name="type"]:checked').value;

    // Stateful description switching for group/personal
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (lastType === 'group') groupDescription = descriptionInput.value;
            else if (lastType === 'personal') personalDescription = descriptionInput.value;
            lastType = this.value;
            if (lastType === 'group') descriptionInput.value = groupDescription;
            else if (lastType === 'personal') descriptionInput.value = personalDescription;
            renderScheduleFields();
            fetchAndRenderExistingSchedules();
        });
    });
    if (lastType === 'group') descriptionInput.value = groupDescription;
    else if (lastType === 'personal') descriptionInput.value = personalDescription;
    fetchAndRenderExistingSchedules();
    addScheduleBtn.addEventListener('click', function() {
        if (lastType === 'group') groupDescription = descriptionInput.value;
        else if (lastType === 'personal') personalDescription = descriptionInput.value;
    });
});
function getSelectedType() {
    const checked = document.querySelector('input[name="type"]:checked');
    return checked ? checked.value : '';
}
// Prevent form submission on Enter
const teachProgramForm = document.getElementById('teachProgramForm');
teachProgramForm.addEventListener('submit', function(e) { e.preventDefault(); });
// Cart system
let scheduleCart = [];
const scheduleCartSection = document.getElementById('scheduleCartSection');
const scheduleCartList = document.getElementById('scheduleCart');
const addScheduleBtn = document.getElementById('addScheduleBtn');
const saveAllSchedulesBtn = document.getElementById('saveAllSchedulesBtn');
saveAllSchedulesBtn.addEventListener('click', saveAllSchedules);

function saveAllSchedules() {
    const programId = document.querySelector('input[name="program_id"]').value;
    if (!scheduleCart.length) return;
    saveAllSchedulesBtn.disabled = true;
    fetch('teach_program_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            program_id: programId,
            schedules: scheduleCart,
            group_description: groupDescription,
            personal_description: personalDescription
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Schedules saved successfully!');
            scheduleCart = [];
            updateScheduleCartUI();
            fetchAndRenderExistingSchedules();
        } else {
            alert(data.message || 'Failed to save schedules.');
        }
    })
    .catch(() => {
        alert('An error occurred while saving schedules.');
    })
    .finally(() => {
        saveAllSchedulesBtn.disabled = false;
    });
}

addScheduleBtn.addEventListener('click', function() {
    const type = getSelectedType();
    if (!type) return;
    // Get schedule values
    const inputs = scheduleInputs.querySelectorAll('input, select');
    let schedule = { type };
    let valid = true;
    inputs.forEach(inp => {
        if (inp.required && !inp.value) valid = false;
        schedule[inp.name] = inp.value;
    });
    if (!valid) { alert('Please fill all required schedule fields.'); return; }

    // Validation: start_time must be less than end_time
    if (schedule.start_time && schedule.end_time) {
        const start = schedule.start_time;
        const end = schedule.end_time;
        // Compare as HH:MM
        if (start >= end) {
            alert('Start time must be less than end time.');
            return;
        }
    }

    scheduleCart.push(schedule);
    updateScheduleCartUI();
    // Reset schedule fields
    inputs.forEach(inp => { if(inp.type!=='select-one') inp.value=''; else inp.selectedIndex=0; });
});
function updateScheduleCartUI() {
    scheduleCartSection.style.display = scheduleCart.length ? 'block' : 'none';
    saveAllSchedulesBtn.style.display = scheduleCart.length ? 'inline-block' : 'none';
    scheduleCartList.innerHTML = '';
    scheduleCart.forEach((s, idx) => {
        let details = '';
        if (s.type === 'group') {
            details = `${s.day}, ${s.start_time}-${s.end_time}, Capacity: ${s.capacity}, ₱${s.price}`;
        } else {
            details = `${s.day}, ${s.start_time}-${s.end_time}, Duration: ${s.duration_rate} mins, ₱${s.price}`;
        }
        scheduleCartList.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">${details} <button class="btn btn-sm btn-danger" onclick="removeSchedule(${idx})">Remove</button></li>`;
    });
}
window.removeSchedule = function(idx) {
    scheduleCart.splice(idx,1);
    updateScheduleCartUI();
}
// Show schedule fields if a type is already selected on page load
window.addEventListener('DOMContentLoaded', function() {
    renderScheduleFields();
});
function fetchAndRenderExistingSchedules() {
    const programId = document.querySelector('input[name="program_id"]').value;
    const section = document.getElementById('existingSchedulesSection');
    const groupList = document.getElementById('existingGroupSchedules');
    const personalList = document.getElementById('existingPersonalSchedules');
    fetch(`teach_program_backend.php?program_id=${programId}`)
        .then(res => res.json())
        .then(data => {
            let hasAny = false;
            // Render group schedules
            if (data.success && data.schedules && data.schedules.group.length) {
                groupList.innerHTML = '<li class="list-group-item active">Group Schedules</li>';
                data.schedules.group.forEach(s => {
                    groupList.innerHTML += `<li class="list-group-item">${s.day}, ${s.start_time}-${s.end_time}, Capacity: ${s.capacity}, ₱${s.price}</li>`;
                });
                hasAny = true;
            } else {
                groupList.innerHTML = '<li class="list-group-item text-muted">No group schedules.</li>';
            }
            // Render personal schedules
            if (data.success && data.schedules && data.schedules.personal.length) {
                personalList.innerHTML = '<li class="list-group-item active">Personal Schedules</li>';
                data.schedules.personal.forEach(s => {
                    personalList.innerHTML += `<li class="list-group-item">${s.day}, ${s.start_time}-${s.end_time}, Duration: ${s.duration_rate} mins, ₱${s.price}</li>`;
                });
                hasAny = true;
            } else {
                personalList.innerHTML = '<li class="list-group-item text-muted">No personal schedules.</li>';
            }
            section.style.display = hasAny ? 'block' : 'block';
        })
        .catch(() => {
            document.getElementById('existingSchedulesSection').style.display = 'none';
        });
}

function renderScheduleFields() {
    const type = getSelectedType();
    if (!type) { scheduleInputs.style.display = 'none'; scheduleInputs.innerHTML = ''; return; }
    scheduleInputs.style.display = 'block';
    let html = '';
    if (type === 'group') {
        html += `<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Day</label>
                <select class="form-select" name="day" required>
                    <option value="">Select Day</option>
                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" name="start_time" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" name="end_time" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Capacity</label>
                <input type="number" class="form-control" name="capacity" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price (₱)</label>
                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
            </div>
        </div>`;
    } else {
        html += `<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Day</label>
                <select class="form-select" name="day" required>
                    <option value="">Select Day</option>
                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" name="start_time" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" name="end_time" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Duration (mins)</label>
                <input type="number" class="form-control" name="duration_rate" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price (₱)</label>
                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
            </div>
        </div>`;
    }
    scheduleInputs.innerHTML = html;
}
document.getElementById('teachProgramForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Submit logic not implemented yet.');
        if (res.success) {
            alert('Program availed and schedule added!');
            window.location.reload();
        } else {
            alert(res.message || 'Failed to avail program.');
        }
    })
    .catch(() => alert('An error occurred.'));

</script>
</body>
</html>
