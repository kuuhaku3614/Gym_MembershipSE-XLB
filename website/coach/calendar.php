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
                        <h4 class="card-title">Schedule Calendar</h4>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?php echo json_encode($coach->getCalendarEvents($_SESSION['user_id'])); ?>,
            editable: false,
            selectable: true,
            eventClick: function(info) {
                // Show event details when clicked
                alert('Member: ' + info.event.title + '\nFrom: ' + info.event.startStr + '\nTo: ' + info.event.endStr);
            }
        });
        calendar.render();
    });
</script>
