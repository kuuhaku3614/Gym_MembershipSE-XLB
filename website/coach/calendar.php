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

<!-- Add jQuery first -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Add Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Add required CSS for calendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<!-- Add required JS for calendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<link rel="stylesheet" href="calendar.css">

<div class="main-content">
    <div class="calendar-card">
        <div class="calendar-header">
            <h4 class="calendar-title">Program Schedule</h4>
        </div>
        
        <div class="calendar-legend">
            <div class="legend-item">
                <div class="legend-color legend-personal-available"></div>
                <span>Personal - Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-personal-booked"></div>
                <span>Personal - Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-group-available"></div>
                <span>Group (&lt;50% Full)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-group-half"></div>
                <span>Group (50-74% Full)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-group-almost"></div>
                <span>Group (75-99% Full)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-group-full"></div>
                <span>Group (Full)</span>
            </div>
        </div>
        
        <div id="calendar"></div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Program Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var tooltipEl = document.createElement('div');
        tooltipEl.className = 'event-tooltip';
        document.body.appendChild(tooltipEl);

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: false,
            height: 'auto',
            expandRows: true,
            slotEventOverlap: false,
            eventOverlap: false,
            eventDisplay: 'block',
            nowIndicator: true,
            events: <?php echo json_encode($coach->getCalendarEvents($_SESSION['user_id'])); ?>,
            eventContent: function(arg) {
                let event = arg.event;
                let props = event.extendedProps;
                let isPersonal = props.type === 'personal';
                
                // For month view
                if (arg.view.type === 'dayGridMonth') {
                    if (isPersonal) {
                        // Only show booked personal slots in month view
                        if (!props.isBooked) {
                            return '';
                        }
                        // Show only member name for booked slots
                        return { html: `<div class="fc-event-title">${event.title}</div>` };
                    } else {
                        // For group events, show program name and capacity
                        let capacityText = `(${props.currentMembers}/${props.capacity})`;
                        let title = props.memberNames ? `${props.memberNames}<br>${capacityText}` : `${props.programName} ${capacityText}`;
                        return { html: `<div class="fc-event-title">${title}</div>` };
                    }
                }
                
                // For week/day view
                let timeText = '';
                if (arg.timeText) {
                    timeText = `<div class="fc-event-time">${arg.timeText}</div>`;
                }
                
                let title;
                if (isPersonal) {
                    title = `${event.title} - ${props.programName} (Personal)`;
                } else {
                    let capacityText = `(${props.currentMembers}/${props.capacity})`;
                    let membersText = props.memberNames ? `<br>Members: ${props.memberNames}` : '';
                    title = `${props.programName} ${capacityText}${membersText}`;
                }
                
                return { html: `${timeText}<div class="fc-event-title">${title}</div>` };
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.type === 'personal') {
                    if (info.event.extendedProps.isBooked) {
                        info.el.style.backgroundColor = '#FF6B6B';
                        info.el.style.borderColor = '#FF5252';
                    } else {
                        info.el.style.backgroundColor = '#4CAF50';
                        info.el.style.borderColor = '#45A049';
                    }
                } else {
                    // Group program colors based on capacity
                    let capacity = info.event.extendedProps.capacity;
                    let currentMembers = info.event.extendedProps.currentMembers;
                    let percentage = (currentMembers / capacity) * 100;
                    
                    if (percentage >= 100) {
                        info.el.style.backgroundColor = '#E53935';
                        info.el.style.borderColor = '#D32F2F';
                    } else if (percentage >= 75) {
                        info.el.style.backgroundColor = '#FFA726';
                        info.el.style.borderColor = '#FB8C00';
                    } else if (percentage >= 50) {
                        info.el.style.backgroundColor = '#FFD54F';
                        info.el.style.borderColor = '#FFC107';
                        info.el.style.color = '#333333';
                    } else {
                        info.el.style.backgroundColor = '#66BB6A';
                        info.el.style.borderColor = '#4CAF50';
                    }
                }
            },
            eventClick: function(info) {
                let event = info.event;
                let props = event.extendedProps;
                let isPersonal = props.type === 'personal';
                
                let modalTitle = isPersonal ? 'Personal Program Session' : 'Group Program Session';
                let modalBody = '';
                
                // Format date and time
                const startDate = new Date(event.start);
                const endDate = new Date(event.end);
                const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
                
                modalBody = `
                    <div class="schedule-info">
                        <div class="info-group">
                            <h6 class="info-label">Program</h6>
                            <p class="info-value">${props.programName}</p>
                        </div>
                        <div class="info-group">
                            <h6 class="info-label">Schedule Type</h6>
                            <p class="info-value">${isPersonal ? 'Personal Training' : 'Group Class'}</p>
                        </div>
                        <div class="info-group">
                            <h6 class="info-label">Date & Time</h6>
                            <p class="info-value">
                                ${startDate.toLocaleDateString('en-US', dateOptions)}<br>
                                ${startDate.toLocaleTimeString('en-US', timeOptions)} - ${endDate.toLocaleTimeString('en-US', timeOptions)}
                            </p>
                        </div>
                        <div class="info-group">
                            <h6 class="info-label">Price</h6>
                            <p class="info-value">₱${props.price}</p>
                        </div>`;
                
                if (isPersonal) {
                    modalBody += `
                        <div class="info-group">
                            <h6 class="info-label">Member</h6>
                            <p class="info-value">${event.title}</p>
                        </div>`;
                } else {
                    let capacityText = `${props.currentMembers}/${props.capacity}`;
                    let percentage = (props.currentMembers / props.capacity) * 100;
                    let capacityClass = percentage >= 100 ? 'full' : 
                                    percentage >= 75 ? 'high' :
                                    percentage >= 50 ? 'medium' : 'low';
                    
                    modalBody += `
                        <div class="info-group">
                            <h6 class="info-label">Capacity</h6>
                            <div class="capacity-info">
                                <p class="info-value mb-2">${capacityText} members</p>
                                <div class="capacity-bar">
                                    <div class="progress-bar progress-bar-${capacityClass}" style="width: ${percentage}%"></div>
                                </div>
                            </div>
                        </div>`;
                    
                    if (props.memberNames) {
                        modalBody += `
                            <div class="info-group">
                                <h6 class="info-label">Booked Members</h6>
                                <ul class="member-list info-value">
                                    ${props.memberNames.split(', ').map(name => `<li>${name}</li>`).join('')}
                                </ul>
                            </div>`;
                    }
                }
                
                modalBody += '</div>';
                
                $('#eventModal .modal-title').html(modalTitle);
                $('#eventModal .modal-body').html(modalBody);
                $('#eventModal').modal('show');
            }
        });

        calendar.render();

        // Hide tooltip when clicking outside
        document.addEventListener('click', function() {
            tooltipEl.style.display = 'none';
        });
    });
</script>
