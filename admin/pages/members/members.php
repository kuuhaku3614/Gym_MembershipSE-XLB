<?php
session_start();
require_once 'config.php';
include '../../pages/modal.php';

// Ensure proper session validation
function validateSession() {
    // First check if required session variables exist
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return [
            'success' => false,
            'message' => 'Please log in to continue'
        ];
    }

    // Validate user exists and is active
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.id, u.role_id, u.is_active, r.role_name as role
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ? AND u.is_active = TRUE
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid user session'
        ];
    }

    // Validate role matches session
    if ($user['role_id'] != $_SESSION['role_id']) {
        return [
            'success' => false,
            'message' => 'Invalid role assignment'
        ];
    }

    // Check if role_id is valid (admin=1, staff=2, coach=3, member=4)
    if (!in_array($user['role_id'], [1, 2, 3, 4])) {
        return [
            'success' => false,
            'message' => 'Unauthorized access'
        ];
    }

    return [
        'success' => true,
        'user' => $user
    ];
}

// Example usage:
$sessionValidation = validateSession();
if (!$sessionValidation['success']) {
    // Handle invalid session
    echo json_encode([
        'success' => false,
        'message' => $sessionValidation['message']
    ]);
    exit;
}

// If validation succeeds, you can use the user data
$currentUser = $sessionValidation['user'];


// Define the upload directory relative to the project root
$uploadsDir = dirname(__DIR__, 3) . '/uploads'; // Move up two levels from the current file's directory

// Check if the directory exists, if not, create it
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Query to fetch members
$query = "
    SELECT 
        u.id AS user_id, 
        u.username, 
        pd.first_name, 
        pd.middle_name, 
        pd.last_name, 
        pd.sex, 
        pd.birthdate, 
        pd.phone_number, 
        COALESCE(pp.photo_path, NULL) AS photo_path, 
        CASE 
            WHEN ms.is_paid = 1 THEN 'Paid'
            ELSE 'Pending'
        END AS payment_status,
        COALESCE(ms.amount, 0) AS membership_amount,
        COALESCE(ps.amount, 0) AS program_subscriptions_amount,
        COALESCE(rs.amount, 0) AS rental_services_amount,
        COALESCE(rr.Amount, 0) AS registration_fee_paid,
        GROUP_CONCAT(DISTINCT 
            CONCAT(prg.program_name, ' (', 
                CASE WHEN ps.is_paid = 1 THEN 'Paid' ELSE 'Pending' END, 
            ')') 
            SEPARATOR ', '
        ) AS subscribed_programs, 
        GROUP_CONCAT(DISTINCT 
            CONCAT(rsvc.service_name, ' (', 
                CASE WHEN rs.is_paid = 1 THEN 'Paid' ELSE 'Pending' END, 
            ')') 
            SEPARATOR ', '
        ) AS rental_services,
        (
            COALESCE(ms.amount, 0) + 
            COALESCE(ps.amount, 0) + 
            COALESCE(rs.amount, 0) + 
            COALESCE(rr.Amount, 0)
        ) AS total_price,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM memberships m 
                JOIN transactions t ON m.transaction_id = t.id
                WHERE t.user_id = u.id 
                AND m.status = 'active' 
                AND m.end_date >= CURDATE()
            ) THEN 'Active'
            ELSE 'Inactive'
        END AS membership_status
    FROM 
        users u 
    JOIN 
        roles roles ON u.role_id = roles.id AND roles.id = 3 
    LEFT JOIN 
        transactions t ON u.id = t.user_id
    LEFT JOIN 
        memberships ms ON t.id = ms.transaction_id
    LEFT JOIN 
        personal_details pd ON u.id = pd.user_id 
    LEFT JOIN 
        profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1 
    LEFT JOIN 
        program_subscriptions ps ON t.id = ps.transaction_id 
    LEFT JOIN 
        programs prg ON ps.program_id = prg.id 
    LEFT JOIN 
        rental_subscriptions rs ON t.id = rs.transaction_id 
    LEFT JOIN 
        rental_services rsvc ON rs.rental_service_id = rsvc.id 
    LEFT JOIN 
        registration_records rr ON t.id = rr.transaction_id
    WHERE 
        u.is_active = 1 
    GROUP BY 
        u.id, 
        u.username, 
        pd.first_name, 
        pd.middle_name, 
        pd.last_name, 
        pd.sex, 
        pd.birthdate, 
        pd.phone_number, 
        pp.photo_path, 
        ms.is_paid,
        ms.amount,
        ps.amount,
        rs.amount,
        rr.Amount;
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

function registration_fee() {
    global $pdo;
    $query = "SELECT membership_fee FROM registration LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $fee = $stmt->fetchColumn();
    return '₱' . number_format($fee, 2);
}
?>


    <!-- Styles -->
    <style>
    /* Modal Header and Footer */
    .modal-header {
        border-bottom: 1px solid #ddd;
        padding: 1rem;
    }

    .modal-footer {
        border-top: 1px solid #ddd;
        padding: 1rem;
    }

    /* Member Photo Styling */
    .member-photo {
        border: 3px solid #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .member-photo:hover {
        transform: scale(1.1);
    }

    /* Section Styling */
    .info-section {
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
    }

    .info-header {
        font-size: 1.125rem;
        font-weight: bold;
        color: #495057;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #ddd;
        padding-bottom: 0.5rem;
    }

    /* Member Name */
    .member-name {
        font-size: 1.25rem;
        color: #2c3e50;
    }

    /* Badge Styling */
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .card-body#programsContainer,
    .card-body#rentalsContainer {
        max-height: 320px; /* Adjust the height as needed */
        overflow-y: auto;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none;  /* Internet Explorer 10+ */
    }

    /* WebKit browsers (Chrome, Safari) */
    .card-body#programsContainer::-webkit-scrollbar,
    .card-body#rentalsContainer::-webkit-scrollbar {
        display: none;
    }

    .service-box {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid #ddd;
        margin-bottom: 10px;
        padding: 10px;
    }

    .service-box:hover {
        background-color: #f8f9fa;
        border-color: #007bff;
    }

    .service-box.selected {
        background-color: #e9ecef;
        border-color: #007bff;
        position: relative;
    }

    .service-box.selected::after {
        content: '✓';
        position: absolute;
        top: 5px;
        right: 5px;
        color: #28a745;
        font-weight: bold;
    }

    /* Responsive Design */
    @media (max-width: 576px) {
        .modal-body .row {
            flex-direction: column;
        }

        .info-section {
            margin-bottom: 1rem;
        }
    }
</style>


<!-- Main Container -->
<div class="container-fluid">
<h1 class="nav-title">Members</h1>

    <!-- Add Member Button -->
    <button type="button" class="btn btn-primary mb-4" id="addMemberBtn">
        <i class="fas fa-plus mr-2"></i>Add New Member
    </button>
<!-- Modified Members Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Members List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-center">Profile</th>
                        <th>Member Name</th>
                        <th>Payment Status</th>
                        <th>Membership Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td class="align-middle text-center">
                                    <div class="member-photo-container">
                                        <img src="../<?= htmlspecialchars($member['photo_path'] ?? 'uploads/default.jpg'); ?>" 
                                            class="img-fluid rounded-circle member-photo" 
                                            style="width: 60px; height: 60px; object-fit: cover;"
                                            alt="Profile Photo">
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) ?: 'N/A'; ?></td>
                                <td><?= htmlspecialchars($member['payment_status']) ?: 'Unknown'; ?></td>
                                <td>
                                    <?php 
                                        $status = htmlspecialchars($member['membership_status']); 
                                        $statusClass = $status === 'Active' ? 'text-success' : 'text-danger';
                                        echo "<span class='$statusClass'>$status</span>";
                                    ?>
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-info view-member mr-1" data-id="<?= htmlspecialchars($member['user_id']); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-member" data-id="<?= htmlspecialchars($member['user_id']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <i class="fas fa-users"></i> No members found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    <!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center">
    <h5 class="modal-title">Add New Member</h5>
    <button type="button" class="close border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
            <div class="modal-body">
                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Phase 1/3</div>
                </div>
                <form id="membershipForm" method="POST" enctype="multipart/form-data">
                    <!-- Hidden Fields for User Data -->
                    <input type="hidden" id="userId" name="staff_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                    <input type="hidden" id="userRole" name="user_role" value="<?php echo htmlspecialchars($_SESSION['role_id']); ?>">
                    <input type="hidden" id="currentPhase" name="current_phase" value="1">
                    
                    <!-- Phase 1: Member Details -->
                    <div id="phase1" class="phase-content">
                        <div class="row">
                            <!-- Left Section - Personal Details -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Personal Details</h6>
                                
                                <!-- Profile Photo Upload -->
                                <div class="form-group mb-4">
                                    <label>Profile Photo</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="profile_photo" name="profile_photo" accept=".jpg, .jpeg, .png">
                                        <label class="custom-file-label" for="profile_photo">Choose file</label>
                                    </div>
                                    <div id="preview" class="mt-2"></div>
                                </div>

                                <div id="new_user_form">
                                    <!-- Basic details visible to both admin and staff -->
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" class="form-control" name="first_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control" name="last_name">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Sex</label>
                                            <select class="form-control" name="sex">
                                                <option value="" selected disabled>--select--</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Birthdate</label>
                                            <input type="date" class="form-control" name="birthdate">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" class="form-control" name="phone">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Section - Membership Details -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Membership Details</h6>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" name="username">
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" class="form-control" name="password">
                                </div>
                                
                                <!-- Staff and Admin section -->
                                <div class="staff-only">
                                    <div class="form-group">
                                        <label>Membership Plan</label>
                                        <select class="form-control" name="membership_plan" id="membership_plan">
                                            <option value="">Select Plan</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" id="start_date">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" id="end_date" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="text" class="form-control" name="price" id="price" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Registration Fee</label>
                                        <input type="text" class="form-control-plaintext bg-light p-2 rounded" 
                                            name="registration_fee" 
                                            id="registration_fee" 
                                            readonly 
                                            placeholder="Registration fee will be auto-filled" 
                                            value="<?= registration_fee() ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 2: Services -->
                    <div id="phase2" class="phase-content" style="display: none;">
                        <!-- Availed Services Container -->
                        <div class="col-md-12 mt-4">
                                <div class="card availed-container">
                                    <div class="card-header">
                                        <h6>Membership Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Regular services visible to both -->
                                        <div class="staff-only">
                                            <div class="membership-details mb-3">
                                                <h6>Membership Plan</h6>
                                                <div id="selected_plan_details"></div>
                                            </div>
                                            <div class="additional-services">
                                                <h6>Additional Services</h6>
                                                <div id="selected_services"></div>
                                            </div>
                                            <div class="total-amount mt-3">
                                                <h6>Total Amount: <span id="total_amount">₱0.00</span></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>          
                        <div class="row">
                            <!-- Programs Section -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>Available Programs</h6>
                                    </div>
                                    <div class="card-body" id="programsContainer">
                                        <!-- Programs will be dynamically loaded here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Rentals Section -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>Available Rental Services</h6>
                                    </div>
                                    <div class="card-body" id="rentalsContainer">
                                        <!-- Rentals will be dynamically loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 3: Verification -->
                    <div id="phase3" class="phase-content" style="display: none;">
                        <div class="verification-container text-center">
                            <h6>Phone Verification</h6>
                            <p>Enter verification code sent to <span id="phone_display"></span></p>
                            <p><strong>Mock Code:</strong> <span id="verificationCodeDisplay">Loading...</span></p>
                            <input type="text" class="form-control verification-input" maxlength="6" required>
                            <button class="btn btn-link resend-code">Resend code</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">Previous</button>
                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
            </div>
        </div>
    </div>
</div>
<!-- Success Modal -->
<!-- <div
  class="modal fade"
  id="successModal"
  tabindex="-1"
  aria-labelledby="successModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">Success!</h5>
        <button
          type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>
      <div class="modal-body text-center">
        <i class="fas fa-check-circle text-success" style="font-size: 48px"></i>
        <p class="mt-3">Membership created successfully!</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div> -->
<!-- full details modal -->
<div class="modal fade" id="memberViewModal" tabindex="-1" role="dialog" aria-labelledby="memberViewModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="memberViewModalLabel">Member Details</h5>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-4 text-center">
                        <div class="member-photo-container mb-3">
                            <img 
                                id="memberPhoto" 
                                src="" 
                                alt="Member Photo" 
                                class="img-fluid rounded-circle shadow member-photo"
                                style="width: 150px; height: 150px; object-fit: cover;"
                            >
                        </div>
                        <div class="text-center">
                            <h5 id="memberName" class="member-name text-dark font-weight-bold mb-2"></h5>
                            <p id="memberUsername" class="text-muted mb-2"></p>
                            <span class="badge badge-secondary" id="membershipStatus"></span>
                        </div>
                    </div>
                    <!-- Right Column -->
                    <div class="col-md-8">
                        <div class="info-section mb-4">
                            <h6 class="info-header">Personal Information</h6>
                            <p><strong>Sex:</strong> <span id="memberSex"></span></p>
                            <p><strong>Birthdate:</strong> <span id="memberBirthdate"></span></p>
                            <p><strong>Phone:</strong> <span id="memberPhone"></span></p>
                        </div>
                        <div class="info-section mb-4">
                            <h6 class="info-header">Membership Details</h6>
                            <p><strong>Plan:</strong> <span id="memberPlan"></span></p>
                            <p><strong>Total Amount:</strong> <span id="totalAmount" class="text-primary font-weight-bold"></span></p>
                        </div>
                        <div class="info-section mb-4">
                            <h6 class="info-header">Subscription Period</h6>
                            <p><strong>Start Date:</strong> <span id="membershipStart"></span></p>
                            <p><strong>End Date:</strong> <span id="membershipEnd"></span></p>
                        </div>
                        <div class="info-section mb-4">
                            <h6 class="info-header">Programs Availed</h6>
                            <p id="memberPrograms" class="text-muted">None</p>
                        </div>
                        <div class="info-section">
                            <h6 class="info-header">Services Availed</h6>
                            <p id="memberServices" class="text-muted">None</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div id="paymentButtonContainer" class="mr-auto"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editMemberBtn">Edit Member</button>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    const MembershipManager = {
    state: {
        currentPhase: 1,
        selectedPrograms: [],
        selectedRentals: [],
        totalAmount: 0,
        VERIFICATION_CODE: '123456',
        formData: new FormData(),
        isInitialized: false,
        selectedCoaches: {} // New object to track coaches for selected programs
    },

    init() {
        if (this.state.isInitialized) return;
        
        this.state.isInitialized = true;
        
        // Initialize with current user's session data
        this.state.formData.set('staff_id', document.getElementById('userId').value);
        this.state.formData.set('user_role', document.getElementById('userRole').value);
        
        this.bindEvents();
        this.loadMembershipPlans();
        this.loadMembershipServices();
        this.initializeModal();
    },

    initializeModal() {
        $('#addMemberModal').modal({
            show: false,
            backdrop: 'static',
            keyboard: false
        });

        // Optional: Add explicit close event handling
        $('#addMemberModal').on('click', '[data-dismiss="modal"]', function() {
            $('#addMemberModal').modal('hide');
        });
    },
    bindEvents() {
        // Existing event bindings
        $(document).on('click', '#addMemberBtn', (e) => {
            e.preventDefault();
            this.resetAndShowModal();
        });

        $(document).on('change', '#membership_plan', () => this.handlePlanChange());
        $(document).on('change', '#start_date', () => {
            if ($('#membership_plan').val()) {
                this.handlePlanChange();
            }
        });
        
        $('#nextBtn').on('click', () => this.handleNextPhase());
        $('#prevBtn').on('click', () => this.handlePrevPhase());
        
        this.bindServiceEvents();
        this.bindFileUpload();
        this.bindDeleteMember();
        
        $('#membershipForm').on('change', 'input, select', (e) => {
            this.updateFormData(e.target);
        });

        this.handleRoleBasedAccess();
    },
    loadMembershipServices() {
    // Load Programs
    $.get('../../../get_program_details.php', (response) => {
        $('#programsContainer').html(response);
        
        // Bind click events for adding programs
        $('.program').on('click', (e) => {
            const $program = $(e.currentTarget);
            const programId = $program.data('id');
            const programName = $program.find('.program-name').text();
            const $coachSelect = $program.find('.default-coach-id');
            const programPrice = parseFloat($program.find('.program-price').text().replace('₱', ''));

            // Check if program is already added
            const exists = this.state.selectedPrograms.some(p => p.id === programId);
            
            if (!exists) {
                // If no coaches available, show an alert or use default coach
                if ($coachSelect.length === 0) {
                    alert('No coaches available for this program.');
                    return;
                }

                // Show coach selection modal
                $('#coachSelectionModal').remove(); // Remove any existing modal
                const modalHtml = this.createCoachSelectionModal($program);
                $('body').append(modalHtml);
                $('#coachSelectionModal').modal('show');

                // Bind confirm button in modal
                $('#confirmCoachSelection').off('click').on('click', () => {
                    const selectedCoachId = $('#coachSelectionModal select').val();
                    const selectedCoachName = $('#coachSelectionModal select option:selected').text();

                    this.state.selectedPrograms.push({
                        id: programId,
                        name: programName,
                        price: programPrice,
                        coachId: selectedCoachId,
                        coachName: selectedCoachName
                    });

                    // Store selected coach for this program
                    this.state.selectedCoaches[programId] = {
                        coachId: selectedCoachId,
                        coachName: selectedCoachName
                    };

                    $program.addClass('selected');
                    this.updateTotalAmount();
                    this.updateSelectedServices();
                    $('#coachSelectionModal').modal('hide');
                });
            } else {
                // Remove program if already added
                this.state.selectedPrograms = this.state.selectedPrograms.filter(p => p.id !== programId);
                delete this.state.selectedCoaches[programId];
                $program.removeClass('selected');
                this.updateTotalAmount();
                this.updateSelectedServices();
            }
        });
    });

        // Load Rentals
        $.get('../../../get_rental_details.php', (response) => {
            $('#rentalsContainer').html(response);
            
            // Bind click events for adding rentals
            $('.rental').on('click', (e) => {
                const $rental = $(e.currentTarget);
                const rentalId = $rental.data('id');
                const rentalName = $rental.find('.rental-name').text();
                const rentalPrice = parseFloat($rental.find('.rental-price').text().replace('₱', ''));

                // Check if rental is already added
                const exists = this.state.selectedRentals.some(r => r.id === rentalId);
                if (!exists) {
                    this.state.selectedRentals.push({
                        id: rentalId,
                        name: rentalName,
                        price: rentalPrice
                    });

                    $rental.addClass('selected');
                    this.updateTotalAmount();
                    this.updateSelectedServices();
                } else {
                    // Remove rental if already added
                    this.state.selectedRentals = this.state.selectedRentals.filter(r => r.id !== rentalId);
                    $rental.removeClass('selected');
                    this.updateTotalAmount();
                    this.updateSelectedServices();
                }
            });
        });
    },
    updateTotalAmount() {
        const planOption = $('#membership_plan option:selected');
        const planPrice = parseFloat(planOption.data('price')) || 0;

        const programsTotal = this.state.selectedPrograms.reduce((sum, program) => sum + program.price, 0);
        const rentalsTotal = this.state.selectedRentals.reduce((sum, rental) => sum + rental.price, 0);

        this.state.totalAmount = planPrice + programsTotal + rentalsTotal;
        
        $('#total_amount').text('₱' + this.state.totalAmount.toFixed(2));
    },
    createCoachSelectionModal(programElement) {
        const programName = programElement.find('.program-name').text();
        const coachSelect = programElement.find('.coach-select').clone();
        
        return `
            <div class="modal fade" id="coachSelectionModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Coach for ${programName}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="coachSelection">Choose a Coach:</label>
                                ${coachSelect[0].outerHTML}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmCoachSelection">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    updateSelectedServices() {
        const planOption = $('#membership_plan option:selected');
        const planName = planOption.text();
        const planPrice = parseFloat(planOption.data('price')) || 0;

        let servicesHtml = `
            <div class="service-item">
                <span>Membership Plan: ${planName}</span>
            </div>
        `;

        this.state.selectedPrograms.forEach(program => {
            servicesHtml += `
                <div class="service-item">
                    <span>${program.name} (Coach: ${program.coachName})</span>
                    <span class="float-right">₱${program.price.toFixed(2)}</span>
                </div>
            `;
        });

        this.state.selectedRentals.forEach(rental => {
            servicesHtml += `
                <div class="service-item">
                    <span>${rental.name}</span>
                    <span class="float-right">₱${rental.price.toFixed(2)}</span>
                </div>
            `;
        });

        $('#selected_services').html(servicesHtml);
        $('#total_amount').text('₱' + this.state.totalAmount.toFixed(2));
    },
    handleRoleBasedAccess() {
        const userRole = $('#userRole').val();
        
        // Show/hide elements based on role
        if (userRole === '1') { // Admin
            $('.admin-only').show();
            $('.staff-only').show();
        } else if (userRole === '2') { // Staff
            $('.admin-only').hide();
            $('.staff-only').show();
        } else {
            $('.admin-only, .staff-only').hide();
        }
    },

    updateFormData(element) {
        if (element.type === 'file') {
            this.state.formData.set(element.name, element.files[0]);
        } else {
            this.state.formData.set(element.name, element.value);
        }
    },

    bindFileUpload() {
        $('#profile_photo').change(function() {
            const file = this.files[0];
            const reader = new FileReader();
            
            $(this).next('.custom-file-label').html(file.name);
            
            reader.onload = function(e) {
                $('#preview').html(`
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                `);
            }
            
            reader.readAsDataURL(file);
        });
    },

    bindDeleteMember() {
    let memberIdToDelete;

    $(document).on('click', '.delete-member', (e) => {
        memberIdToDelete = $(e.target).data('id');
        $('#deleteModal').modal('show'); // Show the modal
    });

    // Handle the confirmation button click
    $('#confirmDelete').on('click', () => {
        if (memberIdToDelete) {
            this.deleteMember(memberIdToDelete);
            $('#deleteModal').modal('hide'); // Hide the modal after deletion
        }
    });
},

    bindServiceEvents() {
        $(document).on('click', '.program', (e) => {
            const programId = $(e.target).closest('.program').data('id');
            this.loadProgramDetails(programId);
        });

        $(document).on('click', '.rental', (e) => {
            const rentalId = $(e.target).closest('.rental').data('id');
            this.loadRentalDetails(rentalId);
        });

        $('#addProgramDetailBtn').click(() => this.addProgramToMembership());
        $('#addRentalDetailBtn').click(() => this.addRentalToMembership());
    },

    loadProgramDetails(programId) {
        $('#programDetailContent').load('get_program_details.php?id=' + programId, function(response, status, xhr) {
            if (status == "success") {
                $('#programDetailModal').modal('show');
            } else {
                console.error("Error loading program details:", xhr.status, xhr.statusText);
            }
        });
    },

    loadRentalDetails(rentalId) {
        $('#rentalDetailContent').load('get_rental_details.php?id=' + rentalId, function(response, status, xhr) {
            if (status == "success") {
                $('#rentalDetailModal').modal('show');
            } else {
                console.error("Error loading rental details:", xhr.status, xhr.statusText);
            }
        });
    },

    resetAndShowModal() {
        // Reset state
        this.state.currentPhase = 1;
        this.state.selectedPrograms = [];
        this.state.selectedRentals = [];
        this.state.totalAmount = 0;
        this.state.formData = new FormData();
        
        // Reset form and UI
        $('#membershipForm')[0].reset();
        $('#preview').empty();
        $('.phase-content').hide();
        $('#phase1').show();
        
        // Reset progress bar
        $('.progress-bar')
            .css('width', '33%')
            .attr('aria-valuenow', 33)
            .text('Phase 1/3');
        
        // Reset buttons
        $('#prevBtn').hide();
        $('#nextBtn').text('Next').show();
        
        // Reset service selection
        $('#programsContainer .program, #rentalsContainer .rental').removeClass('selected');
        $('#selected_services').empty();
        $('#total_amount').text('₱0.00');
        
        // Show modal
        $('#addMemberModal').modal('show');
        
        // Load membership plans and services
        this.loadMembershipPlans();
        this.loadMembershipServices();
        this.initializeModal();
    },

    handlePlanChange() {
        const selectedOption = $('#membership_plan option:selected');
        if (!selectedOption.val()) {
            $('#end_date').val('');
            $('#price').val('');
            this.state.formData.set('end_date', '');  // Clear from FormData
            this.state.formData.set('start_date', ''); // Clear from FormData
            this.updateTotalAmount();
            return;
        }

        const duration = parseInt(selectedOption.data('duration'));
        const durationType = parseInt(selectedOption.data('duration-type'));
        const price = parseFloat(selectedOption.data('price'));
        
        // Get or set start date
        let startDate = $('#start_date').val();
        if (!startDate) {
            startDate = new Date().toISOString().split('T')[0];
            $('#start_date').val(startDate);
        }

        // Calculate end date
        const endDate = this.calculateEndDate(startDate, duration, durationType);
        
        // Update fields and FormData
        $('#end_date').val(endDate);
        $('#price').val(price.toFixed(2));
        
        // Explicitly update FormData with dates
        this.state.formData.set('start_date', startDate);
        this.state.formData.set('end_date', endDate);
        this.state.formData.set('price', price.toFixed(2));
        
        this.updateTotalAmount();
    },

    calculateEndDate(startDate, duration, type) {
        const date = new Date(startDate);
        switch(type) {
            case 1: date.setDate(date.getDate() + duration); break;
            case 2: date.setMonth(date.getMonth() + duration); break;
            case 3: date.setFullYear(date.getFullYear() + duration); break;
        }
        return date.toISOString().split('T')[0];
    },


    updateSelectedServices() {
        const planOption = $('#membership_plan option:selected');
        const planName = planOption.text();
        const planPrice = parseFloat(planOption.data('price')) || 0;

        let servicesHtml = `
            <div class="service-item">
                <span>Membership Plan: ${planName}</span>
                <span class="float-right">₱${planPrice.toFixed(2)}</span>
            </div>
        `;

        this.state.selectedPrograms.forEach(program => {
            servicesHtml += `
                <div class="service-item">
                    <span>${program.name}</span>
                    <span class="float-right">₱${program.price.toFixed(2)}</span>
                </div>
            `;
        });

        this.state.selectedRentals.forEach(rental => {
            servicesHtml += `
                <div class="service-item">
                    <span>${rental.name}</span>
                    <span class="float-right">₱${rental.price.toFixed(2)}</span>
                </div>
            `;
        });

        $('#selected_services').html(servicesHtml);
        $('#total_amount').text('₱' + this.state.totalAmount.toFixed(2));
    },

    handleNextPhase() {
        if (this.validateCurrentPhase()) {
            if (this.state.currentPhase === 3) {
                this.processMembership();
            } else {
                this.state.currentPhase++;
                this.state.formData.set('current_phase', this.state.currentPhase.toString());
                this.updatePhaseDisplay();
                
                // If entering phase 2, update services display
                if (this.state.currentPhase === 2) {
                    this.updateSelectedServices();
                }
                // If entering phase 3, update verification display
                else if (this.state.currentPhase === 3) {
                    const phoneNumber = $('input[name="phone"]').val();
                    $('#phone_display').text(phoneNumber);
                    $('#verificationCodeDisplay').text(this.state.VERIFICATION_CODE);
                }
            }
        }
    },

    handlePrevPhase() {
        if (this.state.currentPhase > 1) {
            this.state.currentPhase--;
            this.updatePhaseDisplay();
        }
    },

    validateCurrentPhase() {
        let isValid = true;
        const phase = this.state.currentPhase;
        
        // Clear previous validation messages
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // if (phase === 1) {
        //     // Required fields for phase 1
        //     const requiredFields = {
        //         'first_name': 'First Name',
        //         'last_name': 'Last Name',
        //         'sex': 'Sex',
        //         'birthdate': 'Birthdate',
        //         'phone': 'Phone Number',
        //         'username': 'Username',
        //         'password': 'Password',
        //         'membership_plan': 'Membership Plan',
        //         'start_date': 'Start Date'
        //     };

        //     for (const [fieldName, label] of Object.entries(requiredFields)) {
        //         const field = $(`[name="${fieldName}"]`);
        //         if (!field.val() || field.val().trim() === '') {
        //             isValid = false;
        //             field.addClass('is-invalid');
        //             field.after(`<div class="invalid-feedback">${label} is required.</div>`);
        //         }
        //     }
        // } else if (phase === 2) {
        //     // Validate total amount is greater than 0
        //     if (this.state.totalAmount <= 0) {
        //         isValid = false;
        //         $('#total_amount').addClass('is-invalid');
        //         $('#total_amount').after('<div class="invalid-feedback">Total amount must be greater than 0</div>');
        //     }
        // } else if (phase === 3) {
        //     const inputCode = $('.verification-input').val();
        //     if (!inputCode) {
        //         isValid = false;
        //         $('.verification-input').addClass('is-invalid');
        //         $('.verification-input').after('<div class="invalid-feedback">Verification code is required</div>');
        //     } else if (inputCode !== this.state.VERIFICATION_CODE) {
        //         isValid = false;
        //         $('.verification-input').addClass('is-invalid');
        //         $('.verification-input').after('<div class="invalid-feedback">Invalid verification code</div>');
        //     }
        // }

        return isValid;
    },


    validatePhaseOne() {
        const form = $('#membershipForm')[0];
        if (!form.checkValidity()) {
            $('<input type="submit">').hide().appendTo(form).click().remove();
            return false;
        }

        const membershipPlan = $('#membership_plan').val();
        if (!membershipPlan) {
            alert('Please select a membership plan');
            return false;
        }

        return true;
    },

    validatePhaseTwo() {
        // Add any phase two validation logic here
        return true;
    },

    updatePhaseDisplay() {
        $('.phase-content').hide();
        $(`#phase${this.state.currentPhase}`).show();
        
        const progress = (this.state.currentPhase / 3) * 100;
        $('.progress-bar')
            .css('width', `${progress}%`)
            .attr('aria-valuenow', progress)
            .text(`Phase ${this.state.currentPhase}/3`);
        
        $('#prevBtn').toggle(this.state.currentPhase > 1);
        $('#nextBtn').text(this.state.currentPhase === 3 ? 'Submit' : 'Next');
        
        if (this.state.currentPhase === 2) {
            this.updateSelectedServices();
        } else if (this.state.currentPhase === 3) {
            const phoneNumber = $('input[name="phone"]').val();
            $('#phone_display').text(phoneNumber);
            $('#verificationCodeDisplay').text(this.state.VERIFICATION_CODE);
        }
    },

    validatePhaseThree() {
        const inputCode = $('.verification-input').val();
        if (inputCode !== this.state.VERIFICATION_CODE) {
            alert('Invalid verification code');
            return false;
        }
        return true;
    },

    processMembership() {
    const staffId = document.getElementById('userId').value;
    const userRole = document.getElementById('userRole').value;
    
    if (!staffId || !userRole) {
        alert('Session error: Missing staff credentials');
        return false;
    }

    // Fetch registration fee before processing
    $.ajax({
        url: '../admin/pages/members/get_registration_fee.php', // Endpoint to fetch registration fee
        method: 'GET',
        dataType: 'json',
        success: (feeResponse) => {
            const registrationFee = parseFloat(feeResponse.fee) || 0;

            const formData = this.state.formData;
            
            // Membership Plan Price
            const planOption = $('#membership_plan option:selected');
            const membershipPlanPrice = parseFloat(planOption.data('price')) || 0;
            
            // Program Prices
            const programPrices = this.state.selectedPrograms.map(program => ({
                id: program.id,
                name: program.name,
                price: program.price
            }));
            
            // Rental Prices
            const rentalPrices = this.state.selectedRentals.map(rental => ({
                id: rental.id,
                name: rental.name,
                price: rental.price
            }));
            
            // Set detailed pricing information
            formData.set('staff_id', staffId);
            formData.set('user_role', userRole);
            formData.set('user_type', 'new');
            formData.set('membership_plan_price', membershipPlanPrice);
            formData.set('registration_fee', registrationFee);
            formData.set('total_amount', this.state.totalAmount);
            
            // Add programs and their prices
            if (programPrices.length > 0) {
                formData.set('programs', JSON.stringify(programPrices));
            }
            
            // Add rentals and their prices
            if (rentalPrices.length > 0) {
                formData.set('rentals', JSON.stringify(rentalPrices));
            }

            $.ajax({
                url: '../admin/pages/members/process_membership.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        $('#addMemberModal').modal('hide');
                        
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        $('#successModal').on('hidden.bs.modal', function () {
                            window.location.reload();
                        });
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error: ' + error);
                }
            });
        },
        error: () => {
            alert('Error fetching registration fee');
        }
    });
},

    deleteMember(memberId) {
        $.ajax({
            url: 'delete_member.php',
            method: 'POST',
            data: { id: memberId },
            success: (response) => {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Member deleted successfully');
                        this.loadMembersTable();
                    } else {
                        alert('Error deleting member: ' + result.message);
                    }
                } catch (e) {
                    alert('Error processing server response');
                    console.error('Error parsing delete response:', e);
                }
            },
            error: (xhr, status, error) => {
                alert('Error deleting member');
                console.error('Delete request failed:', error);
            }
        });
    },

    loadMembershipPlans() {
        $.ajax({
            url: '../admin/pages/members/get_membership_plans.php',
            method: 'GET',
            dataType: 'json',
            success: (plans) => {
                let options = '<option value="">Select Plan</option>';
                
                plans.forEach(plan => {
                    options += `
                        <option value="${plan.id}" 
                            data-duration="${plan.duration}"
                            data-duration-type="${plan.duration_type_id}"
                            data-price="${plan.price}"
                            data-duration-name="${plan.duration_type}">
                            ${plan.plan_name} (${plan.duration} ${plan.duration_type}) - ₱${parseFloat(plan.price).toFixed(2)}
                        </option>`;
                });
                
                $('#membership_plan').html(options);
            },
            error: (xhr, status, error) => {
                console.error('Error loading membership plans:', error);
                alert('Error loading membership plans. Please try again.');
            }
        });
    },

    updateTotalAmount() {
        const planPrice = parseFloat($('#price').val()) || 0;
        this.state.totalAmount = planPrice;
        
        // Add prices of selected programs
        this.state.selectedPrograms.forEach(program => {
            this.state.totalAmount += program.price;
        });
        
        // Add prices of selected rentals
        this.state.selectedRentals.forEach(rental => {
            this.state.totalAmount += rental.price;
        });
        
        this.updateSelectedServices();
    }
};

// Initialize the MembershipManager when document is ready
$(document).ready(function() {
    MembershipManager.init();
});

// Update membership plan handling
$('#membership_plan').on('change', function() {
    const planId = $(this).val();
    if (!planId) {
        $('#end_date').val('');
        $('#price').val('');
        MembershipManager.updateTotalAmount();
        return;
    }

    const selectedOption = $(this).find('option:selected');
    const duration = selectedOption.data('duration');
    const durationType = selectedOption.data('duration-type');
    const price = selectedOption.data('price');
    
    // Get or set start date
    let startDate = $('#start_date').val();
    if (!startDate) {
        startDate = new Date().toISOString().split('T')[0];
        $('#start_date').val(startDate);
    }

    // Calculate end date
    const endDate = MembershipManager.calculateEndDate(startDate, duration, durationType);
    
    // Update fields
    $('#end_date').val(endDate);
    $('#price').val(parseFloat(price).toFixed(2));
    
    // Update total amount
    MembershipManager.updateTotalAmount();
});

// Update start date handling
$('#start_date').on('change', function() {
    const planId = $('#membership_plan').val();
    if (planId) {
        // Trigger membership plan change to recalculate end date
        $('#membership_plan').trigger('change');
    }
});

$(document).ready(function () {
    const membersData = <?php echo json_encode($members); ?>;

    // View Member Details
    $('.view-member').on('click', function () {
        const userId = $(this).data('id');
        const memberData = membersData.find((member) => member.user_id == userId);

        if (memberData) {
            // Update photo with default if not available
            $('#memberPhoto').attr(
                'src', 
                memberData.photo_path 
                    ? `../${memberData.photo_path}` 
                    : '../uploads/default.jpg'
            );

            // Personal Information
            $('#memberName').text(`${memberData.first_name} ${memberData.middle_name || ''} ${memberData.last_name}`);
            $('#memberUsername').text(memberData.username);
            $('#memberSex').text(memberData.sex);
            $('#memberBirthdate').text(formatDate(memberData.birthdate));
            $('#memberPhone').text(memberData.phone_number);

            // Payment and Subscription Details
            $('#totalAmount').text(formatCurrency(memberData.total_price));
            $('#membershipStatus').text(memberData.payment_status);

            // Programs and Services
            $('#memberPrograms').html(formatSubscriptions(memberData.subscribed_programs));
            $('#memberServices').html(formatSubscriptions(memberData.rental_services));

            // Add Pay Button based on payment status
            updatePaymentButton(memberData);

            $('#editMemberBtn').data('id', userId);
            $('#memberViewModal').modal('show');
        } else {
            alert('Member data not found');
        }
    });

    // Helper function to format subscriptions
    function formatSubscriptions(subscriptionString) {
        if (!subscriptionString) return 'None';
        
        // Convert comma-separated string to HTML list
        return subscriptionString.split(', ')
            .map(sub => {
                const [name, status] = sub.match(/(.+) \((.+)\)/).slice(1);
                return `<span class="badge badge-${status === 'Paid' ? 'success' : 'warning'} mr-1">${name} (${status})</span>`;
            })
            .join(' ');
    }

    // Update Pay Button functionality
    function updatePaymentButton(memberData) {
    // Remove any existing pay button
    $('#paymentButtonContainer').empty();

    // Create pay button
    const payButton = $(`
        <button type="button" class="btn btn-success" id="paySubscriptionBtn" 
                data-user-id="${memberData.user_id}"
                ${memberData.payment_status === 'Paid' ? 'disabled' : ''}>
            Pay
        </button>
    `);

    payButton.on('click', function() {
        if (!$(this).prop('disabled')) {
            const userId = $(this).data('user-id');
            handlePaySubscription(userId);
        }
    });

    $('#paymentButtonContainer').append(payButton);
}

    // Handle Pay Subscription AJAX call
    function handlePaySubscription(userId) {
    $.ajax({
        url: '../admin/pages/members/pay_subscription.php',
        method: 'POST',
        data: { user_id: userId },
        dataType: 'json',  // Explicitly parse JSON response
        success: function(response) {
            if (response && response.success) {
                alert('Subscriptions paid successfully!');
                location.reload();
            } else {
                alert('Payment failed: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
}

    // Existing helper functions
    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
    }

    $('#editMemberBtn').on('click', function () {
        const userId = $(this).data('id');
        alert('Edit functionality will be implemented for user ID: ' + userId);
    });
    // Add new modal close handlers
    $('#memberViewModal').on('click', '[data-dismiss="modal"]', function() {
        $('#memberViewModal').modal('hide');
    });

    // Ensure standard close buttons also work
    $('.modal-header .close, .modal-footer .close').on('click', function() {
        $(this).closest('.modal').modal('hide');
    });
});


</script>