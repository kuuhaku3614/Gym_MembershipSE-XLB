<!-- add_member_modal.php -->
<?php
require_once 'config.php';
?>
<!-- Add necessary CSS -->
<style>
.service-box {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.service-box:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.verification-input {
    width: 200px;
    margin: 20px auto;
    text-align: center;
    letter-spacing: 5px;
    font-size: 24px;
}

.availed-container {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.modal-xl {
    max-width: 95%;
}

.services-scrollable-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.service-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
</style>
    <!-- Main Content Container -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary mb-3" id="addMemberBtn">
                        Add New Member
                    </button>

                    <!-- Add Member Modal -->
                    <div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-labelledby="addMemberModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addMemberModalLabel">Add New Member</h5>
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
                    <form id="membershipForm">
                        <div class="row">
                            <!-- Left Section - Personal Details -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Personal Details</h6>

                                
                                <div id="existing_user_form" style="display: none;">
                                    <div class="form-group">
                                        <label>Search User</label>
                                        <input type="text" class="form-control" id="search_user">
                                    </div>
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
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM membership_plans WHERE status_id = 1");
                                        while ($row = $stmt->fetch()) {
                                            echo "<option value='{$row['id']}' 
                                                data-price='{$row['price']}' 
                                                data-duration='{$row['duration']}'
                                                data-duration-type='{$row['duration_type_id']}'>{$row['plan_name']}</option>";
                                        }
                                        ?>
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
<<<<<<< HEAD
=======
                        </div>
                        <div class="total-amount mt-3">
                            <h6>Total Amount: <span id="total_amount">₱0.00</span></h6>
>>>>>>> parent of bdfdd0a (membership page)
                        </div>
                    </div>
                </div>
            </div>
        </div>
<<<<<<< HEAD

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
=======
>>>>>>> parent of bdfdd0a (membership page)

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

                    

    <script>
$(document).ready(function() {
    // Initialize global variables
    let currentPhase = 1;
    let selectedPrograms = [];
    let selectedRentals = [];
    let totalAmount = 0;
    
    // Add Member Button
    $('#addMemberBtn').click(function() {
        // Reset everything
        currentPhase = 1;
        selectedPrograms = [];
        selectedRentals = [];
        totalAmount = 0;
        
        // Reset form
        $('#membershipForm')[0].reset();
        
        // Reset phase display
        $('.phase-content').hide();
        $('#phase1').show();
        
        // Reset progress bar
        $('.progress-bar').css('width', '33%')
                         .attr('aria-valuenow', 33)
                         .text('Phase 1/3');
        
        // Reset navigation buttons
        $('#prevBtn').hide();
        $('#nextBtn').text('Next');
        
        // Show modal
        $('#addMemberModal').modal('show');
        
        // Load available services
        loadAvailableServices();
    });

    // Load Available Services
    function loadAvailableServices() {
    $.ajax({
        url: '../admin/pages/members/get_available_services.php',
        method: 'GET',
        success: function(response) {
            // Populate the container with the response HTML
            $('.services-container').html(response);

            // Rebind click events for dynamic content
            bindServiceClickEvents();
        },
        error: function() {
            alert('Error loading available services');
        }
    });
}


    function bindServiceClickEvents() {
        $('.program').click(function() {
            const programId = $(this).data('id');
            
            $.ajax({
                url: '../admin/pages/members/get_program_details.php',
                method: 'GET',
                data: { id: programId },
                success: function(response) {
                    $('#program_details_content').html(response);
                    $('#programDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error fetching program details');
                }
            });
        });

        $('.rental').click(function() {
            const rentalId = $(this).data('id');
            
            $.ajax({
                url: '../admin/pages/members/get_rental_details.php',
                method: 'GET',
                data: { id: rentalId },
                success: function(response) {
                    $('#rental_details_content').html(response);
                    $('#rentalDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error fetching rental details');
                }
            });
        });
    }

    // Membership Plan Calculation
    $('#membership_plan').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price') || 0;
        const duration = selectedOption.data('duration') || 0;
        
        // Set start date to today if not set
        const startDate = new Date().toISOString().split('T')[0];
        $('#start_date').val(startDate);

        // Calculate end date based on duration
        const endDate = new Date(startDate);
        const durationType = selectedOption.data('duration-type');
        
        switch(durationType) {
            case 1: // days
                endDate.setDate(endDate.getDate() + duration);
                break;
            case 2: // months
                endDate.setMonth(endDate.getMonth() + duration);
                break;
            case 3: // year
                endDate.setFullYear(endDate.getFullYear() + duration);
                break;
        }

        $('#end_date').val(endDate.toISOString().split('T')[0]);

        // Set price
        $('#price').val(parseFloat(price).toFixed(2));
    });

    // Display Selected Plan
    function displaySelectedPlan() {
        const planOption = $('#membership_plan option:selected');
        const planName = planOption.text();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const price = parseFloat(planOption.data('price')) || 0;

<<<<<<< HEAD
    loadAvailableServices() {
        $.ajax({
            url: '../admin/pages/members/get_program_details.php',
            method: 'GET',
            success: (programsResponse) => {
                const programs = JSON.parse(programsResponse);
                this.renderPrograms(programs);
            }
        });

        $.ajax({
            url: '../admin/pages/members/get_rental_details.php',
            method: 'GET',
            success: (rentalsResponse) => {
                const rentals = JSON.parse(rentalsResponse);
                this.renderRentals(rentals);
            }
        });
    },

    renderPrograms(programs) {
        const html = programs.map(program => `
            <div class="service-box program" data-id="${program.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${program.program_name}</h6>
                        <p class="text-muted small mb-0">${program.type_name}</p>
                    </div>
                    <div>
                        <span class="badge badge-primary">₱${parseFloat(program.price).toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-success ml-2 add-program" data-id="${program.id}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        $('.card-body.services-scrollable-container').first().html(html);
        
        // Bind add program button
        $('.add-program').click((e) => {
            const programId = $(e.currentTarget).data('id');
            this.loadProgramDetails(programId);
        });
    },

    renderRentals(rentals) {
        const html = rentals.map(rental => `
            <div class="service-box rental" data-id="${rental.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${rental.service_name}</h6>
                        <p class="text-muted small mb-0">Available Slots: ${rental.available_slots}</p>
                    </div>
                    <div>
                        <span class="badge badge-primary">₱${parseFloat(rental.price).toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-success ml-2 add-rental" data-id="${rental.id}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        $('.card-body.services-scrollable-container').last().html(html);
        
        // Bind add rental button
        $('.add-rental').click((e) => {
            const rentalId = $(e.currentTarget).data('id');
            this.loadRentalDetails(rentalId);
        });
    },

    updateSelectedServices() {
        const planOption = $('#membership_plan option:selected');
        const planName = planOption.text();
        const planPrice = parseFloat(planOption.data('price')) || 0;

        let selectedServicesHtml = `
            <div class="service-item bg-light p-2 rounded mb-2">
                <div class="d-flex justify-content-between">
                    <span>Membership Plan: ${planName}</span>
                    <span class="text-primary">₱${planPrice.toFixed(2)}</span>
                </div>
            </div>
        `;

        // Render Programs
        if (this.state.selectedPrograms.length > 0) {
            selectedServicesHtml += `
                <div class="mb-2">
                    <h6 class="text-muted small">Added Programs</h6>
                    ${this.state.selectedPrograms.map(program => `
                        <div class="service-item d-flex justify-content-between align-items-center p-2 bg-white rounded mb-1">
                            <span>${program.name}</span>
                            <div>
                                <span class="text-primary mr-2">₱${program.price.toFixed(2)}</span>
                                <button class="btn btn-xs btn-outline-danger remove-program" data-id="${program.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Render Rentals
        if (this.state.selectedRentals.length > 0) {
            selectedServicesHtml += `
                <div class="mb-2">
                    <h6 class="text-muted small">Added Rental Services</h6>
                    ${this.state.selectedRentals.map(rental => `
                        <div class="service-item d-flex justify-content-between align-items-center p-2 bg-white rounded mb-1">
                            <span>${rental.name}</span>
                            <div>
                                <span class="text-primary mr-2">₱${rental.price.toFixed(2)}</span>
                                <button class="btn btn-xs btn-outline-danger remove-rental" data-id="${rental.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Total Amount
        selectedServicesHtml += `
            <div class="total-amount mt-3 p-2 bg-primary text-white rounded">
                <div class="d-flex justify-content-between">
                    <strong>Total Amount:</strong>
                    <strong>₱${this.state.totalAmount.toFixed(2)}</strong>
                </div>
            </div>
        `;

        $('#selected_services').html(selectedServicesHtml);

        // Bind remove buttons
        $('.remove-program').click((e) => {
            const programId = $(e.currentTarget).data('id');
            this.removeProgram(programId);
        });

        $('.remove-rental').click((e) => {
            const rentalId = $(e.currentTarget).data('id');
            this.removeRental(rentalId);
        });
    },

    removeProgram(programId) {
        const index = this.state.selectedPrograms.findIndex(p => p.id === programId);
        if (index !== -1) {
            const removedProgram = this.state.selectedPrograms.splice(index, 1)[0];
            this.state.totalAmount -= removedProgram.price;
            this.updateSelectedServices();
        }
    },

    removeRental(rentalId) {
        const index = this.state.selectedRentals.findIndex(r => r.id === rentalId);
        if (index !== -1) {
            const removedRental = this.state.selectedRentals.splice(index, 1)[0];
            this.state.totalAmount -= removedRental.price;
            this.updateSelectedServices();
        }
    },

    addProgramToMembership() {
        const $details = $('#programDetailContent');
=======
        $('#selected_plan_details').html(`
            <p><strong>Plan:</strong> ${planName}</p>
            <p><strong>Start Date:</strong> ${startDate}</p>
            <p><strong>End Date:</strong> ${endDate}</p>
            <p><strong>Price:</strong> ₱${price.toFixed(2)}</p>
        `);

        // Update total amount to include membership plan price
        totalAmount = price;
        updateSelectedServices();
    }

    // Add Program to Membership
    $('#addProgramBtn').click(function() {
        const $details = $('#program_details_content');
>>>>>>> parent of bdfdd0a (membership page)
        const programId = $details.find('.program-id').val();
        const programName = $details.find('.program-name').text();
        const programPrice = parseFloat($details.find('.program-price').text().replace('₱', ''));

<<<<<<< HEAD
        // Prevent duplicate programs
        if (!this.state.selectedPrograms.some(p => p.id === programId)) {
            this.state.selectedPrograms.push({
=======
        if (!selectedPrograms.some(p => p.id === programId)) {
            selectedPrograms.push({
>>>>>>> parent of bdfdd0a (membership page)
                id: programId,
                name: programName,
                price: programPrice
            });

            totalAmount += programPrice;
            updateSelectedServices();
        }

        $('#programDetailsModal').modal('hide');
    });

    // Add Rental to Membership
    $('#addRentalBtn').click(function() {
        const $details = $('#rental_details_content');
        const rentalId = $details.find('.rental-id').val();
        const rentalName = $details.find('.rental-name').text();
        const rentalPrice = parseFloat($details.find('.rental-price').text().replace('₱', ''));

<<<<<<< HEAD
        // Prevent duplicate rentals
        if (!this.state.selectedRentals.some(r => r.id === rentalId)) {
            this.state.selectedRentals.push({
=======
        if (!selectedRentals.some(r => r.id === rentalId)) {
            selectedRentals.push({
>>>>>>> parent of bdfdd0a (membership page)
                id: rentalId,
                name: rentalName,
                price: rentalPrice
            });

            totalAmount += rentalPrice;
            updateSelectedServices();
        }

        $('#rentalDetailsModal').modal('hide');
    });

<<<<<<< HEAD
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
=======
    // Update Selected Services
    function updateSelectedServices() {
        let servicesHtml = '';

        // Include Membership Plan in Services
        const planOption = $('#membership_plan option:selected');
        const planName = planOption.text();
        const planPrice = parseFloat(planOption.data('price')) || 0;

        servicesHtml += `
            <div class="service-item">
                <span>Membership Plan: ${planName}</span>
                <span class="float-right">₱${planPrice.toFixed(2)}</span>
            </div>
        `;

        selectedPrograms.forEach(program => {
            servicesHtml += `
                <div class="service-item">
                    <span>${program.name}</span>
                    <span class="float-right">₱${program.price.toFixed(2)}</span>
                </div>
            `;
        });

        selectedRentals.forEach(rental => {
            servicesHtml += `
                <div class="service-item">
                    <span>${rental.name}</span>
                    <span class="float-right">₱${rental.price.toFixed(2)}</span>
                </div>
            `;
        });

        $('#selected_services').html(servicesHtml);
        $('#total_amount').text('₱' + totalAmount.toFixed(2));
    }

    // Submit Membership Form
    function submitMembershipForm() {
>>>>>>> parent of bdfdd0a (membership page)
        const formData = new FormData($('#membershipForm')[0]);
        
        // Add selected programs and rentals to form data
        formData.append('selected_programs', JSON.stringify(selectedPrograms));
        formData.append('selected_rentals', JSON.stringify(selectedRentals));
        formData.append('total_amount', totalAmount);

        $.ajax({
            url: '../admin/pages/members/process_membership.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    $('#addMemberModal').modal('hide');
                    alert('Membership registered successfully!');
                    // Optionally reload the page or update the members list
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing the membership.');
            }
        });
    }

    // Phase navigation
    $('#nextBtn').click(function () {
        if (currentPhase === 1) {
            if (validatePhase1()) {
                currentPhase++;
                updatePhase();
                displaySelectedPlan(); // Show selected plan details in phase 2
            }
        } else if (currentPhase === 2) {
            currentPhase++;
            updatePhase();

            // Set phone number for verification
            const phoneNumber = $('input[name="phone"]').val();
            $('#phone_display').text(phoneNumber);
        } else if (currentPhase === 3) {
            const phoneNumber = $('input[name="phone"]').val();

            // Mock verification: Get the code from the server
            $.post('process_membership.php', { phone: phoneNumber }, function (response) {
                if (response.success) {
                    $('#verificationCodeDisplay').text(response.verificationCode); // Display the mock code
                } else {
                    alert(response.message || 'Failed to send verification code');
                }
            }, 'json');
        }
    });

    $('#prevBtn').click(function () {
        if (currentPhase > 1) {
            currentPhase--;
            updatePhase();
        }
    });

    function updatePhase() {
        // Hide all phases
        $('.phase-content').hide();

        // Show current phase
        $(`#phase${currentPhase}`).show();

        // Update progress bar
        const progress = (currentPhase / 3) * 100;
        $('.progress-bar')
            .css('width', `${progress}%`)
            .attr('aria-valuenow', progress)
            .text(`Phase ${currentPhase}/3`);

        // Update navigation buttons
        $('#prevBtn').toggle(currentPhase > 1);
        $('#nextBtn').text(currentPhase === 3 ? 'Submit' : 'Next');
    }

    function validatePhase1() {
        const form = $('#membershipForm')[0];
        if (!form.checkValidity()) {
            // Trigger HTML5 validation
            $('<input type="submit">').hide().appendTo(form).click().remove();
            return false;
        }

        // Additional validation if needed
        const membershipPlan = $('#membership_plan').val();
        if (!membershipPlan) {
            alert('Please select a membership plan');
            return false;
        }

        return true;
    }
});
</script>