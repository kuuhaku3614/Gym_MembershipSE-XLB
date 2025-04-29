<?php
// --- AJAX handler for mark as read ---
if (isset($_POST['action']) && $_POST['action'] === 'mark_as_read' && isset($_POST['transaction_id'])) {
    require_once(__DIR__ . '/../../../config.php');
    session_start();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $transaction_id = $_POST['transaction_id'];
    header('Content-Type: application/json');
    if ($user_id && is_numeric($transaction_id)) {
        // Insert or update notification_reads
        $stmt = $pdo->prepare("INSERT INTO notification_reads (user_id, notification_id, notification_type, read_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE read_at = NOW()");
        $success = $stmt->execute([$user_id, $transaction_id, 'transactions']);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user or transaction ID.']);
    }
    exit;
}

// --- AJAX handler for transaction details (must be first!) ---
if (isset($_POST['action']) && $_POST['action'] === 'pay_transaction' && isset($_POST['transaction_id'])) {
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    require_once(__DIR__ . '/functions/activity_logger.php'); // Include the logger
    $notificationsObj = new Notifications();
    header('Content-Type: application/json');
    $transactionId = $_POST['transaction_id'];
    if (is_numeric($transactionId)) {
        $result = $notificationsObj->markTransactionPaid($transactionId);
        if ($result === true) {
            // Fetch full name for logging
            $stmt = $pdo->prepare("SELECT CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name
                                   FROM transactions t
                                   LEFT JOIN users u ON t.user_id = u.id
                                   LEFT JOIN personal_details pd ON u.id = pd.user_id
                                   WHERE t.id = ?");
            $stmt->execute([$transactionId]);
            $fullName = '';
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fullName = trim(preg_replace('/\s+/', ' ', $row['full_name']));
            }
            // Log staff activity with full name
            logStaffActivity('Confirm Transaction', 'Confirm pending request and marked as paid - ' . $fullName);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $result ?: 'Failed to update payment.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
    }
    exit;
}

// --- AJAX handler for transaction details (must be first!) ---
if (isset($_POST['transaction_id'])) {
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    $notificationsObj = new Notifications();
    header('Content-Type: application/json');
    if (is_numeric($_POST['transaction_id'])) {
        try {
            $details = $notificationsObj->getTransactionDetails($_POST['transaction_id']);
            if ($details !== null) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction details not found.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
    }
    exit;
}

// --- Dynamic Color Palette CSS Variables Injection ---
require_once(__DIR__ . '/../../../config.php');
function getDynamicPaletteColors($pdo) {
    $query = "SELECT * FROM website_content WHERE section = 'color'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $colorContent = $stmt->fetch(PDO::FETCH_ASSOC);
    $primaryColor = '#4CAF50';
    $secondaryColor = '#2196F3';
    if ($colorContent) {
        if (isset($colorContent['latitude'])) {
            $primaryColor = '#' . str_pad(dechex(abs(floor($colorContent['latitude'] * 16777215))), 6, '0', STR_PAD_LEFT);
        }
        if (isset($colorContent['longitude'])) {
            $secondaryColor = '#' . str_pad(dechex(abs(floor($colorContent['longitude'] * 16777215))), 6, '0', STR_PAD_LEFT);
        }
    }
    return [
        'primary' => $primaryColor,
        'secondary' => $secondaryColor
    ];
}
$palette = getDynamicPaletteColors($pdo);
echo '<style>:root { --primary-color: ' . htmlspecialchars($palette['primary']) . '; --secondary-color: ' . htmlspecialchars($palette['secondary']) . '; }</style>';
require_once(__DIR__ . '/functions/notifications.class.php');
$notificationsObj = new Notifications();
session_start();
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$pendingRequests = $notificationsObj->getAllPendingRequests();
// Add read status for each transaction
foreach ($pendingRequests as &$request) {
    $stmt = $pdo->prepare("SELECT id FROM notification_reads WHERE user_id = ? AND notification_id = ? AND notification_type = ? LIMIT 1");
    $stmt->execute([$current_user_id, $request['transaction_id'], 'transactions']);
    $request['is_read'] = $stmt->rowCount() > 0;
}
unset($request);
?>
<link rel="stylesheet" href="css/notification.css">
<div class="container mt-4">
    <h2>Pending Requests</h2>
    <form class="mb-3" id="searchForm">
        <div class="input-group">
            <input type="text" class="form-control" id="searchInput" placeholder="Search by name or date...">
        </div>
    </form>
    <div class="notification-container">
        <?php if (!empty($pendingRequests)): ?>
            <?php foreach ($pendingRequests as $request): ?>
                <div class="notification-card mb-3 clickable-card position-relative" data-transaction-id="<?= htmlspecialchars($request['transaction_id']) ?>">
    <div class="notification-content d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">
                <?= htmlspecialchars($request['full_name']) ?>
            </h5>
            <?php
    $date = new DateTime($request['created_at']);
    $formatted = $date->format('F j, Y \a\t h:i A');
?>
<p class="mb-0 text-muted"><small><?= htmlspecialchars($formatted) ?></small></p>
        </div>
        <?php if (!$request['is_read']): ?>
            <span class="badge badge-new">NEW</span>
        <?php endif; ?>
    </div>
</div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No pending transaction requests.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Real-time search as you type
    $('#searchInput').on('input', function() {
        var query = $(this).val().toLowerCase();
        $('.notification-card').each(function() {
            var cardText = $(this).text().toLowerCase();
            if (cardText.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    $('#resetBtn').on('click', function() {
        $('#searchInput').val('');
        $('.notification-card').show();
    });
});
</script>

<style>
.badge-new {
    position: absolute;
    top: 16px;
    right: 18px;
    background: var(--primary-color, #4e73df);
    color: #fff;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 9px 2px 8px;
    border-radius: 10px;
    letter-spacing: 1px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    z-index: 2;
    text-transform: uppercase;
    pointer-events: none;
}
.notification-card { position: relative; }

/* Modern Modal Styles */
#notificationModal .modal-content {
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    background: #f8fafd;
    border: none;
}
#notificationModal .modal-header {
    background: linear-gradient(90deg, var(--primary-color, #4e73df) 0%, var(--secondary-color, #1cc88a) 100%);
    color: #fff;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    border-bottom: none;
    padding-bottom: 0.5rem;
}
#notificationModal .modal-title {
    font-size: 1.4rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
#notificationModal .profile-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
#notificationModal .profile-pic-lg {
    width: 110px;
    height: 110px;
    object-fit: cover;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 3px solid #e3e3e3;
    background: #fff;
}
#notificationModal .profile-details {
    flex: 1;
    min-width: 200px;
}
#notificationModal .profile-details h4 {
    margin-bottom: 0.3rem;
    font-weight: 700;
    font-size: 1.2rem;
}
#notificationModal .profile-details .info-row {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    font-size: 1rem;
    color: #5a5c69;
}
#notificationModal .section-card {
    background: #fff;
    border-radius: 0.7rem;
    box-shadow: 0 1px 6px rgba(0,0,0,0.03);
    border: 1px solid var(--secondary-color, #e9ecef);
    padding: 1rem 1.2rem;
    margin-bottom: 1.3rem;
}
#notificationModal .section-title {
    font-size: 1.08rem;
    font-weight: 600;
    color: var(--primary-color, #4e73df);
    margin-bottom: 0.7rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
#notificationModal .section-title i {
    font-size: 1.1em;
    color: var(--secondary-color, #1cc88a);
}
#notificationModal .list-group-item {
    border-radius: 0.5rem;
    margin-bottom: 0.3rem;
    background: #f8fafd;
}
@media (max-width: 767.98px) {
    #notificationModal .modal-dialog {
        max-width: 97vw;
        margin: 1rem auto;
    }
    #notificationModal .profile-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.7rem;
    }
}
</style>
<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- Success Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 11000">
  <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2000">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle-fill me-2"></i><span id="successToastMsg">Success!</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmPayModal" tabindex="-1" aria-labelledby="confirmPayModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="confirmPayModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Payment</h5>
      </div>
      <div class="modal-body text-center">
        <div class="alert alert-warning mb-0" role="alert">
          Are you sure you want to <strong>confirm this transaction</strong> and mark all services as <strong>paid</strong>?
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning px-4" id="confirmPayBtn">Yes, Confirm & Mark as Paid</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i class="bi bi-receipt"></i> Transaction Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="profile-section">
                    <img id="modalProfilePic" src="" alt="Profile Picture" class="profile-pic-lg">
                    <div class="profile-details">
                        <h4 id="modalName"></h4>
                        <div>
                            <span class="mb-1 text-muted" id="modalSex"></span><span class="text-muted">,</span>
                            <span class="mb-1 text-muted" id="modalAge"></span>
                        </div>
                        <div class="info-row">
                            <div><strong>Birthdate:</strong> <span id="modalBirthdate"></span></div>
                            <div><strong>Contact:</strong> <span id="modalPhone"></span></div>
                        </div>
                    </div>
                </div>
                <div id="modalServices">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>


<script>
  var BASE_URL = "<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Show success toast
function showSuccessToast(msg) {
    $('#successToastMsg').text(msg || 'Success!');
    var toastEl = document.getElementById('successToast');
    var toast = bootstrap.Toast.getOrCreateInstance(toastEl);
    toast.show();
}

// Remove confirmation modal
$('body').append(`
<div class="modal fade" id="removeConfirmModal" tabindex="-1" aria-labelledby="removeConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="removeConfirmModalLabel"><i class="bi bi-trash me-2"></i>Confirm Removal</h5>
      </div>
      <div class="modal-body text-center">
        <div id="removeConfirmMessage" class="alert alert-danger mb-0" role="alert"></div>
        <div id="removeRentalWarning" class="alert alert-warning mt-2 d-none" role="alert"></div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger px-4" id="removeConfirmBtn">Remove</button>
      </div>
    </div>
  </div>
</div>
`);

// Remove button click handler (delegated)
$(document).on('click', '.remove-item-btn', function() {
    var type = $(this).data('type');
    var idx = parseInt($(this).data('index'));
    var recordId = $(this).data('id');
    var d = window.lastTransactionDetailsData; // store d globally when modal opens
    if (!d) {
        alert('Error: Transaction details not loaded. Please try again.');
        return;
    }
    let msg = '', rentalWarning = '', affectedRentals = [];
    if (type === 'walkin') {
        msg = 'Are you sure you want to remove this walk-in record?';
    } else if (type === 'rental') {
        msg = 'Are you sure you want to remove this rental?';
    } else if (type === 'membership') {
        msg = 'Are you sure you want to remove this membership plan?';
        // Check for overlapping rentals
        if (d && d.memberships && d.rentals) {
            var m = d.memberships[idx];
            var mStart = m.start_date ? new Date(m.start_date) : null;
            var mEnd = m.end_date ? new Date(m.end_date) : null;
            // Helper to format date in words
            function formatDateWords(dateStr) {
                if (!dateStr) return '';
                const dateObj = new Date(dateStr);
                if (isNaN(dateObj)) return dateStr;
                return dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
            // Helper to get duration in days
            function getDurationDays(start, end) {
                if (!start || !end) return '';
                const startDate = new Date(start);
                const endDate = new Date(end);
                if (isNaN(startDate) || isNaN(endDate)) return '';
                const diffMs = endDate - startDate;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                return diffDays + ' day' + (diffDays !== 1 ? 's' : '');
            }
            d.rentals.forEach(function(r) {
                var rStart = r.start ? new Date(r.start) : null;
                var rEnd = r.end ? new Date(r.end) : null;
                if (mStart && mEnd && rStart && rEnd && rStart <= mEnd && rEnd >= mStart) {
                    var period = (r.start && r.end) ? `${formatDateWords(r.start)} to ${formatDateWords(r.end)}` : '';
                    var duration = (r.start && r.end) ? `, Duration: ${getDurationDays(r.start, r.end)}` : '';
                    affectedRentals.push(`<strong>${r.name || '(Unnamed Rental)'}</strong>${period ? ' (' + period + duration + ')' : ''}`);
                }
            });
            if (affectedRentals.length > 0) {
                msg += '<br><div class="alert alert-warning mt-2 mb-0">'
                  + 'Warning: Removing this membership plan will affect the following rental(s) (their period may no longer be valid or covered by a membership). '
                  + '<ul>' + affectedRentals.map(function(detail) { return '<li>' + detail + '</li>'; }).join('') + '</ul></div>';
            }
        }
    }
    $('#removeConfirmMessage').html(msg);
    // Hide the old rental warning area (now merged into main message)
    $('#removeRentalWarning').addClass('d-none').html('');
    var removeModal = new bootstrap.Modal(document.getElementById('removeConfirmModal'));
    removeModal.show();
    // Remove handler
    $('#removeConfirmBtn').off('click').on('click', function() {
        removeModal.hide();
        // Debug: log values for troubleshooting
        console.log('Remove Confirm Debug:', {type, idx, recordId, d});
        // Defensive: check if d and d.transaction_id and recordId are present
        if (!d) {
            alert('Error: Transaction details are not loaded.');
            return;
        }
        if (!d.transaction_id) {
            alert('Error: Transaction ID is missing.');
            return;
        }
        if (!recordId) {
            alert('Error: Record ID is missing.');
            return;
        }
        // AJAX removal
        $.post(BASE_URL + '/functions/remove_transaction_item.php', {
            type: type,
            transaction_id: d.transaction_id,
            record_id: recordId
        }, function(resp) {
            if (resp.success) {
                showSuccessToast('Item removed successfully!');
                // Reload details modal data (simulate click on card to refresh)
                if (typeof window.reloadTransactionDetails === 'function') {
                    window.reloadTransactionDetails(d.transaction_id);
                } else {
                    // Fallback: re-show the modal if reloadTransactionDetails is not defined
                    showNotificationDetailsAjax(d.transaction_id);
                }
            } else {
                alert('Failed to remove item: ' + (resp.error || 'Unknown error'));
            }
        }, 'json').fail(function(xhr) {
            alert('Failed to remove item: ' + xhr.statusText);
        });
    });
});

// Make cards clickable
$(document).on('click', '.clickable-card', function() {
    var transactionId = $(this).data('transaction-id');
    var card = $(this);
    // Mark as read via AJAX
    $.ajax({
        type: 'POST',
        url: BASE_URL + '/transactions.php',
        data: { action: 'mark_as_read', transaction_id: transactionId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                card.find('.badge-new').fadeOut(200, function() { $(this).remove(); });
            }
        }
    });
    showNotificationDetailsAjax(transactionId);
});

function showNotificationDetailsAjax(transactionId) {
    // Clear previous content
    $('#modalName, #modalPhone, #modalSex, #modalBirthdate, #modalAge').text('');
    $('#modalProfilePic').attr('src', BASE_URL + '/assets/images/default-profile.png');
    $('#modalServices').empty();

    $.ajax({
        type: 'POST',
        url: BASE_URL + '/transactions.php',
        data: { transaction_id: transactionId },
        dataType: 'json',
        success: function(json) {
            if (!json.success) {
                // Show error in modal body
                $('#modalServices').html('<div class="alert alert-danger">' + (json.message || 'Failed to load details.') + '</div>');
                // Optionally disable remove buttons
                $('.remove-item-btn').prop('disabled', true);
                // Clear global variable to prevent accidental actions
                window.lastTransactionDetailsData = null;
                return;
            }
            var d = json.data;
            // Set global variable for transaction details
            window.lastTransactionDetailsData = d;
            $('#modalName').text(d.full_name || '');
            $('#modalPhone').text(d.phone_number || '');
            $('#modalSex').text(d.sex || '');
            // Format birthday in words
            let birthdateText = '';
            if (d.birthdate) {
                const dateObj = new Date(d.birthdate);
                if (!isNaN(dateObj.getTime())) {
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    birthdateText = dateObj.toLocaleDateString(undefined, options);
                } else {
                    birthdateText = d.birthdate;
                }
            }
            $('#modalBirthdate').text(birthdateText);
            $('#modalAge').text(d.age || '');
            let photoPath = d.profile_picture || 'uploads/default.jpg';
            let imgSrc = '../' + photoPath;
            $('#modalProfilePic')
                .attr('src', imgSrc)
                .attr('onerror', "this.src='../uploads/default.jpg'");

            // Structure modal into sections: Walk-in, Membership Plan, Rentals, Registration Fee
            let servicesHtml = '';

            // Helper to format date in words
            function formatDateWords(dateStr) {
                if (!dateStr) return '';
                const dateObj = new Date(dateStr);
                if (isNaN(dateObj)) return dateStr;
                return dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
            // Helper to get duration in days
            function getDurationDays(start, end) {
                if (!start || !end) return '';
                const startDate = new Date(start);
                const endDate = new Date(end);
                if (isNaN(startDate) || isNaN(endDate)) return '';
                const diffMs = endDate - startDate;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                return diffDays + ' day' + (diffDays !== 1 ? 's' : '');
            }

            // Walk-in section (array)
            if (Array.isArray(d.walkins) && d.walkins.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-person-walking"></i> Walk-in</div>'
                    + '<ul class="list-group">';
                d.walkins.forEach(function(walkin, i) {
                    servicesHtml += '<li class="list-group-item d-flex justify-content-between align-items-start">';
                    servicesHtml += '<div>';
                    if (walkin.date) servicesHtml += '<strong>Date:</strong> ' + formatDateWords(walkin.date) + '<br>';
                    if (walkin.time_in) servicesHtml += '<small>Time In: ' + walkin.time_in + '</small><br>';
                    if (walkin.amount) servicesHtml += '<small>Amount: ' + walkin.amount + '</small><br>';
                    if (walkin.start_date && walkin.end_date) {
                        servicesHtml += '<small>Date Range: ' + formatDateWords(walkin.start_date) + ' - ' + formatDateWords(walkin.end_date) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(walkin.start_date, walkin.end_date) + '</small>';
                    }
                    servicesHtml += '</div>';
                    servicesHtml += '<button class="btn btn-sm btn-danger ms-2 remove-item-btn" data-type="walkin" data-index="' + i + '" data-id="' + (walkin.id || '') + '" title="Remove"><i class="bi bi-trash"></i></button>';
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            }

            // Membership Plan section (array)
            if (Array.isArray(d.memberships) && d.memberships.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-card-checklist"></i> Membership Plans</div>'
                    + '<ul class="list-group">';
                d.memberships.forEach(function(m, i) {
                    servicesHtml += '<li class="list-group-item d-flex justify-content-between align-items-start">';
                    servicesHtml += '<div>';
                    if (m.plan_name) servicesHtml += '<strong>Plan:</strong> ' + m.plan_name + '<br>';
                    if (m.amount) servicesHtml += '<small>Amount: ' + m.amount + '</small><br>';
                    if (m.start_date && m.end_date) {
                        servicesHtml += '<small>Period: ' + formatDateWords(m.start_date) + ' - ' + formatDateWords(m.end_date) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(m.start_date, m.end_date) + '</small>';
                    } else if (m.start_date) {
                        servicesHtml += '<small>Start: ' + formatDateWords(m.start_date) + '</small><br>';
                    } else if (m.end_date) {
                        servicesHtml += '<small>End: ' + formatDateWords(m.end_date) + '</small><br>';
                    }
                    servicesHtml += '</div>';
                    servicesHtml += '<button class="btn btn-sm btn-danger ms-2 remove-item-btn" data-type="membership" data-index="' + i + '" data-id="' + (m.id || '') + '" title="Remove"><i class="bi bi-trash"></i></button>';
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            }

            // Rentals section (array)
            if (Array.isArray(d.rentals) && d.rentals.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-box-seam"></i> Rentals</div>'
                    + '<ul class="list-group">';
                d.rentals.forEach(function(rental, i) {
                    servicesHtml += '<li class="list-group-item d-flex justify-content-between align-items-start">';
                    servicesHtml += '<div>';
                    if (rental.name) servicesHtml += '<strong>' + rental.name + '</strong><br>';
                    if (rental.amount) servicesHtml += '<small>Amount: ' + rental.amount + '</small><br>';
                    if (rental.start && rental.end) {
                        servicesHtml += '<small>Period: ' + formatDateWords(rental.start) + ' - ' + formatDateWords(rental.end) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(rental.start, rental.end) + '</small>';
                    } else if (rental.start) {
                        servicesHtml += '<small>Start: ' + formatDateWords(rental.start) + '</small><br>';
                    } else if (rental.end) {
                        servicesHtml += '<small>End: ' + formatDateWords(rental.end) + '</small><br>';
                    }
                    servicesHtml += '</div>';
                    servicesHtml += '<button class="btn btn-sm btn-danger ms-2 remove-item-btn" data-type="rental" data-index="' + i + '" data-id="' + (rental.id || '') + '" title="Remove"><i class="bi bi-trash"></i></button>';
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            }

            // Registration Fee section(s) from registration_records
            if (Array.isArray(d.registration_records) && d.registration_records.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-cash-coin"></i> Registration Fee</div>'
                    + '<ul class="list-group">';
                d.registration_records.forEach(function(reg) {
                    servicesHtml += '<li class="list-group-item">Amount: ' + reg.amount + '</li>';
                });
                servicesHtml += '</ul></div>';
            }

            // Calculate total unpaid amount
            let totalUnpaid = 0;
            if (Array.isArray(d.walkins)) {
                d.walkins.forEach(function(w) { if (w.amount) totalUnpaid += parseFloat(w.amount) || 0; });
            }
            if (Array.isArray(d.memberships)) {
                d.memberships.forEach(function(m) { if (m.amount) totalUnpaid += parseFloat(m.amount) || 0; });
            }
            if (Array.isArray(d.rentals)) {
                d.rentals.forEach(function(r) { if (r.amount) totalUnpaid += parseFloat(r.amount) || 0; });
            }
            if (Array.isArray(d.registration_records)) {
                d.registration_records.forEach(function(reg) { if (reg.amount) totalUnpaid += parseFloat(reg.amount) || 0; });
            }
            if (totalUnpaid > 0) {
                servicesHtml += '<div class="section-card bg-light mb-2">'
                    + '<div class="section-title"><i class="bi bi-calculator"></i> Total Amount</div>'
                    + '<div class="fs-4 fw-bold">₱ ' + totalUnpaid.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</div>'
                    + '</div>';
            }

            // Add Pay button if not confirmed
            if (d.transaction_status !== 'confirmed') {
                servicesHtml += '<div class="d-grid gap-2 mt-3">'
                    + '<button id="payTransactionBtn" class="btn btn-success btn-lg"><i class="bi bi-credit-card"></i> Comfirm transaction & Mark as Paid</button>'
                    + '</div>';
            }

            if (!servicesHtml) {
                servicesHtml = '<div class="text-muted">No details available.</div>';
            }
            $('#modalServices').html(servicesHtml);

            // Pay button handler
            if (d.transaction_status !== 'confirmed') {
                $('#payTransactionBtn').off('click').on('click', function() {
                    // Hide the transaction modal first
                    var notifModal = bootstrap.Modal.getInstance(document.getElementById('notificationModal'));
                    if (notifModal) notifModal.hide();
                    // Wait for the modal to be fully hidden before showing the confirm modal
                    $('#notificationModal').one('hidden.bs.modal', function() {
                        var confirmModal = new bootstrap.Modal(document.getElementById('confirmPayModal'));
                        confirmModal.show();
                        // When confirmed, run AJAX
                        $('#confirmPayBtn').off('click').on('click', function() {
                            $('#confirmPayBtn').prop('disabled', true).text('Processing...');
                            $.ajax({
                                type: 'POST',
                                url: BASE_URL + '/transactions.php',
                                data: { action: 'pay_transaction', transaction_id: d.transaction_id },
                                dataType: 'json',
                                success: function(resp) {
                                    if (resp.success) {
                                        // Hide confirmation modal, show success toast
                                        var confirmModalInst = bootstrap.Modal.getInstance(document.getElementById('confirmPayModal'));
                                        if (confirmModalInst) confirmModalInst.hide();
                                        // Show success toast
                                        showSuccessToast('Transaction has been confirmed and marked as paid!');
                                        setTimeout(function() {
                                            location.reload();
                                        }, 2000);
                                    } else {
                                        $('#confirmPayBtn').prop('disabled', false).text('Yes, Confirm & Mark as Paid');
                                        alert(resp.message || 'Failed to update payment.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    $('#confirmPayBtn').prop('disabled', false).text('Yes, Confirm & Mark as Paid');
                                    alert('AJAX error: ' + error);
                                }
                            });
                        });
                    });
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            $('#modalServices').html('<div class="alert alert-danger">Failed to load details.</div>');
        }
    });
    var modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();
}
</script>
