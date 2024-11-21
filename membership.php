<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <!-- add_member_modal.php -->

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
</head>
<body>
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
                                <div class="form-group">
                                    <input type="radio" name="user_type" id="existing_user" value="existing">
                                    <label for="existing_user">Existing User</label>
                                    <input type="radio" name="user_type" id="new_user" value="new" checked>
                                    <label for="new_user">New User</label>
                                </div>
                                
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

                                    <!-- Phase 2: Additional Services -->
                                    <div id="phase2" class="phase-content" style="display: none;">
                                        <div class="row">
                                            <!-- Available Services Container -->
                                            <div class="col-md-7">
                                                <div class="row">
                                                    <!-- Programs -->
                                                    <div class="col-md-6">
                                                        <h6>Available Programs</h6>
                                                        <div class="services-scrollable-container" style="max-height: 400px; overflow-y: auto;">
                                                            <?php
                                                            $stmt = $pdo->query("
                                                                SELECT p.*, pt.type_name, c.user_id as coach_user_id 
                                                                FROM programs p 
                                                                JOIN program_types pt ON p.program_type_id = pt.id
                                                                JOIN coaches c ON p.coach_id = c.id 
                                                                WHERE p.status_id = 1
                                                            ");
                                                            while ($program = $stmt->fetch()) {
                                                                ?>
                                                                <div class="service-box program" data-id="<?= $program['id'] ?>" data-toggle="modal" data-target="#programDetailsModal">
                                                                    <h6><?= htmlspecialchars($program['program_name']) ?></h6>
                                                                    <p>Type: <?= htmlspecialchars($program['type_name']) ?></p>
                                                                    <p>Price: ₱<?= number_format($program['price'], 2) ?></p>
                                                                </div>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <!-- Rental Services -->
                                                    <div class="col-md-6">
                                                        <h6>Available Rental Services</h6>
                                                        <div class="services-scrollable-container" style="max-height: 400px; overflow-y: auto;">
                                                            <?php
                                                            $stmt = $pdo->query("SELECT * FROM rental_services WHERE status_id = 1");
                                                            while ($rental = $stmt->fetch()) {
                                                                ?>
                                                                <div class="service-box rental" data-id="<?= $rental['id'] ?>" data-toggle="modal" data-target="#rentalDetailsModal">
                                                                    <h6><?= htmlspecialchars($rental['service_name']) ?></h6>
                                                                    <p>Available Slots: <?= $rental['available_slots'] ?></p>
                                                                    <p>Price: ₱<?= number_format($rental['price'], 2) ?></p>
                                                                </div>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Availed Services Container -->
                                            <div class="col-md-5">
                                                <div class="availed-container">
                                                    <h6>Availed Services</h6>
                                                    <div class="card" style="max-height: 400px; overflow-y: auto;">
                                                        <div class="card-body">
                                                            <div id="availed_services">
                                                                <!-- Membership Plan Details -->
                                                                <div class="membership-details mb-3">
                                                                    <h6>Membership Plan</h6>
                                                                    <div id="selected_plan_details"></div>
                                                                </div>
                                                                <!-- Additional Services -->
                                                                <div class="additional-services">
                                                                    <h6>Additional Services</h6>
                                                                    <div id="selected_services"></div>
                                                                </div>
                                                                <!-- Total Amount -->
                                                                <div class="total-amount mt-3">
                                                                    <h6>Total Amount: <span id="total_amount">₱0.00</span></h6>
                                                                </div>
                                                            </div>
                                                        </div>
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

                    <!-- Program Details Modal -->
                    <div class="modal fade" id="programDetailsModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Program Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="program_details_content"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="addProgramBtn">Add to Membership</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Details Modal -->
                    <div class="modal fade" id="rentalDetailsModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rental Service Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="rental_details_content"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="addRentalBtn">Add to Membership</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
$(document).ready(function() {
    // Initialize the add member button
    $('#addMemberBtn').click(function() {
        // Reset the form and phase before showing modal
        currentPhase = 1;
        selectedPrograms = [];
        selectedRentals = [];
        totalAmount = 0;
        
        // Reset form fields
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
        
        // Show the modal
        $('#addMemberModal').modal('show');
    });

    let currentPhase = 1;
    let selectedPrograms = [];
    let selectedRentals = [];
    let totalAmount = 0;
    
    // User type selection
    $('input[name="user_type"]').change(function() {
        if ($(this).val() === 'existing') {
            $('#existing_user_form').show();
            $('#new_user_form').hide();
        } else {
            $('#existing_user_form').hide();
            $('#new_user_form').show();
        }
    });

    // Membership plan calculation
    $('#membership_plan').change(function() {
        const selectedOption = $(this).find('option:selected');
        const startDate = $('#start_date').val();
        
        if (startDate && selectedOption.val()) {
            const price = selectedOption.data('price');
            const duration = selectedOption.data('duration');
            const durationType = selectedOption.data('duration-type');
            
            const start = new Date(startDate);
            let endDate = new Date(start);
            
            switch(durationType) {
                case 1: endDate.setDate(start.getDate() + parseInt(duration)); break;
                case 2: endDate.setMonth(start.getMonth() + parseInt(duration)); break;
                case 3: endDate.setFullYear(start.getFullYear() + parseInt(duration)); break;
            }
            
            const formattedEndDate = endDate.toISOString().split('T')[0];
            $('#end_date').val(formattedEndDate);
            $('#price').val('₱' + parseFloat(price).toFixed(2));
            
            totalAmount = parseFloat(price);
            $('#total_amount').text('₱' + totalAmount.toFixed(2));
        }
    });

        // Phase navigation
        $('#nextBtn').click(function() {
            if (currentPhase === 1 && validatePhase1()) {
                currentPhase++;
                updatePhase();
            } else if (currentPhase === 2) {
                currentPhase++;
                updatePhase();
            } else if (currentPhase === 3) {
                submitMembershipForm();
            }
        });

        $('#prevBtn').click(function() {
            if (currentPhase > 1) {
                currentPhase--;
                updatePhase();
            }
        });

        function updatePhase() {
            $('#phase1, #phase2, #phase3').hide();
            $(`#phase${currentPhase}`).show();
            
            $('.progress-bar').css('width', `${currentPhase * 33}%`)
                               .attr('aria-valuenow', currentPhase * 33)
                               .text(`Phase ${currentPhase}/3`);
            
            $('#prevBtn').toggle(currentPhase > 1);
            $('#nextBtn').text(currentPhase === 3 ? 'Submit' : 'Next');

            if (currentPhase === 2) {
                displaySelectedPlan();
            }
        }

        function validatePhase1() {
            const form = $('#membershipForm')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            return true;
        }

        function displaySelectedPlan() {
            const planName = $('#membership_plan option:selected').text();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const price = $('#price').val();

            $('#selected_plan_details').html(`
                <p><strong>Plan:</strong> ${planName}</p>
                <p><strong>Start Date:</strong> ${startDate}</p>
                <p><strong>End Date:</strong> ${endDate}</p>
                <p><strong>Price:</strong> ${price}</p>
            `);
        }

        // Service Details Modals
        function setupServiceDetails(containerSelector, detailsModalId, ajaxUrl) {
            $(containerSelector).on('click', '.service-box', function() {
                const serviceId = $(this).data('id');
                
                $.ajax({
                    url: ajaxUrl,
                    method: 'GET',
                    data: { id: serviceId },
                    success: function(response) {
                        $(`${detailsModalId} .modal-body .modal-details-content`).html(response);
                        $(`${detailsModalId}`).modal('show');
                    }
                });
            });
        }

        setupServiceDetails('.programs-container', '#programDetailsModal', 'get_program_details.php');
        setupServiceDetails('.rentals-container', '#rentalDetailsModal', 'get_rental_details.php');

        // Add services to membership
        function setupServiceAddition(addButtonId, selectedServices, detailsModalId, updateCallback) {
            $(addButtonId).click(function() {
                const $details = $(detailsModalId + ' .modal-details-content');
                const serviceId = $details.find('.service-id').val();
                const serviceName = $details.find('.service-name').text();
                const servicePrice = parseFloat($details.find('.service-price').text().replace('₱', ''));

                if (!selectedServices.some(s => s.id === serviceId)) {
                    selectedServices.push({
                        id: serviceId,
                        name: serviceName,
                        price: servicePrice
                    });

                    totalAmount += servicePrice;
                    updateCallback();
                }

                $(detailsModalId).modal('hide');
            });
        }

        setupServiceAddition('#addProgramBtn', selectedPrograms, '#programDetailsModal', updateSelectedServices);
        setupServiceAddition('#addRentalBtn', selectedRentals, '#rentalDetailsModal', updateSelectedServices);

        function updateSelectedServices() {
    let servicesHtml = '';

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

function submitMembershipForm() {
    const formData = new FormData($('#membershipForm')[0]);
    
    // Add selected programs and rentals to form data
    formData.append('selected_programs', JSON.stringify(selectedPrograms));
    formData.append('selected_rentals', JSON.stringify(selectedRentals));
    formData.append('total_amount', totalAmount);

    $.ajax({
        url: 'process_membership.php',
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
});
</script>
</body>
</html>