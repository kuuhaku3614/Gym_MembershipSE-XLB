<?php
// Add this near the top of your file
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Make sure default.png exists

?>

<!-- members.php -->
<?php require_once 'config.php'; ?>

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
                    <tbody id="membersTableBody">
                        <!-- Table content will be dynamically populated -->
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
                        <form id="membershipForm" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Left Section - Personal Details -->
                                <div class="col-md-6">
                                    <h6 class="mb-3">Personal Details</h6>
                                    <!-- Profile Photo Upload -->
                                    <div class="form-group mb-4">
                                        <label>Profile Photo</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="profile_photo" name="profile_photo" accept="image/*">
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
                            <!-- Availed Services Container -->
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
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Offers -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">Available Programs</div>
                                    <div class="card-body services-scrollable-container">
                                        <!-- Program boxes will be dynamically populated -->
                                    </div>
                                </div>
                            </div>
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
   document.addEventListener('DOMContentLoaded', () => {
    const MembershipManager = {
        state: {
            currentPhase: 1,
            selectedPrograms: [],
            selectedRentals: [],
            totalAmount: 0,
            VERIFICATION_CODE: '123456'
        },

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadMembersTable();
        },

        cacheDOM() {
            this.addMemberBtn = document.getElementById('addMemberBtn');
            this.addMemberModal = document.getElementById('addMemberModal');
            this.membershipForm = document.getElementById('membershipForm');
            this.nextBtn = document.getElementById('nextBtn');
            this.prevBtn = document.getElementById('prevBtn');
            this.membershipPlanSelect = document.getElementById('membership_plan');
            this.startDateInput = document.getElementById('start_date');
            this.endDateInput = document.getElementById('end_date');
            this.priceInput = document.getElementById('price');
            this.profilePhotoInput = document.getElementById('profile_photo');
            this.previewContainer = document.getElementById('preview');
            this.programContainer = document.querySelector('.services-scrollable-container[data-type="programs"]');
            this.rentalContainer = document.querySelector('.services-scrollable-container[data-type="rentals"]');
        },

        bindEvents() {
            this.addMemberBtn.addEventListener('click', () => this.resetAndShowModal());
            this.nextBtn.addEventListener('click', () => this.handleNextPhase());
            this.prevBtn.addEventListener('click', () => this.handlePrevPhase());
            
            this.membershipPlanSelect.addEventListener('change', () => this.handlePlanChange());
            this.startDateInput.addEventListener('change', () => {
                if (this.membershipPlanSelect.value) {
                    this.handlePlanChange();
                }
            });

            this.profilePhotoInput.addEventListener('change', this.handleFileUpload.bind(this));
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            
            // Update file label
            event.target.nextElementSibling.textContent = file.name;
            
            reader.onload = (e) => {
                this.previewContainer.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                `;
            };
            
            reader.readAsDataURL(file);
        },

        resetAndShowModal() {
            // Reset state
            this.state = {
                currentPhase: 1,
                selectedPrograms: [],
                selectedRentals: [],
                totalAmount: 0,
                VERIFICATION_CODE: '123456'
            };

            // Reset form
            this.membershipForm.reset();
            this.previewContainer.innerHTML = '';

            // Hide/show phases
            document.querySelectorAll('.phase-content').forEach(phase => phase.style.display = 'none');
            document.getElementById('phase1').style.display = 'block';

            // Update progress bar
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = '33%';
            progressBar.setAttribute('aria-valuenow', 33);
            progressBar.textContent = 'Phase 1/3';

            // Update buttons
            this.prevBtn.style.display = 'none';
            this.nextBtn.textContent = 'Next';

            // Load data
            this.loadMembershipPlans();
            this.loadAvailableServices();

            // Show modal
            const modal = new bootstrap.Modal(this.addMemberModal);
            modal.show();
        },

        loadAvailableServices() {
            // Fetch Programs
            fetch('../admin/pages/members/get_program_details.php')
                .then(response => response.json())
                .then(programs => this.renderPrograms(programs))
                .catch(error => console.error('Error loading programs:', error));

            // Fetch Rentals
            fetch('../admin/pages/members/get_rental_details.php')
                .then(response => response.json())
                .then(rentals => this.renderRentals(rentals))
                .catch(error => console.error('Error loading rentals:', error));
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
            
            this.programContainer.innerHTML = html;
            
            // Add event listeners to add buttons
            this.programContainer.querySelectorAll('.add-program').forEach(button => {
                button.addEventListener('click', (e) => {
                    const programId = e.currentTarget.dataset.id;
                    this.loadProgramDetails(programId);
                });
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
            
            this.rentalContainer.innerHTML = html;
            
            // Add event listeners to add buttons
            this.rentalContainer.querySelectorAll('.add-rental').forEach(button => {
                button.addEventListener('click', (e) => {
                    const rentalId = e.currentTarget.dataset.id;
                    this.loadRentalDetails(rentalId);
                });
            });
        },

        loadProgramDetails(programId) {
            // Placeholder for loading program details
            // You'll need to implement the actual AJAX call to fetch program details
            console.log('Loading program details for ID:', programId);
        },

        loadRentalDetails(rentalId) {
            // Placeholder for loading rental details
            // You'll need to implement the actual AJAX call to fetch rental details
            console.log('Loading rental details for ID:', rentalId);
        },

        handlePlanChange() {
            const selectedOption = this.membershipPlanSelect.options[this.membershipPlanSelect.selectedIndex];
            
            if (!selectedOption.value) {
                this.endDateInput.value = '';
                this.priceInput.value = '';
                this.updateTotalAmount();
                return;
            }

            const duration = parseInt(selectedOption.dataset.duration);
            const durationType = parseInt(selectedOption.dataset.durationType);
            const price = parseFloat(selectedOption.dataset.price);
            
            // Get or set start date
            let startDate = this.startDateInput.value;
            if (!startDate) {
                startDate = new Date().toISOString().split('T')[0];
                this.startDateInput.value = startDate;
            }

            // Calculate end date
            const endDate = this.calculateEndDate(startDate, duration, durationType);
            
            // Update fields
            this.endDateInput.value = endDate;
            this.priceInput.value = price.toFixed(2);
            
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

        loadMembershipPlans() {
            fetch('../admin/pages/members/get_membership_plans.php')
                .then(response => response.json())
                .then(plans => {
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
                    
                    this.membershipPlanSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading membership plans:', error);
                    alert('Error loading membership plans. Please try again.');
                });
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
            if (!this.membershipForm.checkValidity()) {
                // Trigger form validation
                this.membershipForm.reportValidity();
                return false;
            }

            if (!this.membershipPlanSelect.value) {
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
            // Hide all phase contents
            document.querySelectorAll('.phase-content').forEach(phase => phase.style.display = 'none');
            
            // Show current phase
            document.getElementById(`phase${this.state.currentPhase}`).style.display = 'block';
            
            // Update progress bar
            const progress = (this.state.currentPhase / 3) * 100;
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
            progressBar.textContent = `Phase ${this.state.currentPhase}/3`;
            
            // Toggle previous button
            this.prevBtn.style.display = this.state.currentPhase > 1 ? 'block' : 'none';
            
            // Update next button text
            this.nextBtn.textContent = this.state.currentPhase === 3 ? 'Submit' : 'Next';
            
            // Additional phase-specific logic
            if (this.state.currentPhase === 2) {
                this.updateSelectedServices();
            } else if (this.state.currentPhase === 3) {
                const phoneNumber = this.membershipForm.querySelector('input[name="phone"]').value;
                document.getElementById('phone_display').textContent = phoneNumber;
                document.getElementById('verificationCodeDisplay').textContent = this.state.VERIFICATION_CODE;
            }
        },

        updateTotalAmount() {
            // Reset total amount with plan price
            this.state.totalAmount = parseFloat(this.priceInput.value) || 0;
            
            // Add prices of selected programs
            this.state.selectedPrograms.forEach(program => {
                this.state.totalAmount += program.price;
            });
            
            // Add prices of selected rentals
            this.state.selectedRentals.forEach(rental => {
                this.state.totalAmount += rental.price;
            });
            
            this.updateSelectedServices();
        },

        updateSelectedServices() {
            // Implementation will depend on your specific UI requirements
            console.log('Selected Services Total Amount:', this.state.totalAmount);
        },

        processMembership() {
            const formData = new FormData(this.membershipForm);
            formData.append('programs', JSON.stringify(this.state.selectedPrograms));
            formData.append('rentals', JSON.stringify(this.state.selectedRentals));
            formData.append('user_type', 'new');
            formData.append('total_amount', this.state.totalAmount.toString());

            fetch('../admin/pages/members/process_membership.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Membership created successfully!');
                    // Close modal and refresh table
                    const modal = bootstrap.Modal.getInstance(this.addMemberModal);
                    modal.hide();
                    this.loadMembersTable();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing membership. Please check the console.');
            });

            return true;
        },

        loadMembersTable() {
            const tableBody = document.getElementById('membersTableBody');
            
            // Show loading state
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading members...</p>
                    </td>
                </tr>
            `;

            fetch('../admin/pages/members/get_members.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load members');
                    }

                    const members = data.data;
                    if (!members || members.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="fas fa-users"></i>
                                    No members found
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    const tableHTML = members.map(member => `
                        <tr>
                            <td class="align-middle text-center">
                                <div class="member-photo-container">
                                <td class="align-middle text-center">
                                <div class="member-photo-container">
                                    ${member.profile_photo ? 
                                        `<img src="${member.profile_photo}" alt="${member.first_name} ${member.last_name}" class="img-thumbnail rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                        `<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            ${member.first_name.charAt(0)}${member.last_name.charAt(0)}
                                        </div>`
                                    }
                                </div>
                            </td>
                            <td class="align-middle">${member.first_name} ${member.last_name}</td>
                            <td class="align-middle">${member.phone}</td>
                            <td class="align-middle">${member.membership_plan}</td>
                            <td class="align-middle">
                                <span class="badge ${member.status === 'Active' ? 'badge-success' : 'badge-warning'}">
                                    ${member.status}
                                </span>
                            </td>
                            <td class="align-middle">
                                ${new Date(member.start_date).toLocaleDateString()} - 
                                ${new Date(member.end_date).toLocaleDateString()}
                            </td>
                            <td class="align-middle text-right">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-info view-member" data-id="${member.id}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning edit-member" data-id="${member.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-member" data-id="${member.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');

                    tableBody.innerHTML = tableHTML;

                    // Add event listeners for view, edit, and delete buttons
                    tableBody.querySelectorAll('.view-member').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const memberId = e.currentTarget.dataset.id;
                            this.viewMemberDetails(memberId);
                        });
                    });

                    tableBody.querySelectorAll('.edit-member').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const memberId = e.currentTarget.dataset.id;
                            this.editMemberDetails(memberId);
                        });
                    });

                    tableBody.querySelectorAll('.delete-member').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const memberId = e.currentTarget.dataset.id;
                            this.deleteMember(memberId);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading members:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Error loading members: ${error.message}
                            </td>
                        </tr>
                    `;
                });
        },

        viewMemberDetails(memberId) {
            // Fetch and display full member details in a modal
            fetch(`../admin/pages/members/get_member_details.php?id=${memberId}`)
                .then(response => response.json())
                .then(member => {
                    // Create and populate view modal
                    const viewModal = document.getElementById('memberDetailsModal');
                    viewModal.querySelector('.modal-title').textContent = `${member.first_name} ${member.last_name}`;
                    
                    // Populate modal with member details
                    const detailsHTML = `
                        <div class="row">
                            <div class="col-md-4 text-center">
                                ${member.profile_photo ? 
                                    `<img src="${member.profile_photo}" alt="${member.first_name} ${member.last_name}" class="img-fluid rounded-circle mb-3" style="max-width: 200px;">` : 
                                    `<div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3" style="width: 200px; height: 200px; font-size: 64px;">
                                        ${member.first_name.charAt(0)}${member.last_name.charAt(0)}
                                    </div>`
                                }
                            </div>
                            <div class="col-md-8">
                                <h5>Personal Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Full Name:</th>
                                        <td>${member.first_name} ${member.last_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td>${member.phone}</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>${member.email || 'N/A'}</td>
                                    </tr>
                                </table>
                                <h5>Membership Details</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Plan:</th>
                                        <td>${member.membership_plan}</td>
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td>${new Date(member.start_date).toLocaleDateString()}</td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td>${new Date(member.end_date).toLocaleDateString()}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge ${member.status === 'Active' ? 'badge-success' : 'badge-warning'}">
                                                ${member.status}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    viewModal.querySelector('.modal-body').innerHTML = detailsHTML;
                    
                    // Show the modal
                    const modal = new bootstrap.Modal(viewModal);
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching member details:', error);
                    alert('Failed to load member details');
                });
        },

        editMemberDetails(memberId) {
            // Fetch member details for editing
            fetch(`../admin/pages/members/get_member_details.php?id=${memberId}`)
                .then(response => response.json())
                .then(member => {
                    // Populate edit modal with existing member details
                    const editModal = document.getElementById('editMemberModal');
                    
                    // Populate form fields
                    editModal.querySelector('input[name="member_id"]').value = memberId;
                    editModal.querySelector('input[name="first_name"]').value = member.first_name;
                    editModal.querySelector('input[name="last_name"]').value = member.last_name;
                    editModal.querySelector('input[name="phone"]').value = member.phone;
                    editModal.querySelector('input[name="email"]').value = member.email || '';
                    
                    // Populate membership plan
                    const membershipPlanSelect = editModal.querySelector('select[name="membership_plan"]');
                    Array.from(membershipPlanSelect.options).forEach(option => {
                        if (option.value == member.membership_plan_id) {
                            option.selected = true;
                        }
                    });
                    
                    // Populate date inputs
                    editModal.querySelector('input[name="start_date"]').value = member.start_date;
                    editModal.querySelector('input[name="end_date"]').value = member.end_date;
                    
                    // Show edit modal
                    const modal = new bootstrap.Modal(editModal);
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching member details:', error);
                    alert('Failed to load member details for editing');
                });
        },

        deleteMember(memberId) {
            // Confirm deletion
            if (!confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
                return;
            }

            // Send delete request
            fetch('../admin/pages/members/delete_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ member_id: memberId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Member deleted successfully');
                    this.loadMembersTable(); // Refresh table
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting member. Please check the console.');
            });
        }
    };

    // Initialize the Membership Manager
    MembershipManager.init();
});
</script>