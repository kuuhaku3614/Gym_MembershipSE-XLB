
    <link rel="stylesheet" href="sample.css">

    <div class="container-fluid">
        <button type="button" class="btn btn-primary mb-4" id="addMemberBtn">
            <i class="fas fa-plus mr-2"></i>Add New Member
        </button>

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
    class MembershipManager {
        constructor() {
            this.state = {
                currentPhase: 1,
                selectedPrograms: [],
                selectedRentals: [],
                totalAmount: 0,
                VERIFICATION_CODE: '123456'
            };

            this.initializeEventListeners();
            this.loadMembersTable();
        }

        initializeEventListeners() {
            // Add Member Button
            document.getElementById('addMemberBtn')?.addEventListener('click', () => this.resetAndShowModal());

            // Membership Plan Change
            const membershipPlanSelect = document.getElementById('membership_plan');
            if (membershipPlanSelect) {
                membershipPlanSelect.addEventListener('change', () => this.handlePlanChange());
            }

            // Next/Previous Phase Buttons
            document.getElementById('nextBtn')?.addEventListener('click', () => this.handleNextPhase());
            document.getElementById('prevBtn')?.addEventListener('click', () => this.handlePrevPhase());

            // File Upload
            const profilePhotoInput = document.getElementById('profile_photo');
            if (profilePhotoInput) {
                profilePhotoInput.addEventListener('change', this.handleFileUpload.bind(this));
            }
        }

        handleFileUpload(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const label = event.target.nextElementSibling;
            const preview = document.getElementById('preview');

            label.textContent = file.name;

            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                `;
            };

            reader.readAsDataURL(file);
        }

        async loadMembersTable() {
            const tableBody = document.getElementById('membersTableBody');
            
            // Loading state
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

            try {
                const response = await fetch('get_members.php');
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Failed to load members');
                }

                const members = result.data || [];

                if (members.length === 0) {
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

                tableBody.innerHTML = members.map(member => `
                    <tr>
                        <td class="align-middle text-center">
                            <div class="member-photo-container">
                                ${this.renderMemberPhoto(member)}
                            </div>
                        </td>
                        <td class="align-middle">
                            <span class="member-name">${member.full_name || 'N/A'}</span>
                        </td>
                        <td class="align-middle plan-cell">${member.plan_name || 'No Plan'}</td>
                        <td class="align-middle date-cell">${member.start_date || 'N/A'}</td>
                        <td class="align-middle date-cell">${member.end_date || 'N/A'}</td>
                        <td class="align-middle">
                            <span class="badge badge-pill badge-${this.getStatusClass(member.status)}">
                                ${member.status}
                            </span>
                        </td>
                        <td class="align-middle">
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info view-member mr-1" data-id="${member.member_id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-member" data-id="${member.member_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading members:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Error loading members data. ${error.message}
                        </td>
                    </tr>
                `;
            }
        }

        renderMemberPhoto(member) {
            if (member.photo_path) {
                return `
                    <img src="../${member.photo_path}" 
                         class="img-fluid rounded-circle member-photo" 
                         style="width: 60px; height: 60px; object-fit: cover;"
                         alt="Profile Photo"
                         onerror="this.parentElement.innerHTML='<div class=\'default-user-icon\'><i class=\'fas fa-user\'></i></div>';"
                    />
                `;
            }
            return `
                <div class="default-user-icon">
                    <i class="fas fa-user"></i>
                </div>
            `;
        }

        getStatusClass(status) {
            switch(status) {
                case 'Active': return 'success';
                case 'Expired': return 'danger';
                default: return 'warning';
            }
        }

        resetAndShowModal() {
            // Reset state and show modal logic
            this.state.currentPhase = 1;
            this.state.selectedPrograms = [];
            this.state.selectedRentals = [];
            this.state.totalAmount = 0;

            // Add your modal display and reset logic here
            this.loadMembershipPlans();
            this.loadAvailableServices();
        }

        handlePlanChange() {
            // Plan change logic (similar to jQuery version)
        }

        async loadMembershipPlans() {
            try {
                const response = await fetch('get_membership_plans.php');
                const plans = await response.json();

                const membershipPlanSelect = document.getElementById('membership_plan');
                membershipPlanSelect.innerHTML = `
                    <option value="">Select Plan</option>
                    ${plans.map(plan => `
                        <option 
                            value="${plan.id}"
                            data-duration="${plan.duration}"
                            data-duration-type="${plan.duration_type_id}"
                            data-price="${plan.price}"
                            data-duration-name="${plan.duration_type}">
                            ${plan.plan_name} (${plan.duration} ${plan.duration_type}) - ₱${parseFloat(plan.price).toFixed(2)}
                        </option>
                    `).join('')}
                `;
            } catch (error) {
                console.error('Error loading membership plans:', error);
                alert('Error loading membership plans. Please try again.');
            }
        }

        async loadAvailableServices() {
            // Programs
            try {
                const programResponse = await fetch('get_program_details.php');
                const programs = await programResponse.json();
                this.renderPrograms(programs);
            } catch (error) {
                console.error('Error loading programs:', error);
            }

            // Rentals
            try {
                const rentalResponse = await fetch('get_rental_details.php');
                const rentals = await rentalResponse.json();
                this.renderRentals(rentals);
            } catch (error) {
                console.error('Error loading rentals:', error);
            }
        }

        renderPrograms(programs) {
            // Implement program rendering logic
        }

        renderRentals(rentals) {
            // Implement rental rendering logic
        }

        // Additional methods for adding programs, rentals, etc.
    }

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', () => {
        new MembershipManager();
    });
    </script>
</body>
</html>