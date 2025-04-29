<?php
session_start();
require_once '../../functions/sanitize.php';
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$rental_id = $service_name = $price = $duration = $duration_type = $available_slots = $description = '';
$start_date = $end_date = $image = '';

// Error variables
$rental_idErr = $start_dateErr = $end_dateErr = $priceErr = '';
$selected_dates = []; // Array to store multiple selected dates

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $rental_id = clean_input($_GET['id']);
        $record = $Services->fetchRental($rental_id);

        if (!$record) {
            $_SESSION['error'] = 'Rental service not found';
            header('location: ../services.php');
            exit;
        }

        if ($record['available_slots'] < 1) {
            $_SESSION['error'] = 'No available slots for this service';
            header('location: ../services.php');
            exit;
        }

        $service_name = $record['service_name'];
        $price = $record['price'];
        $duration = $record['duration'];
        $duration_type = $record['duration_type'];
        $available_slots = $record['available_slots'];
        $description = $record['description'];
        $image = $record['image'];
    } else {
        header('location: ../services.php');
        exit;
    }
}

// Handle AJAX request for adding to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login first');
        }

        $rental_id = clean_input($_POST['rental_id']);
        $service_name = clean_input($_POST['service_name']);
        $price = clean_input($_POST['price']);

        // Get selected dates as JSON array
        $dates = isset($_POST['selected_dates']) ? json_decode($_POST['selected_dates'], true) : [];

        if (empty($dates)) {
            throw new Exception('Please select at least one date');
        }

        // Get validity directly from the record
        $record = $Services->fetchRental($rental_id);
        if (!$record) {
            throw new Exception('Invalid rental service');
        }

        $validity = $record['duration'] . ' ' . $record['duration_type'];

        // Validate dates
        $today = date('Y-m-d');
        foreach ($dates as $date) {
            if ($date < $today) {
                throw new Exception('Selected dates cannot be in the past');
            }
        }

        // Check if service still has available slots
        if ($record['available_slots'] < count($dates)) {
            throw new Exception('Not enough available slots for this service');
        }

        $Cart = new Cart_Class();
        $success = true;

        // Add each date as a separate rental service to cart
        foreach ($dates as $date) {
            // Calculate end date based on start date and duration
            $start = new DateTime($date);
            $end = clone $start;

            if ($record['duration_type'] === 'days') {
                $end->modify("+{$record['duration']} days");
            } else if ($record['duration_type'] === 'months') {
                $end->modify("+{$record['duration']} months");
            } else if ($record['duration_type'] === 'year') {
                $end->modify("+{$record['duration']} years");
            }

            $end_date = $end->format('Y-m-d');

            $item = [
                'id' => $rental_id,
                'name' => $service_name,
                'price' => $price,
                'validity' => $validity,
                'start_date' => $date,
                'end_date' => $end_date
            ];

            if (!$Cart->addRental($item)) {
                $success = false;
                break;
            }
        }

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => count($dates) > 1 ? 'Rental services added to cart successfully' : 'Rental service added to cart successfully',
                'redirect' => '../services.php'
            ]);
        } else {
            throw new Exception('Failed to add rental service(s) to cart');
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


?>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="service.css">
<style>
    :root {
        --primary-color: <?= $primaryHex ?>;
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
        background-color: var(--primary-color)!important;
        padding: 1rem;
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
    #start_date {
        display: none;
    }
    p, label {
        font-weight: 600;
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

        .calendar-day.paid-active-rental {
            background-color: #cce5ff; /* Light blue */
            color: #004085;
            cursor: not-allowed;
        }

        /* Changed color for unpaid-active-rental (Pending Rental) */
        .calendar-day.unpaid-active-rental {
            background-color: #ffebcc; /* Light orange */
            color: #663500; /* Darker text for contrast */
            /* Cursor is NOT not-allowed here to allow clicking for the modal */
        }

        .calendar-day.selected-range {
            background-color: rgba(var(--primary-color-rgb), 0.3);
            color: var(--primary-color);
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

        .legend-color.paid-active-rental {
            background-color: #cce5ff;
        }

        /* Changed color for unpaid-active-rental legend */
        .legend-color.unpaid-active-rental {
            background-color: #ffebcc; /* Light orange */
        }

        .legend-color.selected-range {
            background-color: rgba(var(--primary-color-rgb), 0.3);
        }

         .legend-color.past { /* Added style for past in legend */
            background-color: #f5f5f5;
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

    .img-fluid.rounded{
        display: none!important;
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

<div class="avail-rental-page">
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
                    <h2 class="h4 fw-bold mb-0 text-center">Rental Service</h2>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <?php
                                $defaultImage = '../../cms_img/default/rental.jpeg';
                                $imagePath = $defaultImage;
                                if (!empty($membership['image']) && file_exists(__DIR__ . "/../../cms_img/rentals/" . $record['image'])) {
                                    $imagePath = '../../cms_img/rentals/' . $record['image'];
                                }
                                ?>
                                <img src="<?= $imagePath ?>"
                                     class="img-fluid rounded"
                                     alt="<?= htmlspecialchars($service_name) ?>">
                                <h3 class="h5 fw-bold mt-3"><?= $service_name ?></h3>
                            </div>
                            <?php if (!empty($description)): ?>
                                <div class="mt-3">
                                    <p class="mb-0">Description:
                                        <span style="display:block; max-height: 50px; overflow-y: auto; max-width: 100%; word-break: break-word;">
                                            <?= nl2br(htmlspecialchars($description)) ?>
                                        </span>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <section class="scrollable-section">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?> per rental</p>
                                        </div>
                                    </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="form-group">
                                        <label class="form-label">Select Dates:</label>
                                        <input type="date"
                                            class="form-control form-control-lg"
                                            id="start_date"
                                            min="<?= date('Y-m-d', strtotime('today')) ?>"
                                            value=""
                                            required>

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
                                                </div>
                                            <div class="calendar-grid" id="calendar-days">
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
                                            <p class="mb-0">End dates will be calculated based on the selected start dates and duration</p>
                                            <div id="end-dates-info" class="mt-2">
                                                </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Price per rental: ₱<?= number_format($price, 2) ?></p>
                                    <p class="mb-0 mt-2">Total price: ₱<span id="total_price">0.00</span></p>
                                            <?php if(!empty($priceErr)): ?>
                                                <div class="text-danger mt-1"><?= $priceErr ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    </section>
                                </div>

                    <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                        <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="flex-fill" onsubmit="return validateForm(event)">
                                <input type="hidden" name="rental_id" value="<?= $rental_id ?>">
                                <input type="hidden" name="service_name" value="<?= $service_name ?>">
                                <input type="hidden" name="price" value="<?= $price ?>">
                                <input type="hidden" name="selected_dates" id="hidden_selected_dates">
                                <button type="submit" name="add_to_cart" class="btn btn-lg w-100 add-cart">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <a href="../../login/login.php" class="btn btn-lg w-100 add-cart">Login to Add</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="conflictModal" tabindex="-1" aria-labelledby="conflictModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="conflictModalLabel">Conflict Detected</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="conflictMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="replacePendingBtn">Replace Pending Rental</button>
        </div>
    </div>
  </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Array to store selected dates
let selectedDates = [];
const price = <?= $price ?>;
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const duration = <?= $duration ?>;
const durationType = '<?= $duration_type ?>';

// Object to store disabled dates from server with their status and info
let disabledDatesInfo = {};

// Store data for the modal
let modalConflictData = null;

// Format date for display
function formatDisplayDate(dateStr) {
    const date = new Date(dateStr);
     // Adjust for potential timezone offset issues
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
    const end = new Date(start);

    if (durationType === 'days') {
        // For 1-day duration, end date is 24 hours from start
        // So, a 1-day rental on 2023-10-26 ends on 2023-10-27
        // For multi-day rentals, we add the full number of days
        end.setDate(end.getDate() + parseInt(duration));
    } else if (durationType === 'months') {
        end.setMonth(end.getMonth() + parseInt(duration));
        // Adjust for end of month issue if needed
        if (start.getDate() !== end.getDate() && 
            end.getDate() !== new Date(end.getFullYear(), end.getMonth(), 0).getDate()) {
            end.setDate(new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate());
        }
    } else if (durationType === 'year') {
        end.setFullYear(end.getFullYear() + parseInt(duration));
    }

    return end;
}


// Format date toYYYY-MM-DD
function formatDateYMD(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Fetch disabled dates from server for rentals
async function fetchDisabledDates() {
    // Add cache-busting query parameter
    const url = `date_functions/get_rental_disabled_dates.php?t=${new Date().getTime()}`; // Assuming this endpoint exists and uses RentalValidation
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log("Fetched rental disabled dates:", data); // Debugging
        disabledDatesInfo = data; // Store the fetched data
        renderCalendar(); // Re-render calendar with updated disabled dates
    } catch (error) {
        console.error('Error fetching rental disabled dates:', error);
        alert('Could not load availability information. Please try again later.');
    }
}

// Check if a date is disabled and why
function getDateStatusInfo(dateToCheck) {
    const dateStr = typeof dateToCheck === 'string' ? dateToCheck : formatDateYMD(dateToCheck);

    // Check if date is in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
     const checkDate = new Date(dateStr);
     // Adjust checkDate for timezone
    const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
    const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);

    if (adjustedCheckDate < today) {
        return { status: 'past' }; // Return object for consistency
    }

    // Check if date is in disabledDatesInfo object from server
    // This object should contain statuses like 'paid-active-rental', 'unpaid-active-rental', etc.
    if (disabledDatesInfo && disabledDatesInfo[dateStr]) {
        console.log(`Date ${dateStr} has disabled info:`, disabledDatesInfo[dateStr]); // Debug
        return disabledDatesInfo[dateStr]; // Returns the object like { status: '...', transactionId: ... }
    }

    // Check if date is within the range of any currently selected date's rental duration
    for (const startDate of selectedDates) {
        const endDate = calculateEndDate(startDate);
        const start = new Date(startDate);
        const end = new Date(endDate);
        const check = new Date(dateStr);

         // Adjust dates for timezone for accurate comparison
        const adjustedStart = new Date(start.getTime() + userTimezoneOffset);
        const adjustedEnd = new Date(end.getTime() + userTimezoneOffset);
        const adjustedCheck = new Date(check.getTime() + userTimezoneOffset);


        // Check if the date falls within the range of a *different* selected date
        if (dateStr !== startDate && adjustedCheck >= adjustedStart && adjustedCheck <= adjustedEnd) {
            return { status: 'selected-range' }; // This date is within a selected rental duration
        }
    }

    return null; // Date is not disabled by external factors or selected rentals
}


// Add date to the selection - with enhanced validation for rental duration conflicts
async function addDate(dateStr) { // Make async to handle await for fetch
    if (!dateStr) {
        return;
    }

    console.log("addDate called for:", dateStr); // Debugging

    // Check if date is already selected (toggle off)
    if (selectedDates.includes(dateStr)) {
        console.log("Date already selected, removing:", dateStr); // Debugging
        removeDate(dateStr);
        return;
    }

    // Check if date is disabled or conflicts with anything
    const statusInfo = getDateStatusInfo(dateStr);
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = new bootstrap.Modal(conflictModalElement);

     console.log("Status info for", dateStr, ":", statusInfo); // Debugging


    if (statusInfo && statusInfo.status) {
        const status = statusInfo.status;

        // Handle conflicts with existing pending unpaid rentals
        if (status === 'unpaid-active-rental') {
            const transactionId = statusInfo.transactionId;
            const conflictDate = dateStr; // The date the user clicked, which has a pending rental conflict

            if (!transactionId || !conflictDate) {
                 alert('Error: Cannot identify the pending rental conflict data. Please refresh and try again.');
                 return;
             }

            // Store conflict data for modal button handlers
            modalConflictData = {
                type: 'rental', // Add type
                transactionId: transactionId,
                conflictDate: conflictDate // Store the date the user clicked
            };

            // Update modal message and show/hide buttons for rental conflict
            document.getElementById('conflictModalLabel').innerText = 'Rental Conflict Detected'; // Set modal title
            document.getElementById('conflictMessage').innerText =
                `Selecting a rental starting on ${formatDisplayDate(conflictDate)} would conflict with a pending rental reservation on this date.` +
                `\n\nDo you want to replace the pending rental reservation?`; // Adjusted message

            // For rentals, we generally only offer 'Replace' as 'Append' logic is complex with durations.
            // document.getElementById('appendMembershipBtn').style.display = 'none'; // Ensure Append button is hidden (already hidden in HTML)
            document.getElementById('replacePendingBtn').style.display = ''; // Ensure Replace button is shown
            document.getElementById('replacePendingBtn').innerText = 'Replace Pending Rental'; // Change button text
            document.getElementById('replacePendingBtn').classList.remove('btn-danger'); // Ensure correct style
            document.getElementById('replacePendingBtn').classList.add('btn-warning'); // Use warning color


            conflictModal.show();
            console.log("Conflict modal shown for unpaid-active-rental"); // Debugging
            return; // Stop further processing here, wait for modal interaction
        }

        // Handle conflicts with pending memberships or walk-ins or paid rentals/memberships
        // These are generally not resolvable from the rental page by replacing, so just show an alert.
        let message;
        switch (status) {
            case 'past':
                message = 'You cannot select a date in the past.';
                break;
            case 'pending-membership':
                 message = `Selecting a rental starting on ${formatDisplayDate(dateStr)} would conflict with a pending membership ending ${formatDisplayDate(statusInfo.endDate)}. You cannot book a rental during a pending membership period. Please choose a different date or resolve the pending membership.`;
                break;
            case 'active-membership':
                 message = `Selecting a rental starting on ${formatDisplayDate(dateStr)} would conflict with an active membership. You cannot book a rental during an active membership period. Please choose a different date.`;
                break;
             case 'pending-walkin':
                 message = `This date conflicts with a pending walk-in reservation on ${formatDisplayDate(dateStr)}. Please select a different date.`;
                 break;
             case 'paid-active-rental':
                 message = `This date falls within an active rental period (${formatDisplayDate(dateStr)}). Please choose a different start date.`;
                break;
            case 'selected-range':
                 // This case should ideally be handled before the `if (statusInfo && statusInfo.status)` block
                 // by the `selectedDates.includes(dateStr)` check if the click was on a selected start date.
                 // However, if a date within a selected range is clicked (which is visually highlighted but
                 // not the start date), getDateStatusInfo will return 'selected-range'. We should prevent
                 // adding it as a new start date.
                 message = `This date falls within the duration of a rental you have already selected starting on ${formatDisplayDate(findSelectedStartDateForDate(dateStr))}. Please choose a different start date.`;
                 break;
            default:
                message = 'This date is unavailable.';
        }
        alert(message);
        console.log("Alert shown for status:", status); // Debugging
        return;
    }

    // If checks pass, add date to array
    console.log("Adding date to selectedDates:", dateStr); // Debugging
    selectedDates.push(dateStr);

    // Sort dates chronologically
    selectedDates.sort();

    // Update the hidden input with JSON string of selected dates
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // Update UI
    updateSelectedDatesUI();

    // Update calendar to reflect the selection and ranges
    renderCalendar();
}

// Helper function to find the start date of a selected rental range that a given date falls within
function findSelectedStartDateForDate(dateToCheckStr) {
    const checkDate = new Date(dateToCheckStr);
     const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
     const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);


    for (const startDateStr of selectedDates) {
        const endDate = calculateEndDate(startDateStr);
        const startDate = new Date(startDateStr);

        const adjustedStartDate = new Date(startDate.getTime() + userTimezoneOffset);
        const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset);


        if (adjustedCheckDate >= adjustedStartDate && adjustedCheckDate <= adjustedEndDate) {
            return startDateStr; // Return the start date of the overlapping selected range
        }
    }
    return null; // Should not happen if getDateStatusInfo returned 'selected-range' correctly
}


// Function to handle replacing a pending rental
async function handleReplacePendingRental() {
    console.log("handleReplacePendingRental called."); // Debugging
     if (!modalConflictData || modalConflictData.type !== 'rental' || !modalConflictData.transactionId || !modalConflictData.conflictDate) {
         alert('Error: Missing rental conflict data. Please try again.');
         console.error("Missing modalConflictData:", modalConflictData); // Debugging
         return;
    }

    const { transactionId, conflictDate } = modalConflictData; // conflictDate is the clicked date
    console.log(`Attempting to replace pending rental with transaction ID: ${transactionId} for date: ${conflictDate}`); // Debugging

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();
    console.log("Conflict modal hidden."); // Debugging


    // Call backend to delete the pending rental transaction
    try {
        const formData = new FormData();
        formData.append('transactionId', transactionId);

        console.log("Calling delete_pending_rental.php with transactionId:", transactionId); // Debugging
        const response = await fetch('date_functions/delete_pending_rental.php', { // Use the correct endpoint
            method: 'POST',
            body: formData
        });
        const result = await response.json();
         console.log("Response from delete_pending_rental.php:", result); // Debugging


        if (response.ok && result.success) {
            alert('Previous pending rental reservation removed.');
            console.log("Previous pending rental removed successfully."); // Debugging
            // Refresh disabled dates from server BEFORE attempting to add the new date
            console.log("Fetching disabled dates again..."); // Debugging
            await fetchDisabledDates(); // Wait for refresh
            console.log("Disabled dates fetched again."); // Debugging


            // Now, attempt to add the originally clicked date again, AFTER refreshing disabled dates
            // Check if still disabled for other reasons after deletion
             const updatedStatusInfo = getDateStatusInfo(conflictDate);
             console.log("Status info for", conflictDate, "after refresh:", updatedStatusInfo); // Debugging
             if(updatedStatusInfo && updatedStatusInfo.status){
                alert('This date is still unavailable after attempting to remove the previous pending rental.');
                return;
            }
            // Proceed to add the date if no longer conflicting
            console.log("Date is now available, calling addDate again for:", conflictDate); // Debugging
            addDate(conflictDate); // Use addDate to re-run validation and add to selectedDates array

        } else {
            alert(`Failed to remove previous pending rental: ${result.message || 'Unknown error'}`);
            console.error("Failed to remove pending rental:", result.message); // Debugging
        }
    } catch (error) {
        console.error('Error deleting pending rental:', error);
        alert('An error occurred while trying to remove the pending rental.');
    } finally {
        modalConflictData = null; // Clear data after handling
         // Reset modal buttons and text for next time
        document.getElementById('replacePendingBtn').innerText = 'Replace Pending Rental';
        document.getElementById('replacePendingBtn').classList.remove('btn-warning');
        document.getElementById('replacePendingBtn').classList.add('btn-danger');
         // document.getElementById('appendBtn').style.display = 'none'; // Ensure append is hidden
         console.log("Modal data cleared and button reset."); // Debugging
    }
}


// Remove date from selection
function removeDate(dateToRemove) {
    console.log("Removing date from selectedDates:", dateToRemove); // Debugging
    // Remove the date from selected dates
    selectedDates = selectedDates.filter(date => date !== dateToRemove);

    // Update the hidden input with JSON string of selected dates
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // Update UI
    updateSelectedDatesUI();

    // Update calendar to reflect the removal and re-evaluate ranges
    renderCalendar();
}

// Format number for display (similar to PHP's number_format)
function number_format(number, decimals) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// Update the UI to show selected dates and calculated end dates
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

        document.getElementById('total_price').textContent = '0.00';
        endDatesInfo.innerHTML = '<p class="text-muted">No rental periods selected</p>';
        return;
    }

    // Add date chips and end date info for each selected date
    selectedDates.forEach(date => {
        const dateChip = document.createElement('div');
        dateChip.className = 'date-chip';
        dateChip.innerHTML = `
            ${formatDisplayDate(date)}
            <span class="remove-date" onclick="removeDate('${date}')" style="cursor: pointer;">
                <i class="bi bi-x-circle"></i>
            </span>
        `;
        container.appendChild(dateChip);

        // Add end date information
        const endDate = calculateEndDate(date);
        const endDateInfo = document.createElement('div');
        endDateInfo.className = 'mb-2';
        endDateInfo.innerHTML = `
            <strong>Rental Period:</strong> ${formatDisplayDate(date)} to ${formatDisplayDate(endDate)}
        `;
        endDatesInfo.appendChild(endDateInfo);
    });

    // Update total price
    const totalPrice = (price * selectedDates.length).toFixed(2);
    document.getElementById('total_price').textContent = number_format(totalPrice, 2);
}

function validateForm(event) {
    event.preventDefault();

    if (selectedDates.length === 0) {
        alert('Please select at least one start date for your rental.');
        return false;
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


    fetch(window.location.href, { // Post to the same page (avail_rental.php)
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


// Format date string from year, month, day
function formatDateString(year, month, day) {
    // Ensure month and day are two digits
    const formattedMonth = (month + 1).toString().padStart(2, '0');
    const formattedDay = day.toString().padStart(2, '0');
    return `${year}-${formattedMonth}-${formattedDay}`;
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

function renderCalendar() {
    console.log("Rendering calendar..."); // Debugging
    // Update month-year header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;

    // Get first day of the month and total days in month
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
        let tooltipText = '';

        if (statusInfo && statusInfo.status) {
            statusClass = statusInfo.status;
             // Dates with these statuses are truly disabled and cannot be clicked
            if (statusClass === 'past' || statusClass === 'active-membership' || statusClass === 'paid-active-rental' || statusClass === 'pending-membership' || statusClass === 'pending-walkin') {
                 isDisabled = true;
                 switch(statusClass) {
                     case 'past': tooltipText = 'Date is in the past'; break;
                     case 'active-membership': tooltipText = 'Conflicts with an active membership. Cannot book rental during this period.'; break;
                     case 'paid-active-rental': tooltipText = 'This date is already reserved for an active rental.'; break;
                     case 'pending-membership': tooltipText = `Conflicts with a pending membership ending ${formatDisplayDate(statusInfo.endDate)}. Cannot book rental during this period.`; break;
                     case 'pending-walkin': tooltipText = 'Conflicts with a pending walk-in reservation. Cannot book rental on this date.'; break;
                 }
             } else if (statusClass === 'unpaid-active-rental') {
                 // Pending rentals are NOT disabled to allow clicking for the modal
                 tooltipText = 'This date conflicts with a pending rental reservation. Click to resolve.';
             } else if (statusClass === 'selected-range') {
                 // Dates within a selected range are not disabled to allow clicking the start date
                 // but we visually style them differently and prevent adding them as *new* start dates in addDate.
                 // We will handle preventing clicks on these using pointer-events in CSS if needed,
                 // but the addDate logic is the primary guard.
                  tooltipText = `Within selected rental starting ${formatDisplayDate(findSelectedStartDateForDate(dateString))}`;
             }
        }

        dayElement.className = `calendar-day ${statusClass}`;
        if(isDisabled) {
             dayElement.classList.add('disabled'); // Add generic disabled style if truly disabled
        }


        // Highlight selected dates and their ranges explicitly
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
             // Ensure selected start dates are clickable to deselect
             isDisabled = false; // Override if marked disabled by a status below
             dayElement.classList.remove('disabled'); // Remove the disabled class if it was added

             // Add tooltip for the selected start date itself if no other status provided one
             if (!tooltipText) {
                  tooltipText = 'Selected start date';
             }


            // Also highlight the range of this selected rental
            const endDate = calculateEndDate(dateString);
            const currentDate = new Date(dateString);
             const userTimezoneOffset = currentDate.getTimezoneOffset() * 60000;

            // Iterate from the day *after* the start date up to the end date
            currentDate.setDate(currentDate.getDate() + 1);

            while (true) {
                 const currentDateStr = formatDateYMD(currentDate);
                 const adjustedCurrentDate = new Date(currentDate.getTime() + userTimezoneOffset);
                 const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset);


                 if (adjustedCurrentDate > adjustedEndDate) break; // Stop if past end date

                 // Find the element for this date string in the current render cycle
                 // This is a bit inefficient, but necessary to modify elements
                 // after they've been added to the container.
                 const rangeDayElement = daysContainer.querySelector(`.calendar-day[data-date="${currentDateStr}"]`);
                 if (rangeDayElement && !rangeDayElement.classList.contains('selected')) {
                      rangeDayElement.classList.add('selected-range');
                       // Add tooltip for range dates if no other status provided one
                       if (!rangeDayElement.getAttribute('title')) { // Avoid overwriting existing status tooltips
                           rangeDayElement.setAttribute('title', `Within selected rental starting ${formatDisplayDate(dateString)}`);
                       }
                       // Dates within a selected range are not selectable as *new* start dates,
                       // and we should prevent their click events if they don't have another clickable status.
                       // The addDate logic prevents adding them as new start dates.
                       // We don't add the 'disabled' class here to allow clicks if they have
                       // a status like 'unpaid-active-rental' that *should* be clickable for the modal.
                 }
                 currentDate.setDate(currentDate.getDate() + 1);
            }
        }


         // Add a data attribute to easily find the element by date string later
        dayElement.setAttribute('data-date', dateString);


        // Add tooltip if generated
        if (tooltipText) {
             dayElement.setAttribute('title', tooltipText);
        }


        // Add click event for all dates (we'll check validity in the click handler)
        // BUT only if it's NOT a truly disabled date.
        if (!isDisabled) {
             dayElement.addEventListener('click', function() {
                 addDate(dateString); // Let addDate handle logic, including modal for pending conflicts
             });
        } else {
            // For truly disabled dates, optionally add pointer-events: none; via CSS or inline
             dayElement.style.pointerEvents = 'none';
        }


        daysContainer.appendChild(dayElement);
    }
    console.log("Calendar rendered."); // Debugging
}

// Enhanced function to render calendar legend with only relevant statuses
function renderCalendarLegend() {
    // Create legend container
    const legendContainer = document.createElement('div');
    legendContainer.className = 'calendar-legend mt-3';
    legendContainer.innerHTML = `
        <div class="d-flex flex-wrap justify-content-start">
            <div class="legend-item me-3 mb-1">
                <span class="legend-color selected" style="border: 1px solid #ccc;"></span>
                <span>Selected Start Date</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color paid-active-rental" style="border: 1px solid #ccc;"></span>
                <span>Active Rental</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color unpaid-active-rental" style="border: 1px solid #ccc;"></span>
                <span>Pending Rental</span>
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
    // This button now handles replacing a pending rental
    document.getElementById('replacePendingBtn').addEventListener('click', function() {
        console.log("Replace Pending Btn clicked. modalConflictData:", modalConflictData); // Debugging
        if (modalConflictData && modalConflictData.type === 'rental') {
            handleReplacePendingRental(); // Handle replacing a pending rental
        }
         // Add other types here if needed in the future (e.g., membership, walkin conflicts)
    });

    // The append button is not relevant for rentals due to duration complexity, so its listener is effectively unused here.
};

</script>