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
    /* Add style for in-cart walkin dates */
    .calendar-day.in-cart-walkin {
        border: 2px dashed var(--secondary-color, #6c757d); /* Dashed border using secondary color or gray */
        background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1); /* Light background tint */
        color: var(--secondary-color, #6c757d);
        position: relative; /* Needed for potential icon overlay */
    }

    /* Optional: Add an icon to in-cart walkin days */
    .calendar-day.in-cart-walkin::after {
        content: '\F23F'; /* Bootstrap Icons cart icon */
        font-family: 'bootstrap-icons';
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 0.7em;
        opacity: 0.6;
    }

     /* Add style for in-cart membership dates */
     .calendar-day.in-cart-membership {
        border: 2px dashed #0d6efd; /* Hardcoded blue color for the border */
        background-color: rgba(13, 110, 253, 0.1); /* Light blue background with transparency */
        color: #0d6efd; /* Hardcoded blue color for text */
        position: relative; /* Kept as needed for potential icon overlay */
    }

    /* Optional: Add an icon to in-cart membership days */
    .calendar-day.in-cart-membership::before { /* Changed to ::before to allow both walkin and membership icons if needed */
        content: '\F23F'; /* Bootstrap Icons cart icon */
        font-family: 'bootstrap-icons';
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 0.7em;
        opacity: 0.6;
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
     /* Add style for in-cart walkin legend color */
    .legend-color.in-cart-walkin {
        border: 2px dashed var(--secondary-color, #6c757d);
        background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1);
    }
     /* Add style for in-cart membership legend color */
     .legend-color.in-cart-membership {
        border: 2px dashed #0d6efd; /* Hardcoded blue color for border */
        background-color: rgba(13, 110, 253, 0.1); /* Light blue background with transparency */
    }


    /* Adjust modal button visibility */
    #replaceInCartBtn, #appendToCartBtn, #replaceInCartWalkinBtn, #appendToCartWalkinBtn {
        display: none; /* Hide new buttons initially */
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
                                                <input type="date"
                                                    class="form-control form-control-lg"
                                                    id="start_date"
                                                    name="start_date"
                                                    min="<?= date('Y-m-d', strtotime('today')) ?>"
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
                                            <p class="mb-0">End Dates:</p>
                                            <div id="end-dates-info" class="mt-2">
                                                <p class="text-muted">No membership periods selected</p>
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
        <button type="button" class="btn btn-warning" id="appendMembershipBtn">Append After Pending</button>
        <button type="button" class="btn btn-danger" id="replaceMembershipBtn">Replace Pending / Remove Walk-in</button>
        <button type="button" class="btn btn-danger" id="replaceInCartBtn">Replace In Cart</button>
        <button type="button" class="btn btn-primary" id="appendToCartBtn">Append New to Cart</button>
         <button type="button" class="btn btn-danger" id="replaceInCartWalkinBtn">Replace In Cart Walk-in</button>
        <button type="button" class="btn btn-primary" id="appendToCartWalkinBtn">Add After In-Cart Walk-in</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Array to store selected dates
let disabledDates = {};
let cartItems = []; // Renamed from cartMemberships to hold all cart items
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

// Function to fetch all cart items (memberships and walk-ins)
async function fetchCartItems() {
    try {
        const response = await fetch('cart_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ action: 'get' })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data && result.data.cart) {
            console.log("Fetched cart:", result.data.cart); // Debug
            // Combine memberships and walk-ins, adding index and type
            const memberships = (result.data.cart.memberships || []).map((item, index) => ({ ...item, originalIndex: index, type: 'membership' }));
            const walkins = (result.data.cart.walkins || []).map((item, index) => ({ ...item, originalIndex: index, type: 'walkin' }));
            return [...memberships, ...walkins];
        } else {
            console.warn("No items found in cart or failed to fetch cart:", result);
            return [];
        }
    } catch (error) {
        console.error('Error fetching cart items:', error);
        return [];
    }
}


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
            // renderCalendar(); // Re-render calendar with updated disabled dates (will be called after fetching cart items)
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
        // For rentals, we want a full 24-hour period
        // So a 1-day rental starting on April 23 ends on April 24
        end.setDate(end.getDate() + numDuration);
    } else if (durationType === 'month' || durationType === 'months') {
        end.setMonth(end.getMonth() + numDuration);
        // We don't subtract a day to make it inclusive - we want full periods
    } else if (durationType === 'year' || durationType === 'years') {
        end.setFullYear(end.getFullYear() + numDuration);
        // We don't subtract a day to make it inclusive - we want full periods
    } else {
        console.error("Unknown duration type:", durationType);
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

// Check if a date is disabled and get its status object
function getDateStatusInfo(dateToCheck) {
    const dateStr = typeof dateToCheck === 'string' ? dateToCheck : formatDateYMD(dateToCheck);

    // Check if date is in the past (priority 1)
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkDate = new Date(dateStr);
    const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
    const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);

    if (adjustedCheckDate < today) {
        return { status: 'past' };
    }

    // Check server-side disabled dates (active/pending memberships/walkins) (priority 2)
    if (disabledDates && disabledDates[dateStr]) {
        console.log(`Date ${dateStr} has disabled info:`, disabledDates[dateStr]);
        return disabledDates[dateStr]; // Returns { status: '...', endDate: '...', transactionId: ... }
    }

    // Check if date falls within any IN-CART item range (priority 3)
    if (cartItems && cartItems.length > 0) {
        for (const cartItem of cartItems) {
             // Walk-ins are single day, memberships have ranges
             if (cartItem.type === 'walkin' && cartItem.date) {
                 if (formatDateYMD(new Date(cartItem.date)) === dateStr) {
                     console.log(`Date ${dateStr} is an in-cart walk-in:`, cartItem);
                     return { status: 'in-cart-walkin', cartItem: cartItem };
                 }
             } else if (cartItem.type === 'membership' && cartItem.start_date && cartItem.end_date) {
                const cartStart = new Date(cartItem.start_date);
                const cartEnd = new Date(cartItem.end_date);
                 // Adjust for timezone
                const adjCartStart = new Date(cartStart.getTime() + cartStart.getTimezoneOffset() * 60000);
                const adjCartEnd = new Date(cartEnd.getTime() + cartEnd.getTimezoneOffset() * 60000);

                if (adjustedCheckDate >= adjCartStart && adjustedCheckDate <= adjCartEnd) {
                     console.log(`Date ${dateStr} is within in-cart membership:`, cartItem);
                    return { status: 'in-cart-membership', cartItem: cartItem };
                }
            }
        }
    }


    // Check if date is within the range of a *currently being selected* membership (priority 4)
    for (const startDate of selectedDates) {
        const endDate = calculateEndDate(startDate);
        const start = new Date(startDate);
        const end = new Date(endDate);

        const adjStart = new Date(start.getTime() + start.getTimezoneOffset() * 60000);
        const adjEnd = new Date(end.getTime() + end.getTimezoneOffset() * 60000);

        // Skip if this is the startDate we're checking (allow toggling off)
        if (dateStr === startDate) {
            continue;
        }

        if (adjustedCheckDate >= adjStart && adjustedCheckDate <= adjEnd) {
            return { status: 'selected-range' }; // Date is within a selected membership duration
        }
    }

    return null; // Date is available
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
            // Also specifically allow overlap with 'in-cart' items IF this is the *same* item's range we are checking against (this is complex and might not be needed if in-cart dates are handled by clicking them)
            if (statusInfo && statusInfo.status && statusInfo.status !== 'selected-range' /* && statusInfo.status !== 'in-cart-membership' && statusInfo.status !== 'in-cart-walkin' */) { // Removed temporary in-cart exclusion as clicking handles it
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

        // Handle conflicts that should show the modal
        if (status === 'pending-membership' || status === 'pending-walkin' || status === 'in-cart-membership' || status === 'in-cart-walkin') {
             handleConflictClick(dateStr, statusInfo); // Unified handler for all clickable conflicts
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

// Unified handler for any clickable conflict date
function handleConflictClick(dateString, statusInfo) {
    console.log("Conflict clicked:", dateString, statusInfo);

    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement) || new bootstrap.Modal(conflictModalElement);

    // Reset all modal buttons to hidden initially
    document.getElementById('appendMembershipBtn').style.display = 'none';
    document.getElementById('replaceMembershipBtn').style.display = 'none';
    document.getElementById('replaceInCartBtn').style.display = 'none';
    document.getElementById('appendToCartBtn').style.display = 'none';
    document.getElementById('replaceInCartWalkinBtn').style.display = 'none'; // New
    document.getElementById('appendToCartWalkinBtn').style.display = 'none'; // New


    // Store data needed for modal actions
    modalConflictData = {
        type: statusInfo.status, // e.g., 'pending-membership', 'pending-walkin', 'in-cart-membership', 'in-cart-walkin'
        clickedDate: dateString, // The date the user clicked
        details: statusInfo // Contains other details like transactionId, endDate, cartItem etc.
    };

    // Configure modal based on conflict type
    switch(statusInfo.status) {
        case 'pending-membership':
            document.getElementById('conflictModalLabel').innerText = 'Membership Conflict Detected';
            document.getElementById('conflictMessage').innerText =
                `This date conflicts with a pending membership ending ${formatDisplayDate(statusInfo.endDate)}.\n\n` +
                `Choose an action:`;
            document.getElementById('appendMembershipBtn').style.display = '';
            document.getElementById('replaceMembershipBtn').style.display = '';
            document.getElementById('replaceMembershipBtn').innerText = 'Replace Pending'; // Reset text
            document.getElementById('replaceMembershipBtn').classList.remove('btn-warning');
            document.getElementById('replaceMembershipBtn').classList.add('btn-danger');
            break;
        case 'pending-walkin':
            document.getElementById('conflictModalLabel').innerText = 'Walk-in Conflict Detected';
            document.getElementById('conflictMessage').innerText =
                `This date has a pending walk-in reservation (${formatDisplayDate(dateString)}).\n\n` +
                `Do you want to remove the pending walk-in to book this membership?`;
            document.getElementById('replaceMembershipBtn').style.display = '';
            document.getElementById('replaceMembershipBtn').innerText = 'Remove Pending Walk-in'; // Changed text
            document.getElementById('replaceMembershipBtn').classList.remove('btn-danger');
            document.getElementById('replaceMembershipBtn').classList.add('btn-warning');
            break;
        case 'in-cart-membership':
             if (!statusInfo.cartItem || !statusInfo.cartItem.start_date || !statusInfo.cartItem.end_date || statusInfo.cartItem.originalIndex === undefined) {
                alert("Error: Missing details for the membership in the cart. Cannot proceed.");
                console.error("Incomplete cartItem data:", statusInfo.cartItem);
                modalConflictData = null; // Clear data
                return;
             }
            document.getElementById('conflictModalLabel').innerText = 'Membership In Cart';
            document.getElementById('conflictMessage').innerText =
                `A membership (${statusInfo.cartItem.plan_name || 'Membership'}) starting ${formatDisplayDate(statusInfo.cartItem.start_date)} and ending ${formatDisplayDate(statusInfo.cartItem.end_date)} is already in your cart.\n\n` +
                `What would you like to do with the *new* membership selection?`;
            document.getElementById('replaceInCartBtn').style.display = 'inline-block';
            document.getElementById('appendToCartBtn').style.display = 'inline-block';
            document.getElementById('appendToCartBtn').innerText = 'Add After In-Cart'; // Changed text for membership
            break;
        case 'in-cart-walkin': // *** NEW CASE ***
             if (!statusInfo.cartItem || statusInfo.cartItem.originalIndex === undefined || !statusInfo.cartItem.date) {
                alert("Error: Missing details for the walk-in in the cart. Cannot proceed.");
                console.error("Incomplete cartItem data:", statusInfo.cartItem);
                modalConflictData = null; // Clear data
                return;
             }
            document.getElementById('conflictModalLabel').innerText = 'Walk-in In Cart'; // Updated Title
            document.getElementById('conflictMessage').innerText =
                `A walk-in reservation for ${formatDisplayDate(statusInfo.cartItem.date)} is already in your cart.\n\n` +
                `What would you like to do with the *new* membership selection?`; // Message adjusted for walk-in
            document.getElementById('replaceInCartWalkinBtn').style.display = 'inline-block'; // Show New Replace button
            document.getElementById('appendToCartWalkinBtn').style.display = 'inline-block'; // Show New Append button
            break;
    }

    conflictModal.show();
}


// Function to handle "Replace Pending Membership / Remove Walk-in" action
async function handleReplaceOrRemovePending() {
     if (!modalConflictData || !modalConflictData.type || !modalConflictData.details) {
         alert('Error: Missing conflict data. Please try again.');
         return;
     }

     const { type, details, clickedDate } = modalConflictData;

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    if (conflictModal) conflictModal.hide();


     if (type === 'pending-membership') {
         if (!details.transactionId) {
             alert('Error: Cannot identify the pending membership. Please try again.');
             return;
         }
         console.log(`User chose to replace pending membership transaction ID: ${details.transactionId}`);
         // Call backend to delete the pending transaction
         try {
             const formData = new FormData();
             formData.append('transactionId', details.transactionId);

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

     } else if (type === 'pending-walkin') {
          if (!details.transactionId) {
              alert('Error: Cannot identify the pending walk-in. Please try again.');
              return;
          }
         console.log(`User chose to remove pending walk-in transaction ID: ${details.transactionId}`);

         // Call backend to delete the pending walk-in transaction
         try {
             const formData = new FormData();
             formData.append('transactionId', details.transactionId);

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
         }
     }
}

// Function to handle "Append After Pending Membership" action
async function handleAppendAfterPending() {
     if (!modalConflictData || modalConflictData.type !== 'pending-membership' || !modalConflictData.details || !modalConflictData.details.endDate) {
         alert('Error: Missing conflict data. Please try again.');
         return;
     }

    const { endDate: pendingEndDateStr } = modalConflictData.details;

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    if (conflictModal) conflictModal.hide();


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

// --- NEW FUNCTION: Handle "Replace In Cart Membership" button click ---
async function handleReplaceInCartMembership() {
     if (!modalConflictData || modalConflictData.type !== 'in-cart-membership' || !modalConflictData.details || !modalConflictData.details.cartItem || modalConflictData.details.cartItem.originalIndex === undefined || !modalConflictData.clickedDate) {
        alert('Error: Could not identify the in-cart membership or the intended date to replace. Please try again.');
        console.error("Missing data for replace in-cart membership operation:", modalConflictData);
        return;
    }

    const cartItemIndex = modalConflictData.details.cartItem.originalIndex;
    const dateToAdd = modalConflictData.clickedDate; // Get the date to add
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement);

    console.log(`User chose to replace in-cart membership item at index: ${cartItemIndex} with new one starting ${dateToAdd}.`);

    // Ensure modal exists before trying to hide
    if (conflictModal) {
        conflictModal.hide(); // Hide modal immediately
    } else {
        console.warn("Could not get modal instance to hide.");
    }


    // 1. Remove the item from the cart via cart_handler.php
    try {
        const removeFormData = new FormData(); // Use separate FormData for removal
        removeFormData.append('action', 'remove');
        removeFormData.append('type', 'membership'); // Specify type as membership
        removeFormData.append('index', cartItemIndex);

        const removeResponse = await fetch('cart_handler.php', {
            method: 'POST',
            body: removeFormData
        });
        const removeResult = await removeResponse.json();

        if (!removeResponse.ok || !removeResult.success) {
            throw new Error(removeResult.data?.message || 'Failed to remove item from cart.');
        }

        console.log('Successfully removed item from cart.');
        // Optional: Provide user feedback
        // alert('Item removed from cart. Now adding the new selection.');

        // 2. Add the *new* selection by calling validateForm with the specific date
        //    Pass the date explicitly as the second argument.
        console.log(`Proceeding to add new membership starting ${dateToAdd}`);
        // Create a dummy event object if needed, or just null if your validateForm doesn't rely on event details heavily
        await validateForm(new Event('submit'), [dateToAdd]); // Pass date here


    } catch (error) {
        console.error('Error replacing item in cart:', error);
        alert(`An error occurred while replacing the item: ${error.message}`);
        // Refresh state even on error to reflect potential partial changes (like removal failing)
        cartItems = await fetchCartItems(); // Refresh cart items
        await fetchDisabledDates(); // Refresh disabled dates
        renderCalendar();
    } finally {
        modalConflictData = null; // Clear modal data
    }
}


// --- NEW FUNCTION: Handle "Append New Membership to Cart" (when conflicting with in-cart membership) ---
async function handleAppendMembershipToCart() {
     // Check if modal data and cartItem with end_date exist and is a membership
     if (!modalConflictData || modalConflictData.type !== 'in-cart-membership' || !modalConflictData.details || !modalConflictData.details.cartItem || !modalConflictData.details.cartItem.end_date) {
        alert("Error: Cannot identify the existing in-cart membership details to append after.");
        console.error("Missing data for append in-cart membership operation:", modalConflictData);
        return;
    }

    const cartItemEndDateStr = modalConflictData.details.cartItem.end_date;
    const cartItemPlanName = modalConflictData.details.cartItem.plan_name || 'Membership'; // Get plan name for messages

    // --- Calculate the day *after* the in-cart membership ends ---
    const cartItemEndDate = new Date(cartItemEndDateStr);
    const userTimezoneOffset = cartItemEndDate.getTimezoneOffset() * 60000; // Get offset from end date
    const appendStartDate = new Date(cartItemEndDate.getTime() + userTimezoneOffset); // Adjust to UTC midnight

    appendStartDate.setDate(appendStartDate.getDate() + 1); // Add one day
    const appendStartDateStr = formatDateYMD(appendStartDate); // Format asYYYY-MM-DD

    console.log(`User chose to append after in-cart membership. Existing item ends: ${cartItemEndDateStr}. Calculated append start date: ${appendStartDateStr}`);

    // --- Perform validation checks on the calculated appendStartDateStr ---
    const appendStatusInfo = getDateStatusInfo(appendStartDateStr);
    if (appendStatusInfo && appendStatusInfo.status) {
         alert(`Cannot append: The day after the in-cart membership (${formatDisplayDate(appendStartDateStr)}) is also unavailable (Status: ${appendStatusInfo.status}).`);
         modalConflictData = null; // Clear data
         return; // Stop processing
    }
    if (wouldCreateOverlap(appendStartDateStr)) {
        alert(`Cannot append: The membership period starting the day after the in-cart one (${formatDisplayDate(appendStartDateStr)}) would overlap with other reservations or selections.`);
        modalConflictData = null; // Clear data
        return; // Stop processing
    }
    // Although unlikely, check if this exact calculated date is already selected locally
    if (selectedDates.includes(appendStartDateStr)) {
         alert(`Cannot append: The date ${formatDisplayDate(appendStartDateStr)} is already selected locally.`);
         modalConflictData = null; // Clear data
        return;
    }
    // --- End Validation Checks ---


    // If all checks pass, proceed to add using the calculated start date
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement);
    // Ensure modal exists before trying to hide
    if (conflictModal) {
        conflictModal.hide(); // Hide modal
    }

    console.log(`Proceeding to add new membership starting ${appendStartDateStr} (after in-cart membership).`);

    // Call validateForm with ONLY the calculated appendStartDateStr
    await validateForm(new Event('submit'), [appendStartDateStr]); // Pass calculated date

    modalConflictData = null; // Clear modal data after processing
}

// --- NEW FUNCTION: Handle "Replace In Cart Walk-in" button click ---
async function handleReplaceInCartWalkin() {
     if (!modalConflictData || modalConflictData.type !== 'in-cart-walkin' || !modalConflictData.details || !modalConflictData.details.cartItem || modalConflictData.details.cartItem.originalIndex === undefined || !modalConflictData.clickedDate) {
        alert('Error: Could not identify the in-cart walk-in or the intended date to replace. Please try again.');
        console.error("Missing data for replace in-cart walk-in operation:", modalConflictData);
        return;
    }

    const cartItemIndex = modalConflictData.details.cartItem.originalIndex;
    const dateToAdd = modalConflictData.clickedDate; // Get the date of the new membership to add
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement);

    console.log(`User chose to replace in-cart walk-in item at index: ${cartItemIndex} with new membership starting ${dateToAdd}.`);

    // Ensure modal exists before trying to hide
    if (conflictModal) {
        conflictModal.hide(); // Hide modal immediately
    } else {
        console.warn("Could not get modal instance to hide.");
    }


    // 1. Remove the walk-in item from the cart via cart_handler.php
    try {
        const removeFormData = new FormData(); // Use separate FormData for removal
        removeFormData.append('action', 'remove');
        removeFormData.append('type', 'walkin'); // Specify type as walkin
        removeFormData.append('index', cartItemIndex);

        const removeResponse = await fetch('cart_handler.php', {
            method: 'POST',
            body: removeFormData
        });
        const removeResult = await removeResponse.json();

        if (!removeResponse.ok || !removeResult.success) {
            throw new Error(removeResult.data?.message || 'Failed to remove walk-in from cart.');
        }

        console.log('Successfully removed walk-in item from cart.');
        // Optional: Provide user feedback
        // alert('Walk-in item removed from cart. Now adding the new membership selection.');

        // 2. Add the *new* membership selection by calling validateForm with the specific date
        //    Pass the date explicitly as the second argument.
        console.log(`Proceeding to add new membership starting ${dateToAdd}`);
        await validateForm(new Event('submit'), [dateToAdd]); // Pass date here


    } catch (error) {
        console.error('Error replacing walk-in item in cart:', error);
        alert(`An error occurred while replacing the walk-in item: ${error.message}`);
        // Refresh state even on error to reflect potential partial changes (like removal failing)
        cartItems = await fetchCartItems(); // Refresh cart items
        await fetchDisabledDates(); // Refresh disabled dates
        renderCalendar();
    } finally {
        modalConflictData = null; // Clear modal data
    }
}


// --- NEW FUNCTION: Handle "Add New Membership After In-Cart Walk-in" button click ---
async function handleAppendMembershipAfterWalkin() {
     // Check if modal data and cartItem exist and is a walkin
     if (!modalConflictData || modalConflictData.type !== 'in-cart-walkin' || !modalConflictData.details || !modalConflictData.details.cartItem || !modalConflictData.details.cartItem.date) {
        alert("Error: Cannot identify the existing in-cart walk-in details to append after.");
        console.error("Missing data for append after in-cart walk-in operation:", modalConflictData);
        return;
    }

    const cartItemDateStr = modalConflictData.details.cartItem.date;
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement);

    console.log(`User chose to append after in-cart walk-in on: ${cartItemDateStr}`);

    // Calculate the day *after* the in-cart walk-in date
    const cartItemDate = new Date(cartItemDateStr);
     const userTimezoneOffset = cartItemDate.getTimezoneOffset() * 60000;
    const appendStartDate = new Date(cartItemDate.getTime() + userTimezoneOffset);


    appendStartDate.setDate(appendStartDate.getDate() + 1); // Add one day
    const appendStartDateStr = formatDateYMD(appendStartDate); // Format asYYYY-MM-DD

    console.log(`Calculated append start date: ${appendStartDateStr}`);

    // --- Perform validation checks on the calculated appendStartDateStr ---
    const appendStatusInfo = getDateStatusInfo(appendStartDateStr);
    if (appendStatusInfo && appendStatusInfo.status) {
         alert(`Cannot append: The day after the in-cart walk-in (${formatDisplayDate(appendStartDateStr)}) is also unavailable (Status: ${appendStatusInfo.status}).`);
         modalConflictData = null; // Clear data
         return; // Stop processing
    }
    if (wouldCreateOverlap(appendStartDateStr)) {
        alert(`Cannot append: The membership period starting the day after the in-cart walk-in (${formatDisplayDate(appendStartDateStr)}) would overlap with other reservations or selections.`);
        modalConflictData = null; // Clear data
        return; // Stop processing
    }
    // Although unlikely, check if this exact calculated date is already selected locally
    if (selectedDates.includes(appendStartDateStr)) {
         alert(`Cannot append: The date ${formatDisplayDate(appendStartDateStr)} is already selected locally.`);
         modalConflictData = null; // Clear data
        return;
    }
    // --- End Validation Checks ---


    // If all checks pass, proceed to add using the calculated start date
    // Ensure modal exists before trying to hide
    if (conflictModal) {
        conflictModal.hide(); // Hide modal
    }

    console.log(`Proceeding to add new membership starting ${appendStartDateStr} (after in-cart walk-in).`);

    // Call validateForm with ONLY the calculated appendStartDateStr
    await validateForm(new Event('submit'), [appendStartDateStr]); // Pass calculated date

    modalConflictData = null; // Clear modal data after processing
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
async function validateForm(event, datesToSubmit = null) {
    event.preventDefault(); // Prevent default form submission

    // Use datesToSubmit if provided, otherwise use the global selectedDates
    const finalDates = datesToSubmit !== null ? datesToSubmit : selectedDates;

    if (finalDates.length === 0) {
        alert('Please select at least one start date for your membership.');
        return false; // Stop submission
    }

    const form = document.querySelector('form[onsubmit="return validateForm(event)"]'); // More robust selector
    if (!form) {
        console.error("Could not find the form!");
        alert("An internal error occurred (Form not found).");
        return false;
    }
    const formData = new FormData(form);


    // Ensure the hidden input and FormData use the correct dates for submission
    const datesJson = JSON.stringify(finalDates);
    document.getElementById('hidden_selected_dates').value = datesJson;
    formData.set('selected_dates', datesJson); // Explicitly set it for FormData


    // Display loading state (optional)
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'; // Changed text


    // Use await for fetch to handle async operations cleanly
    try {
        const response = await fetch(window.location.href, { // Post to the same page (avail_membership.php)
            method: 'POST',
            body: formData
        });

        // Check if response is JSON, otherwise handle potential HTML errors
        const contentType = response.headers.get("content-type");
        let result;
        if (contentType && contentType.includes("application/json")) {
            result = await response.json();
        } else {
            const text = await response.text();
            throw new Error(`Unexpected response format: ${text.substring(0, 200)}...`);
        }

        if (response.ok && result.success) {
            // Success: Redirect or show success message
             // --- Refresh state AFTER successful add/replace ---
             console.log('Action successful, refreshing state...');
             cartItems = await fetchCartItems(); // Refetch cart items
             await fetchDisabledDates(); // Refetch disabled dates
             selectedDates = []; // Clear local selection after successful submission
             updateSelectedDatesUI(); // Update UI (clear chips)
             renderCalendar(); // Re-render calendar

             if (result.redirect) {
                 window.location.href = result.redirect;
             } else {
                 console.log("Success, but no redirect URL provided. Calendar refreshed.");
             }
        } else {
             // Handle failure: Show specific error message from server if available
             alert(result.message || `Failed processing request (Status: ${response.status})`);
             // Re-render calendar even on failure to reflect current state
             renderCalendar();
        }

    } catch (error) {
        console.error('Error submitting form:', error);
        alert(`An error occurred: ${error.message}. Please check the console and try again.`);
        // Optionally re-render calendar on catch
        renderCalendar();
    } finally {
         // Restore button state
         submitButton.disabled = false;
         submitButton.innerHTML = originalButtonText || 'Add to Cart'; // Restore original or default
    }


    return false; // Prevent default form submission behaviour in all cases
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

    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    const daysContainer = document.getElementById('calendar-days');
    daysContainer.innerHTML = '';

    for (let i = 0; i < firstDayOfMonth; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        daysContainer.appendChild(emptyDay);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.textContent = day;
        const dateString = formatDateString(currentYear, currentMonth, day);
        const statusInfo = getDateStatusInfo(dateString); // This now checks in-cart status too

        let statusClass = '';
        let isDisabled = false;
        let isClickableConflict = false;
        let clickHandler = () => addDate(dateString); // Default handler

        if (statusInfo && statusInfo.status) {
            statusClass = statusInfo.status;

            // Determine if the day should be visually disabled or just styled
            switch(statusClass) {
                case 'past':
                case 'active-membership':
                case 'selected-range': // Non-start days within a selected range are disabled
                    isDisabled = !selectedDates.includes(dateString); // Allow clicking start date to deselect
                    break;
                case 'pending-membership':
                case 'pending-walkin':
                case 'in-cart-membership':
                case 'in-cart-walkin': // *** NEW ***
                    isClickableConflict = true; // These trigger the conflict modal via handleConflictClick
                    // Use the unified conflict handler for all clickable conflicts
                    clickHandler = () => handleConflictClick(dateString, statusInfo);
                    break;
                 // 'selected' status is handled below
            }
        }

        dayElement.className = `calendar-day ${statusClass}`;

        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
            isDisabled = false; // Ensure selected start dates are always clickable to deselect
            isClickableConflict = false; // Selecting overrides other conflicts for the click action
            clickHandler = () => addDate(dateString); // Ensure it calls addDate for deselect
        }

        if (isDisabled) {
            dayElement.classList.add('disabled');
        } else {
            // Add click listener only if not disabled
             // Use the appropriate handler determined above
            dayElement.addEventListener('click', clickHandler);

            // Add tooltip for clickable conflict days
            let title = '';
            if (isClickableConflict) {
                 switch(statusClass) {
                     case 'pending-membership': title = `Conflicts with pending membership ending ${formatDisplayDate(statusInfo.endDate)}. Click to resolve.`; break;
                     case 'pending-walkin': title = `Conflicts with pending walk-in on ${formatDisplayDate(dateString)}. Click to resolve.`; break;
                     case 'in-cart-membership': title = `Membership for this date is in your cart (Starts: ${formatDisplayDate(statusInfo.cartItem.start_date)}). Click to manage.`; break;
                      case 'in-cart-walkin': title = `Walk-in for this date is in your cart (${formatDisplayDate(statusInfo.cartItem.date)}). Click to manage.`; break; // *** NEW ***
                 }
                 dayElement.setAttribute('title', title);
            } else if (selectedDates.includes(dateString)) {
                 dayElement.setAttribute('title', 'Click to deselect start date');
            } else if (!statusInfo) {
                 dayElement.setAttribute('title', 'Click to select start date');
            }
        }

        daysContainer.appendChild(dayElement);
    }
    updateDisabledDateRanges();
}


function renderCalendarLegend() {
    // Create legend container
    const legendContainer = document.createElement('div');
    legendContainer.className = 'calendar-legend mt-3';
    // *** ADD 'In Cart Walk-in' legend item ***
    legendContainer.innerHTML = `
        <div class="d-flex flex-wrap justify-content-start">
            <div class="legend-item me-3 mb-1">
                <span class="legend-color selected" style="border: 1px solid #ccc;"></span> <span>Selected Start Date</span>
            </div>
             <div class="legend-item me-3 mb-1">
                <span class="legend-color in-cart-membership"></span> <span>In Cart Membership</span>
            </div>
             <div class="legend-item me-3 mb-1">
                <span class="legend-color in-cart-walkin"></span> <span>In Cart Walk-in</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color pending-membership"></span>
                <span>Pending Membership</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color active-membership"></span>
                <span>Active Membership</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color pending-walkin"></span>
                <span>Pending Walk-in</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color past"></span>
                <span>Past/Unavailable</span>
            </div>
        </div>
         <style>
             /* Add styles for legend colors if not already defined */
            .legend-color { display: inline-block; width: 16px; height: 16px; margin-right: 5px; border-radius: 3px; vertical-align: middle; border: 1px solid #ccc;} /* Added border here */
            /* Ensure legend item text is aligned */
            .legend-item span:last-child { vertical-align: middle; }

             .legend-color.in-cart-walkin { /* *** NEW *** */
                border: 2px dashed var(--secondary-color, #6c757d); /* Use dashed border */
                background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1); /* Use light background */
            }
             .legend-color.selected { /* Ensure selected has solid border in legend */
                 border: 1px solid var(--primary-color);
             }


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
    const primaryColorHex = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
    // -- Add Secondary Color RGB definition ---
    const secondaryColorHex = getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim();


    // Function to convert hex to RGB
    function hexToRgb(hex) {
        let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    const primaryRgb = hexToRgb(primaryColorHex);
    if (primaryRgb) {
        document.documentElement.style.setProperty('--primary-color-rgb', `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
        console.log("Set --primary-color-rgb:", `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
    } else {
         console.error("Could not parse primary color:", primaryColorHex);
         document.documentElement.style.setProperty('--primary-color-rgb', '255, 0, 0'); // Default red
    }
    // *** SET SECONDARY RGB VARIABLE ***
    const secondaryRgb = hexToRgb(secondaryColorHex);
    if (secondaryRgb) {
        document.documentElement.style.setProperty('--secondary-color-rgb', `${secondaryRgb.r}, ${secondaryRgb.g}, ${secondaryRgb.b}`);
        console.log("Set --secondary-color-rgb:", `${secondaryRgb.r}, ${secondaryRgb.g}, ${secondaryRgb.b}`);
    } else {
         console.warn("Could not parse secondary color:", secondaryColorHex);
         document.documentElement.style.setProperty('--secondary-color-rgb', '108, 117, 125'); // Default gray
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
window.onload = async function() {
    renderCalendarWeekdays();
    renderCalendarLegend();

    // Set initial hidden dates value
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // *** Fetch disabled dates AND cart items concurrently ***
    try {
        console.log("Fetching initial data...");
        // Use Promise.all to wait for both fetch calls
        const [disabledResult, cartResult] = await Promise.all([
            fetchDisabledDates(), // Existing function (updates global disabledDates)
            fetchCartItems() // Modified function (returns all cart items)
        ]);

        // Store fetched cart data globally
        cartItems = cartResult;
        console.log("Initial data fetched.");

    } catch (error) {
        console.error("Error fetching initial calendar data:", error);
        alert("Failed to load all calendar data. Some information might be missing.");
        // Assign empty array if cart fetch failed but disabled fetch might have succeeded
        cartItems = cartItems || [];
    }

    // Initial calendar render (now uses fetched disabledDates AND cartItems)
    renderCalendar(); // Render after data is available

    setupCalendarNavigation();

    // --- Setup ALL modal button event listeners ---
    // Existing buttons for pending conflicts (repurposed replaceMembershipBtn)
    document.getElementById('replaceMembershipBtn').addEventListener('click', handleReplaceOrRemovePending);
    document.getElementById('appendMembershipBtn').addEventListener('click', handleAppendAfterPending);

    // Buttons for In Cart Memberships
    document.getElementById('replaceInCartBtn').addEventListener('click', handleReplaceInCartMembership);
    document.getElementById('appendToCartBtn').addEventListener('click', handleAppendMembershipToCart); // Appends Membership

    // *** New buttons for In Cart Walk-ins ***
    document.getElementById('replaceInCartWalkinBtn').addEventListener('click', handleReplaceInCartWalkin); // Replaces Walk-in with Membership
    document.getElementById('appendToCartWalkinBtn').addEventListener('click', handleAppendMembershipAfterWalkin); // Appends Membership after Walk-in


    // Optional: Reset modal buttons on modal close to prevent wrong buttons showing briefly
    const conflictModalElement = document.getElementById('conflictModal');
    conflictModalElement.addEventListener('hidden.bs.modal', function () {
        document.getElementById('replaceMembershipBtn').style.display = 'none';
        document.getElementById('appendMembershipBtn').style.display = 'none';
        document.getElementById('replaceInCartBtn').style.display = 'none';
        document.getElementById('appendToCartBtn').style.display = 'none';
        document.getElementById('replaceInCartWalkinBtn').style.display = 'none'; // New
        document.getElementById('appendToCartWalkinBtn').style.display = 'none'; // New

        // Reset modal title/text if needed
        document.getElementById('conflictModalLabel').innerText = 'Conflict Detected';
        document.getElementById('conflictMessage').innerText = '';
        modalConflictData = null; // Clear data on close
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