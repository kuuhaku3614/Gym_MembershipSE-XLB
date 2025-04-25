<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/sanitize.php';
require_once __DIR__ . '/services.class.php';
require_once __DIR__ . '/cart.class.php';


if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize Services class
$Services = new Services_Class();

// Get walk-in details
try {
    $db = new Database();
    $conn = $db->connect();
    
    $sql = "SELECT w.*, dt.type_name as duration_type 
            FROM walk_in w 
            JOIN duration_types dt ON w.duration_type_id = dt.id 
            WHERE w.id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $walkin = $stmt->fetch();
    
    if (!$walkin) {
        throw new Exception("Walk-in service not found");
    }
    
    $price = $walkin['price'];
    $duration = $walkin['duration'];
    $duration_type = $walkin['duration_type'];
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('location: ../services.php');
    exit;
}

// Initialize variables
$selected_dates = []; // Array to store multiple selected dates

// Error variables
$datesErr = '';

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login first');
        }

        // Get selected dates as JSON array
        $dates = isset($_POST['selected_dates']) ? json_decode($_POST['selected_dates'], true) : [];
        
        if (empty($dates)) {
            throw new Exception('Please select at least one date');
        }

        // Validate dates
        $today = date('Y-m-d');
        foreach ($dates as $date) {
            if ($date < $today) {
                throw new Exception('Selected dates cannot be in the past');
            }
        }

        // Create cart instance with correct class name
        $Cart = new Cart_Class();
        $success = true;
        
        // Add each date as a separate walk-in service to cart
        foreach ($dates as $date) {
            if (!$Cart->addWalkinToCart(1, $date)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => count($dates) > 1 ? 'Walk-in services added to cart successfully' : 'Walk-in service added to cart successfully',
                'redirect' => '../services.php'
            ]);
        } else {
            throw new Exception('Failed to add walk-in service(s) to cart');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Centralized function for querying the database
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}
$color = executeQuery("SELECT * FROM website_content WHERE section = 'color'")[0] ?? [];
function decimalToHex($decimal) {
    $hex = dechex(abs(floor($decimal * 16777215)));
    // Ensure hex values are properly formatted with leading zeros
    return '#' . str_pad($hex, 6, '0', STR_PAD_LEFT);
}

$primaryHex = isset($color['latitude']) ? decimalToHex($color['latitude']) : '#000000';
$secondaryHex = isset($color['longitude']) ? decimalToHex($color['longitude']) : '#000000';

// Create an RGB version of the primary color for transparency
$primary_rgb = sscanf($primaryHex, "#%02x%02x%02x");
$primary_rgb_str = implode(', ', $primary_rgb);
?>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
    :root {
        --primary-color: <?= $primaryHex ?>;
        --primary-color-rgb: <?= $primary_rgb_str ?>;
        --secondary-color: <?= $secondaryHex ?>;
    }
    .bg-custom-red {
        background-color: var(--primary-color) !important;
    }
    .card-header, .btn-custom-red {
        background-color: #ff0000;
        color: white;
    }
    .card-header {
        background-color: var(--primary-color);
        padding: 1rem;
    }
    p, label {
        font-weight: 600;
    }
    #start_date {
        transition: all 0.3s ease;
    }
    #start_date:required:invalid:not(:focus) {
        animation: pulse-border 1.5s infinite;
    }
    @keyframes pulse-border {
        0% {
            border-color: #ced4da;
        }
        50% {
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        100% {
            border-color: #ced4da;
        }
    }

    /* Styles for date chips */
    .date-chip {
        display: inline-block;
        background-color: var(--primary-color);
        color: white;
        padding: 5px 10px;
        margin: 5px;
        border-radius: 20px;
        font-size: 14px;
    }

    .date-chip .remove-date {
        margin-left: 5px;
        cursor: pointer;
    }

    #dates-container {
        min-height: 50px;
        padding: 10px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        margin-top: 10px;
    }

    /* Calendar styles */
    .calendar-container {
        margin-top: 15px;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        text-align: center;
    }

    .calendar-weekday {
        font-weight: bold;
        padding: 5px;
    }

    .calendar-day {
        padding: 10px 5px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .calendar-day.disabled {
        background-color: #f5f5f5;
        color: #aaa;
        cursor: not-allowed;
    }

    .calendar-day.selected {
        background-color: var(--primary-color);
        color: white;
    }

    .calendar-day:not(.disabled):not(.empty):hover {
        background-color: rgba(var(--primary-color-rgb), 0.2);
    }

    .calendar-day.empty {
        visibility: hidden;
    }

    /* Hide the date input as we'll use the calendar instead */
    #selected_date {
        display: none;
    }

    /* Disabled and status-specific day styles */
    .calendar-day.past {
        background-color: #f5f5f5;
        color: #aaa;
        cursor: not-allowed;
    }

    .calendar-day.pending-membership {
        background-color: #fff3cd; /* Light yellow */
        color: #856404;
        cursor: not-allowed;
    }

    .calendar-day.active-membership {
        background-color: #d4edda; /* Light green */
        color: #155724;
        cursor: not-allowed;
    }

    .calendar-day.pending-walkin {
        background-color: #f8d7da; /* Light red */
        color: #721c24;
        cursor: not-allowed;
    }

    /* Calendar legend styles */
    .calendar-legend {
        border-top: 1px solid #dee2e6;
        padding-top: 10px;
        margin-top: 10px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        margin-right: 10px;
        margin-bottom: 5px;
        font-size: 12px;
    }

    .legend-color {
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-right: 5px;
        border-radius: 3px;
    }

    .legend-color.selected {
        background-color: var(--primary-color);
    }

    .legend-color.pending-membership {
        background-color: #fff3cd;
    }

    .legend-color.active-membership {
        background-color: #d4edda;
    }

    .legend-color.pending-walkin {
        background-color: #f8d7da;
    }
    
    @media screen and (max-width: 480px) {
        .services-header {
            display: none !important;
        }

        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .avail-walkin-page {
            width: 100%;
            height: 100%;
        }

        .img-fluid.rounded {
            display: none !important;
        }

        .container-fluid {
            padding: 0;
            margin: 0;
            width: 100%;
            background-color: #f5f5f5;
        }

        .main-container {
            height: 100%;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .col-12 {
            padding: 10px;
            margin-top: 0px !important;
            margin-bottom: 0px !important;
        }

        .card-body {
            height: 100%;
        }

        .scrollable-section {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0;
        }

        .row {
            margin: 0;
            height: 100%;
        }

        .d-grid {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap;
        }

        .h5 {
            font-size: 1.5rem !important;
            margin-bottom: 5px !important;
        }
    }
</style>

<div class="avail-walkin-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-red text-white p-3 d-flex align-items-center services-header">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

        <div class="container-fluid">
    <div class="row flex-grow-1 overflow-auto">
        <div class="col-12 col-lg-8 mx-auto py-3 main-container">
            <div class="card main-content" style="width: 100%;">
                <div class="card-header py-3">
                    <h2 class="h4 fw-bold mb-0 text-center">Walk-in Service</h2>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <!-- Left column for image -->
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <?php
                                // Default image path
                                $defaultImage = '../../cms_img/default/walkIn.jpeg';

                                // Get the image path
                                $imagePath = $defaultImage; // Set default first
                                ?>
                                <img src="<?= $imagePath ?>" 
                                     class="img-fluid rounded" 
                                     alt="WalkIn">
                            </div>
                        </div>
                    
                    <div class="col-12 col-md-6">
                    <section class="scrollable-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Validity: <?= $duration ?> <?= strtolower($duration_type) ?> per visit</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="form-group">
                                        <label class="form-label">Select Dates:</label>
                                        <!-- Hidden date input -->
                                        <input type="date" 
                                            class="form-control form-control-lg" 
                                            id="selected_date" 
                                            min="<?= date('Y-m-d') ?>" 
                                            required>
                                        
                                        <!-- Calendar widget -->
                                        <div class="calendar-container">
                                            <div class="calendar-header">
                                                <button type="button" class="btn btn-sm" id="prev-month">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <h5 id="calendar-month-year" class="mb-0"></h5>
                                                <button type="button" class="btn btn-sm" id="next-month">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>
                                            <div class="calendar-grid" id="calendar-weekdays">
                                                <!-- Weekday headers will be inserted here -->
                                            </div>
                                            <div class="calendar-grid" id="calendar-days">
                                                <!-- Calendar days will be inserted here -->
                                            </div>
                                            
                                            <!-- Calendar legend -->
                                            <div class="calendar-legend">
                                                <div class="d-flex flex-wrap justify-content-between">
                                                    <div class="legend-item">
                                                        <span class="legend-color selected"></span>
                                                        <span>Selected Date</span>
                                                    </div>
                                                    <div class="legend-item">
                                                        <span class="legend-color pending-membership"></span>
                                                        <span>Pending Membership</span>
                                                    </div>
                                                    <div class="legend-item">
                                                        <span class="legend-color active-membership"></span>
                                                        <span>Active Membership</span>
                                                    </div>
                                                    <div class="legend-item">
                                                        <span class="legend-color pending-walkin"></span>
                                                        <span>Pending Walk-in</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if(!empty($datesErr)): ?>
                                            <div class="text-danger mt-1"><?= $datesErr ?></div>
                                        <?php endif; ?>
                                        <div id="dates-container" class="mt-2">
                                            <p id="no-dates-message">No dates selected</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <p class="mb-0">Price per visit: ₱<?= number_format($price, 2) ?></p>
                                    <p class="mb-0 mt-2">Total price: ₱<span id="total_price">0.00</span></p>
                                </div>
                            </div>
                        </div>
                    </section>
                    </div>

                    <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                        <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="flex-fill" onsubmit="return validateForm(event)">
                                <input type="hidden" name="walkin_id" value="1">
                                <input type="hidden" name="selected_dates" id="hidden_selected_dates">
                                <button type="submit" class="btn btn-lg w-100 add-cart" style="height: 48px;">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <a href="../../login/login.php" class="btn btn-custom-red btn-lg w-100">Login to Add</a>
                        <?php endif; ?>
                    </div>
                </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Array to store selected dates
let selectedDates = [];
let disabledDates = {}; // Store disabled dates fetched from server
const price = <?= $price ?>;
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const duration = <?= $duration ?>;
const durationType = '<?= strtolower($duration_type) ?>';

// Function to fetch disabled dates from server
function fetchDisabledDates() {
    fetch('date_functions/get_disabled_dates.php')
        .then(response => response.json())
        .then(data => {
            disabledDates = data;
            renderCalendar(); // Re-render calendar with disabled dates
        })
        .catch(error => {
            console.error('Error fetching disabled dates:', error);
        });
}

// Format date for display
function formatDisplayDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Calculate end date based on start date and duration
function calculateEndDate(startDate) {
    const start = new Date(startDate);
    const end = new Date(start);
    
    if (durationType === 'day' || durationType === 'days') {
        // Subtract 1 since the selected date is already counted as 1 day
        end.setDate(end.getDate() + parseInt(duration) - 1);
    } else if (durationType === 'month' || durationType === 'months') {
        end.setMonth(end.getMonth() + parseInt(duration));
        end.setDate(end.getDate() - 1); // Last day of the period
    } else if (durationType === 'year' || durationType === 'years') {
        end.setFullYear(end.getFullYear() + parseInt(duration));
        end.setDate(end.getDate() - 1); // Last day of the period
    }
    
    return end;
}

// Format date to YYYY-MM-DD
function formatDateYMD(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Check if a date is disabled and why
function getDateStatus(dateToCheck) {
    const dateStr = typeof dateToCheck === 'string' ? dateToCheck : formatDateYMD(dateToCheck);
    
    // Check if date is in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (new Date(dateStr) < today) {
        return 'past';
    }
    
    // Check if date is in disabledDates array from server
    if (disabledDates[dateStr]) {
        return disabledDates[dateStr]; // Will return 'pending-membership', 'active-membership', or 'pending-walkin'
    }
    
    return null; // Date is not disabled
}

// Check if a date is within any of the disabled ranges
function isDateDisabled(dateToCheck) {
    const status = getDateStatus(dateToCheck);
    return status !== null;
}

// Add date to the selection
function addDate(dateStr) {
    if (!dateStr) {
        return;
    }
    
    // If already selected, toggle off (remove it)
    if (selectedDates.includes(dateStr)) {
        removeDate(dateStr);
        return;
    }
    
    // Check if date is disabled
    const status = getDateStatus(dateStr);
    if (status) {
        // Show specific error message based on status
        let message;
        switch (status) {
            case 'past':
                message = 'You cannot select a date in the past.';
                break;
            case 'pending-membership':
                message = 'This date conflicts with a pending membership.';
                break;
            case 'active-membership':
                message = 'This date conflicts with an active membership.';
                break;
            case 'pending-walkin':
                message = 'This date already has a pending walk-in reservation.';
                break;
            default:
                message = 'This date is unavailable.';
        }
        alert(message);
        return;
    }
    
    // Add date to array
    selectedDates.push(dateStr);
    
    // Sort dates chronologically
    selectedDates.sort();
    
    // Update the hidden input with JSON string of selected dates
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    
    // Update UI
    updateSelectedDatesUI();
    
    // Update calendar to reflect the selection
    renderCalendar();
}

// Remove date from selection
function removeDate(dateToRemove) {
    // Remove the date from selected dates
    selectedDates = selectedDates.filter(date => date !== dateToRemove);
    
    // Update the hidden input with JSON string of selected dates
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    
    // Update UI
    updateSelectedDatesUI();
    
    // Update calendar to reflect the removal
    renderCalendar();
}

// Update the UI to show selected dates
function updateSelectedDatesUI() {
    const container = document.getElementById('dates-container');
    
    // Clear container
    container.innerHTML = '';
    
    if (selectedDates.length === 0) {
        const noDateMessage = document.createElement('p');
        noDateMessage.id = 'no-dates-message';
        noDateMessage.textContent = 'No dates selected';
        container.appendChild(noDateMessage);
        
        document.getElementById('total_price').textContent = '0.00';
        return;
    }
    
    // Add date chips for each selected date
    selectedDates.forEach(date => {
        const dateChip = document.createElement('div');
        dateChip.className = 'date-chip';
        dateChip.innerHTML = `
            ${formatDisplayDate(date)}
            <span class="remove-date" onclick="removeDate('${date}')">
                <i class="bi bi-x-circle"></i>
            </span>
        `;
        container.appendChild(dateChip);
    });
    
    // Update total price
    const totalPrice = (price * selectedDates.length).toFixed(2);
    document.getElementById('total_price').textContent = number_format(totalPrice, 2);
}

// Format number for display (similar to PHP's number_format)
function number_format(number, decimals) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// Validate form before submission
function validateForm(event) {
    event.preventDefault();
    
    if (selectedDates.length === 0) {
        alert('Please select at least one date for your walk-in service.');
        return false;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    formData.set('selected_dates', document.getElementById('hidden_selected_dates').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
    
    return false;
}

// Calendar functions
function renderCalendarWeekdays() {
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const weekdaysContainer = document.getElementById('calendar-weekdays');
    weekdaysContainer.innerHTML = '';
    
    weekdays.forEach(day => {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-weekday';
        dayElement.textContent = day;
        weekdaysContainer.appendChild(dayElement);
    });
}

function formatDateString(year, month, day) {
    // Ensure month and day are two digits
    const formattedMonth = (month + 1).toString().padStart(2, '0');
    const formattedDay = day.toString().padStart(2, '0');
    return `${year}-${formattedMonth}-${formattedDay}`;
}

function renderCalendar() {
    // Update month-year header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    // Get first day of the month and total days in month
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // Clear calendar days container
    const daysContainer = document.getElementById('calendar-days');
    daysContainer.innerHTML = '';
    
    // Add empty slots for days before the first day of month
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        daysContainer.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.textContent = day;
        
        // Format date string for comparison
        const dateString = formatDateString(currentYear, currentMonth, day);
        
        // Add appropriate class based on date status
        const status = getDateStatus(dateString);
        if (status) {
            dayElement.className = `calendar-day ${status}`;
            // Always disable dates with a status unless they are selected dates
            if (!selectedDates.includes(dateString)) {
                dayElement.classList.add('disabled');
            }
        } else {
            dayElement.className = 'calendar-day';
        }
        
        // Highlight selected dates
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
        }
        
        // Add click event for all dates (we'll check validity in the click handler)
        dayElement.addEventListener('click', function() {
            addDate(dateString);
        });
        
        daysContainer.appendChild(dayElement);
    }
}

function setupCalendarNavigation() {
    // Previous month
    document.getElementById('prev-month').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });
    
    // Next month
    document.getElementById('next-month').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });
}

// Initialize UI on page load
window.onload = function() {
    // Initialize the UI components
    renderCalendarWeekdays();
    
    // Set initial hidden dates value
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    
    // Fetch disabled dates
    fetchDisabledDates();
    
    // Setup calendar navigation
    setupCalendarNavigation();
    
    // Initial render of calendar (will be updated once disabled dates are fetched)
    renderCalendar();
    
    // Initial UI update
    updateSelectedDatesUI();
};
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">