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

         /* In avail_rental.php <style> tag or your CSS file */
         .calendar-day.in-cart-rental {
            border: 2px dashed #0d6efd; /* Blue dashed border */
            background-color: rgba(13, 110, 253, 0.1); /* Light blue background */
            color: #0d6efd; /* Blue text */
            position: relative; /* Needed for icon positioning */
            cursor: pointer; /* Indicate it's clickable (for modal) */
        }

        /* Add the cart icon to the top-right corner of the in-cart day */
        .calendar-day.in-cart-rental::before {
            content: '\F23F'; /* Bootstrap Icons cart glyph */
            font-family: 'bootstrap-icons'; /* Make sure Bootstrap Icons font is loaded */
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.7em; /* Adjust icon size */
            opacity: 0.6; /* Make icon slightly transparent */
            color: #0d6efd; /* Match text color */
            line-height: 1; /* Ensure proper vertical alignment */
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

<!--conflict modal -->
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
        <button type="button" class="btn btn-warning" id="replacePendingBtn" style="display: none;">Replace Pending Rental</button>
        <button type="button" class="btn btn-warning" id="replaceInCartBtn" style="display: none;">Replace In-Cart Rental</button>
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
// Array to store rental dates from the cart { index: i, startDate: 'YYYY-MM-DD', endDate: 'YYYY-MM-DD' }
let inCartRentalDates = [];

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
        end.setDate(end.getDate() + parseInt(duration));
    } else if (durationType === 'months') {
        end.setMonth(end.getMonth() + parseInt(duration));
        if (start.getDate() !== end.getDate() &&
            end.getDate() !== new Date(end.getFullYear(), end.getMonth(), 0).getDate()) {
            end.setDate(new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate());
        }
    } else if (durationType === 'year') {
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

// Fetch disabled dates from server for rentals (existing bookings, etc.)
async function fetchDisabledDates() {
    const url = `date_functions/get_rental_disabled_dates.php?t=${new Date().getTime()}`;
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log("Fetched server disabled dates:", data);
        disabledDatesInfo = data; // Store the fetched data
        renderCalendar(); // Re-render calendar with updated disabled dates
    } catch (error) {
        console.error('Error fetching server disabled dates:', error);
        alert('Could not load availability information. Please try again later.');
    }
}

// --- NEW: Fetch rentals currently in the cart ---
async function fetchInCartRentals() {
    console.log("Fetching in-cart rentals...");
    try {
        // Use cart_handler.php to get cart contents
        const response = await fetch('cart_handler.php', { // Adjust path if needed
            method: 'POST', // Assuming POST for action=get
            headers: {
                'Content-Type': 'application/json'
                // Add 'Accept': 'application/json' if needed
            },
            body: JSON.stringify({ action: 'get' })
        });

        if (!response.ok) {
             // Try to read error message from response body if possible
             let errorText = `HTTP error! status: ${response.status}`;
             try {
                 const errorData = await response.json();
                 errorText += ` - ${errorData.message || JSON.stringify(errorData)}`;
             } catch (e) { /* Ignore if response is not JSON */ }
             throw new Error(errorText);
        }

        const result = await response.json();
        console.log("Cart data received:", result);

        if (result.success && result.data && result.data.cart && result.data.cart.rentals) {
            inCartRentalDates = result.data.cart.rentals.map((item, index) => {
                 // --- IMPORTANT: Ensure your cart items have 'start_date' and 'end_date' ---
                 // If 'end_date' is not stored, you MUST calculate it here using duration,
                 // similar to calculateEndDate function.
                 if (!item.start_date || !item.end_date) {
                     console.warn("Cart rental item missing start or end date:", item);
                     // Attempt to calculate end date if duration is available globally (less ideal)
                     if(item.start_date && duration && durationType){
                         const calculatedEnd = calculateEndDate(item.start_date);
                         item.end_date = formatDateYMD(calculatedEnd);
                         console.log(`Calculated end date for cart item ${index}: ${item.end_date}`);
                     } else {
                         return null; // Skip item if dates are missing and cannot be calculated
                     }
                 }
                 // Ensure dates are in YYYY-MM-DD format
                return {
                    index: index, // Store original cart array index for removal
                    startDate: item.start_date,
                    endDate: item.end_date,
                    id: item.id // Store item ID if needed
                };
            }).filter(item => item !== null); // Filter out items that couldn't be processed

            console.log("Processed in-cart rental dates:", inCartRentalDates);
        } else {
            console.log("No rental items found in cart or failed to fetch cart.");
            inCartRentalDates = [];
        }
    } catch (error) {
        console.error('Error fetching in-cart rentals:', error);
        inCartRentalDates = []; // Reset on error
        // alert('Could not load cart information.'); // Optional user alert
    }
     // Re-render the calendar AFTER fetching cart data
     renderCalendar();
}


// Check if a date is disabled and why (checks past, cart, server, selected)
function getDateStatusInfo(dateToCheck) {
    const dateStr = typeof dateToCheck === 'string' ? dateToCheck : formatDateYMD(dateToCheck);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const checkDate = new Date(dateStr);
    const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
    const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);

    // 1. Check Past
    if (adjustedCheckDate < today) return { status: 'past' };

    // 2. Check In-Cart Rentals
    for (const cartItem of inCartRentalDates) {
         const cartStartDate = new Date(cartItem.startDate);
         const cartEndDate = new Date(cartItem.endDate);
         const adjustedCartStart = new Date(cartStartDate.getTime() + userTimezoneOffset);
         const adjustedCartEnd = new Date(cartEndDate.getTime() + userTimezoneOffset);

         if (adjustedCheckDate >= adjustedCartStart && adjustedCheckDate <= adjustedCartEnd) {
              console.log(`Date ${dateStr} conflicts with in-cart rental index ${cartItem.index}`);
              return {
                  status: 'in-cart-rental',
                  cartIndex: cartItem.index,
                  itemId: cartItem.id,
                  startDate: cartItem.startDate
              };
         }
    }

    // 3. Check Server-Disabled Dates (Pending, Active, etc.)
    if (disabledDatesInfo && disabledDatesInfo[dateStr]) {
        console.log(`Date ${dateStr} has server disabled info:`, disabledDatesInfo[dateStr]);
        return disabledDatesInfo[dateStr]; // Returns object like { status: '...', transactionId: ... }
    }

    // 4. Check Currently Selected Ranges (by user in this session)
    for (const startDate of selectedDates) {
        const endDate = calculateEndDate(startDate);
        const start = new Date(startDate);
        const end = new Date(endDate);
        const adjustedStart = new Date(start.getTime() + userTimezoneOffset);
        const adjustedEnd = new Date(end.getTime() + userTimezoneOffset);

        // Check if the date falls within the range of a *different* selected date
        if (dateStr !== startDate && adjustedCheckDate >= adjustedStart && adjustedCheckDate <= adjustedEnd) {
            return { status: 'selected-range' };
        }
    }

    return null; // Date is available
}


// Add date to the selection or handle conflicts
async function addDate(dateStr) {
    if (!dateStr) return;

    console.log("addDate called for:", dateStr);

    // Toggle off if already selected
    if (selectedDates.includes(dateStr)) {
        console.log("Date already selected, removing:", dateStr);
        removeDate(dateStr);
        return;
    }

    // Check date status for conflicts
    const statusInfo = getDateStatusInfo(dateStr);
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = new bootstrap.Modal(conflictModalElement);

    console.log("Status info for", dateStr, ":", statusInfo);

    if (statusInfo && statusInfo.status) {
        const status = statusInfo.status;

        // Handle In-Cart Conflict
        if (status === 'in-cart-rental') {
            const cartIndex = statusInfo.cartIndex;
            const conflictDate = dateStr;

            if (cartIndex === undefined || !conflictDate) {
                alert('Error: Cannot identify the in-cart rental conflict data.'); return;
            }
            modalConflictData = { type: 'in-cart-rental', cartIndex: cartIndex, conflictDate: conflictDate };
            document.getElementById('conflictModalLabel').innerText = 'In-Cart Conflict';
            document.getElementById('conflictMessage').innerText = `Selecting ${formatDisplayDate(conflictDate)} conflicts with a rental in your cart (starting ${formatDisplayDate(statusInfo.startDate)}).\n\nReplace the item in your cart?`;
            document.getElementById('replacePendingBtn').style.display = 'none';
            document.getElementById('replaceInCartBtn').style.display = ''; // Show in-cart replace button
            conflictModal.show();
            console.log("Conflict modal shown for in-cart-rental");
            return;
        }
        // Handle Pending Rental Conflict
        else if (status === 'unpaid-active-rental') {
            const transactionId = statusInfo.transactionId;
            const conflictDate = dateStr;
            if (!transactionId || !conflictDate) {
                alert('Error: Cannot identify pending rental conflict data.'); return;
            }
            modalConflictData = { type: 'rental', transactionId: transactionId, conflictDate: conflictDate };
            document.getElementById('conflictModalLabel').innerText = 'Rental Conflict Detected';
            document.getElementById('conflictMessage').innerText = `Selecting ${formatDisplayDate(conflictDate)} conflicts with a pending rental reservation.\n\nReplace the pending rental?`;
            document.getElementById('replacePendingBtn').style.display = ''; // Show pending replace button
            document.getElementById('replaceInCartBtn').style.display = 'none';
            conflictModal.show();
            console.log("Conflict modal shown for unpaid-active-rental");
            return;
        }
        // Handle Other Non-Clickable Conflicts (Past, Active, etc.)
        else if (['past', 'pending-membership', 'active-membership', 'pending-walkin', 'paid-active-rental', 'selected-range'].includes(status)) {
            let message;
            switch (status) {
                case 'past': message = 'Cannot select a date in the past.'; break;
                case 'pending-membership': message = `Conflicts with a pending membership. Cannot book rental during this period.`; break;
                case 'active-membership': message = `Conflicts with an active membership. Cannot book rental during this period.`; break;
                case 'pending-walkin': message = `Conflicts with a pending walk-in on ${formatDisplayDate(dateStr)}.`; break;
                case 'paid-active-rental': message = `Conflicts with an active rental on ${formatDisplayDate(dateStr)}.`; break;
                case 'selected-range': message = `Date falls within another selected rental period. Choose a different start date.`; break;
                default: message = 'This date is unavailable.';
            }
            alert(message);
            console.log("Alert shown for status:", status);
            return;
        }
    }

    // If no conflicts / resolved, add date
    console.log("Adding date to selectedDates:", dateStr);
    selectedDates.push(dateStr);
    selectedDates.sort();
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    updateSelectedDatesUI();
    renderCalendar(); // Re-render to show new selection and ranges
}

// Helper to find the start date of a selected range containing a given date
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
            return startDateStr;
        }
    }
    return null;
}

// --- NEW: Handle replacing an item in the cart ---
async function handleReplaceInCartRental() {
    console.log("handleReplaceInCartRental called.");
    if (!modalConflictData || modalConflictData.type !== 'in-cart-rental' || modalConflictData.cartIndex === undefined || !modalConflictData.conflictDate) {
        alert('Error: Missing in-cart conflict data.');
        console.error("Missing modalConflictData for in-cart:", modalConflictData);
        return;
    }

    const { cartIndex, conflictDate } = modalConflictData;
    console.log(`Attempting to replace in-cart rental index: ${cartIndex} to add date: ${conflictDate}`);

    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();
    console.log("Conflict modal hidden.");

    // Call backend (cart_handler.php) to remove the item
    try {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('type', 'rental');
        formData.append('index', cartIndex);

        console.log("Calling cart_handler.php (remove)...");
        const response = await fetch('cart_handler.php', { method: 'POST', body: formData });
        const result = await response.json();
        console.log("Response from cart_handler.php (remove):", result);

        if (response.ok && result.success) {
            alert('Item removed from cart.');
            console.log("In-cart rental removed.");

            // Refresh cart data AND server disabled dates
            console.log("Fetching cart data again...");
            await fetchInCartRentals(); // Calls renderCalendar
            console.log("Fetching server disabled dates again...");
            await fetchDisabledDates(); // Calls renderCalendar again

            // Attempt to add the originally clicked date AFTER refreshes
            console.log("Re-attempting to add date:", conflictDate);
            // IMPORTANT: Re-check status *after* removal and refreshes
            const updatedStatusInfo = getDateStatusInfo(conflictDate);
            console.log("Status info for", conflictDate, "after removal & refresh:", updatedStatusInfo);
            // Check if it's clear or if it *still* conflicts (e.g., with a server-side booking)
             if (updatedStatusInfo && updatedStatusInfo.status && updatedStatusInfo.status !== 'in-cart-rental') {
                  alert(`Date ${formatDisplayDate(conflictDate)} is still unavailable (${updatedStatusInfo.status}) after removing the cart item.`);
             } else {
                 // Proceed to add the date using the main addDate function
                  console.log("Date should be available, calling addDate again.");
                 addDate(conflictDate); // Let addDate handle adding to selection
             }

        } else {
            alert(`Failed to remove item from cart: ${result.message || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error removing in-cart rental:', error);
        alert('An error occurred while trying to remove the item from the cart.');
    } finally {
        modalConflictData = null; // Clear data
        document.getElementById('replacePendingBtn').style.display = 'none';
        document.getElementById('replaceInCartBtn').style.display = 'none';
        console.log("Modal data cleared, buttons reset.");
    }
}

// Handle replacing a pending rental (from server)
async function handleReplacePendingRental() {
    console.log("handleReplacePendingRental called.");
     if (!modalConflictData || modalConflictData.type !== 'rental' || !modalConflictData.transactionId || !modalConflictData.conflictDate) {
         alert('Error: Missing pending rental conflict data.'); return;
    }
    const { transactionId, conflictDate } = modalConflictData;
    console.log(`Replacing pending rental ID: ${transactionId} for date: ${conflictDate}`);

    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    conflictModal.hide();

    // Call backend to delete the pending rental
    try {
        const formData = new FormData();
        formData.append('transactionId', transactionId);

        console.log("Calling delete_pending_rental.php...");
        const response = await fetch('date_functions/delete_pending_rental.php', { method: 'POST', body: formData });
        const result = await response.json();
         console.log("Response from delete_pending_rental.php:", result);

        if (response.ok && result.success) {
            alert('Pending rental removed.');
            // Refresh server disabled dates FIRST
            console.log("Fetching server disabled dates again...");
            await fetchDisabledDates(); // Calls renderCalendar
            console.log("Fetching cart data again (in case needed)..."); // Good practice
            await fetchInCartRentals(); // Calls renderCalendar again

            // Attempt to add the originally clicked date AFTER refreshes
            console.log("Re-attempting to add date:", conflictDate);
             const updatedStatusInfo = getDateStatusInfo(conflictDate);
             console.log("Status info for", conflictDate, "after removal & refresh:", updatedStatusInfo);
              if (updatedStatusInfo && updatedStatusInfo.status && updatedStatusInfo.status !== 'unpaid-active-rental') {
                  alert(`Date ${formatDisplayDate(conflictDate)} is still unavailable (${updatedStatusInfo.status}) after removing the pending rental.`);
             } else {
                  console.log("Date should be available, calling addDate again.");
                 addDate(conflictDate);
             }
        } else {
            alert(`Failed to remove pending rental: ${result.message || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error deleting pending rental:', error);
        alert('An error occurred while trying to remove the pending rental.');
    } finally {
        modalConflictData = null;
        document.getElementById('replacePendingBtn').style.display = 'none';
        document.getElementById('replaceInCartBtn').style.display = 'none';
         console.log("Modal data cleared, buttons reset.");
    }
}


// Remove date from selection
function removeDate(dateToRemove) {
    console.log("Removing date from selectedDates:", dateToRemove);
    selectedDates = selectedDates.filter(date => date !== dateToRemove);
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    updateSelectedDatesUI();
    renderCalendar(); // Re-render to update ranges
}

// Format number for display
function number_format(number, decimals) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(number);
}

// Update UI for selected dates chips and total price
function updateSelectedDatesUI() {
    const container = document.getElementById('dates-container');
    const endDatesInfo = document.getElementById('end-dates-info');
    container.innerHTML = '';
    endDatesInfo.innerHTML = '';

    if (selectedDates.length === 0) {
        container.innerHTML = '<p id="no-dates-message">No dates selected</p>';
        document.getElementById('total_price').textContent = '0.00';
        endDatesInfo.innerHTML = '<p class="text-muted">No rental periods selected</p>';
        return;
    }

    selectedDates.forEach(date => {
        const dateChip = document.createElement('div');
        dateChip.className = 'date-chip';
        dateChip.innerHTML = `${formatDisplayDate(date)} <span class="remove-date" onclick="removeDate('${date}')" style="cursor: pointer;"><i class="bi bi-x-circle"></i></span>`;
        container.appendChild(dateChip);

        const endDate = calculateEndDate(date);
        const endDateInfoP = document.createElement('p');
        endDateInfoP.className = 'mb-1'; // Smaller margin
        endDateInfoP.innerHTML = `<strong>Period:</strong> ${formatDisplayDate(date)} to ${formatDisplayDate(endDate)}`;
        endDatesInfo.appendChild(endDateInfoP);
    });

    const totalPrice = (price * selectedDates.length).toFixed(2);
    document.getElementById('total_price').textContent = number_format(totalPrice, 2);
}

// Validate form before submitting (Add to Cart)
function validateForm(event) {
    event.preventDefault(); // Prevent default POST

    if (selectedDates.length === 0) {
        alert('Please select at least one start date.');
        return false;
    }

    const form = event.target;
    const formData = new FormData(form);
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
    formData.set('selected_dates', JSON.stringify(selectedDates)); // Ensure FormData has it too

    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
        } else {
            return response.text().then(text => { throw new Error(`Unexpected response: ${text.substring(0, 200)}...`) });
        }
    })
    .then(({ ok, status, data }) => {
        if (ok && data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Successfully added to cart!');
                // Optionally clear selection and refresh data after adding
                // selectedDates = [];
                // updateSelectedDatesUI();
                // fetchInCartRentals(); // Refresh cart view on calendar
                // fetchDisabledDates(); // Refresh server dates
            }
        } else {
            alert(data.message || `Failed to add to cart (Status: ${status})`);
        }
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        alert(`An error occurred: ${error.message}. Please try again.`);
    })
    .finally(() => {
         submitButton.disabled = false;
         submitButton.innerHTML = originalButtonText;
    });

    return false; // Prevent default form submission again just in case
}


// Format date string from year, month, day
function formatDateString(year, month, day) {
    const formattedMonth = (month + 1).toString().padStart(2, '0');
    const formattedDay = day.toString().padStart(2, '0');
    return `${year}-${formattedMonth}-${formattedDay}`;
}

// Render calendar weekdays header
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

// Main calendar rendering function
function renderCalendar() {
    console.log("Rendering calendar...");
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;

    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysContainer = document.getElementById('calendar-days');
    daysContainer.innerHTML = ''; // Clear previous days

    // Add empty slots before the first day
    for (let i = 0; i < firstDayOfMonth; i++) {
        daysContainer.appendChild(document.createElement('div')).className = 'calendar-day empty';
    }

    // Add actual days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.textContent = day;
        const dateString = formatDateString(currentYear, currentMonth, day);
        dayElement.setAttribute('data-date', dateString); // Set data attribute

        const statusInfo = getDateStatusInfo(dateString);
        let statusClass = '';
        let isDisabled = false; // Is the day completely unclickable?
        let isClickableConflict = false; // Is it a conflict that needs modal?
        let tooltipText = '';

        if (statusInfo && statusInfo.status) {
            statusClass = statusInfo.status;
            if (['past', 'active-membership', 'paid-active-rental', 'pending-membership', 'pending-walkin'].includes(statusClass)) {
                 isDisabled = true; // These are hard blocks
                 // Add specific tooltips for disabled reasons
                 switch(statusClass) {
                     case 'past': tooltipText = 'Past date'; break;
                     case 'active-membership': tooltipText = 'Conflicts with active membership'; break;
                     case 'paid-active-rental': tooltipText = 'Date reserved (Active Rental)'; break;
                     case 'pending-membership': tooltipText = 'Conflicts with pending membership'; break;
                     case 'pending-walkin': tooltipText = 'Conflicts with pending walk-in'; break;
                 }
            } else if (statusClass === 'unpaid-active-rental' || statusClass === 'in-cart-rental') {
                 isClickableConflict = true; // These trigger modal
                 tooltipText = statusClass === 'in-cart-rental'
                     ? `Conflicts with item in cart (starts ${formatDisplayDate(statusInfo.startDate)}). Click to resolve.`
                     : 'Conflicts with pending rental. Click to resolve.';
            } else if (statusClass === 'selected-range') {
                // Visually distinct, but click handled by addDate checking start date
                 tooltipText = `Within selected rental starting ${formatDisplayDate(findSelectedStartDateForDate(dateString))}`;
                 // Technically not disabled, but addDate prevents adding it as a new start
            }
        }

        dayElement.className = `calendar-day ${statusClass}`; // Apply status class (e.g., 'in-cart-rental', 'past')
        if (isDisabled) dayElement.classList.add('disabled'); // Add generic disabled style

        // Highlight selected start dates
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
            isDisabled = false; // Ensure selected start date is clickable for removal
            isClickableConflict = false; // Override conflict status if it's the selected start date itself
            dayElement.classList.remove('disabled'); // Ensure not disabled
            tooltipText = tooltipText ? `${tooltipText} (Selected)` : 'Selected start date'; // Append or set tooltip
        }

        if (tooltipText) dayElement.setAttribute('title', tooltipText);

        // Add click listener ONLY if not strictly disabled
        if (!isDisabled) {
             dayElement.addEventListener('click', function() { addDate(dateString); });
        } else {
            dayElement.style.pointerEvents = 'none'; // Make visually unclickable
        }

        daysContainer.appendChild(dayElement);
    }

     // After adding days, highlight ranges for selected dates
     highlightSelectedRanges(daysContainer);

    console.log("Calendar rendered.");
}

// Helper function to highlight the duration range for selected dates
function highlightSelectedRanges(daysContainer) {
    const userTimezoneOffset = new Date().getTimezoneOffset() * 60000;
    selectedDates.forEach(startDateStr => {
        const endDate = calculateEndDate(startDateStr);
        let currentDate = new Date(startDateStr);
        currentDate.setDate(currentDate.getDate() + 1); // Start highlighting from the day AFTER start date

        while (true) {
            const currentDateStr = formatDateYMD(currentDate);
             const adjustedCurrentDate = new Date(currentDate.getTime() + userTimezoneOffset);
             const adjustedEndDate = new Date(endDate.getTime() + userTimezoneOffset);

             if (adjustedCurrentDate > adjustedEndDate) break; // Stop if past calculated end date

             const rangeDayElement = daysContainer.querySelector(`.calendar-day[data-date="${currentDateStr}"]`);
             if (rangeDayElement && !rangeDayElement.classList.contains('selected') && !rangeDayElement.classList.contains('selected-range')) {
                 // Only add 'selected-range' if it's not already a selected start date itself or already marked
                  rangeDayElement.classList.add('selected-range');
                  // Optionally add a tooltip if it doesn't have one already
                  if (!rangeDayElement.getAttribute('title')) {
                       rangeDayElement.setAttribute('title', `Within selected rental starting ${formatDisplayDate(startDateStr)}`);
                  }
                  // Ensure it's not clickable as a *new* start date (addDate handles this)
             }
            currentDate.setDate(currentDate.getDate() + 1); // Move to next day
        }
    });
}


// Render calendar legend
function renderCalendarLegend() {
    const legendContainer = document.createElement('div');
    legendContainer.className = 'calendar-legend mt-3';
    legendContainer.innerHTML = `
        <div class="d-flex flex-wrap justify-content-start align-items-center">
            <div class="legend-item me-3 mb-1"><span class="legend-color selected"></span><span>Selected Start</span></div>
            <div class="legend-item me-3 mb-1"><span class="legend-color in-cart-rental"></span><span>In Cart</span></div>
            <div class="legend-item me-3 mb-1"><span class="legend-color unpaid-active-rental"></span><span>Pending Rental</span></div>
            <div class="legend-item me-3 mb-1"><span class="legend-color paid-active-rental"></span><span>Active Rental</span></div>
            <div class="legend-item me-3 mb-1"><span class="legend-color past"></span><span>Past/Unavailable</span></div>
            </div>
        <style>
            .legend-item { display: inline-flex; align-items: center; font-size: 0.8rem; }
            .legend-color {
                display: inline-block;
                width: 16px;
                height: 16px;
                margin-right: 5px;
                border-radius: 3px;
                border: 1px solid #ccc; /* Default border */
                vertical-align: middle;
            }
            .legend-color.selected { background-color: var(--primary-color); color: white; }
            .legend-color.in-cart-rental {
                border: 2px dashed #0d6efd; /* Blue dashed border */
                background-color: rgba(13, 110, 253, 0.1); /* Light blue background */
            }
            .legend-color.unpaid-active-rental { background-color: #ffebcc; } /* Light orange */
            .legend-color.paid-active-rental { background-color: #cce5ff; } /* Light blue */
            .legend-color.past { background-color: #f5f5f5; }
            /* Add other status colors if needed (membership, walkin) */
            .calendar-day.selected-range { background-color: rgba(var(--primary-color-rgb), 0.3); color: var(--primary-color); }
            .calendar-day.in-cart-rental { background-color: #e2e3e5; color: #495057; cursor: pointer; }
            .calendar-day.in-cart-rental:hover {
                background-color: rgba(13, 110, 253, 0.2);
            }
        </style>`;

    const calendarDaysContainer = document.getElementById('calendar-days');
    const existingLegend = calendarDaysContainer.parentNode.querySelector('.calendar-legend');
    if (existingLegend) existingLegend.remove();
    calendarDaysContainer.parentNode.insertBefore(legendContainer, calendarDaysContainer.nextSibling);

    // Define --primary-color-rgb for opacity styles
    const primaryColorHex = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
    function hexToRgb(hex) {
        let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? { r: parseInt(result[1], 16), g: parseInt(result[2], 16), b: parseInt(result[3], 16) } : null;
    }
    const rgb = hexToRgb(primaryColorHex);
    if (rgb) {
        document.documentElement.style.setProperty('--primary-color-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
    } else {
         console.error("Could not parse primary color for RGB:", primaryColorHex);
         document.documentElement.style.setProperty('--primary-color-rgb', '255, 0, 0'); // Fallback red
    }
}

// Setup calendar navigation buttons
function setupCalendarNavigation() {
    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar();
    });
    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar();
    });
}

// Initialize UI on page load
window.onload = async function() {
    renderCalendarWeekdays();
    renderCalendarLegend(); // Render legend structure (colors depend on CSS vars defined later)
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // --- Fetch data and render ---
    // 1. Fetch server disabled dates (existing bookings etc.)
    await fetchDisabledDates(); // Calls renderCalendar
    // 2. Fetch items currently in cart
    await fetchInCartRentals(); // Calls renderCalendar again with cart info included

    // Setup navigation and modal listeners
    setupCalendarNavigation();

    // Modal button listeners
    document.getElementById('replacePendingBtn').addEventListener('click', () => {
        if (modalConflictData && modalConflictData.type === 'rental') handleReplacePendingRental();
    });
    document.getElementById('replaceInCartBtn').addEventListener('click', () => { // Listener for NEW button
        if (modalConflictData && modalConflictData.type === 'in-cart-rental') handleReplaceInCartRental();
    });

    console.log("Page initialization complete.");
};

</script>