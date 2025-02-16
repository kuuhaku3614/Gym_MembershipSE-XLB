<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    include('../coach.nav.php');
?>

<!-- Add Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Add required CSS for calendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<!-- Add required JS for calendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Program Members Schedule</h4>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Member:</strong> <span id="memberName"></span></p>
                <p><strong>Program:</strong> <span id="programName"></span></p>
                <p><strong>Schedule:</strong></p>
                <p>From: <span id="startTime"></span></p>
                <p>To: <span id="endTime"></span></p>
                <p><strong>Duration:</strong> <span id="duration"></span></p>
                <p><strong>Status:</strong> <span id="status"></span></p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek', // Show week view by default for better schedule visibility
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?php echo json_encode($coach->getCalendarEvents($_SESSION['user_id'])); ?>,
            editable: false,
            selectable: true,
            eventClick: function(info) {
                // Show detailed event information in modal
                document.getElementById('memberName').textContent = info.event.title.split(' - ')[0];
                document.getElementById('programName').textContent = info.event.title.split(' - ')[1];
                document.getElementById('startTime').textContent = new Date(info.event.start).toLocaleString();
                document.getElementById('endTime').textContent = new Date(info.event.end).toLocaleString();
                document.getElementById('duration').textContent = info.event.extendedProps.duration + ' ' + info.event.extendedProps.durationType;
                document.getElementById('status').textContent = info.event.extendedProps.status;
                
                // Show the modal
                var modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            }
        });
        calendar.render();
    });
</script>
