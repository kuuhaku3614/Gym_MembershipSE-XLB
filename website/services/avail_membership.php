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

    .calendar-day.selected-range {
        background-color: rgba(var(--primary-color-rgb), 0.3);
        color: var(--primary-color);
    }

    /* Calendar legend styles */
    .calendar-legend {
        border-top: 1px solid #dee2e6;
        padding-top: 10px;
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
    .img-fluid.rounded{
        display: none!important;
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
                                            <div>
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
                                            <div class="form-group">
                                                <label for="start_date" class="form-label">Select Dates:</label>
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
                                    
                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Price: ₱<?= number_format($membership['price'], 2) ?></p>
                                            <p class="mb-0 mt-2">Total price: ₱<span id="total_price"><?= number_format($membership['price'], 2) ?></span></p>
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

<!-- conflict modal -->
<div class="modal fade" id="conflictModal" tabindex="-1" aria-labelledby="conflictModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="conflictModalLabel">Membership Conflict Detected</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="conflictMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="appendMembershipBtn">Append After Pending</button>
        <button type="button" class="btn btn-danger" id="replaceMembershipBtn">Replace Pending</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Array to store selected dates
let disabledDates = {}; // Will now store objects: { status: '...', endDate: '...', transactionId: ... }
const price = <?= $membership['price'] ?>;
let selectedDates = [];
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const duration = <?= $membership['duration'] ?>;
const durationType = '<?= strtolower($membership['duration_type']) ?>';

// Dictionary to track all disabled date ranges (no change needed here)
let disabledDateRanges = {};

// Store data for the modal
let modalConflictData = null;


// Function to fetch disabled dates from server
function fetchDisabledDates() {
    // Add cache-busting query parameter to prevent stale data
    const url = `date_functions/get_disabled_dates.php?t=${new Date().getTime()}`;
    return fetch(url) // Modified URL
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Fetched disabled dates:", data); // Debugging
            disabledDates = data;
            renderCalendar(); // Re-render calendar with updated disabled dates
        })
        .catch(error => {
            console.error('Error fetching disabled dates:', error);
            // Optionally show an error message to the user
            alert('Could not load availability information. Please try again later.');
        });
}

// Format date for display
function formatDisplayDate(dateStr) {
    const date = new Date(dateStr);
    // Adjust for potential timezone offset issues if needed when creating the date object
    // This ensures the date isn't shifted by timezone differences
    const userTimezoneOffset = date.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(date.getTime() + userTimezoneOffset);

    return adjustedDate.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Calculate end date based on start date and duration
function calculateEndDate(startDate) {
    const start = new Date(startDate);
    // Adjust for potential timezone offset issues
    const userTimezoneOffset = start.getTimezoneOffset() * 60000;
    const adjustedStart = new Date(start.getTime() + userTimezoneOffset);

    const end = new Date(adjustedStart);

    // Ensure duration is treated as a number
    const numDuration = parseInt(duration);
    if (isNaN(numDuration)) {
        console.error("Invalid duration:", duration);
        return start; // Return start date if duration is invalid
    }


    if (durationType === 'day' || durationType === 'days') {
         // For daily, add duration days. + (duration - 1) days total.
        end.setDate(end.getDate() + (numDuration - 1));
    } else if (durationType === 'month' || durationType === 'months') {
        end.setMonth(end.getMonth() + numDuration);
         // Go to the day *before* the start date in the end month.
        end.setDate(end.getDate() - 1);
    } else if (durationType === 'year' || durationType === 'years') {
        end.setFullYear(end.getFullYear() + numDuration);
        // Go to the day *before* the start date in the end year.
        end.setDate(end.getDate() - 1);
    } else {
         console.error("Unknown duration type:", durationType);
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

// Check if a date is disabled and get its status object
function getDateStatusInfo(dateToCheck) {
    const dateStr = typeof dateToCheck === 'string' ? dateToCheck : formatDateYMD(dateToCheck);

    // Check if date is in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkDate = new Date(dateStr);
     // Adjust checkDate for timezone to compare correctly with today
    const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
    const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);


    if (adjustedCheckDate < today) {
        return { status: 'past' }; // Return object for consistency
    }

    // Check if date is in disabledDates object from server
    if (disabledDates && disabledDates[dateStr]) {
         console.log(`Date ${dateStr} has disabled info:`, disabledDates[dateStr]); // Debug
        return disabledDates[dateStr]; // Returns the object like { status: 'pending-membership', endDate: '...', transactionId: ... } or { status: 'active-membership' }
    }

    // Check if date is in any of the currently selected date ranges
    for (const startDate of selectedDates) {
        const endDate = calculateEndDate(startDate);
        const start = new Date(startDate);
        const end = new Date(endDate);
        const check = new Date(dateStr);

         // Adjust dates for timezone before comparison
        const tzOffsetStart = start.getTimezoneOffset() * 60000;
        const adjStart = new Date(start.getTime() + tzOffsetStart);
        const tzOffsetEnd = end.getTimezoneOffset() * 60000;
        const adjEnd = new Date(end.getTime() + tzOffsetEnd);
        const tzOffsetCheck = check.getTimezoneOffset() * 60000;
        const adjCheck = new Date(check.getTime() + tzOffsetCheck);


        // Skip if this is the startDate we're checking (allow toggling off)
        if (dateStr === startDate) {
            continue;
        }

        if (adjCheck >= adjStart && adjCheck <= adjEnd) {
            return { status: 'selected-range' }; // Date is within a selected membership duration
        }
    }

    return null; // Date is not disabled
}

// Check if selecting a start date would create overlapping memberships
function wouldCreateOverlap(startDateStr) {
    const endDate = calculateEndDate(startDateStr);
    const endDateStr = formatDateYMD(endDate);

    // Check each day in the range
    const currentDate = new Date(startDateStr);
     // Adjust for timezone
    const userTimezoneOffset = currentDate.getTimezoneOffset() * 60000;
    const adjustedStartDate = new Date(currentDate.getTime() + userTimezoneOffset);
    const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset); // Use calculated end date adjusted

    let checkDate = new Date(adjustedStartDate);


    while (checkDate <= adjustedEndDate) { // Use adjusted end date
        const currentDateStr = formatDateYMD(checkDate);

        // If it's not the start date and it's already disabled or selected, there's an overlap
        if (currentDateStr !== startDateStr) {
            const statusInfo = getDateStatusInfo(currentDateStr);
            // Check if statusInfo exists and its status is not 'selected-range'
            if (statusInfo && statusInfo.status && statusInfo.status !== 'selected-range') {
                 console.log(`Overlap detected for ${startDateStr}: Date ${currentDateStr} has status ${statusInfo.status}`); // Debug
                return true;
            }
        }

        checkDate.setDate(checkDate.getDate() + 1);
    }

    return false;
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
        const userTimezoneOffset = currentDate.getTimezoneOffset() * 60000;
        let loopDate = new Date(currentDate.getTime() + userTimezoneOffset);
        const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset);

        while (loopDate <= adjustedEndDate) {
            const currentDateStr = formatDateYMD(loopDate);
            if (!disabledDateRanges[currentDateStr]) {
                // Just mark the date string as part of a range for checking, value doesn't matter much here
                disabledDateRanges[currentDateStr] = true;
            }
            loopDate.setDate(loopDate.getDate() + 1);
        }
    });
     // console.log("Updated disabled ranges based on selection:", disabledDateRanges); // Debug
}

// Add date to the selection
async function addDate(dateStr) { // Make async to handle await for fetch
    if (!dateStr) {
        return;
    }

    // Check if date is already selected (toggle off)
    if (selectedDates.includes(dateStr)) {
        removeDate(dateStr);
        return;
    }

    // Check if date is disabled
    const statusInfo = getDateStatusInfo(dateStr);
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = new bootstrap.Modal(conflictModalElement);

    if (statusInfo && statusInfo.status) {
        const status = statusInfo.status;

        // Handle pending membership conflict
        if (status === 'pending-membership') {
            const transactionId = statusInfo.transactionId;
            const pendingEndDateStr = statusInfo.endDate;
            const clickedDate = dateStr; // Store the date the user actually clicked

             if (!transactionId || !pendingEndDateStr || !clickedDate) {
                 alert('Error: Cannot identify the pending membership or clicked date. Please refresh and try again.');
                 return;
             }

            // Store conflict data for modal button handlers
            modalConflictData = {
                type: 'membership', // Add type
                transactionId: transactionId,
                pendingEndDateStr: pendingEndDateStr,
                clickedDate: clickedDate // Store the date the user clicked
            };

            // Update modal message and show/hide buttons for membership
            document.getElementById('conflictModalLabel').innerText = 'Membership Conflict Detected'; // Set modal title
            document.getElementById('conflictMessage').innerText =
                `This date conflicts with a pending membership ending ${formatDisplayDate(pendingEndDateStr)}.\n\n` +
                `Choose an action:`;
            document.getElementById('appendMembershipBtn').style.display = ''; // Show Append button
            document.getElementById('replaceMembershipBtn').style.display = ''; // Show Replace button

            conflictModal.show();
            return; // Stop further processing here, wait for modal interaction

        } else if (status === 'pending-walkin') { // Handle pending walk-in conflict
             const transactionId = statusInfo.transactionId;
             const clickedDate = dateStr; // Store the date the user actually clicked

             if (!transactionId || !clickedDate) {
                 alert('Error: Cannot identify the pending walk-in or clicked date. Please refresh and try again.');
                 return;
             }

            // Store conflict data for modal button handlers
             modalConflictData = {
                type: 'walkin', // Add type
                transactionId: transactionId,
                clickedDate: clickedDate // Store the date the user clicked
            };

            // Update modal message and show/hide buttons for walk-in
            document.getElementById('conflictModalLabel').innerText = 'Walk-in Conflict Detected'; // Set modal title
            document.getElementById('conflictMessage').innerText =
                `This date has a pending walk-in reservation (${formatDisplayDate(clickedDate)}).\n\n` +
                `Do you want to remove the pending walk-in to book this membership?`;

            document.getElementById('appendMembershipBtn').style.display = 'none'; // Hide Append button for walk-in
            // The 'replaceMembershipBtn' will be repurposed as 'Remove Walk-in'
            document.getElementById('replaceMembershipBtn').style.display = '';
            document.getElementById('replaceMembershipBtn').innerText = 'Remove Walk-in'; // Change button text
            document.getElementById('replaceMembershipBtn').classList.remove('btn-danger'); // Optional: adjust button color if needed
            document.getElementById('replaceMembershipBtn').classList.add('btn-warning'); // Example: use warning color

            conflictModal.show();
            return; // Stop further processing here, wait for modal interaction

        }

        // Don't allow selecting dates within another selected membership's duration
        if (status === 'selected-range') {
            alert('This date falls within the duration of an already selected membership. Please choose a different start date.');
            return;
        }

        // Show specific error message based on other statuses (past, active-membership)
        let message;
        switch (status) {
            case 'past':
                message = 'You cannot select a date in the past.';
                break;
            case 'active-membership':
                message = 'This date conflicts with an active membership.';
                break;
             // pending-walkin case is handled above
            default:
                message = 'This date is unavailable.';
        }
        alert(message);
        return;
    }

    // Check if selecting this date would create overlapping memberships
    if (wouldCreateOverlap(dateStr)) {
        alert('The membership period from this start date would overlap with existing memberships or reservations.');
        return;
    }

    // ---- If checks pass, add date to array ----
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

// Add the new function to handle walk-in removal
async function handleRemoveWalkin() {
    if (!modalConflictData || modalConflictData.type !== 'walkin' || !modalConflictData.transactionId || !modalConflictData.clickedDate) {
         alert('Error: Missing walk-in conflict data. Please try again.');
         return;
    }

    const { transactionId, clickedDate } = modalConflictData;

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();

    console.log(`User chose to remove pending walk-in transaction ID: ${transactionId}`);

    // Call backend to delete the pending walk-in transaction
    try {
        const formData = new FormData();
        formData.append('transactionId', transactionId);

        // Use the new endpoint for deleting walk-ins
        const response = await fetch('date_functions/delete_pending_walkin.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok && result.success) {
            alert('Pending walk-in reservation removed.');
            // Refresh disabled dates from server BEFORE attempting to add the new date
            await fetchDisabledDates(); // Wait for refresh

            // Now, attempt to add the originally clicked date again, AFTER refreshing disabled dates
            // Need to re-check potential overlaps after deletion
            if (wouldCreateOverlap(clickedDate)) {
                 alert('The membership period from this start date would overlap with other existing memberships or reservations even after removing the walk-in.');
                 return;
            }
             if(getDateStatusInfo(clickedDate)){ // Check if still disabled for other reasons
                alert('This date is still unavailable after attempting to remove the pending walk-in.');
                return;
            }
            // Proceed to add the date if no longer conflicting
            selectedDates.push(clickedDate);
            selectedDates.sort();
            document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
            updateSelectedDatesUI();
            renderCalendar(); // Re-render with the new selection

        } else {
            alert(`Failed to remove pending walk-in: ${result.message || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error deleting pending walk-in:', error);
        alert('An error occurred while trying to remove the pending walk-in.');
    } finally {
        modalConflictData = null; // Clear data after handling
         // Reset modal buttons and text for next time
        document.getElementById('replaceMembershipBtn').innerText = 'Replace Pending';
        document.getElementById('replaceMembershipBtn').classList.remove('btn-warning');
        document.getElementById('replaceMembershipBtn').classList.add('btn-danger');
    }
}

// Function to handle "Replace Pending" action
async function handleReplacePending() {
    if (!modalConflictData || !modalConflictData.transactionId || !modalConflictData.clickedDate) {
         alert('Error: Missing conflict data. Please try again.');
         return;
    }

    const { transactionId, clickedDate } = modalConflictData;

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();

    console.log(`User chose to replace pending transaction ID: ${transactionId}`);

    // Call backend to delete the pending transaction
    try {
        const formData = new FormData();
        formData.append('transactionId', transactionId);

        const response = await fetch('date_functions/delete_pending_membership.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok && result.success) {
            alert('Pending membership removed.');
            // Refresh disabled dates from server BEFORE attempting to add the new date
            await fetchDisabledDates(); // Wait for refresh

            // Now, attempt to add the originally clicked date again, AFTER refreshing disabled dates
            // Need to re-check potential overlaps after deletion
            if (wouldCreateOverlap(clickedDate)) {
                 alert('The membership period from this start date would overlap with other existing memberships or reservations even after removing the pending one.');
                 return;
            }
            if(getDateStatusInfo(clickedDate)){ // Check if still disabled for other reasons
                alert('This date is still unavailable after attempting to remove the pending membership.');
                return;
            }
            // Proceed to add the date if no longer conflicting
            selectedDates.push(clickedDate);
            selectedDates.sort();
            document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
            updateSelectedDatesUI();
            renderCalendar(); // Re-render with the new selection

        } else {
            alert(`Failed to remove pending membership: ${result.message || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error deleting pending membership:', error);
        alert('An error occurred while trying to remove the pending membership.');
    } finally {
        modalConflictData = null; // Clear data after handling
    }
}

// Function to handle "Append After Pending" action
async function handleAppendAfterPending() {
     if (!modalConflictData || !modalConflictData.pendingEndDateStr) {
         alert('Error: Missing conflict data. Please try again.');
         return;
     }

    const { pendingEndDateStr } = modalConflictData;

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();

    console.log(`User chose to append after pending end date: ${pendingEndDateStr}`);

    if (!pendingEndDateStr) {
         alert('Error: Cannot determine when the pending membership ends. Please refresh and try again.');
         return;
    }

    // Calculate the day *after* the pending membership ends
    const pendingEndDate = new Date(pendingEndDateStr);
    const userTimezoneOffset = pendingEndDate.getTimezoneOffset() * 60000;
    const appendStartDate = new Date(pendingEndDate.getTime() + userTimezoneOffset);

    appendStartDate.setDate(appendStartDate.getDate() + 1); // Add one day
    const appendStartDateStr = formatDateYMD(appendStartDate);

    console.log(`Attempting to add appended date: ${appendStartDateStr}`);

     // Check if the *appended* date is valid before adding
     const appendStatusInfo = getDateStatusInfo(appendStartDateStr);
     if (appendStatusInfo && appendStatusInfo.status) {
         alert(`Cannot append: The day after the pending membership (${formatDisplayDate(appendStartDateStr)}) is also unavailable (Status: ${appendStatusInfo.status}).`);
         modalConflictData = null; // Clear data
         return;
     }
     if (wouldCreateOverlap(appendStartDateStr)) {
        alert(`Cannot append: The membership period starting the day after the pending one (${formatDisplayDate(appendStartDateStr)}) would overlap with other reservations.`);
        modalConflictData = null; // Clear data
        return;
     }
     if (selectedDates.includes(appendStartDateStr)) {
          alert(`Cannot append: The date ${formatDisplayDate(appendStartDateStr)} is already selected.`);
          modalConflictData = null; // Clear data
         return;
     }


    // Add the calculated append date instead of the clicked date
    selectedDates.push(appendStartDateStr);
    selectedDates.sort();
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    updateSelectedDatesUI();
    renderCalendar();

    modalConflictData = null; // Clear data after handling
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
    const endDatesInfo = document.getElementById('end-dates-info');

    // Clear containers
    container.innerHTML = '';
    endDatesInfo.innerHTML = '';

    if (selectedDates.length === 0) {
        const noDateMessage = document.createElement('p');
        noDateMessage.id = 'no-dates-message';
        noDateMessage.textContent = 'No dates selected';
        container.appendChild(noDateMessage);

        document.getElementById('total_price').textContent = number_format(price, 2); // Update price display
        endDatesInfo.innerHTML = '<p class="text-muted">No membership periods selected</p>';
        return;
    }

    // Add date chips for each selected date
    selectedDates.forEach(dateStr => {
        const dateChip = document.createElement('div');
        dateChip.className = 'date-chip';
        dateChip.innerHTML = `
            ${formatDisplayDate(dateStr)}
            <span class="remove-date" onclick="removeDate('${dateStr}')" style="cursor: pointer;">
                <i class="bi bi-x-circle"></i>
            </span>
        `;
        container.appendChild(dateChip);

        // Add end date information
        const endDate = calculateEndDate(dateStr);
        const endDateInfo = document.createElement('div');
        endDateInfo.className = 'mb-1'; // Reduced margin
        endDateInfo.style.fontSize = '0.9em'; // Slightly smaller font
        endDateInfo.innerHTML = `
            <small><strong>Period:</strong> ${formatDisplayDate(dateStr)} to ${formatDisplayDate(endDate)}</small>
        `;
        endDatesInfo.appendChild(endDateInfo);
    });

    // Update total price
    const totalPrice = (price * selectedDates.length).toFixed(2);
    document.getElementById('total_price').textContent = number_format(totalPrice, 2); // Update price display


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
    event.preventDefault(); // Prevent default form submission

    if (selectedDates.length === 0) {
        alert('Please select at least one start date for your membership.');
        return false; // Stop submission
    }

    const form = event.target;
    const formData = new FormData(form);

    // Ensure the hidden input has the latest selected dates
     document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
     formData.set('selected_dates', JSON.stringify(selectedDates)); // Explicitly set it for FormData


    // Display loading state (optional)
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';


    fetch(window.location.href, { // Post to the same page (avail_membership.php)
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON, otherwise handle potential HTML errors
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
        } else {
            return response.text().then(text => { throw new Error(`Unexpected response format: ${text.substring(0, 100)}...`) });
        }
    })
    .then(({ ok, status, data }) => { // Destructure the object
        if (ok && data.success) {
             // Success: Redirect or show success message
            // alert('Successfully added to cart!'); // Optional alert
             if (data.redirect) {
                 window.location.href = data.redirect;
             } else {
                 // Handle case where redirect URL isn't provided (e.g., refresh or update UI)
                 console.log("Success, but no redirect URL provided.");
                 // Maybe refresh disabled dates?
                 fetchDisabledDates();
             }
        } else {
             // Handle failure: Show specific error message from server if available
             alert(data.message || `Failed to add to cart (Status: ${status})`);
        }
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        alert(`An error occurred: ${error.message}. Please check the console and try again.`);
    })
    .finally(() => {
         // Restore button state
         submitButton.disabled = false;
         submitButton.innerHTML = originalButtonText;
    });


    return false; // Prevent default form submission behaviour
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
    // Update month-year header (no change)
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;

    // Get first day of the month and total days in month (no change)
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay(); // 0=Sun, 1=Mon,...
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();


    // Clear calendar days container
    const daysContainer = document.getElementById('calendar-days');
    daysContainer.innerHTML = '';

     // Add empty slots for days before the first day of month
    for (let i = 0; i < firstDayOfMonth; i++) {
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

        // Get status info for the date
        const statusInfo = getDateStatusInfo(dateString);
        let statusClass = '';
        let isDisabled = false;

        if (statusInfo && statusInfo.status) {
            statusClass = statusInfo.status;
            if (statusClass !== 'pending-membership' && statusClass !== 'pending-walkin' && statusClass !== 'selected' && !selectedDates.includes(dateString)) {
                // Also check if it's part of a selected range but *not* the start date
                if(statusClass === 'selected-range' && !selectedDates.includes(dateString)) {
                    isDisabled = true;
                } else if (statusClass !== 'selected-range') {
                    isDisabled = true;
                }
            }
        }

        dayElement.className = `calendar-day ${statusClass}`;

        // Highlight selected start dates explicitly
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
            isDisabled = false; // Ensure selected start dates are clickable to deselect
        }

        // Add tooltip for disabled dates (optional)
        if (statusInfo && statusInfo.status && statusInfo.status !== 'selected-range' && !selectedDates.includes(dateString)) {
             let title = 'Unavailable';
             switch(statusInfo.status) {
                 case 'past': title = 'Date is in the past'; break;
                 case 'pending-membership': title = `Conflicts with a pending membership ending ${formatDisplayDate(statusInfo.endDate)}. Click to resolve.`; break;
                 case 'active-membership': title = 'Conflicts with an active membership'; break;
                 case 'pending-walkin': title = 'Conflicts with a pending walk-in'; break;
             }
             dayElement.setAttribute('title', title);
        }


        if (isDisabled) {
            dayElement.classList.add('disabled'); // Add generic disabled style
            if (statusClass === 'pending-membership' || statusClass === 'pending-walkin' || selectedDates.includes(dateString)) {
                dayElement.addEventListener('click', function() {
                    addDate(dateString); // Let addDate handle logic
                });
            }
        } else {
            // Add click event for selectable/pending dates
            dayElement.addEventListener('click', function() {
                addDate(dateString); // Let addDate handle logic
            });
        }

        daysContainer.appendChild(dayElement);
    }
     // After rendering days, ensure the internal disabled ranges reflect the current selection
    updateDisabledDateRanges();
}

function renderCalendarLegend() {
    // Create legend container
    const legendContainer = document.createElement('div');
    legendContainer.className = 'calendar-legend mt-3';
    legendContainer.innerHTML = `
        <div class="d-flex flex-wrap justify-content-start"> <div class="legend-item me-3 mb-1"> <span class="legend-color selected" style="border: 1px solid #ccc;"></span> <span>Selected Start Date</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color pending-membership" style="border: 1px solid #ccc;"></span>
                <span>Pending Membership</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color active-membership" style="border: 1px solid #ccc;"></span>
                <span>Active Membership</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color pending-walkin" style="border: 1px solid #ccc;"></span>
                <span>Pending Walk-in</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color past" style="background-color: #f5f5f5; border: 1px solid #ccc;"></span>
                <span>Past/Unavailable</span>
            </div>
        </div>
         <style>
             /* Add styles for legend colors if not already defined */
            .legend-color { display: inline-block; width: 16px; height: 16px; margin-right: 5px; border-radius: 3px; vertical-align: middle; }
            /* Ensure legend item text is aligned */
            .legend-item span:last-child { vertical-align: middle; }
         </style>
    `;

    // Insert the legend after the calendar grid container
    const calendarDaysContainer = document.getElementById('calendar-days');
     // Check if legend already exists to prevent duplicates
     const existingLegend = calendarDaysContainer.parentNode.querySelector('.calendar-legend');
     if (existingLegend) {
         existingLegend.remove();
     }

    calendarDaysContainer.parentNode.insertBefore(legendContainer, calendarDaysContainer.nextSibling);

    // -- Add Primary Color RGB definition ---
     // Get primary color from CSS variable
    const primaryColorHex = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();

    // Function to convert hex to RGB
    function hexToRgb(hex) {
        let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    const rgb = hexToRgb(primaryColorHex);
    if (rgb) {
        // Define the --primary-color-rgb CSS variable
        document.documentElement.style.setProperty('--primary-color-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
        console.log("Set --primary-color-rgb:", `${rgb.r}, ${rgb.g}, ${rgb.b}`);
    } else {
         console.error("Could not parse primary color:", primaryColorHex);
         // Fallback RGB color
         document.documentElement.style.setProperty('--primary-color-rgb', '255, 0, 0'); // Default red
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
window.onload = async function() { // Make async
    // Initialize the UI components
    renderCalendarWeekdays();
    renderCalendarLegend(); // Call legend render here

    // Set initial hidden dates value
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // Fetch disabled dates and wait for it to complete before first render
    await fetchDisabledDates(); // Wait here

    // Initial calendar render (now uses fetched data)
    // renderCalendar(); // This is called inside fetchDisabledDates now

    // Setup calendar navigation
    setupCalendarNavigation();
    // Setup modal button event listeners
    document.getElementById('replaceMembershipBtn').addEventListener('click', function() {
        if (modalConflictData && modalConflictData.type === 'membership') {
            handleReplacePending(); // Existing logic for membership
        } else if (modalConflictData && modalConflictData.type === 'walkin') {
            handleRemoveWalkin(); // New logic for walk-in
        }
    });

    // Modify the append button listener to only work for memberships
    document.getElementById('appendMembershipBtn').addEventListener('click', function() {
        if (modalConflictData && modalConflictData.type === 'membership') {
            handleAppendAfterPending(); // Existing logic for membership
        }
        // Do nothing if type is 'walkin' or null
    });

};


// Generate disabled date ranges for all selected dates
function updateDisabledDateRanges() {
    disabledDateRanges = {}; // Clear existing ranges

    selectedDates.forEach(startDate => {
        const endDate = calculateEndDate(startDate);
        disabledDateRanges[startDate] = formatDateYMD(endDate);

        // Disable all dates in between start and end
        const currentDate = new Date(startDate);
        const userTimezoneOffset = currentDate.getTimezoneOffset() * 60000;
        let loopDate = new Date(currentDate.getTime() + userTimezoneOffset);
        const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset);

        while (loopDate <= adjustedEndDate) {
            const currentDateStr = formatDateYMD(loopDate);
            if (!disabledDateRanges[currentDateStr]) {
                // Just mark the date string as part of a range for checking, value doesn't matter much here
                disabledDateRanges[currentDateStr] = true;
            }
            loopDate.setDate(loopDate.getDate() + 1);
        }
    });
     // console.log("Updated disabled ranges based on selection:", disabledDateRanges); // Debug
}
</script>