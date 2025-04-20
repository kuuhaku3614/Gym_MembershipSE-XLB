<?php
session_start();
require_once '../../functions/sanitize.php';
require_once '../../login/functions.php';
require_once 'services.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$program_id = $program_name = $program_description = '';
$coaches = [];

// Error variables
$program_idErr = $coach_program_typeErr = $scheduleErr = '';

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $program_id = clean_input($_GET['id']);
        $programs = $Services->getPrograms($program_id);
        
        if (!$programs || empty($programs)) {
            $_SESSION['error'] = 'Program not found';
            header('location: ../services.php');
            exit;
        }
        
        // Combine coaches from all program types
        $program_name = $programs[0]['program_name'];
        $program_description = $programs[0]['program_description'];
        $coaches = [];
        foreach ($programs as $program) {
            $coaches = array_merge($coaches, $program['coaches']);
        }
    } else {
        header('location: ../services.php');
        exit;
    }
}

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
    <title>Avail Program - Gym Management System</title>
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
        .avail-program-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-container {
            max-width: 1200px;
        }
        .coach-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .coach-card:hover {
            transform: translateY(-5px);
        }
        .schedule-list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="avail-program-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center services-header">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

    <div class="row flex-grow-1 overflow-auto">
        <div class="col-12 col-lg-10 mx-auto py-3 main-container">
            <div class="card main-content">
                <div class="card-header py-3">
                    <h2 class="h4 fw-bold mb-0 text-center"><?php echo htmlspecialchars($program_name); ?></h2>
                </div>
                <div class="card-body">

                    <div class="container main-container py-2">
                        <div class="row mb-4">
                            <div class="col">
                                <p class="text-custom-red"><?php echo htmlspecialchars($program_description); ?></p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <?php if (!empty($coaches)): ?>
                                <?php foreach ($coaches as $coach): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card coach-card h-100">
                                            <div class="card-header bg-custom-red text-white">
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($coach['coach_name']); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?php echo ucfirst(htmlspecialchars($coach['program_type'])); ?> Training
                                                </h6>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars($coach['program_type_description']); ?>
                                                </p>
                                                <button class="btn btn-custom-red w-100" 
                                                    onclick="loadSchedules('<?php echo $coach['coach_program_type_id']; ?>', '<?php echo htmlspecialchars($coach['program_type']); ?>')"
                                                    data-coach-program-type-id="<?php echo $coach['coach_program_type_id']; ?>"
                                                    data-program-id="<?php echo $program_id; ?>"
                                                    data-program-type="<?php echo htmlspecialchars($coach['program_type']); ?>">
                                                    View Available Schedules
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col">
                                    <div class="alert alert-info">
                                        No coaches with available schedules for this program at the moment.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Schedule Section -->
                        <div id="scheduleSection" class="mt-4" style="display: none;">
                            <h3 class="text-custom-red mb-3">Available Schedules</h3>
                            <div id="scheduleList" class="schedule-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add status messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger mt-3">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success mt-3">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadSchedules(coachProgramTypeId, type) {
    // Show loading state
    const scheduleSection = document.getElementById('scheduleSection');
    const scheduleList = document.getElementById('scheduleList');
    scheduleSection.style.display = 'block';
    scheduleList.innerHTML = '<div class="text-center"><div class="spinner-border text-custom-red" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    // Fetch schedules
    fetch(`get_schedules.php?coach_program_type_id=${coachProgramTypeId}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                scheduleList.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            if (data.message) {
                scheduleList.innerHTML = `<div class="alert alert-info">${data.message}</div>`;
                return;
            }

            // For group training, use the regular table format
            if (type === 'group') {
                let tableHtml = `
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-custom-red text-white">
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Capacity</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>`;

                data.forEach(schedule => {
                    const time = `${schedule.start_time} - ${schedule.end_time}`;
                    tableHtml += `
                        <tr>
                            <td>${schedule.day}</td>
                            <td>${time}</td>
                            <td>${schedule.current_members}/${schedule.capacity}</td>
                            <td>₱${schedule.price}</td>
                            <td><button class="btn btn-sm btn-primary" onclick="addToCart(${schedule.id}, '${schedule.day}', '${schedule.start_time}', '${schedule.end_time}', ${schedule.price}, '${schedule.program_name}', '${schedule.coach_name}')" data-schedule-id="${schedule.id}">Add to Cart</button></td>
                        </tr>`;
                });

                tableHtml += `</tbody></table></div>`;
                scheduleList.innerHTML = tableHtml;
            } else {
                // For personal training, group by day with time slots
                let schedulesByDay = {};
                data.forEach(schedule => {
                    if (!schedulesByDay[schedule.day]) {
                        schedulesByDay[schedule.day] = [];
                    }
                    schedulesByDay[schedule.day].push(schedule);
                });

                let scheduleHtml = '<div class="schedule-container">';

                // Sort days in order
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                days.forEach(day => {
                    if (schedulesByDay[day]) {
                        // Sort time slots chronologically
                        schedulesByDay[day].sort((a, b) => {
                            return new Date('2000/01/01 ' + a.start_time) - new Date('2000/01/01 ' + b.start_time);
                        });

                        scheduleHtml += `
                            <div class="card mb-3">
                                <div class="card-header bg-custom-red text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">${day}</h5>
                                        <div>Duration: ${schedulesByDay[day][0].duration_rate} mins | Price: ₱${schedulesByDay[day][0].price}</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">`;

                        schedulesByDay[day].forEach((schedule, index) => {
                            scheduleHtml += `
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100" onclick="addToCart(${schedule.id}, '${schedule.day}', '${schedule.start_time}', '${schedule.end_time}', ${schedule.price}, '${schedule.program_name}', '${schedule.coach_name}')" data-schedule-id="${schedule.id}" data-slot-order="${index + 1}">
                                        ${schedule.start_time} - ${schedule.end_time}
                                    </button>
                                </div>`;
                        });

                        scheduleHtml += `
                                    </div>
                                </div>
                            </div>`;
                    }
                });

                scheduleHtml += '</div>';
                scheduleList.innerHTML = scheduleHtml;

            }
        })
        .catch(error => {
            console.error('Error:', error);
            scheduleList.innerHTML = '<div class="alert alert-danger">Failed to load schedules. Please try again later.</div>';
        });
}

function addToCart(scheduleId, day, startTime, endTime, price, programName, coachName) {
    const scheduleData = {
        schedule_id: scheduleId,
        day: day,
        start_time: startTime,
        end_time: endTime,
        price: price,
        program_name: programName,
        coach_name: coachName,
        is_personal: programName.includes('Personal')
    };

    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'add_program_schedule',
            ...scheduleData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Schedule added to cart successfully!');
            window.location.href = '../services.php';
        } else {
            alert(data.data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again later.');
    });
}
</script>
</body>
</html>