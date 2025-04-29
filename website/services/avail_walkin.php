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

    /* Use --primary-color-rgb for hover transparency */
    .calendar-day:not(.disabled):not(.empty):hover {
        background-color: rgba(var(--primary-color-rgb), 0.2);
    }

    .calendar-day.empty {
        visibility: hidden;
    }

    /* Hide the original date input */
    #selected_date { /* Updated ID to match avail_walkin.php */
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

    /* No selected-range style needed for walk-ins as they are single day */
    /* Remove or adjust if walk-ins have duration that needs visual range indication */
    /* .calendar-day.selected-range {
        background-color: rgba(var(--primary-color-rgb), 0.3);
        color: var(--primary-color);
    } */


    /* Calendar legend styles */
    .calendar-legend {
        border-top: 1px solid #dee2e6;
        padding-top: 10px;
        margin-top: 10px; /* Added margin-top */
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

    .legend-color.past { /* Added style for past in legend */
        background-color: #f5f5f5;
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

<!-- conflict modal -->
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
        <button type="button" class="btn btn-danger" id="replaceMembershipBtn">Replace Pending</button>
        <button type="button" class="btn btn-warning" id="appendMembershipBtn" style="display: none;">Append After Pending</button>
        <button type="button" class="btn btn-danger" id="replaceInCartWalkinBtn" style="display: none;">Replace In Cart Walk-in</button>
        <button type="button" class="btn btn-primary" id="appendToCartWalkinBtn" style="display: none;">Add After In-Cart Walk-in</button>
    </div>
    </div>
  </div>
</div>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Array to store selected dates
let selectedDates = [];
// disabledDates will store objects from the server: { status: '...', endDate: '...', transactionId: ... }
let disabledDates = {};
let cartItems = []; // Added: Array to hold all cart items (memberships and walk-ins)

const price = <?= $price ?>; // Price for a single walk-in visit
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

// Store data for the modal
let modalConflictData = null;

// Function to fetch all cart items (memberships and walk-ins) - Added
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
    return fetch(url)
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
    const userTimezoneOffset = date.getTimezoneOffset() * 60000;
    const adjustedDate = new Date(date.getTime() + userTimezoneOffset);

    return adjustedDate.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Calculate end date for a walk-in (always the same as the start date as it's a single visit)
function calculateEndDate(startDate) {
    // For a walk-in, the end date is the same as the start date.
    return new Date(startDate);
}

// Format date to YYYY-MM-DD
function formatDateYMD(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Check if a date is disabled and get its status object - Updated for In-Cart items
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

    // Check if date is in disabledDates object from server (pending/active memberships/walkins)
    if (disabledDates && disabledDates[dateStr]) {
        console.log(`Date ${dateStr} has disabled info:`, disabledDates[dateStr]); // Debug
        return disabledDates[dateStr]; // Returns the object like { status: 'pending-membership', endDate: '...', transactionId: ... } or { status: 'active-membership' } or { status: 'pending-walkin', transactionId: ... }
    }

     // Check if date falls within any IN-CART item range (priority 3) - Added
    if (cartItems && cartItems.length > 0) {
        for (const cartItem of cartItems) {
             // Walk-ins are single day, memberships have ranges
             // Adjust checkDate for timezone before comparison
             const userTimezoneOffset = checkDate.getTimezoneOffset() * 60000;
             const adjustedCheckDate = new Date(checkDate.getTime() + userTimezoneOffset);


             if (cartItem.type === 'walkin' && cartItem.date) {
                 if (formatDateYMD(new Date(cartItem.date)) === dateStr) {
                     console.log(`Date ${dateStr} is an in-cart walk-in:`, cartItem);
                     return { status: 'in-cart-walkin', cartItem: cartItem };
                 }
             } else if (cartItem.type === 'membership' && cartItem.start_date && cartItem.end_date) {
                const cartStart = new Date(cartItem.start_date);
                const cartEnd = new Date(cartItem.end_date);
                 // Adjust cart item dates for timezone
                 const adjCartStart = new Date(cartStart.getTime() + cartStart.getTimezoneOffset() * 60000);
                 const adjCartEnd = new Date(cartEnd.getTime() + cartEnd.getTimezoneOffset() * 60000);


                if (adjustedCheckDate >= adjCartStart && adjustedCheckDate <= adjCartEnd) {
                     console.log(`Date ${dateStr} is within in-cart membership:`, cartItem);
                    return { status: 'in-cart-membership', cartItem: cartItem };
                }
            }
        }
    }


    // For walk-ins, a selected date marks that single day as taken.
    // No need to check for selected-ranges like memberships.

    return null; // Date is not disabled by external factors, selected walk-ins, or in-cart items
}


// Add date to the selection - Updated for In-Cart conflicts
async function addDate(dateStr) { // Make async to handle await for fetch
    if (!dateStr) {
        return;
    }

    // Check if date is already selected (toggle off)
    if (selectedDates.includes(dateStr)) {
        removeDate(dateStr);
        return;
    }

    // Check if date is disabled or conflicts with existing/in-cart items
    const statusInfo = getDateStatusInfo(dateStr);

    if (statusInfo && statusInfo.status) {
        const status = statusInfo.status;

        // Handle conflicts that should show the modal - Updated
        if (status === 'pending-membership' || status === 'pending-walkin' || status === 'in-cart-membership' || status === 'in-cart-walkin') {
             handleConflictClick(dateStr, statusInfo); // Unified handler for all clickable conflicts
             return; // Stop further processing here, wait for modal interaction
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
             // pending-walkin and pending-membership cases are now handled by handleConflictClick
            default:
                message = 'This date is unavailable.';
        }
        alert(message);
        return;
    }

    // If checks pass, add date to array
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


// Unified handler for any clickable conflict date - Added and MODIFIED for 'in-cart-walkin'
function handleConflictClick(dateString, statusInfo) {
    console.log("Conflict clicked:", dateString, statusInfo);

    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement) || new bootstrap.Modal(conflictModalElement);

    // Get modal buttons
    const replaceMembershipBtn = document.getElementById('replaceMembershipBtn');
    const appendMembershipBtn = document.getElementById('appendMembershipBtn');
    const replaceInCartWalkinBtn = document.getElementById('replaceInCartWalkinBtn'); // This will be used for removal
    const appendToCartWalkinBtn = document.getElementById('appendToCartWalkinBtn');

    // Reset all modal buttons to hidden initially
    if (replaceMembershipBtn) replaceMembershipBtn.style.display = 'none';
    if (appendMembershipBtn) appendMembershipBtn.style.display = 'none';
    if (replaceInCartWalkinBtn) replaceInCartWalkinBtn.style.display = 'none';
    if (appendToCartWalkinBtn) appendToCartWalkinBtn.style.display = 'none';


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
                `Do you want to replace this pending membership with a walk-in on this date?`;
             if (replaceMembershipBtn) {
                replaceMembershipBtn.style.display = '';
                replaceMembershipBtn.innerText = 'Replace Pending Membership with Walk-in';
                replaceMembershipBtn.classList.remove('btn-danger');
                replaceMembershipBtn.classList.add('btn-warning');
             }
            break;
        case 'pending-walkin':
            document.getElementById('conflictModalLabel').innerText = 'Walk-in Conflict Detected';
            document.getElementById('conflictMessage').innerText =
                `This date already has a pending walk-in reservation (${formatDisplayDate(dateString)}).\n\n` +
                `Do you want to replace the pending walk-in reservation with a new one for this date?`;

             if (replaceMembershipBtn) { // Re-using replaceMembershipBtn for this
                replaceMembershipBtn.style.display = '';
                replaceMembershipBtn.innerText = 'Replace Pending Walk-in';
                replaceMembershipBtn.classList.remove('btn-danger');
                replaceMembershipBtn.classList.add('btn-warning');
             }
            break;
        case 'in-cart-membership':
             if (!statusInfo.cartItem || !statusInfo.cartItem.start_date || !statusInfo.cartItem.end_date || statusInfo.cartItem.originalIndex === undefined) {
                alert("Error: Missing details for the membership in the cart. Cannot proceed.");
                console.error("Incomplete cartItem data:", statusInfo.cartItem);
                modalConflictData = null;
                return;
             }
            document.getElementById('conflictModalLabel').innerText = 'Membership In Cart';
            document.getElementById('conflictMessage').innerText =
                `A membership (${statusInfo.cartItem.plan_name || 'Membership'}) starting ${formatDisplayDate(statusInfo.cartItem.start_date)} and ending ${formatDisplayDate(statusInfo.cartItem.end_date)} is already in your cart.\n\n` +
                `A walk-in cannot be added during an active membership period. Please select a different date.`;
            // No buttons shown for this conflict type on the walk-in page

            break;
        case 'in-cart-walkin': // MODIFIED
             if (!statusInfo.cartItem || statusInfo.cartItem.originalIndex === undefined || !statusInfo.cartItem.date) {
                alert("Error: Missing details for the walk-in in the cart. Cannot proceed.");
                console.error("Incomplete cartItem data:", statusInfo.cartItem);
                modalConflictData = null;
                return;
             }
            document.getElementById('conflictModalLabel').innerText = 'Walk-in In Cart'; // Updated Title
            document.getElementById('conflictMessage').innerText =
                `A walk-in reservation for ${formatDisplayDate(statusInfo.cartItem.date)} is already in your cart.\n\n` +
                `Do you want to remove this in-cart walk-in reservation?`; // MODIFIED message

             // Only show the "Remove Walk-in" button
             if (replaceInCartWalkinBtn) { // Re-using replaceInCartWalkinBtn
                 replaceInCartWalkinBtn.style.display = 'inline-block';
                 replaceInCartWalkinBtn.innerText = 'Remove Walk-in'; // MODIFIED button text
                 replaceInCartWalkinBtn.classList.remove('btn-primary', 'btn-warning'); // Remove other possible classes
                 replaceInCartWalkinBtn.classList.add('btn-danger'); // Use danger color for removal
             }
             // Hide all other buttons
             if (replaceMembershipBtn) replaceMembershipBtn.style.display = 'none';
             if (appendMembershipBtn) appendMembershipBtn.style.display = 'none';
             if (appendToCartWalkinBtn) appendToCartWalkinBtn.style.display = 'none';

            break;
    }

    conflictModal.show();
}


// Function to handle replacing a pending walk-in (repurposed 'replaceMembershipBtn') - Added
async function handleReplaceWalkin() {
    if (!modalConflictData || modalConflictData.type !== 'pending-walkin' || !modalConflictData.details || !modalConflictData.details.transactionId || !modalConflictData.clickedDate) {
         alert('Error: Missing walk-in conflict data. Please try again.');
         return;
    }

    const { transactionId, clickedDate } = modalConflictData.details; // Corrected to get from details and clickedDate

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    if (conflictModal) conflictModal.hide();

    console.log(`User chose to replace pending walk-in transaction ID: ${transactionId}`);

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

        if (!response.ok || !result.success) {
             throw new Error(result.message || `Failed to remove previous pending walk-in (Status: ${response.status})`);
        }

        alert('Previous pending walk-in reservation removed.');
        // Refresh disabled dates from server AFTER attempting to add the new date
        await fetchDisabledDates(); // Wait for refresh

        // Now, attempt to add the originally clicked date again, AFTER refreshing disabled dates
        // Check if still disabled for other reasons after deletion
         if(getDateStatusInfo(clickedDate)){
            alert('This date is still unavailable after attempting to remove the previous pending walk-in.');
            return;
        }
        // Proceed to add the date if no longer conflicting
        selectedDates.push(clickedDate);
        selectedDates.sort();
        document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
        updateSelectedDatesUI();
        renderCalendar(); // Re-render with the new selection


    } catch (error) {
        console.error('Error deleting pending walk-in:', error);
        alert(`An error occurred while trying to remove the pending walk-in: ${error.message}`);
        // Refresh state even on error
        cartItems = await fetchCartItems();
        await fetchDisabledDates();
        renderCalendar();

    } finally {
        modalConflictData = null; // Clear data after handling
         // Reset modal buttons and text for next time
         const replaceMembershipBtn = document.getElementById('replaceMembershipBtn');
         if(replaceMembershipBtn) {
             replaceMembershipBtn.innerText = 'Replace Pending';
             replaceMembershipBtn.classList.remove('btn-warning');
             replaceMembershipBtn.classList.add('btn-danger');
         }
         const appendMembershipBtn = document.getElementById('appendMembershipBtn');
         if(appendMembershipBtn) appendMembershipBtn.style.display = 'none';
    }
}


// Function to handle replacing a pending membership from walk-in page (repurposed 'replaceMembershipBtn') - Added
async function handleReplacePendingMembership() {
     if (!modalConflictData || modalConflictData.type !== 'pending-membership' || !modalConflictData.details || !modalConflictData.details.transactionId || !modalConflictData.clickedDate) {
         alert('Error: Missing conflict data. Please try again.');
         return;
     }

    const { transactionId, clickedDate } = modalConflictData.details; // Corrected to get from details and clickedDate

    // Close the modal
    const conflictModal = bootstrap.Modal.getInstance(document.getElementById('conflictModal'));
    if (conflictModal) conflictModal.hide();

    console.log(`User chose to replace pending membership transaction ID: ${transactionId} from walk-in page.`);

    // Call backend to delete the pending transaction
    try {
        const formData = new FormData();
        formData.append('transactionId', transactionId);

        const response = await fetch('date_functions/delete_pending_membership.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

         if (!response.ok || !result.success) {
             throw new Error(result.message || `Failed to remove pending membership (Status: ${response.status})`);
         }

        alert('Pending membership removed.');
        // Refresh disabled dates from server BEFORE attempting to add the new date
        await fetchDisabledDates(); // Wait for refresh

        // Now, attempt to add the originally clicked date again, AFTER refreshing disabled dates
        // Check if still disabled for other reasons after deletion
        if(getDateStatusInfo(clickedDate)){
            alert('This date is still unavailable after attempting to remove the pending membership.');
            return;
        }
        // Proceed to add the date if no longer conflicting
        selectedDates.push(clickedDate);
        selectedDates.sort();
        document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);
        updateSelectedDatesUI();
        renderCalendar(); // Re-render with the new selection


    } catch (error) {
        console.error('Error deleting pending membership:', error);
        alert(`An error occurred while trying to remove the pending membership: ${error.message}`);
         // Refresh state even on error
        cartItems = await fetchCartItems();
        await fetchDisabledDates();
        renderCalendar();
    } finally {
        modalConflictData = null; // Clear data after handling
         // Reset modal buttons and text for next time
        const replaceMembershipBtn = document.getElementById('replaceMembershipBtn');
         if(replaceMembershipBtn) {
             replaceMembershipBtn.innerText = 'Replace Pending';
             replaceMembershipBtn.classList.remove('btn-warning');
             replaceMembershipBtn.classList.add('btn-danger');
         }
        const appendMembershipBtn = document.getElementById('appendMembershipBtn');
        if(appendMembershipBtn) appendMembershipBtn.style.display = 'none';
    }
}


// --- MODIFIED FUNCTION: Handle "Remove In Cart Walk-in" button click ---
async function handleRemoveInCartWalkin() {
     if (!modalConflictData || modalConflictData.type !== 'in-cart-walkin' || !modalConflictData.details || !modalConflictData.details.cartItem || modalConflictData.details.cartItem.originalIndex === undefined) {
        alert('Error: Could not identify the in-cart walk-in to remove. Please try again.');
        console.error("Missing data for remove in-cart walk-in operation:", modalConflictData);
        return;
    }

    const cartItemIndex = modalConflictData.details.cartItem.originalIndex;
    const conflictModalElement = document.getElementById('conflictModal');
    const conflictModal = bootstrap.Modal.getInstance(conflictModalElement);

    console.log(`User chose to remove in-cart walk-in item at index: ${cartItemIndex}.`);

    // Ensure modal exists before trying to hide
    if (conflictModal) {
        conflictModal.hide(); // Hide modal immediately
    } else {
        console.warn("Could not get modal instance to hide.");
    }

    // 1. Remove the item from the cart via cart_handler.php
    try {
        const removeFormData = new FormData();
        removeFormData.append('action', 'remove');
        removeFormData.append('type', 'walkin'); // Specify type as walkin
        removeFormData.append('index', cartItemIndex); // Use the original index

        const removeResponse = await fetch('cart_handler.php', {
            method: 'POST',
            body: removeFormData
        });
        const removeResult = await removeResponse.json();

        if (!removeResponse.ok || !removeResult.success) {
            throw new Error(removeResult.data?.message || 'Failed to remove item from cart.');
        }

        console.log('Successfully removed item from cart. Preparing for reload.');

        // --- ADDED: Save state before reload ---
        sessionStorage.setItem('scrollPos', window.scrollY);
        sessionStorage.setItem('calendarMonth', currentMonth);
        sessionStorage.setItem('calendarYear', currentYear);
        // --------------------------------------

        // --- ADDED: Full browser reload on successful removal ---
        window.location.reload();
        // ------------------------------------------------------

    } catch (error) {
        console.error('Error removing item from cart:', error);
        alert(`An error occurred while trying to remove the walk-in from your cart: ${error.message}`);
        // If there was an error, the page won't reload, and the calendar won't refresh automatically.
        // You might consider adding a partial refresh or error handling here if needed.

    } finally {
        modalConflictData = null; // Clear modal data
         // Reset modal buttons and text for next time (though page will reload)
         const replaceInCartWalkinBtn = document.getElementById('replaceInCartWalkinBtn');
         if(replaceInCartWalkinBtn) {
             replaceInCartWalkinBtn.innerText = 'Replace In Cart Walk-in';
             replaceInCartWalkinBtn.classList.remove('btn-danger');
             replaceInCartWalkinBtn.classList.add('btn-primary');
         }
    }
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
    event.preventDefault(); // Prevent default form submission

    if (selectedDates.length === 0) {
        alert('Please select at least one date for your walk-in service.');
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


    fetch(window.location.href, { // Post to the same page (avail_walkin.php)
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
             // Refresh state after successful add/replace
             console.log('Action successful, refreshing state...');
             // No need to clear selectedDates locally for walk-in, as the form submit handles it server-side.
             // Just re-fetch data and re-render.
             selectedDates = []; // Clear selected dates on successful submission for walk-in (single add)
             cartItems = []; // Clear locally before refetching
             disabledDates = {}; // Clear locally before refetching
             fetchDisabledDates(); // Refetch disabled dates (will call renderCalendar)
             fetchCartItems(); // Refetch cart items
             updateSelectedDatesUI(); // Update UI to show no dates selected


             if (data.redirect) {
                 window.location.href = data.redirect;
             } else {
                 // Handle case where redirect URL isn't provided (e.g., refresh or update UI)
                 console.log("Success, but no redirect URL provided. Calendar refreshed.");
             }
        } else {
             // Handle failure: Show specific error message from server if available
             alert(data.message || `Failed to add to cart (Status: ${status})`);
             // Re-render calendar even on failure to reflect current state
             renderCalendar();
        }
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        alert(`An error occurred: ${error.message}. Please check the console and try again.`);
        // Optionally re-render calendar on catch
        renderCalendar();
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

// Render the calendar - Updated for In-Cart statuses
function renderCalendar() {
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
        const statusInfo = getDateStatusInfo(dateString); // This now checks in-cart status too
        let statusClass = '';
        let isDisabled = false;
        let isClickableConflict = false; // Added
        let clickHandler = () => addDate(dateString); // Default handler

        if (statusInfo && statusInfo.status) {
            statusClass = statusInfo.status;
             // Determine if the day should be visually disabled or trigger conflict modal
            switch(statusClass) {
                case 'past':
                case 'active-membership':
                    isDisabled = true; // These statuses disable clicking
                    break;
                 // No 'selected-range' for walk-ins
                case 'pending-membership':
                case 'pending-walkin':
                case 'in-cart-membership': // Added
                case 'in-cart-walkin': // Added
                    isClickableConflict = true; // These trigger the conflict modal via handleConflictClick
                    // Use the unified conflict handler for all clickable conflicts
                    clickHandler = () => handleConflictClick(dateString, statusInfo);
                    break;
                 // 'selected' status is handled below
            }
        }

        dayElement.className = `calendar-day ${statusClass}`;

        // Highlight selected dates explicitly
        if (selectedDates.includes(dateString)) {
            dayElement.classList.add('selected');
            isDisabled = false; // Ensure selected dates are clickable to deselect
            isClickableConflict = false; // Selecting overrides other conflicts for the click action
            clickHandler = () => addDate(dateString); // Ensure it calls addDate for deselect
        }

        // Add tooltip for clickable dates (conflicts or selectable) - Updated
        if (statusInfo && statusInfo.status && !selectedDates.includes(dateString)) {
             let title = 'Unavailable';
             switch(statusInfo.status) {
                 case 'past': title = 'Date is in the past'; break;
                 case 'pending-membership': title = `Conflicts with a pending membership ending ${formatDisplayDate(statusInfo.endDate)}. Click to resolve.`; break;
                 case 'active-membership': title = 'Conflicts with an active membership'; break;
                 case 'pending-walkin': title = 'Conflicts with a pending walk-in. Click to resolve.'; break;
                  case 'in-cart-membership': title = `Membership for this date is in your cart (Starts: ${formatDisplayDate(statusInfo.cartItem.start_date)}). Click to manage.`; break; // Added
                  case 'in-cart-walkin': title = `Walk-in for this date is in your cart (${formatDisplayDate(statusInfo.cartItem.date)}). Click to manage.`; break; // Added
             }
             dayElement.setAttribute('title', title);
        } else if (selectedDates.includes(dateString)) {
             dayElement.setAttribute('title', 'Click to deselect date'); // Adjusted for walk-in
        } else if (!statusInfo) {
             dayElement.setAttribute('title', 'Click to select date'); // Adjusted for walk-in
        }


        if (isDisabled) {
            dayElement.classList.add('disabled'); // Add generic disabled style
            // No click listener for truly disabled dates (past, active-membership)
        } else {
            // Add click event for selectable/pending dates
            dayElement.addEventListener('click', clickHandler);
        }

        daysContainer.appendChild(dayElement);
    }
}

// Render the calendar legend - Updated for In-Cart statuses
function renderCalendarLegend() {
    // Create legend container
    const legendContainer = document.createElement('div');
    legendContainer.className = 'calendar-legend mt-3';
    // *** ADD 'In Cart Membership' and 'In Cart Walk-in' legend items ***
    legendContainer.innerHTML = `
        <div class="d-flex flex-wrap justify-content-start">
            <div class="legend-item me-3 mb-1">
                <span class="legend-color selected" style="border: 1px solid #ccc;"></span>
                <span>Selected Date</span>
            </div>
            <div class="legend-item me-3 mb-1">
                <span class="legend-color in-cart-membership" style="border: 2px dashed #0d6efd; background-color: rgba(13, 110, 253, 0.1);"></span>
                <span>In Cart Membership</span>
            </div>
             <div class="legend-item me-3 mb-1">
                <span class="legend-color in-cart-walkin" style="border: 2px dashed var(--secondary-color, #6c757d); background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1);"></span>
                <span>In Cart Walk-in</span>
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

             /* Added styles for in-cart legend items */
            .calendar-day.in-cart-walkin { /* *** NEW *** */
                border: 2px dashed var(--secondary-color, #6c757d); /* Use dashed border */
                background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1); /* Use light background */
                color: var(--secondary-color, #6c757d);
             }
            .legend-color.in-cart-walkin { /* *** NEW *** */
                border: 2px dashed var(--secondary-color, #6c757d); /* Use dashed border */
                background-color: rgba(var(--secondary-color-rgb, 108, 117, 125), 0.1); /* Use light background */
            }
             .calendar-day.in-cart-membership { /* Added */
                 border: 2px dashed #0d6efd; /* Hardcoded blue color for border */
                 background-color: rgba(13, 110, 253, 0.1); /* Light blue background with transparency */
                 color: #0d6efd;
             }
             .legend-color.in-cart-membership { /* Added */
                border: 2px dashed #0d6efd; /* Hardcoded blue color for border */
                background-color: rgba(13, 110, 253, 0.1); /* Light blue background with transparency */
            }
             .legend-color.selected { /* Ensure selected has solid border in legend */
                 border: 1px solid var(--primary-color);
             }
              /* Add Calendar Day Styles for pending/active/past */
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

    const primaryRgb = hexToRgb(primaryColorHex);
    if (primaryRgb) {
        // Define the --primary-color-rgb CSS variable
        document.documentElement.style.setProperty('--primary-color-rgb', `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
        console.log("Set --primary-color-rgb:", `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
    } else {
         console.error("Could not parse primary color:", primaryColorHex);
         // Fallback RGB color
         document.documentElement.style.setProperty('--primary-color-rgb', '255, 0, 0'); // Default red
    }

    // *** SET SECONDARY RGB VARIABLE *** - Added
     const secondaryColorHex = getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim();
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

// Initialize UI on page load - Updated for fetching cart items and new modal buttons
window.onload = async function() { // Make async
    // Initialize the UI components
    renderCalendarWeekdays();
    renderCalendarLegend();

    // Set initial hidden dates value
    document.getElementById('hidden_selected_dates').value = JSON.stringify(selectedDates);

    // --- ADDED: Restore calendar state and scroll position ---
    const savedMonth = sessionStorage.getItem('calendarMonth');
    const savedYear = sessionStorage.getItem('calendarYear');
    const savedScrollPos = sessionStorage.getItem('scrollPos');

    if (savedMonth !== null && savedYear !== null) {
        currentMonth = parseInt(savedMonth, 10);
        currentYear = parseInt(savedYear, 10);
        sessionStorage.removeItem('calendarMonth');
        sessionStorage.removeItem('calendarYear');
         console.log(`Restored calendar to ${currentMonth + 1}/${currentYear}`);
    } else {
         console.log("No saved calendar state found.");
         // Initialize with current month/year if no saved state
         currentMonth = new Date().getMonth();
         currentYear = new Date().getFullYear();
    }

    // *** Fetch disabled dates AND cart items concurrently *** - Updated
    try {
        console.log("Fetching initial data...");
        // Use Promise.all to wait for both fetch calls
        const [disabledResult, cartResult] = await Promise.all([
            fetchDisabledDates().catch(e => { console.error("Error fetching disabled dates:", e); return {}; }), // Handle individual errors gracefully
            fetchCartItems().catch(e => { console.error("Error fetching cart items:", e); return []; }) // Handle individual errors gracefully
        ]);

        // Store fetched cart data globally
        cartItems = cartResult;
        console.log("Initial data fetched.");

    } catch (error) {
        console.error("Error fetching initial calendar data:", error);
        alert("Failed to load all calendar data. Some information might be missing.");
        // Assign empty arrays if any fetch failed
        cartItems = cartItems || [];
        disabledDates = disabledDates || {};
    }


    // Initial calendar render (now uses fetched data and potentially restored month/year)
    renderCalendar(); // Ensure calendar is rendered AFTER fetching data and restoring state

     // --- ADDED: Restore scroll position after calendar render ---
     // Use requestAnimationFrame to ensure scroll happens after rendering and layout
     if (savedScrollPos !== null) {
         requestAnimationFrame(() => {
             window.scrollTo(0, parseInt(savedScrollPos, 10));
             sessionStorage.removeItem('scrollPos');
             console.log(`Restored scroll position to ${savedScrollPos}`);
         });
     } else {
         console.log("No saved scroll position found.");
     }
     // -----------------------------------------------------------


    // Setup calendar navigation
    setupCalendarNavigation();

    // Setup modal button event listeners - Updated and MODIFIED for in-cart walk-in removal
    // ... (existing modal button setup code) ...
    const replaceMembershipBtn = document.getElementById('replaceMembershipBtn'); // Used for pending conflicts
    const appendMembershipBtn = document.getElementById('appendMembershipBtn'); // For pending membership append (likely hidden)
    const replaceInCartWalkinBtn = document.getElementById('replaceInCartWalkinBtn'); // This button will now handle removal
    const appendToCartWalkinBtn = document.getElementById('appendToCartWalkinBtn'); // New (likely hidden for walk-ins)

    // This button handles replacing a pending membership AND replacing a pending walk-in
    if (replaceMembershipBtn) {
        replaceMembershipBtn.addEventListener('click', function() {
            if (modalConflictData && modalConflictData.type === 'pending-membership') {
                handleReplacePendingMembership(); // Handle replacing a pending membership
            } else if (modalConflictData && modalConflictData.type === 'pending-walkin') {
                handleReplaceWalkin(); // Handle replacing a pending walk-in
            }
        });
    } else {
         console.warn("replaceMembershipBtn not found.");
    }

    // The append button is not relevant for walk-ins, so its listener is effectively unused here.
    if (appendMembershipBtn) {
        appendMembershipBtn.addEventListener('click', function() {
            // Logic for appending walk-ins if applicable (currently hidden)
            console.log("Append button clicked - feature not implemented for walk-ins");
        });
    } else {
         console.warn("appendMembershipBtn not found.");
    }

     // *** Add listener for the new In Cart Walk-in Remove button (re-using replaceInCartWalkinBtn) *** - Added and MODIFIED
     // This button now specifically handles the removal of an in-cart walk-in
     if (replaceInCartWalkinBtn) {
         replaceInCartWalkinBtn.addEventListener('click', handleRemoveInCartWalkin); // Call the removal function
     } else {
         console.warn("replaceInCartWalkinBtn not found.");
     }

     // The append button for in-cart walk-ins is likely not needed, but adding listener for completeness if button exists
      if (appendToCartWalkinBtn) {
          appendToCartWalkinBtn.addEventListener('click', function() {
              console.log("Append after in-cart walk-in button clicked - feature not implemented.");
              // You could add logic here if needed in the future
          });
      } else {
          console.warn("appendToCartWalkinBtn not found.");
      }

    // Optional: Reset modal buttons on modal close to prevent wrong buttons showing briefly - Updated and MODIFIED
    const conflictModalElement = document.getElementById('conflictModal');
    if (conflictModalElement) {
        conflictModalElement.addEventListener('hidden.bs.modal', function () {
             // Get modal buttons again inside the listener to ensure they exist
             const replaceMembershipBtn = document.getElementById('replaceMembershipBtn');
             const appendMembershipBtn = document.getElementById('appendMembershipBtn');
             const replaceInCartWalkinBtn = document.getElementById('replaceInCartWalkinBtn');
             const appendToCartWalkinBtn = document.getElementById('appendToCartWalkinBtn');

             // Hide all buttons on close
             if (replaceMembershipBtn) replaceMembershipBtn.style.display = 'none';
             if (appendMembershipBtn) appendMembershipBtn.style.display = 'none';
             if (replaceInCartWalkinBtn) replaceInCartWalkinBtn.style.display = 'none';
             if (appendToCartWalkinBtn) appendToCartWalkinBtn.style.display = 'none';


            // Reset modal title/text
            document.getElementById('conflictModalLabel').innerText = 'Conflict Detected';
            document.getElementById('conflictMessage').innerText = '';
            modalConflictData = null; // Clear data on close

             // Reset button text and class for next time IF they exist
             if (replaceMembershipBtn) {
                 replaceMembershipBtn.innerText = 'Replace Pending'; // Reset text
                 replaceMembershipBtn.classList.remove('btn-warning');
                 replaceMembershipBtn.classList.add('btn-danger');
             }
             // Reset the in-cart walk-in button's text and class for next time
             if (replaceInCartWalkinBtn) {
                 replaceInCartWalkinBtn.innerText = 'Replace In Cart Walk-in'; // Reset text to its original purpose
                 replaceInCartWalkinBtn.classList.remove('btn-danger'); // Remove danger class
                 // Add back its original class (assuming btn-primary)
                 replaceInCartWalkinBtn.classList.add('btn-primary');
             }
             // Append button likely remains hidden but reset its text just in case
             if (appendMembershipBtn) {
                appendMembershipBtn.innerText = 'Append After Pending';
                appendMembershipBtn.classList.remove('btn-warning', 'btn-success');
                appendMembershipBtn.classList.add('btn-primary'); // Assuming original is primary
             }
              if (appendToCartWalkinBtn) {
                appendToCartWalkinBtn.innerText = 'Add After In-Cart Walk-in';
                appendToCartWalkinBtn.classList.remove('btn-warning', 'btn-success');
                appendToCartWalkinBtn.classList.add('btn-primary'); // Assuming original is primary
             }
        });
    }
};

// Removed updateDisabledDateRanges as it's not needed for single-day walk-in selections.
// The disabledDates object from the server fetch is sufficient.

</script>