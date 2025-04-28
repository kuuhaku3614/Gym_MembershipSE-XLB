<?php
// --- AJAX handler for transaction details (must be first!) ---
if (isset($_POST['transaction_id'])) {
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__ . '/functions/notifications.class.php');
    $notificationsObj = new Notifications();
    header('Content-Type: application/json');
    if (is_numeric($_POST['transaction_id'])) {
        try {
            $details = $notificationsObj->getTransactionDetails($_POST['transaction_id']);
            if ($details) {
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
$pendingRequests = $notificationsObj->getAllPendingRequests();
?>
<link rel="stylesheet" href="css/notification.css">
<div class="container mt-4">
    <h2>Pending Requests</h2>
    <div class="notification-container">
        <?php if (!empty($pendingRequests)): ?>
            <?php foreach ($pendingRequests as $request): ?>
                <div class="notification-card mb-3 clickable-card" data-transaction-id="<?= htmlspecialchars($request['transaction_id']) ?>">
                    <div class="notification-content d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <span class="text-primary">#<?= htmlspecialchars($request['transaction_id']) ?></span>
                                <?= htmlspecialchars($request['full_name']) ?>
                            </h5>
                            <p class="mb-0 text-muted"><small><?= htmlspecialchars($request['created_at']) ?></small></p>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No pending transaction requests.</div>
        <?php endif; ?>
    </div>
</div>

<style>
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
                        <div class="mb-1 text-muted" id="modalPhone"></div>
                        <div class="info-row">
                            <div><strong>Sex:</strong> <span id="modalSex"></span></div>
                            <div><strong>Birthdate:</strong> <span id="modalBirthdate"></span></div>
                            <div><strong>Age:</strong> <span id="modalAge"></span></div>
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Make cards clickable
$(document).on('click', '.clickable-card', function() {
    var transactionId = $(this).data('transaction-id');
    showNotificationDetailsAjax(transactionId);
});

function showNotificationDetailsAjax(transactionId) {
    // Clear previous content
    $('#modalName, #modalPhone, #modalSex, #modalBirthdate, #modalAge').text('');
    $('#modalProfilePic').attr('src', '/Gym_MembershipSE-XLB/assets/images/default-profile.png');
    $('#modalServices').empty();

    $.ajax({
        type: 'POST',
        url: '/Gym_MembershipSE-XLB/admin/pages/notification/transactions.php',
        data: { transaction_id: transactionId },
        dataType: 'json',
        success: function(json) {
            if (!json.success) {
                $('#modalServices').html('<div class="alert alert-danger">' + (json.message || 'Failed to load details.') + '</div>');
                return;
            }
            var d = json.data;
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
                d.walkins.forEach(function(walkin) {
                    servicesHtml += '<li class="list-group-item">';
                    if (walkin.date) servicesHtml += '<strong>Date:</strong> ' + formatDateWords(walkin.date) + '<br>';
                    if (walkin.time_in) servicesHtml += '<small>Time In: ' + walkin.time_in + '</small><br>';
                    if (walkin.amount) servicesHtml += '<small>Amount: ' + walkin.amount + '</small><br>';
                    // If walk-in has a start and end date, show range and duration
                    if (walkin.start_date && walkin.end_date) {
                        servicesHtml += '<small>Date Range: ' + formatDateWords(walkin.start_date) + ' - ' + formatDateWords(walkin.end_date) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(walkin.start_date, walkin.end_date) + '</small>';
                    }
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            } else {
                servicesHtml += '<div class="section-card text-muted"><div class="section-title"><i class="bi bi-person-walking"></i> Walk-in</div>No walk-in records.</div>';
            }

            // Membership Plan section (array)
            if (Array.isArray(d.memberships) && d.memberships.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-card-checklist"></i> Membership Plans</div>'
                    + '<ul class="list-group">';
                d.memberships.forEach(function(m) {
                    servicesHtml += '<li class="list-group-item">';
                    if (m.plan_name) servicesHtml += '<strong>Plan:</strong> ' + m.plan_name + '<br>';
                    if (m.amount) servicesHtml += '<small>Amount: ' + m.amount + '</small><br>';
                    // Combine start and end date
                    if (m.start_date && m.end_date) {
                        servicesHtml += '<small>Period: ' + formatDateWords(m.start_date) + ' - ' + formatDateWords(m.end_date) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(m.start_date, m.end_date) + '</small>';
                    } else if (m.start_date) {
                        servicesHtml += '<small>Start: ' + formatDateWords(m.start_date) + '</small><br>';
                    } else if (m.end_date) {
                        servicesHtml += '<small>End: ' + formatDateWords(m.end_date) + '</small><br>';
                    }
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            } else {
                servicesHtml += '<div class="section-card text-muted"><div class="section-title"><i class="bi bi-card-checklist"></i> Membership Plans</div>No membership plans.</div>';
            }

            // Rentals section (array)
            if (Array.isArray(d.rentals) && d.rentals.length > 0) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-box-seam"></i> Rentals</div>'
                    + '<ul class="list-group">';
                d.rentals.forEach(function(rental) {
                    servicesHtml += '<li class="list-group-item">';
                    if (rental.name) servicesHtml += '<strong>' + rental.name + '</strong><br>';
                    if (rental.amount) servicesHtml += '<small>Amount: ' + rental.amount + '</small><br>';
                    // Combine start and end date
                    if (rental.start && rental.end) {
                        servicesHtml += '<small>Period: ' + formatDateWords(rental.start) + ' - ' + formatDateWords(rental.end) + '</small><br>';
                        servicesHtml += '<small>Duration: ' + getDurationDays(rental.start, rental.end) + '</small>';
                    } else if (rental.start) {
                        servicesHtml += '<small>Start: ' + formatDateWords(rental.start) + '</small><br>';
                    } else if (rental.end) {
                        servicesHtml += '<small>End: ' + formatDateWords(rental.end) + '</small><br>';
                    }
                    servicesHtml += '</li>';
                });
                servicesHtml += '</ul></div>';
            } else {
                servicesHtml += '<div class="section-card text-muted"><div class="section-title"><i class="bi bi-box-seam"></i> Rentals</div>No rentals.</div>';
            }

            // Registration Fee section
            if (d.registration_fee) {
                servicesHtml += '<div class="section-card">'
                    + '<div class="section-title"><i class="bi bi-cash-coin"></i> Registration Fee</div>'
                    + '<ul class="list-group">'
                    + '<li class="list-group-item">Amount: ' + d.registration_fee + '</li>'
                    + '</ul></div>';
            }

            if (!servicesHtml) {
                servicesHtml = '<div class="text-muted">No details available.</div>';
            }
            $('#modalServices').html(servicesHtml);
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
