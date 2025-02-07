<?php
  require_once("functions/members.class.php");
  
  // Handle AJAX request for member details
  if(isset($_GET['ajax_view_member']) && isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    try {
        $members = new Members();
        $memberDetails = $members->getMemberDetails($_GET['user_id']);
        
        if ($memberDetails === null) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch member details']);
        } else {
            echo json_encode($memberDetails);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
  }
  
  $members = new Members();
  $membersList = $members->getAllMembers();
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="nav-title">Members</h1>
    <a href="#" class="btn btn-primary" id="add_member-link">
      <i class="fas fa-user-plus"></i> Add Member
    </a>
  </div>

  <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr class="text-center">
                    <th>Photo</th>
                    <th>Member</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
              <?php if(!empty($membersList)): ?>
                  <?php foreach($membersList as $member): ?>
                  <tr class="text-center">
                      <td>
                          <img src="../<?php echo $member['photo_path'] ?? 'uploads/default.jpg'; ?>" 
                               class="rounded-circle"
                               alt="Profile Photo" 
                               width="50" 
                               height="50"
                               onerror="this.src='../uploads/default.jpg'"
                               style="object-fit: cover;">
                      </td>
                      <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                      <td>
                          <?php 
                          switch($member['status']) {
                              case 'active':
                                  $statusClass = 'success';
                                  break;
                              case 'pending':
                                  $statusClass = 'warning';
                                  break;
                              default:
                                  $statusClass = 'danger';
                          }
                          ?>
                          <span class="badge bg-<?php echo $statusClass; ?>">
                              <?php echo ucfirst($member['status']); ?>
                          </span>
                      </td>
                      <td>
                          <?php 
                          switch($member['payment_status']) {
                              case 'paid':
                                  $paymentClass = 'success';
                                  break;
                              case 'unpaid':
                                  $paymentClass = 'danger';
                                  break;
                              default:
                                  $paymentClass = ' ';
                          }
                          ?>
                          <span class="badge bg-<?php echo $paymentClass; ?>">
                              <?php echo ucfirst($member['payment_status']); ?>
                          </span>
                      </td>
                      <td>
                          <button type="button" class="btn btn-primary btn-sm" onclick="viewMemberDetails(<?php echo $member['user_id']; ?>)">
                              View
                          </button>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr>
                      <td colspan="5" class="text-center">No members found</td>
                  </tr>
              <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Member Details Modal -->
<div class="modal fade" id="memberDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img id="memberPhoto" class="rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 id="memberName" class="mb-0"></h5>
                        <p id="memberUsername" class="text-muted small"></p>
                    </div>
                    <div class="col-md-8">
                        <h6 class="border-bottom pb-2">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Sex:</strong> <span id="memberSex"></span></p>
                                <p class="mb-1"><strong>Birthdate:</strong> <span id="memberBirthdate"></span></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Phone:</strong> <span id="memberPhone"></span></p>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2">Membership Details</h6>
                        <div id="membershipDetails" class="mb-3">
                            <!-- Membership details will be inserted here -->
                        </div>

                        <h6 class="border-bottom pb-2">Program Subscriptions</h6>
                        <div id="programsList" class="mb-3">
                            <!-- Programs will be inserted here -->
                        </div>

                        <h6 class="border-bottom pb-2">Rental Services</h6>
                        <div id="rentalsList">
                            <!-- Rentals will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Add transition styles */
.sidebar, .burger-menu, .sidebar-overlay, #sidebar, #burgerMenu, #sidebarOverlay {
    transition: all 0.3s ease-in-out !important;
}
.main-content {
    transition: margin-left 0.3s ease-in-out !important;
}
</style>

<script>
function viewMemberDetails(userId) {
    // Show loading state
    const loadingHtml = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading member details...</p></div>';
    const modal = new bootstrap.Modal(document.getElementById('memberDetailsModal'));
    document.querySelector('#memberDetailsModal .modal-body').innerHTML = loadingHtml;
    modal.show();

    // Fetch member details with full path
    fetch(`../admin/pages/members/members_new.php?ajax_view_member=1&user_id=${userId}`)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server responded with status ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('Failed to load member details');
            }

            // Prepare the modal content
            const modalContent = `
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img src="${data.photo_path ? '../' + data.photo_path : '../assets/img/default-profile.jpg'}" 
                             alt="Profile Photo" 
                             class="img-fluid rounded-circle mb-2" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="col-md-8">
                        <h4 class="mb-3">${data.first_name} ${data.middle_name || ''} ${data.last_name}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Username:</strong> ${data.username}</p>
                                <p><strong>Sex:</strong> ${data.sex || 'N/A'}</p>
                                <p><strong>Birthdate:</strong> ${data.birthdate || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${data.phone_number || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Membership Status:</strong> 
                                    <span class="badge ${getBadgeClass(data.membership_status)}">
                                        ${data.membership_status}
                                    </span>
                                </p>
                                ${data.membership_status !== 'Inactive' ? `
                                    <p><strong>Payment Status:</strong> 
                                        <span class="badge ${getPaymentBadgeClass(data.payment_status)}">
                                            ${data.payment_status}
                                        </span>
                                    </p>
                                    <p><strong>Total Price:</strong> ₱${data.total_price || '0'}</p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                ${data.membership_status !== 'Inactive' ? `
                    <hr>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Membership Details</h5>
                            <p><strong>Plan:</strong> ${data.membership_plan_name || 'N/A'}</p>
                            <p><strong>Price:</strong> ₱${data.membership_amount || '0'}</p>
                            <p><strong>Start Date:</strong> ${data.membership_start || 'N/A'}</p>
                            <p><strong>End Date:</strong> ${data.membership_end || 'N/A'}</p>
                            ${data.has_registration_fee === 'Yes' ? 
                                `<p><strong>Registration Fee:</strong> ₱${data.registration_fee}</p>` 
                                : ''
                            }
                        </div>
                    </div>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Program Subscriptions</h5>
                            ${data.program_details ? 
                                `<div class="list-group">
                                    ${data.program_details.split('\n').map(program => {
                                        const [name, coach, duration, price, status] = program.split(' | ');
                                        return `
                                            <div class="list-group-item">
                                                <h6 class="mb-1">${name}</h6>
                                                <p class="mb-1 text-muted small">${coach}</p>
                                                <p class="mb-1 text-muted small">${duration}</p>
                                                <p class="mb-0 text-muted small">${price}</p>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>`
                                : '<p class="text-muted">No program subscriptions</p>'
                            }
                        </div>
                    </div>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Rental Services</h5>
                            ${data.rental_details ? 
                                `<div class="list-group">
                                    ${data.rental_details.split('\n').map(rental => {
                                        const [name, duration, price, status] = rental.split(' | ');
                                        return `
                                            <div class="list-group-item">
                                                <h6 class="mb-1">${name}</h6>
                                                <p class="mb-1 text-muted small">${duration}</p>
                                                <p class="mb-0 text-muted small">${price}</p>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>`
                                : '<p class="text-muted">No rental services</p>'
                            }
                        </div>
                    </div>
                ` : ''}
            `;

            // Update modal content
            document.querySelector('#memberDetailsModal .modal-body').innerHTML = modalContent;
        })
        .catch(error => {
            document.querySelector('#memberDetailsModal .modal-body').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    Failed to load member details. Please try again later.
                </div>
            `;
        });
}

function getBadgeClass(status) {
    switch (status?.toLowerCase()) {
        case 'active':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'inactive':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getPaymentBadgeClass(status) {
    switch (status?.toLowerCase()) {
        case 'paid':
            return 'bg-success';
        case 'unpaid':
            return 'bg-danger';
    }
}
</script>

<script>
$(document).ready(function() {
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
</script>
