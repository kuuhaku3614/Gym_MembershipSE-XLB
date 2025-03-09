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

<style>
    .modal-content{
        padding: 0;
        max-width: 100%;
    }

    .fc-view *{
        font-family: "Gill Sans", sans-serif !important;
    }

    /* Override blue links in month view */
    .fc-daygrid-day-number,
    .fc-daygrid-day-top a,
    .fc a{
        color: #333 !important;
        text-decoration: none !important;
    }

    .fc a:hover{
        color: #c92f2f !important;
    }

    /* Style for other month days */
    .fc-day-other .fc-daygrid-day-number {
        color: #999 !important;
    }

    .fc {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    /* Header styling */
    .fc-toolbar-title {
        font-size: 1.5em !important;
        font-weight: 600 !important;
        color: #333;
    }

    /* Button styling */
    .fc-button-primary {
        background-color: #c92f2f !important;
        border-color: #c92f2f !important;
        font-weight: 500 !important;
    }

    .fc-button-primary:hover {
        background-color: #a62828 !important;
        border-color: #a62828 !important;
    }

    /* Event styling */
    .fc-event {
        border-radius: 4px !important;
        padding: 2px 5px !important;
        font-size: 0.85em !important;
        font-weight: 500 !important;
    }

    /* Time grid styling */
    .fc-timegrid-slot {
        height: 45px !important;
    }

    .fc-timegrid-slot-label {
        font-size: 0.85em !important;
        color: #666;
    }

    /* Day header styling */
    .fc-col-header-cell {
        background-color: #f8f9fa !important;
        padding: 10px 0 !important;
    }

    .fc-col-header-cell-cushion {
        color: #333 !important;
        font-weight: 600 !important;
    }

    /* Today highlight */
    .fc-day-today {
        background-color: rgba(201, 47, 47, 0.05) !important;
    }

    /* Modal styling */
    .modal-content {
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    .modal-header {
        background-color: #c92f2f;
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .modal-title {
        font-weight: 600;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-body p {
        margin-bottom: 12px;
        color: #333;
    }

    .modal-body strong {
        color: #c92f2f;
    }

    .btn-close {
        color: white;
    }
</style>

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
                <p><strong>Status:</strong> <span id="status" class="badge"></span></p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00', // Start time
            slotMaxTime: '22:00:00', // End time
            allDaySlot: false,
            slotDuration: '00:30:00',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false,
                hour12: true
            },
            events: function(info, successCallback) {
                let events = <?php echo json_encode($coach->getCalendarEvents($_SESSION['user_id'])); ?>;
                // Add color coding based on status
                events = events.map(event => {
                    let color;
                    // Check if event has extendedProps and status
                    const status = event.extendedProps?.status?.toLowerCase() || 'default';
                    
                    switch(status) {
                        case 'scheduled':
                            color = '#4CAF50'; // Green
                            break;
                        case 'completed':
                            color = '#2196F3'; // Blue
                            break;
                        case 'cancelled':
                            color = '#F44336'; // Red
                            break;
                        case 'pending':
                            color = '#FF9800'; // Orange
                            break;
                        default:
                            color = '#9E9E9E'; // Grey
                    }
                    return { 
                        ...event, 
                        backgroundColor: color, 
                        borderColor: color,
                        extendedProps: {
                            ...event.extendedProps,
                            status: event.extendedProps?.status || 'Not Set'
                        }
                    };
                });
                successCallback(events);
            },
            editable: false,
            selectable: true,
            nowIndicator: true,
            eventClick: function(info) {
                // Show detailed event information in modal
                const eventTitle = info.event.title.split(' - ');
                document.getElementById('memberName').textContent = eventTitle[0] || 'N/A';
                document.getElementById('programName').textContent = eventTitle[1] || 'N/A';
                document.getElementById('startTime').textContent = info.event.start ? new Date(info.event.start).toLocaleString() : 'N/A';
                document.getElementById('endTime').textContent = info.event.end ? new Date(info.event.end).toLocaleString() : 'N/A';
                document.getElementById('duration').textContent = 
                    (info.event.extendedProps?.duration ? info.event.extendedProps.duration + ' ' + (info.event.extendedProps.durationType || '') : 'N/A');
                
                // Style the status badge
                const statusElement = document.getElementById('status');
                const status = info.event.extendedProps?.status || 'Not Set';
                statusElement.textContent = status;
                statusElement.className = 'badge';
                
                // Add color coding for status badge
                switch(status.toLowerCase()) {
                    case 'scheduled':
                        statusElement.classList.add('bg-success');
                        break;
                    case 'completed':
                        statusElement.classList.add('bg-primary');
                        break;
                    case 'cancelled':
                        statusElement.classList.add('bg-danger');
                        break;
                    case 'pending':
                        statusElement.classList.add('bg-warning');
                        break;
                    default:
                        statusElement.classList.add('bg-secondary');
                }
                
                // Show the modal
                var modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            }
        });
        calendar.render();
    });
</script>
