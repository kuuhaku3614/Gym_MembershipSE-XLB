<?php
session_start();
require_once '../../functions/sanitize.php';
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

$Services = new Services_Class();
$Cart = new Cart_Class();

if (!isset($_GET['id'])) {
    header('location: ../services.php');
    exit;
}

$membership_id = clean_input($_GET['id']);
$membership = $Services->fetchGymrate($membership_id);

if (!$membership) {
    $_SESSION['error'] = "Invalid membership plan.";
    header('location: ../services.php');
    exit;
}

// Construct validity from duration and duration_type
$membership['validity'] = $membership['duration'] . ' ' . strtolower($membership['duration_type']);

// Initialize error variables
$start_dateErr = '';
$start_date = ''; // Initialize with today's date

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $membership_id = clean_input($_POST['membership_id']);
        $selected_dates = isset($_POST['selected_dates']) ? json_decode($_POST['selected_dates'], true) : [];
        
        if (empty($membership_id)) {
            throw new Exception("Please select a membership plan.");
        }
        
        if (empty($selected_dates)) {
            throw new Exception("Please select at least one start date.");
        }
        
        $membership = $Services->fetchGymrate($membership_id);
        if (!$membership) {
            throw new Exception("Invalid membership plan selected.");
        }
        
        // Construct validity again for the selected membership
        $membership['validity'] = $membership['duration'] . ' ' . strtolower($membership['duration_type']);
        
        $success = true;
        
        // Add each selected date as a separate membership to cart
        foreach ($selected_dates as $start_date) {
            // Calculate end date based on validity period
            $duration = $membership['duration'];
            $duration_type = strtolower($membership['duration_type']);
            
            // Convert duration type to a format strtotime understands
            switch ($duration_type) {
                case 'month':
                case 'months':
                    $interval = $duration . ' month';
                    break;
                case 'year':
                case 'years':
                    $interval = $duration . ' year';
                    break;
                case 'day':
                case 'days':
                    $interval = $duration . ' day';
                    break;
                default:
                    throw new Exception("Invalid duration type");
            }
            
            $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $interval));
            
            $item = array_merge($membership, [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'validity' => $membership['validity']
            ]);
            
            if (!$Cart->addMembership($item)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $_SESSION['success_message'] = count($selected_dates) > 1 ? 
                "Successfully added memberships to the cart!" : 
                "Successfully added membership to the cart!";
                
            // Return a JSON response instead of redirecting immediately
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => '../services.php'
            ]);
            exit;
        } else {
            throw new Exception("Failed to add membership to cart.");
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
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
?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
        :root {
    --primary-color: <?php echo $primaryHex; ?>;
    --secondary-color: <?php echo $secondaryHex; ?>;
    }
    body {
        height: 100vh;
    }
    .main-container {
        max-height: 100vh;
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
    .form-control{
        font-size: 1rem;
    }
    #start_date{
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
        }}
        
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
    #start_date {
        display: none;
    }
    
 @media screen and (max-width: 480px) {
    /* 1. Hide the services-header */
    .services-header {
        display: none !important;
    }
    
    /* 2. Make the content fill the entire screen and scale properly */
    body, html {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    .avail-membership-page {
        width: 100%;
        height: 100%;
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
        margin-top: 0px!important;
        margin-bottom: 0px!important;
    }
    

    .card-body{
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
    .d-grid{
        display: flex!important;
        flex-direction: row!important;
        flex-wrap: nowrap;
    }
    .h5{
        font-size: 1.5rem!important;
        margin-bottom: 5px!important;
    }
}
    
</style>

<div class="avail-membership-page">
    <div class="container-fluid p-0">
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
                    <h2 class="h4 fw-bold mb-0 text-center"><?= $membership['plan_type'] ?> Membership</h2>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <!-- Left column for image -->
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <?php
                                // Default image path
                                $defaultImage = '../../cms_img/default/membership.jpeg';

                                // Get the image path
                                $imagePath = $defaultImage; // Set default first
                                if (!empty($membership['image']) && file_exists(__DIR__ . "/../../cms_img/gym_rates/" . $membership['image'])) {
                                    $imagePath = '../../cms_img/gym_rates/' . $membership['image'];
                                }
                                ?>
                                <img src="<?= $imagePath ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?= htmlspecialchars($membership['plan_name']) ?>">
                                <h3 class="h5 fw-bold mt-3"><?= $membership['plan_name'] ?></h3>
                            </div>
                            <?php if (!empty($membership['description'])): ?>
                                        <div class="col-12">
                                            <div class="border rounded p-3">
                                                <p class="mb-0">Description: 
                                                    <span style="display: block; max-height: 50px; overflow-y: auto; max-width: 100%; word-break: break-word;">
                                                        <?= nl2br(htmlspecialchars($membership['description'])) ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right column for forms -->
                        <div class="col-12 col-md-6">
                            <section class="scrollable-section">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Validity: <?= $membership['validity'] ?></p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Price: ₱<?= number_format($membership['price'], 2) ?></p>
                                            <p class="mb-0 mt-2">Total price: ₱<span id="total_price"><?= number_format($membership['price'], 2) ?></span></p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <div class="form-group">
                                                <label for="start_date" class="form-label">Select Multiple Start Dates:</label>
                                                <!-- Hidden date input -->
                                                <input type="date" 
                                                    class="form-control form-control-lg" 
                                                    id="start_date" 
                                                    name="start_date" 
                                                    min="<?= date('Y-m-d', strtotime('today')) ?>" 
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
                                                </div>
                                                
                                                <?php if(!empty($start_dateErr)): ?>
                                                    <div class="text-danger mt-1"><?= $start_dateErr ?></div>
                                                <?php endif; ?>
                                                <div id="dates-container" class="mt-2">
                                                    <p id="no-dates-message">No dates selected</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">End Dates:</p>
                                            <div id="end-dates-info" class="mt-2">
                                                <!-- End dates will be displayed here -->
                                                <p class="text-muted">Select start dates to see end dates</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </section>
                        </div>
                        <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                                <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form method="POST" class="flex-fill" onsubmit="return validateForm(event)">
                                        <input type="hidden" name="membership_id" value="<?= $membership_id ?>">
                                        <input type="hidden" name="selected_dates" id="hidden_selected_dates">
                                        <button type="submit" class="btn btn-lg w-100 add-cart" style="height: 48px!;">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-custom-red btn-lg"><a href="../../login/login.php">Login to Add</a></button>
                                <?php endif; ?>
                            </div>
                    </div>
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
const price = <?= $membership['price'] ?>;
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const duration = <?= $membership['duration'] ?>;
const durationType = '<?= strtolower($membership['duration_type']) ?>';

// Dictionary to track all disabled dates (for checking overlaps)
let disabledDateRanges = {};

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
        end.setDate(end.getDate() + parseInt(duration));
    } else if (durationType === 'month' || durationType === 'months') {
        end.setMonth(end.getMonth() + parseInt(duration));
    } else if (durationType === 'year' || durationType === 'years') {
        end.setFullYear(end.getFullYear() + parseInt(duration));
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

// Check if a date is within any of the disabled ranges
function isDateDisabled(dateToCheck) {
    const checkDate = new Date(dateToCheck);
    
    // Check if date is in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (checkDate < today) {
        return true;
    }
    
    // Check if date is in any of the disabled ranges
    for (const startDate in disabledDateRanges) {
        const endDate = disabledDateRanges[startDate];
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (checkDate >= start && checkDate <= end) {
            return true;
        }
    }
    
    return false;
}

// Generate disabled date ranges for all selected dates
function updateDisabledDateRanges() {
    disabledDateRanges = {}; // Clear existing ranges
    
    selectedDates.forEach(startDate => {
        const endDate = calculateEndDate(startDate);
        disabledDateRanges[startDate] = formatDateYMD(endDate);
        
        // Disable all dates in between start and end
        const currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            const currentDateStr = formatDateYMD(currentDate);
            if (!disabledDateRanges[currentDateStr]) {
                disabledDateRanges[currentDateStr] = currentDateStr;
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }
    });
}

// Add date to the selection
function addDate(dateStr) {
    if (!dateStr) {
        return;
    }
    
    // Check if date is disabled (except if it's already selected)
    if (isDateDisabled(dateStr) && !selectedDates.includes(dateStr)) {
        alert('This date is either in the past or conflicts with another membership period.');
        return;
    }
    
    // If already selected, toggle off (remove it)
    if (selectedDates.includes(dateStr)) {
        removeDate(dateStr);
        return;
    }
    
    // Add date to array
    selectedDates.push(dateStr);
    
    // Sort dates chronologically
    selectedDates.sort();
    
    // Update disabled date ranges
    updateDisabledDateRanges();
    
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
    
    // Update disabled date ranges with new selection set
    updateDisabledDateRanges();
    
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
    const endDatesInfo = document.getElementById('end-dates-info');
    
    // Clear containers
    container.innerHTML = '';
    endDatesInfo.innerHTML = '';
    
    if (selectedDates.length === 0) {
        const noDateMessage = document.createElement('p');
        noDateMessage.id = 'no-dates-message';
        noDateMessage.textContent = 'No dates selected';
        container.appendChild(noDateMessage);
        
        document.getElementById('total_price').textContent = number_format(price, 2);
        endDatesInfo.innerHTML = '<p class="text-muted">No membership periods selected</p>';
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
        
        // Add end date information
        const endDate = calculateEndDate(date);
        const endDateInfo = document.createElement('div');
        endDateInfo.className = 'mb-2';
        endDateInfo.innerHTML = `
            <strong>Membership Period:</strong> ${formatDisplayDate(date)} to ${formatDisplayDate(endDate)}
        `;
        endDatesInfo.appendChild(endDateInfo);
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
        alert('Please select at least one start date for your membership.');
        return false;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    
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
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        // Format date string for comparison
        const dateString = formatDateString(currentYear, currentMonth, day);
        
        // Check if this date is selected
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
        }
        
        // Check if date is disabled (past or in an existing membership period)
        if (isDateDisabled(dateString) && !selectedDates.includes(dateString)) {
            dayElement.classList.add('disabled');
        } else {
            // Add click event for valid dates
            dayElement.addEventListener('click', function() {
                if (!this.classList.contains('disabled') || this.classList.contains('selected')) {
                    addDate(dateString);
                }
            });
        }
        
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
    renderCalendar();
    setupCalendarNavigation();
    updateSelectedDatesUI();
    
    // Set initial hidden dates value
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
};
</script>