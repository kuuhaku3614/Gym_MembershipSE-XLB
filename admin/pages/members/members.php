<?php
// Add this near the top of your file
// Define the upload directory relative to the project root
$uploadsDir = dirname(__DIR__, 3) . '/uploads'; // Move up two levels from the current file's directory

// Check if the directory exists, if not, create it
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}


require_once 'config.php';

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
        msp.plan_name,
        ms.start_date AS membership_start,
        ms.end_date AS membership_end,
        ms.total_amount AS membership_amount,
        st.status_name AS membership_status,
        GROUP_CONCAT(DISTINCT prg.program_name SEPARATOR ', ') AS subscribed_programs,
        GROUP_CONCAT(DISTINCT rsvc.service_name SEPARATOR ', ') AS rental_services
        FROM users u
        INNER JOIN memberships ms ON u.id = ms.user_id
        LEFT JOIN personal_details pd ON u.id = pd.user_id
        LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
        LEFT JOIN membership_plans msp ON ms.membership_plan_id = msp.id
        LEFT JOIN status_types st ON ms.status_id = st.id
        LEFT JOIN program_subscriptions ps ON ms.id = ps.membership_id
        LEFT JOIN programs prg ON ps.program_id = prg.id
        LEFT JOIN rental_subscriptions rs ON ms.id = rs.membership_id
        LEFT JOIN rental_services rsvc ON rs.rental_service_id = rsvc.id
        WHERE u.is_active = 1
        GROUP BY u.id;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

    <!-- Styles -->
    <style>
        /* General Modal Styles */
        .modal-header { border-bottom: 2px solid #e9ecef; }
        .modal-footer { border-top: 2px solid #e9ecef; }

        /* Service Container Styles */
        .service-container {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .service-box {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .service-box:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Services Grid Layout */
        .services-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .services-grid { grid-template-columns: 1fr 1fr; }
        }

        /* Summary Container */
        .summary-container {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-header {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        /* Scrollable Containers */
        .services-scrollable-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .services-scrollable-container::-webkit-scrollbar {
            width: 6px;
        }

        .services-scrollable-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .services-scrollable-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        /* Table Styles */
        .table {
            font-size: 0.9rem;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        /* Member Name Style */
        .member-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        /* Status Badge Styles */
        .badge {
            font-weight: 500;
            padding: 8px 12px;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        /* Action Buttons */
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Photo Container */
        .member-photo-container {
            width: 60px;
            height: 60px;
            margin: 0 auto;
        }

        /* Default User Icon */
        .default-user-icon {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .default-user-icon i {
            font-size: 1.5rem;
            color: #6c757d;
        }

        /* Date and Plan Styles */
        .date-cell, .plan-cell {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .member-photo-container {
            display: inline-block;
            position: relative;
            width: 60px;
            height: 60px;
        }

        .member-photo {
            border: 1px solid; /* Adds a border for better visibility */
            transition: transform 0.3s ease;
        }

        .member-photo:hover {
            transform: scale(1.2 ); /* Slight zoom-in effect on hover */
        }

        .bg-light {
            border: 2px dashed #ccc; /* Dashed border for placeholders */
        }

        .bi-person-circle {
            color: #6c757d; /* Neutral gray for placeholder icon */
        }

    </style>

<!-- Main Container -->
<div class="container-fluid">
    <!-- Add Member Button -->
    <button type="button" class="btn btn-primary mb-4" id="addMemberBtn">
        <i class="fas fa-plus mr-2"></i>Add New Member
    </button>
    <!-- Members Table -->
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
                            <th>Membership Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($members)): ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td class="align-middle text-center">
                                        <div class="member-photo-container">
                                            <img src="../../../<?= htmlspecialchars($member['photo_path']); ?>" 
                                                class="img-fluid rounded-circle member-photo" 
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                alt="Profile Photo">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) ?: 'N/A'; ?></td>
                                    <td><?= htmlspecialchars($member['plan_name']) ?: 'No Plan'; ?></td>
                                    <td><?= htmlspecialchars($member['membership_start']) ?: 'N/A'; ?></td>
                                    <td><?= htmlspecialchars($member['membership_end']) ?: 'N/A'; ?></td>
                                    <td><?= htmlspecialchars($member['membership_status']) ?: 'Unknown'; ?></td>
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
                                <td colspan="7" class="text-center">
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
                <div class="modal-header">
                    <h5 class="modal-title">Add New Member</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Progress Bar -->
                    <div class="progress mb-4">
                        <div class="progress-bar" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Phase 1/3</div>
                    </div>

                    <!-- Phase 1: Member Details -->
                    <div id="phase1" class="phase-content">
                        <form id="membershipForm" method="POST" enctype="multipart/form-data">
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
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" name="first_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Middle Name</label>
                                            <input type="text" class="form-control" name="middle_name">
                                        </div>
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" name="last_name" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Sex</label>
                                                <select class="form-control" name="sex" required>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Birthdate</label>
                                                <input type="date" class="form-control" name="birthdate" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Section - Membership Details -->
                                <div class="col-md-6">
                                    <h6 class="mb-3">Membership Details</h6>
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Membership Plan</label>
                                        <select class="form-control" name="membership_plan" id="membership_plan" required>
                                            <option value="">Select Plan</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" id="end_date" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="text" class="form-control" name="price" id="price" readonly>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                                    <!-- Phase 2: Services -->
                <div id="phase2" class="phase-content" style="display: none;">
                    <div class="row">
                        <!-- Availed Services Container (Top) -->
                        <div class="col-md-12 mb-4">
                            <div class="card availed-container">
                                <div class="card-header">
                                    <h6>Membership Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div id="availed_services">
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

                        <!-- Optional Offers Container -->
                        <div class="col-md-12">
                            <div class="row">
                                <!-- Programs -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">Available Programs</div>
                                        <div class="card-body services-scrollable-container">
                                            <!-- Program boxes will be dynamically populated -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Services -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">Available Rental Services</div>
                                        <div class="card-body services-scrollable-container">
                                            <!-- Rental service boxes will be dynamically populated -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                                <!-- Detailed Program Modal -->
                            <div class="modal fade" id="programDetailModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Program Details</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Program Information</h6>
                                                    <div id="programDetailContent"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Available Coaches</h6>
                                                    <select id="coachSelect" class="form-control"></select>
                                                    <div id="coachDetails" class="mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" id="addProgramDetailBtn">Add to Membership</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detailed Rental Modal -->
                            <div class="modal fade" id="rentalDetailModal" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Rental Service Details</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body" id="rentalDetailContent"></div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" id="addRentalDetailBtn">Add to Membership</button>
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
                            <input type="text" class="form-control verification-input" maxlength="6">
                            <button class="btn btn-link resend-code">Resend code</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">Previous</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Membership Management Script
    const MembershipManager = {
    state: {
        currentPhase: 1,
        selectedPrograms: [],
        selectedRentals: [],
        totalAmount: 0,
        VERIFICATION_CODE: '123456'
    },

    init() {
        this.bindEvents();
    },


    bindEvents() {
        $('#addMemberBtn').click(() => this.resetAndShowModal());
        $('#membership_plan').change(() => this.handlePlanChange());
        $('#start_date').change(() => {
            if ($('#membership_plan').val()) {
                this.handlePlanChange();
            }
        });
        $('#nextBtn').click(() => this.handleNextPhase());
        $('#prevBtn').click(() => this.handlePrevPhase());
        this.bindServiceEvents();
        this.bindFileUpload();
        this.bindDeleteMember();
    },

    bindFileUpload() {
        $('#profile_photo').change(function() {
            const file = this.files[0];
            const reader = new FileReader();
            
            // Update file input label
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
        $(document).on('click', '.delete-member', (e) => {
            const memberId = $(e.target).data('id');
            if (confirm('Are you sure you want to delete this member?')) {
                this.deleteMember(memberId);
            }
        });
    },

    bindServiceEvents() {
        $('.program').click((e) => {
            const programId = $(e.target).closest('.program').data('id');
            this.loadProgramDetails(programId);
        });

        $('.rental').click((e) => {
            const rentalId = $(e.target).closest('.rental').data('id');
            this.loadRentalDetails(rentalId);
        });

        $('#addProgramDetailBtn').click(() => this.addProgramToMembership());
        $('#addRentalDetailBtn').click(() => this.addRentalToMembership());
    },

    resetAndShowModal() {
        this.state.currentPhase = 1;
        this.state.selectedPrograms = [];
        this.state.selectedRentals = [];
        this.state.totalAmount = 0;
        
        $('#membershipForm')[0].reset();
        $('#preview').empty();
        $('.phase-content').hide();
        $('#phase1').show();
        
        $('.progress-bar')
            .css('width', '33%')
            .attr('aria-valuenow', 33)
            .text('Phase 1/3');
        
        $('#prevBtn').hide();
        $('#nextBtn').text('Next');
        
        $('#addMemberModal').modal('show');
        this.loadMembershipPlans();
        this.loadAvailableServices();
    },

    handlePlanChange() {
        const selectedOption = $('#membership_plan option:selected');
        if (!selectedOption.val()) {
            $('#end_date').val('');
            $('#price').val('');
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
        
        // Update fields
        $('#end_date').val(endDate);
        $('#price').val(price.toFixed(2));
        
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

    loadAvailableServices() {
        $.ajax({
            url: 'get_program_details.php',
            method: 'GET',
            success: (response) => {
                const programs = JSON.parse(response);
                this.renderPrograms(programs);
            }
        });

        $.ajax({
            url: 'get_rental_details.php',
            method: 'GET',
            success: (response) => {
                const rentals = JSON.parse(response);
                this.renderRentals(rentals);
            }
        });
    },

    renderPrograms(programs) {
        const html = programs.map(program => `
            <div class="service-box program" data-id="${program.id}">
                <h6>${program.program_name}</h6>
                <p>${program.type_name}</p>
                <p class="text-primary">₱${parseFloat(program.price).toFixed(2)}</p>
            </div>
        `).join('');
        $('.programs-container').html(html);
    },

    renderRentals(rentals) {
        const html = rentals.map(rental => `
            <div class="service-box rental" data-id="${rental.id}">
                <h6>${rental.service_name}</h6>
                <p>Available Slots: ${rental.available_slots}</p>
                <p class="text-primary">₱${parseFloat(rental.price).toFixed(2)}</p>
            </div>
        `).join('');
        $('.rentals-container').html(html);
    },

    loadProgramDetails(programId) {
        $.ajax({
            url: '../admin/pages/members/get_program_details.php',
            method: 'GET',
            data: { id: programId },
            success: (response) => {
                $('#programDetailContent').html(response);
                $('#programDetailModal').modal('show');
            }
        });
    },

    loadRentalDetails(rentalId) {
        $.ajax({
            url: '../admin/pages/members/get_rental_details.php',
            method: 'GET',
            data: { id: rentalId },
            success: (response) => {
                $('#rentalDetailContent').html(response);
                $('#rentalDetailModal').modal('show');
            }
        });
    },

    addProgramToMembership() {
        const $details = $('#programDetailContent');
        const programId = $details.find('.program-id').val();
        const programName = $details.find('.program-name').text();
        const programPrice = parseFloat($details.find('.program-price').text().replace('₱', ''));

        if (!this.state.selectedPrograms.some(p => p.id === programId)) {
            this.state.selectedPrograms.push({
                id: programId,
                name: programName,
                price: programPrice
            });

            this.state.totalAmount += programPrice;
            this.updateSelectedServices();
        }

        $('#programDetailModal').modal('hide');
    },

    addRentalToMembership() {
        const $details = $('#rentalDetailContent');
        const rentalId = $details.find('.rental-id').val();
        const rentalName = $details.find('.rental-name').text();
        const rentalPrice = parseFloat($details.find('.rental-price').text().replace('₱', ''));

        if (!this.state.selectedRentals.some(r => r.id === rentalId)) {
            this.state.selectedRentals.push({
                id: rentalId,
                name: rentalName,
                price: rentalPrice
            });

            this.state.totalAmount += rentalPrice;
            this.updateSelectedServices();
        }

        $('#rentalDetailModal').modal('hide');
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
            this.state.currentPhase++;
            this.updatePhaseDisplay();
        }
    },

    handlePrevPhase() {
        if (this.state.currentPhase > 1) {
            this.state.currentPhase--;
            this.updatePhaseDisplay();
        }
    },

    validateCurrentPhase() {
        switch(this.state.currentPhase) {
            case 1:
                return this.validatePhaseOne();
            case 2:
                return this.validatePhaseTwo();
            case 3:
                return this.processMembership();
            default:
                return false;
        }
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
            this.loadServices();
            this.updateSelectedServices();
        } else if (this.state.currentPhase === 3) {
            const phoneNumber = $('input[name="phone"]').val();
            $('#phone_display').text(phoneNumber);
            $('#verificationCodeDisplay').text(this.state.VERIFICATION_CODE);
        }
    },

    processMembership() {
        const profilePhotoInput = $('#profile_photo')[0];
        
        // Create FormData from the form in the first phase
        const formData = new FormData($('#membershipForm')[0]);
        
        // Explicitly check and append the file
        if (profilePhotoInput.files.length > 0) {
            formData.append('profile_photo', profilePhotoInput.files[0]);
        }

        formData.append('programs', JSON.stringify(this.state.selectedPrograms));
        formData.append('rentals', JSON.stringify(this.state.selectedRentals));
        formData.append('user_type', 'new');
        formData.append('total_amount', this.state.totalAmount.toString());

        $.ajax({
            url: '../admin/pages/members/process_membership.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    alert('Membership created successfully!');
                    $('#addMemberModal').modal('hide');
                    location.reload(); // Simple way to refresh the page
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('Server Response:', xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert('Error processing membership: ' + response.message);
                } catch (e) {
                    alert('Error processing membership. Please check the console for details.');
                }
            }
        });
        return true;
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
$(document).ready(() => MembershipManager.init());

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
</script>