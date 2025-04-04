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
    $rgb = [];
    for ($i = 0; $i < 3; $i++) {
        $rgb[] = str_pad(dechex(min(255, max(0, intval($decimal)))), 2, '0', STR_PAD_LEFT);
        $decimal = ($decimal - intval($decimal)) * 256;
    }
    return '#' . implode('', $rgb);
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
            <h1 class="h3 mb-0">Avail Program</h1>
        </div>
    </div>

    <div class="row flex-grow-1 overflow-auto">
        <div class="col-12 col-lg-10 mx-auto py-3 main-container">
            <div class="card main-content">
                <div class="card-header py-3">
                    <h2 class="h4 fw-bold mb-0 text-center"><?php echo htmlspecialchars($program_name); ?></h2>
                </div>
                <div class="card-body">

                    <div class="container main-container py-5">
                        <div class="row mb-4">
                            <div class="col">
                                <h2 class="text-custom-red"><?php echo htmlspecialchars($program_name); ?></h2>
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

            // Create schedule table
            let tableHtml = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-custom-red text-white">
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                ${type === 'group' ? '<th>Capacity</th>' : '<th>Duration</th>'}
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;

            data.forEach(schedule => {
                const time = `${schedule.start_time} - ${schedule.end_time}`;
                const status = schedule.availability_status === 'available' 
                    ? '<span class="badge bg-success">Available</span>'
                    : schedule.availability_status === 'full'
                        ? '<span class="badge bg-danger">Full</span>'
                        : '<span class="badge bg-warning text-dark">Booked</span>';

                tableHtml += `
                    <tr>
                        <td>${schedule.day}</td>
                        <td>${time}</td>
                        <td>${type === 'group' ? `${schedule.current_members}/${schedule.capacity}` : schedule.duration + ' mins'}</td>
                        <td>${status}</td>
                    </tr>`;
            });

            tableHtml += `
                        </tbody>
                    </table>
                </div>`;

            scheduleList.innerHTML = tableHtml;
        })
        .catch(error => {
            console.error('Error:', error);
            scheduleList.innerHTML = '<div class="alert alert-danger">Failed to load schedules. Please try again later.</div>';
        });
}
</script>
</body>
</html>