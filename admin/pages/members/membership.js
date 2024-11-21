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
        const programId = $details.find('.program-id').val();
        const programName = $details.find('.program-name').text();
        const programPrice = parseFloat($details.find('.program-price').text().replace('₱', ''));

        if (!selectedPrograms.some(p => p.id === programId)) {
            selectedPrograms.push({
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

        if (!selectedRentals.some(r => r.id === rentalId)) {
            selectedRentals.push({
                id: rentalId,
                name: rentalName,
                price: rentalPrice
            });

            totalAmount += rentalPrice;
            updateSelectedServices();
        }

        $('#rentalDetailsModal').modal('hide');
    });

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