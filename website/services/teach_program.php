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
        /* Responsive FullCalendar wrapper */
        .calendar-picture-wrapper {
            width: 100%;
            height: auto;
            font-size: 1.1vw; /* Scale all calendar content based on viewport width */
            max-width: 100%;
            min-width: 0;
            overflow: visible; /* No scrollbars */
        }
        #coachScheduleCalendar, .fc {
            width: 100% !important;
            height: auto !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
        .fc, .fc-scrollgrid, .fc-view, .fc-timegrid, .fc-timegrid-body, .fc-timegrid-slots, .fc-timegrid-cols {
            transition: none !important;
        }
        .fc-col-header-cell, .fc-col-header-cell a {
        color: #111 !important;
        text-decoration: none !important;
        }
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

        <!-- Button to trigger modal -->
<button id="showFullScheduleModalBtn" type="button" class="btn btn-outline-secondary mb-2" data-bs-toggle="modal" data-bs-target="#fullScheduleModal" title="Show My Full Schedule">
    <i class="bi bi-calendar-week fs-5"></i>
</button>
<!-- Modal for Full Schedule Calendar -->
<div class="modal fade" id="fullScheduleModal" tabindex="-1" aria-labelledby="fullScheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fullScheduleModalLabel">My Full Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="calendar-picture-wrapper">
          <div id="coachScheduleCalendar" class="mt-0"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var fullScheduleModal = document.getElementById('fullScheduleModal');
    if (fullScheduleModal) {
        fullScheduleModal.addEventListener('shown.bs.modal', function () {
            if (window.coachScheduleCalendarObj) {
                window.coachScheduleCalendarObj.updateSize();
            }
        });
    }
});
</script>

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
<!-- FullCalendar CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
// All DOM references and event listeners are declared only once below
let groupDescription = '';
let personalDescription = '';

window.addEventListener('DOMContentLoaded', function() {
    // Fetch all schedules for this coach for overlap checking
    fetch('teach_program_backend.php?all_coach_schedules=1')
        .then(res => res.json())
        .then(data => {
            window.allCoachSchedulesGroup = Array.isArray(data.schedules && data.schedules.group) ? data.schedules.group : [];
            window.allCoachSchedulesPersonal = Array.isArray(data.schedules && data.schedules.personal) ? data.schedules.personal : [];
        })
        .catch(() => {
            window.allCoachSchedulesGroup = [];
            window.allCoachSchedulesPersonal = [];
        });
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
            window.location.href = '../services.php';
            return;
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

    // Validation: capacity, duration_rate, and price must be greater than 0
    if (type === 'group') {
        if (Number(schedule.capacity) <= 1) {
            alert('Capacity must be greater than 1.');
            return;
        }
        if (Number(schedule.price) <= 0) {
            alert('Price must be greater than 0.');
            return;
        }
    } else if (type === 'personal') {
        if (Number(schedule.duration_rate) <= 0) {
            alert('Duration must be greater than 0.');
            return;
        }
        if (Number(schedule.price) <= 0) {
            alert('Price must be greater than 0.');
            return;
        }
    }

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

    // Overlap check: same day and time ranges intersect (cart + ALL schedules for this coach across ALL programs)
    const allExistingSchedules = [
        ...scheduleCart,
        ...(window.allCoachSchedulesGroup || []),
        ...(window.allCoachSchedulesPersonal || [])
    ];
    const isOverlap = allExistingSchedules.some(existing => {
        if (existing.day !== schedule.day) return false;
        // Convert to minutes for easier comparison
        const [startHour, startMin] = schedule.start_time.split(':').map(Number);
        const [endHour, endMin] = schedule.end_time.split(':').map(Number);
        const [exStartHour, exStartMin] = existing.start_time.split(':').map(Number);
        const [exEndHour, exEndMin] = existing.end_time.split(':').map(Number);
        const startMins = startHour * 60 + startMin;
        const endMins = endHour * 60 + endMin;
        const exStartMins = exStartHour * 60 + exStartMin;
        const exEndMins = exEndHour * 60 + exEndMin;
        // Overlap if ranges intersect
        return (startMins < exEndMins && endMins > exStartMins);
    });
    if (isOverlap) {
        alert('This schedule overlaps with an existing or already saved schedule. Please choose a different time.');
        return;
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
// Always initialize these globals so they're never undefined
window.allCoachSchedulesGroup = [];
window.allCoachSchedulesPersonal = [];

window.addEventListener('DOMContentLoaded', function() {
    // Fetch all schedules for this coach for overlap checking
    fetch('teach_program_backend.php?all_coach_schedules=1')
        .then(res => res.json())
        .then(data => {
            window.allCoachSchedulesGroup = Array.isArray(data.schedules && data.schedules.group) ? data.schedules.group : [];
            window.allCoachSchedulesPersonal = Array.isArray(data.schedules && data.schedules.personal) ? data.schedules.personal : [];
        })
        .catch(() => {
            window.allCoachSchedulesGroup = [];
            window.allCoachSchedulesPersonal = [];
        })
        .finally(() => {
            fetchAndRenderExistingSchedules();
        });
    renderScheduleFields();
});
function fetchAndRenderExistingSchedules() {
    // Hide the old section UI
    const section = document.getElementById('existingSchedulesSection');
    if (section) section.style.display = 'none';
    // FullCalendar rendering
    const calendarEl = document.getElementById('coachScheduleCalendar');
    const groupSchedules = window.allCoachSchedulesGroup || [];
    const personalSchedules = window.allCoachSchedulesPersonal || [];
    const allSchedules = groupSchedules.concat(personalSchedules);

    // Map to FullCalendar event objects
    const events = allSchedules.map(s => {
        // Map PHP day name to JS day index (0=Sunday...6=Saturday)
        const dayMap = {
            'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3,
            'Thursday': 4, 'Friday': 5, 'Saturday': 6
        };
        // Find the next occurrence of the schedule's day in the current week
        const now = new Date();
        const weekStart = new Date(now.setDate(now.getDate() - now.getDay())); // Sunday
        const eventDate = new Date(weekStart);
        eventDate.setDate(weekStart.getDate() + dayMap[s.day]);
        function setTime(date, time) {
            const [h, m] = time.split(":");
            date.setHours(+h, +m, 0, 0);
        }
        const start = new Date(eventDate);
        setTime(start, s.start_time);
        const end = new Date(eventDate);
        setTime(end, s.end_time);
        return {
            title: (s.program_name ? s.program_name + ' - ' : '') + (s.type === 'group' ? 'Group' : 'Personal'),
            start,
            end,
            backgroundColor: (function() {
                function blendWithWhite(hex, percent) {
                    hex = hex.replace('#', '');
                    if (hex.length === 3) {
                        hex = hex.split('').map(x => x + x).join('');
                    }
                    const num = parseInt(hex, 16);
                    let r = (num >> 16) & 255;
                    let g = (num >> 8) & 255;
                    let b = num & 255;
                    r = Math.round(r + (255 - r) * percent);
                    g = Math.round(g + (255 - g) * percent);
                    b = Math.round(b + (255 - b) * percent);
                    return `rgb(${r},${g},${b})`;
                }
                const primary = getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim() || '#0d6efd';
                const secondary = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#198754';
                return s.type === 'group' ? blendWithWhite(primary, 0.2) : blendWithWhite(secondary, 0.2);
            })(), // pastel system palette
            borderColor: '#fff',
            textColor: '#fff',
            extendedProps: s
        };
    });

    // Destroy previous calendar if exists
    if (window.coachScheduleCalendarObj) {
        window.coachScheduleCalendarObj.destroy();
    }

    // Render FullCalendar
    window.coachScheduleCalendarObj = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        allDaySlot: false,
        slotMinTime: "06:00:00",
        slotMaxTime: "22:00:00",
        headerToolbar: false, // Remove all navigation and title
        titleFormat: '', // Remove date range title
        events,
        initialDate: '2025-04-21', // Static Monday (any Monday works)
        navLinks: false,
        editable: false,
        selectable: false,
        showNonCurrentDates: false, // Hide any extra dates if possible
        dayHeaderFormat: { weekday: 'long' }, // Show only full day name, no date
        eventContent: function(arg) {
            const event = arg.event.extendedProps;
            const programName = event.program_name || arg.event.title || '';
            let details = '';
            if (event.type === 'personal') {
                details = ` (Personal) <br> ${event.duration_rate} mins, ₱${Number(event.price).toLocaleString()}`;
            } else {
                details = ` (Group) <br> ${event.capacity} slots, ₱${Number(event.price).toLocaleString()}`;
            }
            return { html: `<div class='fc-simple-event'>${programName}${details}</div>` };
        }
    });
    window.coachScheduleCalendarObj.render();
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
});

</script>
</body>
</html>
