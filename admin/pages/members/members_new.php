<?php
require_once("../../../config.php");
require_once("./functions/members.class.php");

// Check if this is an AJAX request for member details
if (isset($_GET['ajax_view_member']) && isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    try {
        $members = new Members();
        $memberDetails = $members->getMemberDetails($_GET['user_id']);
        echo json_encode($memberDetails);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Main page display
$members = new Members();
$membersList = $members->getAllMembers();
?>

<div class="container-fluid mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Members</h2>
    <a href="#" class="btn btn-primary" id="add_member-link">
      <i class="fas fa-user-plus"></i> Add Member
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="membersTable" class="table table-hover">
          <thead>
            <tr>
              <th class="text-center" style="width: 80px">Photo</th>
              <th>Name</th>
              <th>Status</th>
              <th>Payment Status</th>
              <th class="text-center" style="width: 100px">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($membersList)): ?>
              <?php foreach ($membersList as $member): ?>
                <tr>
                  <td class="text-center">
                    <img src="../<?php echo htmlspecialchars($member['photo_path'] ?? 'uploads/default.jpg'); ?>" 
                         class="rounded-circle"
                         alt="Member photo"
                         width="50" 
                         height="50"
                         style="object-fit: cover;"
                         onerror="this.src='../uploads/default.jpg'">
                  </td>
                  <td class="align-middle"><?php echo htmlspecialchars($member['full_name']); ?></td>
                  <td class="align-middle">
                    <span class="badge <?php echo $member['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                      <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                    </span>
                  </td>
                  <td class="align-middle">
                    <?php 
                    // Initialize $hasUnpaid variable
                    $hasUnpaid = false;
                    if ($member['status'] === 'active'): 
                      $hasUnpaid = ($member['unpaid_memberships'] > 0 || $member['unpaid_rentals'] > 0);
                      $badgeClass = $hasUnpaid ? 'bg-danger' : 'bg-success';
                      $paymentText = $hasUnpaid ? 'Unpaid' : 'Paid';
                    ?>
                      <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo $paymentText; ?>
                      </span>
                      <?php if ($hasUnpaid): ?>
                        <small class="d-block text-muted mt-1">
                          <?php 
                          $details = [];
                          if ($member['unpaid_memberships'] > 0) {
                              $details[] = $member['unpaid_memberships'] . ' membership' . ($member['unpaid_memberships'] > 1 ? 's' : '');
                          }
                          if ($member['unpaid_rentals'] > 0) {
                              $details[] = $member['unpaid_rentals'] . ' rental' . ($member['unpaid_rentals'] > 1 ? 's' : '');
                          }
                          echo '(' . implode(', ', $details) . ')';
                          ?>
                        </small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle">
                    <div class="btn-group">
                      <button class="btn btn-primary btn-sm" onclick="viewMemberDetails(<?php echo (int)$member['user_id']; ?>)">
                        <i class="fas fa-eye"></i> View
                      </button>
                      <?php if ($hasUnpaid): ?>
                        <button class="btn btn-success btn-sm" onclick="viewPaymentDetails(<?php echo (int)$member['user_id']; ?>)">
                          <i class="fas fa-money-bill"></i> Pay
                        </button>
                      <?php endif; ?>

                      <a href="#" class="btn btn-warning btn-sm renew_member-link" data-member-id="<?php echo (int)$member['user_id']; ?>">
                        <i class="fas fa-sync"></i> Renew
                      </a>
                      
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No members found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Member Details Modal -->
<div class="modal fade" id="memberDetailsModal" tabindex="-1" aria-labelledby="memberDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="memberDetailsModalLabel">Member Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="memberDetailsContent">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="paymentDetailsContent">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Destroy existing DataTable instance if it exists
  if ($.fn.DataTable.isDataTable('#membersTable')) {
    $('#membersTable').DataTable().destroy();
  }
  
  // Initialize DataTable with Bootstrap 5 styling
  $('#membersTable').DataTable({
    autoWidth: false,
    stateSave: true,
    stateDuration: -1, // Save state forever
    columnDefs: [
      { orderable: false, targets: [0, 4] }, // Disable sorting on photo and action columns
      { className: "align-middle", targets: "_all" }, // Vertically center all columns
      { width: "80px", targets: 0 }, // Photo column width
      { width: "100px", targets: 4 } // Action column width
    ],
    language: {
      search: "Search members:",
      lengthMenu: "Show _MENU_ members per page",
      info: "Showing _START_ to _END_ of _TOTAL_ members",
      emptyTable: "No members found"
    },
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']] // Sort by name by default
  });
});

function processPayment(userId, type, itemId) {
    if (!confirm('Are you sure you want to mark this as paid?')) {
        return;
    }
    
    // Get base URL from current page URL
    const baseUrl = '<?php echo BASE_URL; ?>';
    
    $.ajax({
        url: `${baseUrl}/admin/pages/members/process_payment.php`,
        type: 'POST',
        data: {
            user_id: userId,
            type: type,
            item_id: itemId
        },
        success: function(response) {
            try {
                console.log('Server response:', response);
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    alert('Payment processed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process payment'));
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.error('Raw response:', response);
                alert('Error processing payment. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            alert('Error processing payment. Please try again.');
        }
    });
}

function viewMemberDetails(userId) {
    // Show loading state
    const loadingHtml = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading member details...</p></div>';
    const modal = new bootstrap.Modal(document.getElementById('memberDetailsModal'));
    document.querySelector('#memberDetailsModal .modal-body').innerHTML = loadingHtml;
    modal.show();

    fetch('./pages/members/members_new.php?ajax_view_member=1&user_id=' + userId)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('Failed to load member details');
            }

            // Format dates
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            };

            // Sort memberships by start date (most recent first)
            if (data.memberships && Array.isArray(data.memberships)) {
                data.memberships.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
            }

            // Sort rental services by start date (most recent first)
            if (data.rental_services && Array.isArray(data.rental_services)) {
                data.rental_services.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
            }

            // Prepare membership info HTML
            const membershipHtml = data.memberships && Array.isArray(data.memberships) && data.memberships.length > 0 ? 
                `<div class="list-group">
                    ${data.memberships.map(membership => {
                        const statusBadgeClass = membership.status.toLowerCase() === 'active' ? 'success' : 
                                               membership.status.toLowerCase() === 'expiring' ? 'warning' : 'danger';
                        return `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${membership.plan_name}</h6>
                                <div>
                                    <span class="badge bg-${statusBadgeClass} me-1">
                                        ${membership.status}
                                    </span>
                                    <span class="badge bg-${membership.is_paid ? 'success' : 'danger'}">
                                        ${membership.is_paid ? 'Paid' : 'Unpaid'}
                                    </span>
                                </div>
                            </div>
                            <div class="small text-muted">
                                <div>Duration: ${formatDate(membership.start_date)} - ${formatDate(membership.end_date)}</div>
                                <div>Amount: ₱${parseFloat(membership.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>`;
                    }).join('')}
                </div>` :
                '<p class="text-muted">No active membership plans</p>';

            // Prepare rental services HTML
            const rentalServicesHtml = data.rental_services && Array.isArray(data.rental_services) && data.rental_services.length > 0 ?
                `<div class="list-group">
                    ${data.rental_services.map(service => {
                        const statusBadgeClass = service.status.toLowerCase() === 'active' ? 'success' : 
                                               service.status.toLowerCase() === 'expiring' ? 'warning' : 'danger';
                        return `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${service.service_name}</h6>
                                <div>
                                    <span class="badge bg-${statusBadgeClass} me-1">
                                        ${service.status}
                                    </span>
                                    <span class="badge bg-${service.is_paid ? 'success' : 'danger'}">
                                        ${service.is_paid ? 'Paid' : 'Unpaid'}
                                    </span>
                                </div>
                            </div>
                            <div class="small text-muted">
                                <div>Duration: ${formatDate(service.start_date)} - ${formatDate(service.end_date)}</div>
                                <div>Amount: ₱${parseFloat(service.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>`;
                    }).join('')}
                </div>` :
                '<p class="text-muted">No active rental services</p>';

            // Update modal content
            document.querySelector('#memberDetailsModal .modal-body').innerHTML = `
                <div class="text-center mb-4">
                    <img id="memberPhoto" src="../${data.photo_path || 'uploads/default.jpg'}" 
                         class="rounded-circle mb-2" 
                         style="width: 150px; height: 150px; object-fit: cover;"
                         onerror="this.src='../uploads/default.jpg'">
                    <h5 class="mb-0">${data.first_name} ${data.middle_name || ''} ${data.last_name}</h5>
                    <p class="text-muted small">@${data.username}</p>
                </div>
                <div class="personal-info">
                    <h6 class="border-bottom pb-2">Personal Information</h6>
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="mb-2"><strong>Sex:</strong> ${data.sex || 'Not specified'}</p>
                            <p class="mb-2"><strong>Birthdate:</strong> ${formatDate(data.birthdate)}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-2"><strong>Phone:</strong> ${data.phone_number || 'Not specified'}</p>
                        </div>
                    </div>
                </div>
                <div class="membership-info mt-4">
                    <h6 class="border-bottom pb-2">Current Membership</h6>
                    ${membershipHtml}
                </div>
                <div class="rental-services mt-4">
                    <h6 class="border-bottom pb-2">Rental Services</h6>
                    ${rentalServicesHtml}
                </div>`;
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('#memberDetailsModal .modal-body').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    Failed to load member details. Please try again later.
                </div>`;
        });
}

function viewPaymentDetails(userId) {
    // Show loading state
    const loadingHtml = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading payment details...</p></div>';
    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    // Store the user ID in the modal's data attribute
    $('#paymentDetailsModal').data('userId', userId);
    document.querySelector('#paymentDetailsModal .modal-body').innerHTML = loadingHtml;
    modal.show();

    // Get base URL from current page URL
    const baseUrl = '<?php echo BASE_URL; ?>';

    fetch(`${baseUrl}/admin/pages/members/members_new.php?ajax_view_member=1&user_id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('No data received');
            }
            console.log('Member data:', data);

            let unpaidMemberships = data.memberships?.filter(m => !m.is_paid) || [];
            let unpaidRentals = data.rental_services?.filter(r => !r.is_paid) || [];
            
            console.log('Unpaid memberships:', unpaidMemberships);
            console.log('Unpaid rentals:', unpaidRentals);
            
            let content = `
                <h6>Member: ${data.first_name} ${data.middle_name || ''} ${data.last_name}</h6>
                <hr>
                <form id="paymentForm">
                    ${data.registration_records && data.registration_records.filter(r => !r.is_paid).length ? `
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Unpaid Registration</h6>
                                <div class="list-group">
                                    ${data.registration_records.filter(r => !r.is_paid).map(r => `
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input payment-checkbox" type="checkbox" 
                                                        value="${r.id}" 
                                                        data-type="registration"
                                                        data-amount="${r.amount}"
                                                        id="registration_${r.id}"
                                                        checked
                                                        onclick="return false;">
                                                    <label class="form-check-label" for="registration_${r.id}">
                                                        <h6 class="mb-1">${r.payment_type}</h6>
                                                        <small>Date: ${r.registration_date}</small>
                                                        <p class="mb-1">Amount: ₱${parseFloat(r.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${unpaidMemberships.length ? `
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Unpaid Memberships</h6>
                                <div class="list-group">
                                    ${unpaidMemberships.map(m => `
                                        <div class="list-group-item" id="membership-item-${m.id}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="form-check">
                                                    <input class="form-check-input payment-checkbox" type="checkbox" 
                                                        value="${m.id}" 
                                                        data-type="membership"
                                                        data-amount="${m.amount}"
                                                        id="membership_${m.id}">
                                                    <label class="form-check-label" for="membership_${m.id}">
                                                        <h6 class="mb-1">${m.plan_name}</h6>
                                                        <small>Valid: ${m.start_date} to ${m.end_date}</small>
                                                        <p class="mb-1">Amount: ₱${parseFloat(m.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="cancelItem('membership', ${m.id})"
                                                    title="Cancel Membership">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${unpaidRentals.length ? `
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Unpaid Rentals</h6>
                                <div class="list-group">
                                    ${unpaidRentals.map(r => `
                                        <div class="list-group-item" id="rental-item-${r.id}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="form-check">
                                                    <input class="form-check-input payment-checkbox" type="checkbox" 
                                                        value="${r.id}" 
                                                        data-type="rental"
                                                        data-amount="${r.amount}"
                                                        id="rental_${r.id}">
                                                    <label class="form-check-label" for="rental_${r.id}">
                                                        <h6 class="mb-1">${r.service_name}</h6>
                                                        <small>Valid: ${r.start_date} to ${r.end_date}</small>
                                                        <p class="mb-1">Amount: ₱${parseFloat(r.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="cancelItem('rental', ${r.id})"
                                                    title="Cancel Rental">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${(data.registration_records && data.registration_records.filter(r => !r.is_paid).length) || 
                      unpaidMemberships.length || 
                      unpaidRentals.length ? `
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Total Amount: ₱<span id="totalAmount">0.00</span></h5>
                                            <button type="button" class="btn btn-success" id="processSelectedPayments" disabled>
                                                Mark Selected as Paid
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : '<div class="alert alert-info">No unpaid items found.</div>'}
                </form>
            `;
            
            $('#paymentDetailsModal .modal-body').html(content);

            // Add event listeners for checkboxes
            $('.payment-checkbox').change(function() {
                updateTotalAmount();
            });

            // Calculate initial total for registration fee
            updateTotalAmount();

            function updateTotalAmount() {
                let total = 0;
                // Always include registration fees if they exist
                $('.payment-checkbox[data-type="registration"]').each(function() {
                    total += parseFloat($(this).data('amount'));
                });
                // Add other selected payments
                $('.payment-checkbox:not([data-type="registration"]):checked').each(function() {
                    total += parseFloat($(this).data('amount'));
                });
                $('#totalAmount').text(total.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
                
                // Enable/disable the process button based on selections
                // Button should be enabled if there are any checked items (registration is always checked)
                $('#processSelectedPayments').prop('disabled', $('.payment-checkbox:checked').length === 0);
            }

            // Handle process payments button click
            $('#processSelectedPayments').click(function() {
                const selectedPayments = [];
                $('.payment-checkbox:checked').each(function() {
                    selectedPayments.push({
                        id: $(this).val(),
                        type: $(this).data('type')
                    });
                });

                if (selectedPayments.length === 0) {
                    alert('Please select at least one item to process payment.');
                    return;
                }

                processMultiplePayments(userId, selectedPayments);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('#paymentDetailsModal .modal-body').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    Failed to load payment details. Please try again.
                </div>
            `;
        });
}

function cancelItem(type, itemId) {
    // Get the current user ID from the URL in the modal's AJAX call
    const userId = $('#paymentDetailsModal').data('userId');
    
    if (!confirm(`Are you sure you want to cancel this ${type}?`)) {
        return;
    }

    // Get base URL from PHP
    const baseUrl = '<?php echo BASE_URL; ?>';

    // Show loading state for the specific item
    const itemElement = $(`#${type}-item-${itemId}`);
    const originalContent = itemElement.html();
    itemElement.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Canceling...</div>');

    $.ajax({
        url: `${baseUrl}/admin/pages/members/cancel_item.php`,
        type: 'POST',
        data: {
            type: type,
            item_id: itemId,
            user_id: userId
        },
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    alert(result.message);
                    
                    // Only store userId for modal reopen if it wasn't the last item
                    if (!result.onlyRegistrationLeft) {
                        sessionStorage.setItem('reopen_payment_modal', userId);
                    }
                    
                    // Refresh the page to update all data
                    location.reload();
                } else {
                    // Restore original content and show error
                    itemElement.html(originalContent);
                    alert('Error: ' + (result.message || `Failed to cancel ${type}`));
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                itemElement.html(originalContent);
                alert(`Error canceling ${type}. Please try again.`);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            itemElement.html(originalContent);
            alert(`Error canceling ${type}. Please try again.`);
        }
    });
}

function processMultiplePayments(userId, payments) {
    if (!confirm('Are you sure you want to mark these as paid?')) {
        return;
    }
    
    // Get base URL from current page URL
    const baseUrl = '<?php echo BASE_URL; ?>';
    
    $.ajax({
        url: `${baseUrl}/admin/pages/members/process_payment.php`,
        type: 'POST',
        data: {
            user_id: userId,
            payments: JSON.stringify(payments)
        },
        success: function(response) {
            try {
                console.log('Server response:', response);
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    alert('Payments processed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process payments'));
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.error('Raw response:', response);
                alert('Error processing payments. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            alert('Error processing payments. Please try again.');
        }
    });
}

$(document).ready(function() {
  // Check for modal reopen after refresh
  const storedUserId = sessionStorage.getItem('reopen_payment_modal');
  if (storedUserId) {
    sessionStorage.removeItem('reopen_payment_modal');
    setTimeout(() => viewPaymentDetails(storedUserId), 100);
  }

  // Add member button click handler
  $('#add_member-link').on('click', function(e) {
    e.preventDefault();
    
    // First adjust the opacity with transition
    $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
      'opacity': '0',
      'transform': 'translateX(-20px)'
    });
    
    // Adjust main content with transition
    $('.main-content').css('margin-left', '0');
    
    // After the transition, hide the elements completely
    setTimeout(function() {
      $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
        'display': 'none',
        'transform': 'translateX(0)'
      });
    }, 300);
    
    $.ajax({
      type: "GET",
      url: "pages/members/add_member.php",
      dataType: "html",
      success: function(response) {
        $(".main-content").html(response);
      },
      error: function() {
        alert("Error loading the add member form.");
        // Show navigation elements back with transition
        $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
          'display': '',
          'opacity': '0',
          'transform': 'translateX(-20px)'
        });
        setTimeout(function() {
          $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
            'opacity': '1',
            'transform': 'translateX(0)'
          });
          $('.main-content').css('margin-left', '');
        }, 50);
      }
    });
  });
});

$(document).ready(function() {
  // Event delegation for renew member links
  $(document).on('click', '.renew_member-link', function(e) {
    e.preventDefault();
    
    const memberId = $(this).data('member-id');
    console.log('Renewing member with ID:', memberId);
    
    // First adjust the opacity with transition
    $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
      'opacity': '0',
      'transform': 'translateX(-20px)'
    });
    
    // Adjust main content with transition
    $('.main-content').css('margin-left', '0');
    
    // After the transition, hide the elements completely
    setTimeout(function() {
      $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
        'display': 'none',
        'transform': 'translateX(0)'
      });
    }, 300);
    
    $.ajax({
      type: "GET",
      url: "pages/members/renew_member.php",
      data: { member_id: memberId },
      dataType: "html",
      success: function(response) {
        $(".main-content").html(response);
      },
      error: function() {
        alert("Error loading the renew member form.");
        // Show navigation elements back with transition
        $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
          'display': '',
          'opacity': '0',
          'transform': 'translateX(-20px)'
        });
        setTimeout(function() {
          $('.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay').css({
            'opacity': '1',
            'transform': 'translateX(0)'
          });
          $('.main-content').css('margin-left', '');
        }, 50);
      }
    });
  });
});
</script>
